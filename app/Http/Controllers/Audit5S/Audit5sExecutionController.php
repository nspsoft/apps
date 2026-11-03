<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sChecklist;
use App\Models\Audit5sFinding;
use App\Models\Audit5sRating;
use App\Models\Audit5sSchedule;
use App\Models\Audit5sSession;
use App\Models\Audit5sZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class Audit5sExecutionController extends Controller
{
    public function index(Request $request)
    {
        $query = Audit5sSession::with([
            'zone:id,name,code,building_id,pic_employee_id',
            'zone.picEmployee:id,nik,full_name,profile_picture',
            'zone.building:id,name,code',
            'auditor:id,name,email',
        ])->withCount(['findings', 'ratings']);

        if ($request->filled('building_id')) {
            $query->whereHas('zone', fn($zq) => $zq->where('building_id', $request->building_id));
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->filled('grade')) {
            $query->where('grade', $request->grade);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('audit_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('audit_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('session_code', 'like', "%{$search}%")
                  ->orWhereHas('zone', fn($zq) => $zq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                  ->orWhereHas('auditor', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $sessions = $query->orderBy('audit_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $buildings = Audit5sBuilding::where('is_active', true)
            ->with(['zones:id,building_id,name,code'])
            ->get();

        $stats = [
            'total_audits' => Audit5sSession::count(),
            'grade_a_count' => Audit5sSession::where('grade', 'A')->count(),
            'grade_d_count' => Audit5sSession::where('grade', 'D')->count(),
            'average_score' => round((float) Audit5sSession::avg('total_score'), 1),
        ];

        return Inertia::render('Audit5S/Audits/Index', [
            'sessions' => $sessions,
            'buildings' => $buildings,
            'filters' => $request->only(['building_id', 'zone_id', 'grade', 'status', 'date_from', 'date_to', 'search']),
            'stats' => $stats,
        ]);
    }

    public function create(Request $request)
    {
        $buildings = Audit5sBuilding::where('is_active', true)
            ->with(['zones' => function ($zq) {
                $zq->with(['department:id,name', 'picEmployee:id,nik,full_name,profile_picture']);
            }])
            ->get();

        $checklists = Audit5sChecklist::where('is_active', true)
            ->orderBy('pillar')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('pillar');

        $pendingSchedules = Audit5sSchedule::whereIn('status', ['Scheduled', 'In Progress'])
            ->with('building:id,name')
            ->orderBy('scheduled_date')
            ->get();

        $preselectedZoneId = $request->input('zone_id');
        $preselectedScheduleId = $request->input('schedule_id');

        return Inertia::render('Audit5S/Audits/Create', [
            'buildings' => $buildings,
            'checklists' => $checklists,
            'pendingSchedules' => $pendingSchedules,
            'preselectedZoneId' => $preselectedZoneId ? (int)$preselectedZoneId : null,
            'preselectedScheduleId' => $preselectedScheduleId ? (int)$preselectedScheduleId : null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'zone_id' => 'required|exists:audit_5s_zones,id',
            'schedule_id' => 'nullable|exists:audit_5s_schedules,id',
            'audit_date' => 'required|date',
            'summary_notes' => 'nullable|string',
            'ratings' => 'required|array|min:1',
            'ratings.*.checklist_id' => 'required|exists:audit_5s_checklists,id',
            'ratings.*.pillar' => 'required|string',
            'ratings.*.score' => 'required|integer|min:1|max:5',
            'ratings.*.notes' => 'nullable|string',
            'findings' => 'nullable|array',
            'findings.*.pillar' => 'required|string',
            'findings.*.description' => 'required|string',
            'findings.*.before_photo' => 'nullable|image|max:5120',
            'findings.*.is_red_tag' => 'nullable|boolean',
            'findings.*.red_tag_action' => 'nullable|string',
            'findings.*.due_date' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($request) {
            $ratings = $request->input('ratings', []);

            // Group ratings by pillar to calculate pillar scores (1-5 normalized to 0-100)
            $pillarPoints = [
                'Sort' => [],
                'Set In Order' => [],
                'Shine' => [],
                'Standardize' => [],
                'Sustain' => [],
            ];

            foreach ($ratings as $r) {
                $pillar = $r['pillar'] ?? 'Sort';
                if (!isset($pillarPoints[$pillar])) {
                    $pillarPoints[$pillar] = [];
                }
                // score is 1..5 -> percent = (score / 5) * 100
                $pillarPoints[$pillar][] = ((float)$r['score'] / 5.0) * 100.0;
            }

            $scoreSort = !empty($pillarPoints['Sort']) ? array_sum($pillarPoints['Sort']) / count($pillarPoints['Sort']) : 0;
            $scoreSetInOrder = !empty($pillarPoints['Set In Order']) ? array_sum($pillarPoints['Set In Order']) / count($pillarPoints['Set In Order']) : 0;
            $scoreShine = !empty($pillarPoints['Shine']) ? array_sum($pillarPoints['Shine']) / count($pillarPoints['Shine']) : 0;
            $scoreStandardize = !empty($pillarPoints['Standardize']) ? array_sum($pillarPoints['Standardize']) / count($pillarPoints['Standardize']) : 0;
            $scoreSustain = !empty($pillarPoints['Sustain']) ? array_sum($pillarPoints['Sustain']) / count($pillarPoints['Sustain']) : 0;

            $activePillars = array_filter([$scoreSort, $scoreSetInOrder, $scoreShine, $scoreStandardize, $scoreSustain], fn($v) => $v > 0);
            $totalScore = count($activePillars) > 0 ? array_sum($activePillars) / count($activePillars) : 0;
            $totalScore = round($totalScore, 2);

            // Grade evaluation
            if ($totalScore >= 90) {
                $grade = 'A';
            } elseif ($totalScore >= 80) {
                $grade = 'B';
            } elseif ($totalScore >= 70) {
                $grade = 'C';
            } else {
                $grade = 'D';
            }

            // Generate Session Code: AUD-YYYYMMDD-XXXX
            $code = 'AUD-' . date('Ymd') . '-' . strtoupper(Str::random(4));

            $session = Audit5sSession::create([
                'session_code' => $code,
                'schedule_id' => $request->schedule_id,
                'zone_id' => $request->zone_id,
                'auditor_id' => auth()->id(),
                'audit_date' => $request->audit_date,
                'score_sort' => round($scoreSort, 2),
                'score_set_in_order' => round($scoreSetInOrder, 2),
                'score_shine' => round($scoreShine, 2),
                'score_standardize' => round($scoreStandardize, 2),
                'score_sustain' => round($scoreSustain, 2),
                'total_score' => $totalScore,
                'grade' => $grade,
                'status' => 'Completed',
                'summary_notes' => $request->summary_notes,
            ]);

            // Save ratings
            foreach ($ratings as $r) {
                Audit5sRating::create([
                    'session_id' => $session->id,
                    'checklist_id' => $r['checklist_id'],
                    'pillar' => $r['pillar'],
                    'score' => $r['score'],
                    'notes' => $r['notes'] ?? null,
                ]);
            }

            // Handle findings and photo uploads
            if ($request->has('findings') && is_array($request->findings)) {
                foreach ($request->findings as $idx => $f) {
                    $beforePath = null;
                    if ($request->hasFile("findings.{$idx}.before_photo")) {
                        $beforePath = $request->file("findings.{$idx}.before_photo")->store('5s-findings', 'public');
                    }

                    Audit5sFinding::create([
                        'session_id' => $session->id,
                        'zone_id' => $request->zone_id,
                        'pillar' => $f['pillar'] ?? 'Sort',
                        'description' => $f['description'],
                        'before_photo_path' => $beforePath,
                        'is_red_tag' => !empty($f['is_red_tag']),
                        'red_tag_action' => $f['red_tag_action'] ?? null,
                        'due_date' => $f['due_date'] ?? now()->addDays(7)->toDateString(),
                        'status' => 'Open',
                    ]);
                }
            }

            // Update Zone with latest score & grade
            $zone = Audit5sZone::findOrFail($request->zone_id);
            $zone->update([
                'latest_audit_date' => $request->audit_date,
                'latest_score' => $totalScore,
                'latest_grade' => $grade,
            ]);

            // If schedule linked, update schedule status
            if ($request->schedule_id) {
                $schedule = Audit5sSchedule::find($request->schedule_id);
                if ($schedule) {
                    $schedule->update(['status' => 'Completed']);
                }
            }

            return redirect()->route('audit-5s.audits.show', $session->id)
                ->with('success', "Audit session {$session->session_code} completed with Score {$totalScore}% (Grade {$grade}).");
        });
    }

    public function show(Audit5sSession $session)
    {
        $session->load([
            'zone.building',
            'zone.department:id,name',
            'zone.picEmployee:id,nik,full_name,profile_picture,phone',
            'auditor:id,name,email',
            'ratings.checklist',
            'findings.remediatedBy:id,name',
            'findings.verifiedBy:id,name',
        ]);

        return Inertia::render('Audit5S/Audits/Show', [
            'session' => $session,
        ]);
    }

    public function destroy(Audit5sSession $session)
    {
        $code = $session->session_code;
        $session->delete();

        return redirect()->route('audit-5s.audits.index')
            ->with('success', "Audit session {$code} deleted successfully.");
    }
}
