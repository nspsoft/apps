<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StockMovement::with(['product', 'warehouse', 'createdBy'])
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->whereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    });
                });
            })
            ->when($request->type, function ($q, $type) {
                $q->where('type', $type);
            })
            ->when($request->warehouse_id, function ($q, $warehouse) {
                $q->where('warehouse_id', $warehouse);
            });

        $movements = $query->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Inventory/Movements/Index', [
            'movements' => $movements,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'type', 'warehouse_id']),
            'types' => [
                ['value' => 'adjustment', 'label' => 'Adjustment'],
                ['value' => 'po_receive', 'label' => 'PO Receive'],
                ['value' => 'so_delivery', 'label' => 'SO Delivery'],
                ['value' => 'production_in', 'label' => 'Production In'],
                ['value' => 'production_out', 'label' => 'Production Out'],
                ['value' => 'transfer', 'label' => 'Transfer'],
            ],
        ]);
    }
}
