<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PortalInvoiceController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        $invoices = PurchaseInvoice::where('supplier_id', $user->supplier_id)
            ->with(['purchaseOrder'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Portal/Invoices/Index', [
            'invoices' => $invoices
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $poId = $request->query('po_id');

        if (!$poId) {
            return redirect()->route('portal.purchase-orders.index');
        }

        $po = PurchaseOrder::with(['warehouse'])->findOrFail($poId);

        if ($po->supplier_id != $user->supplier_id) {
            abort(403);
        }

        // Fetch "Uninvoiced Deliveries": GRNs that have been dispatched/received but NOT linked to an invoice yet.
        $deliveries = \App\Models\GoodsReceipt::where('purchase_order_id', $po->id)
            ->whereIn('status', ['dispatched', 'received', 'completed'])
            ->whereNull('purchase_invoice_id') // Critical: Only uninvoiced ones
            ->with(['items.product', 'items.unit'])
            ->get()
            ->map(function ($gr) {
                // Calculate total value of this GR based on PO prices
                $totalValue = $gr->items->sum(function ($item) {
                    // Use qty_ordered for dispatched, qty_received for others if available.
                    // Simplified: Use the max of both to cover all cases properly.
                    $qty = max($item->qty_ordered, $item->qty_received);
                    return $qty * $item->unit_cost;
                });

                return [
                    'id' => $gr->id,
                    'grn_number' => $gr->grn_number,
                    'delivery_note_number' => $gr->delivery_note_number,
                    'receipt_date' => $gr->receipt_date,
                    'total_value' => $totalValue,
                    'items_count' => $gr->items->count(),
                    'items' => $gr->items->map(function($item) {
                        return [
                            'product_name' => $item->product_name,
                            'qty' => max($item->qty_ordered, $item->qty_received),
                            'unit_price' => $item->unit_cost,
                            'subtotal' => max($item->qty_ordered, $item->qty_received) * $item->unit_cost
                        ];
                    })
                ];
            });

        return Inertia::render('Portal/Invoices/Create', [
            'order' => $po,
            'deliveries' => $deliveries
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'invoice_number' => 'required|string|max:255',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'selected_gr_ids' => 'required|array|min:1',
            'selected_gr_ids.*' => 'exists:goods_receipts,id'
        ]);

        $po = PurchaseOrder::findOrFail($request->purchase_order_id);

        if ($po->supplier_id != $user->supplier_id) {
            abort(403);
        }

        DB::transaction(function () use ($request, $po, $user) {
            // 1. Create Invoice Header
            $invoice = new PurchaseInvoice();
            $invoice->company_id = $po->company_id;
            $invoice->purchase_order_id = $po->id;
            $invoice->supplier_id = $po->supplier_id;
            $invoice->invoice_number = $request->invoice_number;
            $invoice->invoice_date = $request->invoice_date;
            $invoice->due_date = $request->due_date;
            $invoice->status = PurchaseInvoice::STATUS_UNPAID;
            $invoice->currency = $po->currency;
            $invoice->exchange_rate = $po->exchange_rate;
            $invoice->notes = $request->notes;
            $invoice->created_by = $user->id;
            $invoice->save();
            
            $subtotal = 0;

            // 2. Process Selected Deliveries
            $selectedGRs = \App\Models\GoodsReceipt::whereIn('id', $request->selected_gr_ids)
                ->where('purchase_order_id', $po->id) // Security Check
                ->whereNull('purchase_invoice_id') // Double Check
                ->with('items')
                ->get();

            foreach ($selectedGRs as $gr) {
                // Link GR to Invoice
                $gr->purchase_invoice_id = $invoice->id;
                $gr->save();

                // Create Invoice Items from GR Items
                foreach ($gr->items as $grItem) {
                    $qty = max($grItem->qty_ordered, $grItem->qty_received);
                    $lineTotal = $qty * $grItem->unit_cost;
                    $subtotal += $lineTotal;

                    $invoice->items()->create([
                        'purchase_invoice_id' => $invoice->id,
                        'goods_receipt_item_id' => $grItem->id,
                        'product_id' => $grItem->product_id,
                        'description' => $grItem->product_name,
                        'qty' => $qty,
                        'unit_price' => $grItem->unit_cost,
                        'subtotal' => $lineTotal,
                    ]);
                }
            }

            // 3. Finalize Invoice Totals
            $taxAmount = $subtotal * ($po->tax_percent / 100);
            $totalAmount = $subtotal + $taxAmount;

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_percent' => $po->tax_percent,
                'tax_amount' => $taxAmount,
                'discount_total' => 0,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
            ]);
        });

        return redirect()->route('portal.invoices.index')->with('success', 'Invoice created successfully. Delivery Notes have been updated.');
    }

    public function show(PurchaseInvoice $invoice)
    {
        $user = auth()->user();

        if ($invoice->supplier_id != $user->supplier_id) {
            abort(403);
        }

        $invoice->load(['items', 'purchaseOrder', 'company', 'supplier']);

        return Inertia::render('Portal/Invoices/Show', [
            'invoice' => $invoice
        ]);
    }
}
