<?php

namespace App\Http\Controllers\Audit5S;

use App\Http\Controllers\Controller;
use App\Models\Audit5sBuilding;
use App\Models\Audit5sFinding;
use App\Models\Audit5sZone;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class Audit5sCapaController extends Controller
{
    public function index(Request $request)
    {
        $query = Audit5sFinding::with([
            'session:id,session_code,audit_date,auditor_id',
            'session.auditor:id,name',
            'zone:id,name,code,building_id,pic_employee_id',
            'zone.picEmployee:id,nik,full_name',
            'zone.building:id,name,code',
            'remediatedBy:id,name',
            'verifiedBy:id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pillar')) {
            $query->where('pillar', $request->pillar);
        }

        if ($request->filled('building_id')) {
            $query->whereHas('zone', fn($zq) => $zq->where('building_id', $request->building_id));
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->filled('is_red_tag')) {
            $query->where('is_red_tag', $request->boolean('is_red_tag'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('remedy_notes', 'like', "%{$search}%")
                  ->orWhereHas('zone', fn($zq) => $zq->where('name', 'like', "%{$search}%"));
            });
        }

        $findings = $query->orderByRaw("FIELD(status, 'Submitted', 'Open', 'In Progress', 'Closed')")
            ->orderBy('due_date', 'asc')
            ->paginate(15)
            ->withQueryString();

        $buildings = Audit5sBuilding::where('is_active', true)
            ->with('zones:id,building_id,name,code')
            ->get();

        $stats = [
            'total' => Audit5sFinding::count(),
            'open' => Audit5sFinding::where('status', 'Open')->count(),
            'in_progress' => Audit5sFinding::where('status', 'In Progress')->count(),
            'submitted' => Audit5sFinding::where('status', 'Submitted')->count(),
            'closed' => Audit5sFinding::where('status', 'Closed')->count(),
            'overdue' => Audit5sFinding::whereIn('status', ['Open', 'In Progress'])
                ->where('due_date', '<', now()->toDateString())
                ->count(),
        ];

        return Inertia::render('Audit5S/CAPA/Index', [
            'findings' => $findings,
            'buildings' => $buildings,
            'filters' => $request->only(['status', 'pillar', 'building_id', 'zone_id', 'is_red_tag', 'search']),
            'stats' => $stats,
        ]);
    }

    public function submitRemediation(Request $request, Audit5sFinding $finding)
    {
        $validated = $request->validate([
            'after_photo' => 'required|image|max:5120',
            'remedy_notes' => 'required|string|max:1000',
        ]);

        $path = $request->file('after_photo')->store('5s-findings-remedy', 'public');

        $finding->update([
            'after_photo_path' => $path,
            'remedy_notes' => $validated['remedy_notes'],
            'status' => 'Submitted',
            'remediated_by' => auth()->id(),
            'remediated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Remediation submitted successfully. Pending auditor sign-off.');
    }

    public function verifyRemediation(Request $request, Audit5sFinding $finding)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'verification_notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['action'] === 'approve') {
            $finding->update([
                'status' => 'Closed',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verification_notes' => $validated['verification_notes'],
            ]);
            $message = 'Corrective action verified and closed successfully.';
        } else {
            $finding->update([
                'status' => 'In Progress',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verification_notes' => $validated['verification_notes'] ?? 'Remediation rejected. Further action required.',
            ]);
            $message = 'Remediation rejected. Zone PIC notified to re-evaluate.';
        }

        return redirect()->back()->with('success', $message);
    }
}
