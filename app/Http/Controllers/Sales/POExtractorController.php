<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class POExtractorController extends Controller
{
    /**
     * Display the AI PO Extractor page.
     */
    public function index()
    {
        return Inertia::render('Sales/POExtractor', [
            'customers' => \App\Models\Customer::select('id', 'name', 'code')->orderBy('name')->get(),
            'units' => \App\Models\Unit::select('id', 'name', 'code')->where('is_active', true)->orderBy('name')->get(),
            'categories' => \App\Models\Category::select('id', 'name')->where('type', 'product')->orderBy('name')->get(),
        ]);
    }
    /**
     * Export extracted data to Excel.
     */
    public function export(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'po_number' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'po_date' => 'nullable|date',
            'items' => 'required|array',
        ]);

        $fileName = 'PO_Extraction_' . str_replace(['/', '\\'], '_', $data['po_number'] ?? 'Draft') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PoExtractionExport($data), $fileName);
    }
}
