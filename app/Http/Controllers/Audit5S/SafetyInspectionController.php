<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\SafetyInspection;
use App\Models\SafetyInspectionItem;
use App\Models\Audit5sFinding;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sZone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class SafetyInspectionController extends Controller
{
    public function index(Request $request)
    {
        $query = SafetyInspection::with(['building:id,name,code', 'zone:id,name,code', 'inspector:id,name,email'])
            ->latest('inspection_date');

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('risk_status')) {
            $query->where('risk_status', $request->risk_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('inspection_number', 'like', "%{$search}%")
                  ->orWhereHas('zone', fn($z) => $z->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $inspections = $query->paginate(15)->withQueryString();

        $buildings = Audit5sBuilding::where('is_active', true)->select('id', 'name', 'code')->get();
        $zones = Audit5sZone::select('id', 'building_id', 'name', 'code')->get();

        $allInspections = SafetyInspection::all();
        $stats = [
            'total_inspections' => $allInspections->count(),
            'avg_score' => $allInspections->count() > 0 ? round($allInspections->avg('overall_score'), 1) : 100,
            'safe_count' => $allInspections->where('risk_status', 'Safe')->count(),
            'caution_count' => $allInspections->where('risk_status', 'Caution')->count(),
            'danger_count' => $allInspections->where('risk_status', 'Danger')->count(),
            'total_findings' => $allInspections->sum('findings_count'),
        ];

        return Inertia::render('Safety5S/Inspections/Index', [
            'inspections' => $inspections,
            'buildings' => $buildings,
            'zones' => $zones,
            'stats' => $stats,
            'filters' => $request->only(['building_id', 'risk_status', 'search']),
        ]);
    }

    public function create(Request $request)
    {
        $buildings = Audit5sBuilding::where('is_active', true)
            ->with(['zones:id,building_id,name,code,pic_employee_id'])
            ->get();

        $defaultCheckpoints = [
            [
                'category' => 'Fire_Emergency',
                'category_label' => 'Fire Protection & Emergency Readiness',
                'title' => 'APAR Inspection & Evacuation Access',
                'description' => 'Tabung APAR bertekanan normal, tidak kadaluarsa, dan akses bebas rintangan minimal 1 meter. Jalur evakuasi darurat steril.',
            ],
            [
                'category' => 'PPE_Compliance',
                'category_label' => 'PPE / APD Compliance',
                'title' => 'Mandatory PPE Usage',
                'description' => 'Operator dan personil di area mengenakan APD wajib (kacamata safety, sepatu safety, sarung tangan, earplug).',
            ],
            [
                'category' => 'Electrical',
                'category_label' => 'Electrical Safety & LOTO',
                'title' => 'Electrical Panels & Wiring Hygiene',
                'description' => 'Panel listrik tertutup rapat, kabel tersalur rapi dalam cable duct, tidak ada kabel telanjang atau steker bertumpuk.',
            ],
            [
                'category' => 'Machinery_Guarding',
                'category_label' => 'Machinery & Emergency Controls',
                'title' => 'Machine Guarding & Emergency Stop (E-Stop)',
                'description' => 'Cover pelindung gir/pisau terpasang, tombol E-Stop mudah dijangkau dan berfungsi responsif saat diuji.',
            ],
            [
                'category' => 'Chemical_Ergonomics',
                'category_label' => 'Chemicals, Spills & Ergonomics',
                'title' => 'Spill Prevention & Chemical Labeling',
                'description' => 'Lantai bebas ceceran oli/pelumas, wadah kimia berlabel MSDS jelas dan tersimpan dengan wadah penampung tetesan (secondary containment).',
            ],
        ];

        return Inertia::render('Safety5S/Inspections/Create', [
            'buildings' => $buildings,
            'defaultCheckpoints' => $defaultCheckpoints,
            'preselectedZoneId' => $request->zone_id,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:audit_5s_buildings,id',
            'zone_id' => 'required|exists:audit_5s_zones,id',
            'inspection_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.category' => 'required|string',
            'items.*.checkpoint_title' => 'required|string',
            'items.*.status' => 'required|in:Pass,Fail,N/A',
            'items.*.remarks' => 'nullable|string',
            'items.*.photo' => 'nullable|image|max:5120',
        ]);

        $evaluatedItems = collect($validated['items'])->filter(fn($i) => $i['status'] !== 'N/A');
        $passedCount = $evaluatedItems->where('status', 'Pass')->count();
        $totalEvaluated = max(1, $evaluatedItems->count());
        $overallScore = round(($passedCount / $totalEvaluated) * 100, 1);

        $failedItems = collect($validated['items'])->where('status', 'Fail');
        $findingsCount = $failedItems->count();

        $riskStatus = 'Safe';
        if ($overallScore < 75 || $findingsCount >= 2) {
            $riskStatus = 'Danger';
        } elseif ($overallScore < 90 || $findingsCount === 1) {
            $riskStatus = 'Caution';
        }

        $inspectionNumber = 'SAFE-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        $inspection = SafetyInspection::create([
            'inspection_number' => $inspectionNumber,
            'building_id' => $validated['building_id'],
            'zone_id' => $validated['zone_id'],
            'inspector_user_id' => auth()->id(),
            'inspection_date' => $validated['inspection_date'],
            'status' => 'Completed',
            'overall_score' => $overallScore,
            'risk_status' => $riskStatus,
            'findings_count' => $findingsCount,
            'notes' => $validated['notes'] ?? null,
        ]);

        $zone = Audit5sZone::find($validated['zone_id']);

        foreach ($validated['items'] as $index => $itemData) {
            $photoPath = null;
            if ($request->hasFile("items.{$index}.photo")) {
                $photoPath = $request->file("items.{$index}.photo")->store('safety/inspections', 'public');
            }

            SafetyInspectionItem::create([
                'inspection_id' => $inspection->id,
                'category' => $itemData['category'],
                'checkpoint_title' => $itemData['checkpoint_title'],
                'status' => $itemData['status'],
                'remarks' => $itemData['remarks'] ?? null,
                'photo_path' => $photoPath,
            ]);

            // If item failed, automatically create a Unified CAPA action item!
            if ($itemData['status'] === 'Fail') {
                Audit5sFinding::create([
                    'session_id' => null,
                    'zone_id' => $validated['zone_id'],
                    'safety_inspection_id' => $inspection->id,
                    'category' => 'safety',
                    'risk_level' => $riskStatus === 'Danger' ? 'High' : 'Medium',
                    'pillar' => 'standardize',
                    'description' => "Ketidaksesuaian K3 [{$itemData['checkpoint_title']}]: " . ($itemData['remarks'] ?? 'Pemeriksaan keselamatan tidak memenuhi standar.'),
                    'before_photo_path' => $photoPath,
                    'is_red_tag' => false,
                    'due_date' => now()->addDays($riskStatus === 'Danger' ? 2 : 5),
                    'status' => 'Open',
                ]);
            }
        }

        return redirect()->route('safety-5s.inspections.index')
            ->with('success', "Safety Inspection {$inspection->inspection_number} completed. Score: {$overallScore}% ({$riskStatus}).");
    }

    public function show(SafetyInspection $inspection)
    {
        $inspection->load([
            'building:id,name,code',
            'zone:id,name,code,pic_employee_id',
            'zone.picEmployee:id,nik,full_name,profile_picture',
            'inspector:id,name,email',
            'items',
            'findings'
        ]);

        return Inertia::render('Safety5S/Inspections/Show', [
            'inspection' => $inspection,
        ]);
    }
}
