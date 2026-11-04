<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\SafetyHazardReport;
use App\Models\Audit5sFinding;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sZone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class SafetyHazardReportController extends Controller
{
    public function index(Request $request)
    {
        $query = SafetyHazardReport::with([
            'building:id,name,code',
            'zone:id,name,code,pic_employee_id',
            'zone.picEmployee:id,nik,full_name',
            'reporter:id,name,email',
            'resolvedBy:id,name',
            'finding:id,status,hazard_report_id',
        ])->latest();

        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }

        if ($request->filled('hazard_type')) {
            $query->where('hazard_type', $request->hazard_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location_details', 'like', "%{$search}%")
                  ->orWhereHas('zone', fn($z) => $z->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            });
        }

        $reports = $query->paginate(15)->withQueryString();

        $buildings = Audit5sBuilding::where('is_active', true)
            ->with(['zones:id,building_id,name,code,pic_employee_id'])
            ->get();

        $allReports = SafetyHazardReport::all();
        $stats = [
            'total' => $allReports->count(),
            'reported' => $allReports->where('status', 'Reported')->count(),
            'action_taken' => $allReports->where('status', 'Action_Taken')->count(),
            'closed' => $allReports->where('status', 'Closed')->count(),
            'critical_high' => $allReports->whereIn('risk_level', ['Critical', 'High'])->where('status', '!=', 'Closed')->count(),
        ];

        return Inertia::render('Safety5S/HazardReports/Index', [
            'reports' => $reports,
            'buildings' => $buildings,
            'stats' => $stats,
            'filters' => $request->only(['building_id', 'status', 'risk_level', 'hazard_type', 'search']),
        ]);
    }

    public function create()
    {
        $buildings = Audit5sBuilding::where('is_active', true)
            ->with(['zones:id,building_id,name,code,pic_employee_id', 'zones.picEmployee:id,nik,full_name'])
            ->get();

        return Inertia::render('Safety5S/HazardReports/Create', [
            'buildings' => $buildings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:audit_5s_buildings,id',
            'zone_id' => 'required|exists:audit_5s_zones,id',
            'hazard_type' => 'required|in:Unsafe_Condition,Unsafe_Action,Near_Miss,Environmental',
            'risk_level' => 'required|in:Low,Medium,High,Critical',
            'description' => 'required|string|max:2000',
            'location_details' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:5120',
        ]);

        $zone = Audit5sZone::findOrFail($validated['zone_id']);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('safety/hazards', 'public');
        }

        $ticketNumber = 'HAZ-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        $hazardReport = SafetyHazardReport::create([
            'ticket_number' => $ticketNumber,
            'reporter_user_id' => auth()->id(),
            'building_id' => $validated['building_id'],
            'zone_id' => $validated['zone_id'],
            'hazard_type' => $validated['hazard_type'],
            'risk_level' => $validated['risk_level'],
            'description' => $validated['description'],
            'location_details' => $validated['location_details'] ?? null,
            'photo_before_path' => $photoPath,
            'status' => 'Reported',
            'assigned_pic_employee_id' => $zone->pic_employee_id,
        ]);

        // Calculate due date based on risk severity
        $dueDays = match ($validated['risk_level']) {
            'Critical' => 1,
            'High' => 3,
            'Medium' => 7,
            default => 14,
        };

        // Auto-create Unified CAPA action item linked to this hazard
        Audit5sFinding::create([
            'session_id' => null,
            'zone_id' => $validated['zone_id'],
            'category' => 'safety',
            'risk_level' => $validated['risk_level'],
            'hazard_report_id' => $hazardReport->id,
            'pillar' => 'standardize',
            'description' => "[{$validated['hazard_type']}] {$validated['description']}",
            'before_photo_path' => $photoPath,
            'is_red_tag' => in_array($validated['risk_level'], ['Critical', 'High']),
            'red_tag_action' => in_array($validated['risk_level'], ['Critical', 'High']) ? 'Tindakan Pengamanan Mendesak' : null,
            'due_date' => now()->addDays($dueDays)->toDateString(),
            'status' => 'Open',
        ]);

        return redirect()->route('safety-5s.hazard-reports.index')
            ->with('success', "Hazard report {$ticketNumber} has been recorded and assigned to the zone PIC.");
    }

    public function resolve(Request $request, SafetyHazardReport $hazardReport)
    {
        $validated = $request->validate([
            'action_taken_remarks' => 'required|string|max:1500',
            'after_photo' => 'required|image|max:5120',
        ]);

        $photoAfterPath = $request->file('after_photo')->store('safety/hazards/resolved', 'public');

        $hazardReport->update([
            'action_taken_remarks' => $validated['action_taken_remarks'],
            'photo_after_path' => $photoAfterPath,
            'status' => 'Action_Taken',
            'resolved_at' => now(),
            'resolved_by_user_id' => auth()->id(),
        ]);

        // Also update linked CAPA finding
        $finding = Audit5sFinding::where('hazard_report_id', $hazardReport->id)->first();
        if ($finding) {
            $finding->update([
                'status' => 'Submitted',
                'after_photo_path' => $photoAfterPath,
                'remedy_notes' => $validated['action_taken_remarks'],
                'remediated_by' => auth()->id(),
                'remediated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', "Corrective action submitted for {$hazardReport->ticket_number}. Pending supervisor verification.");
    }

    public function verify(Request $request, SafetyHazardReport $hazardReport)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000',
        ]);

        $finding = Audit5sFinding::where('hazard_report_id', $hazardReport->id)->first();

        if ($validated['action'] === 'approve') {
            $hazardReport->update([
                'status' => 'Closed',
            ]);

            if ($finding) {
                $finding->update([
                    'status' => 'Closed',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                    'verification_notes' => $validated['notes'] ?? 'Hazard resolution approved and verified.',
                ]);
            }

            $message = "Hazard report {$hazardReport->ticket_number} verified and closed successfully.";
        } else {
            $hazardReport->update([
                'status' => 'Reported',
            ]);

            if ($finding) {
                $finding->update([
                    'status' => 'In Progress',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                    'verification_notes' => $validated['notes'] ?? 'Resolution rejected. Further mitigation needed.',
                ]);
            }

            $message = "Hazard resolution rejected. Assigned PIC has been notified to re-evaluate.";
        }

        return redirect()->back()->with('success', $message);
    }
}
