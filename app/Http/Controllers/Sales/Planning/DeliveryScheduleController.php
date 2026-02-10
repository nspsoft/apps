<?php

namespace App\Http\Controllers\Sales\Planning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\DeliverySchedule;
use App\Imports\DeliveryScheduleImport;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class DeliveryScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = DeliverySchedule::with(['customer', 'product.unit'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                      ->orWhere('po_number', 'like', "%{$search}%");
            })
            ->when($request->date, function ($query, $date) {
                $query->whereDate('delivery_date', $date);
            })
            ->orderBy('delivery_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Sales/Planning/Schedule/Index', [
            'schedules' => $schedules,
            'filters' => $request->only(['search', 'date']),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new DeliveryScheduleImport, $request->file('file'));
            return back()->with('success', 'Delivery Schedule imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }
}
