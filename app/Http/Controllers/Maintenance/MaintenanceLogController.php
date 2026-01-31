<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceLog;
use App\Models\Machine;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class MaintenanceLogController extends Controller
{
    public function index()
    {
        $logs = MaintenanceLog::with(['machine', 'spareparts'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                // Calculate duration if finished
                $duration = '-';
                if ($log->finished_at) {
                    $start = Carbon::parse($log->started_at);
                    $end = Carbon::parse($log->finished_at);
                    $duration = $start->diffForHumans($end, true); // "2 hours"
                } elseif ($log->status === 'in_progress') {
                    $start = Carbon::parse($log->started_at);
                    $duration = $start->diffForHumans(null, true) . ' (Ongoing)';
                }

                $statusColor = match($log->status) {
                    'resolved' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
                    'in_progress' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                    'open' => 'text-rose-400 bg-rose-500/10 border-rose-500/20',
                    default => 'text-slate-400'
                };

                return [
                    'id' => $log->id,
                    'machine' => $log->machine->name,
                    'machine_code' => $log->machine->code,
                    'type' => strtoupper($log->type),
                    'description' => $log->description,
                    'technician' => $log->technician_name ?? '-',
                    'started_at' => $log->started_at ? $log->started_at->format('d M H:i') : '-',
                    'finished_at' => $log->finished_at ? $log->finished_at->format('d M H:i') : '-',
                    'duration' => $duration,
                    'status' => ucfirst($log->status),
                    'status_raw' => $log->status,
                    'status_color' => $statusColor,
                    'parts_used' => $log->spareparts->map(fn($p) => $p->name . ' (' . $p->pivot->qty_used . ')')->join(', '),
                ];
            });

        return Inertia::render('Maintenance/Breakdown', [
            'logs' => $logs,
            'machines' => Machine::select('id', 'name')->get(),
            'spareparts' => Sparepart::where('stock', '>', 0)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'description' => 'required|string',
            'started_at' => 'required|date',
        ]);

        $validated['type'] = 'breakdown';
        $validated['status'] = 'open';

        MaintenanceLog::create($validated);

        return redirect()->back()->with('success', 'Breakdown Reported');
    }

    public function update(Request $request, MaintenanceLog $log)
    {
        // Handling Status Update (In Progress -> Resolved)
        if ($request->has('status')) {
            $log->status = $request->status;
            if ($request->status === 'resolved') {
                $log->finished_at = Carbon::now();
            }
            if ($request->has('technician_name')) {
                $log->technician_name = $request->technician_name;
            }
            $log->save();
        }

        // Handling Sparepart Usage
        if ($request->has('spareparts')) {
            // Expecting array of {id, qty}
            foreach ($request->spareparts as $part) {
                $log->spareparts()->attach($part['id'], ['qty_used' => $part['qty']]);
                // Decrease stock
                $sparepart = Sparepart::find($part['id']);
                $sparepart->decrement('stock', $part['qty']);
            }
        }

        return redirect()->back()->with('success', 'Maintenance Log Updated');
    }
}
