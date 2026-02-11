<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QcMasterPointController extends Controller
{
    public function index()
    {
        $products = \App\Models\Product::with(['qcMasterPoints', 'unit', 'category'])
            ->has('qcMasterPoints') // Only products with QC points initially, or filter in frontend
            ->paginate(10);
        
        // Also get all products for the dropdown
        $allProducts = \App\Models\Product::select('id', 'name', 'sku')->get();

        return inertia('QualityControl/MasterPoints/Index', [
            'products' => $products,
            'allProducts' => $allProducts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'parameter_name' => 'required|string|max:255',
            'standard_min' => 'required|numeric',
            'standard_max' => 'required|numeric|gte:standard_min',
            'unit' => 'nullable|string|max:50',
            'method' => 'nullable|string|max:100',
        ]);

        \App\Models\QcMasterPoint::create($validated);

        return back()->with('success', 'QC Point created successfully.');
    }

    public function update(Request $request, $id)
    {
        $point = \App\Models\QcMasterPoint::findOrFail($id);

        $validated = $request->validate([
            'parameter_name' => 'required|string|max:255',
            'standard_min' => 'required|numeric',
            'standard_max' => 'required|numeric|gte:standard_min',
            'unit' => 'nullable|string|max:50',
            'method' => 'nullable|string|max:100',
        ]);

        $point->update($validated);

        return back()->with('success', 'QC Point updated successfully.');
    }

    public function destroy($id)
    {
        $point = \App\Models\QcMasterPoint::findOrFail($id);
        $point->delete();

        return back()->with('success', 'QC Point deleted successfully.');
    }
}
