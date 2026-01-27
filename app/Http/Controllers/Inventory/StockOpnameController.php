<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StockOpname::with(['warehouse', 'createdBy'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('opname_number', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->warehouse_id, function ($q, $warehouse) {
                $q->where('warehouse_id', $warehouse);
            });

        $opnames = $query->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Inventory/Opname/Index', [
            'opnames' => $opnames,
            'warehouses' => Warehouse::active()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'warehouse_id']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Inventory/Opname/Form', [
            'opname' => null,
            'opnameNumber' => StockOpname::generateNumber(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'opname_number' => 'required|string|max:30|unique:inv_stock_opnames,opname_number',
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $opname = StockOpname::create([
            'opname_number' => $validated['opname_number'],
            'warehouse_id' => $validated['warehouse_id'],
            'opname_date' => $validated['opname_date'],
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('inventory.opname.show', $opname)
            ->with('success', 'Stock Opname session created.');
    }

    public function show(StockOpname $opname): Response
    {
        $opname->load(['warehouse', 'createdBy', 'items.product']);

        return Inertia::render('Inventory/Opname/Show', [
            'opname' => $opname,
        ]);
    }

    public function updateItems(Request $request, StockOpname $opname)
    {
        if ($opname->status === 'completed') {
            return back()->with('error', 'Cannot update completed opname.');
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:inv_stock_opname_items,id',
            'items.*.qty_physic' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $opname) {
            foreach ($validated['items'] as $itemData) {
                // Determine diff
                // We need to fetch original item to compare
                $item = $opname->items()->find($itemData['id']);
                if ($item) {
                    $item->update([
                        'qty_physic' => $itemData['qty_physic'],
                        'qty_difference' => $itemData['qty_physic'] - $item->qty_system,
                    ]);
                }
            }
            
            if ($opname->status === 'draft') {
                $opname->update(['status' => 'in_progress']);
            }
        });

        return back()->with('success', 'Counts saved successfully.');
    }

    public function populate(StockOpname $opname)
    {
        if ($opname->items()->exists()) {
            return back()->with('error', 'Items already populated.');
        }

        // Fetch all active products
        $products = Product::active()
            ->stockManaged()
            ->get();

        $items = [];
        foreach ($products as $product) {
            $stock = ProductStock::where('product_id', $product->id)
                ->where('warehouse_id', $opname->warehouse_id)
                ->first();
            
            $qtySystem = $stock ? $stock->qty_on_hand : 0;
            
            $items[] = [
                'stock_opname_id' => $opname->id,
                'product_id' => $product->id,
                'qty_system' => $qtySystem,
                'qty_physic' => $qtySystem, // Default to system qty
                'qty_difference' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (count($items) > 0) {
            DB::table('inv_stock_opname_items')->insert($items);
        }

        return back()->with('success', 'Populated ' . count($items) . ' items from system stock.');
    }

    public function complete(StockOpname $opname)
    {
        if ($opname->status === 'completed') {
            return back()->with('error', 'Already completed.');
        }

        DB::transaction(function () use ($opname) {
            foreach ($opname->items as $item) {
                $delta = $item->qty_physic - $item->qty_system;

                if ($delta != 0) {
                    $stock = ProductStock::firstOrCreate(
                        [
                            'product_id' => $item->product_id,
                            'warehouse_id' => $opname->warehouse_id,
                        ],
                        [
                            'qty_on_hand' => 0,
                            'qty_reserved' => 0,
                            'qty_incoming' => 0,
                            'qty_outgoing' => 0,
                            'avg_cost' => 0,
                        ]
                    );

                    $stock->adjustStock(
                        $delta,
                        null,
                        StockMovement::TYPE_OPNAME,
                        $opname,
                        "Stock Opname #{$opname->opname_number}"
                    );
                }
            }

            $opname->update(['status' => 'completed']);
        });

        return back()->with('success', 'Stock Opname completed and adjustments posted.');
    }

    public function destroy(StockOpname $opname)
    {
        if ($opname->status === 'completed') {
            return back()->with('error', 'Cannot delete completed opname.');
        }

        $opname->delete();

        return redirect()->route('inventory.opname.index')
            ->with('success', 'Stock Opname deleted.');
    }
}
