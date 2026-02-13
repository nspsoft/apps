<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Rfq;
use App\Models\SupplierQuotation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalRfqController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403);
        }

        // Get RFQs where this supplier is listed in rfq_suppliers
        $query = Rfq::whereHas('targetSuppliers', function ($q) use ($user) {
                $q->where('suppliers.id', $user->supplier_id);
            })
            ->with(['targetSuppliers' => function ($q) use ($user) {
                $q->where('suppliers.id', $user->supplier_id);
            }])
            ->orderByRaw("FIELD(status, 'open', 'closed', 'awarded')");
            
        if ($request->filled('filter') && $request->filter !== 'all') {
            $query->where('status', $request->filter);
        }

        $rfqs = $query->latest()->paginate(20);

        return Inertia::render('Portal/Rfq/Index', [
            'rfqs' => $rfqs,
            'filters' => $request->only(['filter']),
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403);
        }

        $rfq = Rfq::with(['items', 'targetSuppliers' => function($q) use ($user) {
            $q->where('suppliers.id', $user->supplier_id);
        }])->findOrFail($id);

        // Check availability
        $supplierStatus = $rfq->targetSuppliers->first();
        if (!$supplierStatus) {
            abort(403, 'You are not invited to this RFQ.');
        }

        // Mark as viewed if pending
        if ($supplierStatus->pivot->status === 'pending') {
            $rfq->targetSuppliers()->updateExistingPivot($user->supplier_id, [
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }

        // Get existing quotation if any
        $quotation = SupplierQuotation::where('rfq_id', $rfq->id)
            ->where('supplier_id', $user->supplier_id)
            ->first();

        return Inertia::render('Portal/Rfq/Show', [
            'rfq' => $rfq,
            'quotation' => $quotation,
            'supplierStatus' => $supplierStatus->pivot->status,
        ]);
    }

    public function store(Request $request, $id)
    {
        $user = auth()->user();
        $rfq = Rfq::findOrFail($id);
        
        // Validation
        $request->validate([
            'quote_number' => 'required|string',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'items' => 'required|array',
            'items.*.price' => 'required|numeric|min:0',
            'file' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        // Calculate totals based on RFQ items and inputted prices
        // Simplified for this MVP: We take total directly or calc from items
        // For MVP, letting user input total amount might be easier, but let's do simple sum
        $total = 0;
        // In a real app we would save line items for the quotation too.
        // For now we save the grand total in the quotation header.
        
        $total = $request->input('total_amount');

        $quotationData = [
            'rfq_id' => $rfq->id,
            'supplier_id' => $user->supplier_id,
            'quote_number' => $request->quote_number,
            'quotation_date' => $request->quotation_date,
            'valid_until' => $request->valid_until,
            'subtotal' => $request->subtotal,
            'tax_amount' => $request->tax_amount,
            'total_amount' => $request->total_amount,
            'payment_terms' => $request->payment_terms,
            'delivery_terms' => $request->delivery_terms,
            'notes' => $request->notes,
            'status' => 'submitted',
        ];

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('quotations/' . $user->supplier_id, 'public');
            $quotationData['file_path'] = $path;
        }

        SupplierQuotation::create($quotationData);

        // Update Pivot Status
        $rfq->targetSuppliers()->updateExistingPivot($user->supplier_id, [
            'status' => 'responded',
            'responded_at' => now(),
        ]);

        return redirect()->route('rfq.index')->with('success', 'Quotation submitted successfully.');
    }
}
