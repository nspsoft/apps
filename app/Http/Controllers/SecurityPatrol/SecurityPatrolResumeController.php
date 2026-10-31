<?php

namespace App\Http\Controllers\SecurityPatrol;

use App\Http\Controllers\Controller;
use App\Models\SecurityPatrolSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecurityPatrolResumeController extends Controller
{
    public function index(Request $request)
    {
        $query = SecurityPatrolSession::with([
            'user:id,name,email',
            'schedule:id,name',
            'logs.checkpoint:id,code,name,area_zone,latitude,longitude',
            'logs.gaTicket:id,ticket_code,title,status',
        ]);

        // Filters
        if ($request->filled('date_from')) {
            $query->whereDate('patrol_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('patrol_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('session_code', 'like', "%{$search}%")
                    ->orWhere('shift_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $sessions = $query->latest('patrol_date')
            ->latest('start_time')
            ->paginate(15)
            ->withQueryString();

        $users = User::select('id', 'name')->orderBy('name')->get();

        // Overall summary statistics
        $stats = [
            'total_sessions' => SecurityPatrolSession::count(),
            'completed' => SecurityPatrolSession::where('status', 'completed')->count(),
            'incomplete' => SecurityPatrolSession::where('status', 'incomplete')->count(),
            'in_progress' => SecurityPatrolSession::where('status', 'in_progress')->count(),
            'total_incidents' => SecurityPatrolSession::sum('incidents_count'),
        ];

        return Inertia::render('SecurityPatrol/Resume/Index', [
            'sessions' => $sessions,
            'filters' => $request->only(['date_from', 'date_to', 'status', 'user_id', 'search']),
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    /**
     * Export resume data to CSV / Excel compatible format
     */
    public function exportExcel(Request $request)
    {
        $query = SecurityPatrolSession::with(['user', 'schedule']);

        if ($request->filled('date_from')) {
            $query->whereDate('patrol_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('patrol_date', '<=', $request->date_to);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->latest('patrol_date')->get();

        $filename = 'rekap_patroli_satpam_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($sessions) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No',
                'Kode Sesi',
                'Tanggal',
                'Nama Shift / Rute',
                'Petugas Satpam',
                'Waktu Mulai',
                'Waktu Selesai',
                'Durasi (Menit)',
                'Target Pos',
                'Pos Terperiksa',
                'Pos Terlewat',
                'Jumlah Insiden',
                'Status',
                'Catatan',
            ]);

            foreach ($sessions as $index => $s) {
                $durationMin = $s->duration_seconds ? round($s->duration_seconds / 60) : '-';
                fputcsv($file, [
                    $index + 1,
                    $s->session_code,
                    $s->patrol_date ? $s->patrol_date->format('d/m/Y') : '-',
                    $s->shift_name,
                    $s->user ? $s->user->name : '-',
                    $s->start_time ? $s->start_time->format('H:i') : '-',
                    $s->end_time ? $s->end_time->format('H:i') : '-',
                    $durationMin,
                    $s->total_checkpoints,
                    $s->completed_checkpoints,
                    $s->missed_checkpoints,
                    $s->incidents_count,
                    strtoupper($s->status),
                    $s->notes ?: '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
