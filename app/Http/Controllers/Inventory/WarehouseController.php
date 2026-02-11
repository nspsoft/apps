<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request): Response
    {
        $query = Warehouse::with(['manager', 'locations'])
            ->withCount('productStocks')
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->type, function ($q, $type) {
                $q->where('type', $type);
            });

        // Dynamic Sorting
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        if (in_array($sort, ['code', 'name', 'type', 'location', 'is_active'])) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $warehouses = $query->paginate(20)->withQueryString();

        return Inertia::render('Inventory/Warehouses/Index', [
            'warehouses' => $warehouses,
            'filters' => $request->only(['search', 'type', 'sort', 'direction']),
            'warehouseTypes' => [
                ['value' => 'warehouse', 'label' => 'Warehouse'],
                ['value' => 'production', 'label' => 'Production'],
                ['value' => 'transit', 'label' => 'Transit'],
                ['value' => 'scrap', 'label' => 'Scrap'],
            ],
        ]);
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create(): Response
    {
        return Inertia::render('Inventory/Warehouses/Form', [
            'warehouse' => null,
            'managers' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'type' => 'required|in:warehouse,production,transit,scrap',
            'is_default' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        Warehouse::create($validated);

        return redirect()->route('inventory.warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse): Response
    {
        $warehouse->load(['manager', 'locations', 'productStocks.product']);

        return Inertia::render('Inventory/Warehouses/Show', [
            'warehouse' => $warehouse,
        ]);
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit(Warehouse $warehouse): Response
    {
        $warehouse->load(['locations']);

        return Inertia::render('Inventory/Warehouses/Form', [
            'warehouse' => $warehouse,
            'managers' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * Update the specified warehouse.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code,' . $warehouse->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'manager_id' => 'nullable|exists:users,id',
            'type' => 'required|in:warehouse,production,transit,scrap',
            'is_default' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if (($validated['is_default'] ?? false) && !$warehouse->is_default) {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
        }

        $warehouse->update($validated);

        return redirect()->route('inventory.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    /**
     * Remove the specified warehouse.
     */
    public function destroy(Warehouse $warehouse)
    {
        // Check if warehouse has stock
        if ($warehouse->productStocks()->exists()) {
            return back()->with('error', 'Cannot delete warehouse with existing stock.');
        }

        $warehouse->delete();

        return redirect()->route('inventory.warehouses.index')
            ->with('success', 'Warehouse deleted successfully.');
    }
}
