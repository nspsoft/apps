<?php

namespace App\Http\Controllers\SecurityPatrol;

use App\Http\Controllers\Controller;
use App\Models\GaTicket;
use App\Models\SecurityCheckpoint;
use App\Models\SecurityPatrolLog;
use App\Models\SecurityPatrolSchedule;
use App\Models\SecurityPatrolSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SecurityPatrolExecutionController extends Controller
{
    /**
     * Mobile patrol view for security officer
     */
    public function active(Request $request)
    {
        $userId = auth()->id();
        $today = Carbon::today()->format('Y-m-d');

        // Check if there is an in-progress session for this user
        $activeSession = SecurityPatrolSession::with(['schedule.checkpoints', 'logs.checkpoint'])
            ->where('user_id', $userId)
            ->where('status', 'in_progress')
            ->latest('start_time')
            ->first();

        // If no active session, fetch today's available schedules
        $availableSchedules = SecurityPatrolSchedule::with('checkpoints')
            ->where('is_active', true)
            ->get();

        $allCheckpoints = SecurityCheckpoint::where('is_active', true)
            ->select('id', 'code', 'name', 'area_zone', 'latitude', 'longitude', 'geofence_radius', 'sop_items')
            ->get();

        return Inertia::render('SecurityPatrol/Active/RunPatrol', [
            'activeSession' => $activeSession,
            'availableSchedules' => $availableSchedules,
            'allCheckpoints' => $allCheckpoints,
        ]);
    }

    /**
     * Start a new patrol session
     */
    public function startSession(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'nullable|exists:security_patrol_schedules,id',
            'shift_name' => 'nullable|string|max:100',
        ]);

        $userId = auth()->id();
        $today = Carbon::today();

        // Check if there's already an active session
        $existing = SecurityPatrolSession::where('user_id', $userId)
            ->where('status', 'in_progress')
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Anda masih memiliki sesi patroli yang sedang aktif.');
        }

        $schedule = null;
        $totalCheckpoints = 0;
        $shiftName = $validated['shift_name'] ?? 'Patroli Reguler';

        if (!empty($validated['schedule_id'])) {
            $schedule = SecurityPatrolSchedule::with('checkpoints')->find($validated['schedule_id']);
            if ($schedule) {
                $totalCheckpoints = $schedule->checkpoints->count();
                $shiftName = $schedule->name;
            }
        }

        if ($totalCheckpoints === 0) {
            $totalCheckpoints = SecurityCheckpoint::where('is_active', true)->count();
        }

        if ($totalCheckpoints === 0) {
            return redirect()->back()->with('error', 'Tidak ada titik pos checkpoint aktif. Silakan tambahkan titik pos terlebih dahulu di Master Titik Pos & Cetak QR.');
        }

        $sessionCode = 'PTR-' . $today->format('Ymd') . '-' . Str::upper(Str::random(5));

        $session = SecurityPatrolSession::create([
            'session_code' => $sessionCode,
            'schedule_id' => $schedule?->id,
            'user_id' => $userId,
            'shift_name' => $shiftName,
            'patrol_date' => $today->format('Y-m-d'),
            'start_time' => now(),
            'total_checkpoints' => $totalCheckpoints,
            'completed_checkpoints' => 0,
            'missed_checkpoints' => 0,
            'incidents_count' => 0,
            'status' => 'in_progress',
        ]);

        return redirect()->route('security-patrol.active')->with('success', 'Sesi patroli berhasil dimulai. Selamat bertugas!');
    }

    /**
     * Scan QR code and log checkpoint visit
     */
    public function scanCheckpoint(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:security_patrol_sessions,id',
            'qr_token' => 'required|string',
            'lat_actual' => 'nullable|numeric',
            'lng_actual' => 'nullable|numeric',
            'condition_status' => 'required|in:normal,incident',
            'checklist_answers' => 'nullable|array',
            'notes' => 'nullable|string',
            'photo' => 'nullable|image|max:10240', // Max 10MB
        ]);

        $session = SecurityPatrolSession::findOrFail($request->session_id);

        if ($session->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Sesi patroli ini sudah tidak aktif.',
            ], 422);
        }

        // Find checkpoint by token
        $checkpoint = SecurityCheckpoint::where('qr_token', trim($request->qr_token))
            ->where('is_active', true)
            ->first();

        if (!$checkpoint) {
            // Also fallback check if qr_token sent was the code (e.g. CP-01)
            $checkpoint = SecurityCheckpoint::where('code', trim($request->qr_token))
                ->where('is_active', true)
                ->first();
        }

        if (!$checkpoint) {
            return response()->json([
                'success' => false,
                'message' => 'QR Code tidak dikenali atau checkpoint tidak aktif.',
            ], 404);
        }

        // Check if already scanned in this session
        $existingLog = SecurityPatrolLog::where('patrol_session_id', $session->id)
            ->where('checkpoint_id', $checkpoint->id)
            ->first();

        if ($existingLog) {
            return response()->json([
                'success' => false,
                'message' => 'Titik pos ' . $checkpoint->name . ' sudah dipindai pada sesi ini.',
            ], 422);
        }

        // Calculate GPS distance using Haversine formula
        $distanceMeters = null;
        $isDeviation = false;

        if ($request->filled('lat_actual') && $request->filled('lng_actual')) {
            $distanceMeters = $this->calculateHaversineDistance(
                (float) $request->lat_actual,
                (float) $request->lng_actual,
                (float) $checkpoint->latitude,
                (float) $checkpoint->longitude
            );

            $toleranceRadius = $checkpoint->geofence_radius ?: 50;

            if ($distanceMeters > $toleranceRadius) {
                $isDeviation = true;
                // If deviation > tolerance and no photo provided, require photo as conditional tolerance
                if (!$request->hasFile('photo')) {
                    return response()->json([
                        'success' => false,
                        'is_deviation' => true,
                        'distance_meters' => round($distanceMeters, 1),
                        'message' => "Posisi GPS terdeteksi " . round($distanceMeters, 1) . " meter dari titik pos (toleransi {$toleranceRadius}m). Mohon ambil foto fisik pos langsung untuk verifikasi.",
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('security_patrol', 'public');
            }

            // Create GA Ticket if condition is incident
            $gaTicketId = null;
            if ($request->condition_status === 'incident') {
                $ticketCode = 'TKT-PATROL-' . date('Ymd') . '-' . strtoupper(Str::random(4));
                $ticket = GaTicket::create([
                    'ticket_code' => $ticketCode,
                    'title' => 'Temuan Patroli Satpam: ' . $checkpoint->name,
                    'description' => $request->notes ?: 'Ditemukan potensi bahaya/kerusakan fasilitas saat patroli di ' . $checkpoint->name,
                    'category' => 'security',
                    'priority' => 'high',
                    'status' => 'open',
                    'reporter_id' => auth()->id(),
                    'image_path' => $photoPath,
                ]);
                $gaTicketId = $ticket->id;
                $session->increment('incidents_count');
            }

            // Create log
            $log = SecurityPatrolLog::create([
                'patrol_session_id' => $session->id,
                'checkpoint_id' => $checkpoint->id,
                'user_id' => auth()->id(),
                'scanned_at' => now(),
                'lat_actual' => $request->lat_actual,
                'lng_actual' => $request->lng_actual,
                'distance_meters' => $distanceMeters ? round($distanceMeters, 2) : null,
                'is_deviation' => $isDeviation,
                'condition_status' => $request->condition_status,
                'checklist_answers' => $request->checklist_answers,
                'photo_path' => $photoPath,
                'notes' => $request->notes,
                'ga_ticket_id' => $gaTicketId,
            ]);

            // Update session completed count
            $newCompletedCount = $session->logs()->count();
            $session->completed_checkpoints = $newCompletedCount;

            // Auto-complete if all checkpoints are done
            $isSessionFinished = false;
            if ($newCompletedCount >= $session->total_checkpoints) {
                $session->status = 'completed';
                $session->end_time = now();
                $session->duration_seconds = abs($session->end_time->diffInSeconds($session->start_time));
                $session->missed_checkpoints = 0;
                $isSessionFinished = true;
            }
            $session->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Titik ' . $checkpoint->name . ' berhasil dicatat!',
                'log' => $log->load('checkpoint'),
                'is_session_finished' => $isSessionFinished,
                'completed_checkpoints' => $newCompletedCount,
                'total_checkpoints' => $session->total_checkpoints,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat patroli: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finish patrol session manually (or when time is up)
     */
    public function finishSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:security_patrol_sessions,id',
            'notes' => 'nullable|string',
        ]);

        $session = SecurityPatrolSession::findOrFail($request->session_id);

        if ($session->status !== 'in_progress') {
            return redirect()->route('security-patrol.active');
        }

        $completed = $session->logs()->count();
        $missed = max(0, $session->total_checkpoints - $completed);

        $session->completed_checkpoints = $completed;
        $session->missed_checkpoints = $missed;
        $session->status = ($missed === 0) ? 'completed' : 'incomplete';
        $session->end_time = now();
        $session->duration_seconds = abs($session->end_time->diffInSeconds($session->start_time));
        if ($request->filled('notes')) {
            $session->notes = $request->notes;
        }
        $session->save();

        return redirect()->route('security-patrol.resume')
            ->with('success', 'Sesi patroli selesai! Total ' . $completed . ' titik terperiksa.');
    }

    /**
     * Haversine formula to compute distance in meters between two lat/lng coordinates
     */
    private function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
