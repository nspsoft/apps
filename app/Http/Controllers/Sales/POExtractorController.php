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
        ]);
    }
}
