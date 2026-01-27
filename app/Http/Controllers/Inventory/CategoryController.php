<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Category::query()
            ->when($request->search, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            })
            ->when($request->type, function ($q, $type) {
                $q->where('type', $type);
            }, function ($q) {
                // Default to product categories if not specified
                $q->where('type', 'product'); 
            });

        $categories = $query->with('parent')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Inventory/Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'type']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get potential parents (exclude itself if editing, but here it's create)
        // Only fetch product categories for now
        $parents = Category::where('type', 'product')->orderBy('name')->get();

        return Inertia::render('Inventory/Categories/Form', [
            'category' => null,
            'parents' => $parents
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:categories,code',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'type' => 'required|in:product,service,consumable', // or just strict string if dynamic
            'is_active' => 'boolean'
        ]);

        // Force type to 'product' for now if UI doesn't support others well yet, 
        // or let user choose. Based on request "Product Category", likely 'product' type.
        // But model has 'type' column. Let's assume we handle 'product' type primarily here.
        
        Category::create($validated);

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        $parents = Category::where('type', 'product')
            ->where('id', '!=', $category->id) // Prevent self-parenting
            ->orderBy('name')
            ->get();

        return Inertia::render('Inventory/Categories/Form', [
            'category' => $category,
            'parents' => $parents
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:categories,code,' . $category->id,
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $category->id,
            'description' => 'nullable|string',
            'type' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $category->update($validated);

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category because it is used by products.');
        }

        if ($category->children()->exists()) {
             return back()->with('error', 'Cannot delete category because it has sub-categories.');
        }

        $category->delete();

        return redirect()->route('inventory.categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
