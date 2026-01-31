<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceSparepartController extends Controller
{
    public function index()
    {
        $spareparts = Sparepart::orderBy('name', 'asc')->get()->map(function ($part) {
            $status = 'Optimal';
            $statusColor = 'text-emerald-400';
            
            if ($part->stock == 0) {
                $status = 'Out of Stock';
                $statusColor = 'text-rose-400';
            } elseif ($part->stock <= $part->min_stock) {
                $status = 'Low Stock';
                $statusColor = 'text-amber-400';
            }

            return [
                'id' => $part->id,
                'name' => $part->name,
                'part_number' => $part->part_number ?? '-',
                'location' => $part->location ?? '-',
                'stock' => $part->stock,
                'min_stock' => $part->min_stock,
                'cost' => 'Rp ' . number_format($part->unit_cost, 0, ',', '.'),
                'status' => $status,
                'status_color' => $statusColor,
            ];
        });

        return Inertia::render('Maintenance/Spareparts', [
            'spareparts' => $spareparts,
            'stats' => [
                'total_items' => $spareparts->count(),
                'low_stock' => $spareparts->filter(fn($p) => $p['status'] === 'Low Stock')->count(),
                'stock_value' => 'Rp ' . number_format(Sparepart::sum(\DB::raw('stock * unit_cost')), 0, ',', '.'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'part_number' => 'nullable|string|max:50',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'location' => 'nullable|string',
        ]);

        Sparepart::create($validated);

        return redirect()->back()->with('success', 'Sparepart Added');
    }

    public function update(Request $request, Sparepart $sparepart)
    {
        // Simple stock adjustment
        if ($request->has('adjustment')) {
            $sparepart->increment('stock', $request->adjustment);
        } else {
            $sparepart->update($request->validate([
                'name' => 'required|string',
                'stock' => 'required|integer',
                'min_stock' => 'required|integer',
                'location' => 'nullable|string',
            ]));
        }

        return redirect()->back()->with('success', 'Sparepart Updated');
    }
}
