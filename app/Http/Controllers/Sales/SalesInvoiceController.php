<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesInvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SalesInvoice::with(['salesOrder.customer'])
            ->withCount('items')
            ->when($request->search, function ($q, $search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('salesOrder', function ($sq) use ($search) {
                        $sq->where('so_number', 'like', "%{$search}%")
                            ->orWhereHas('customer', function ($cq) use ($search) {
                                $cq->where('name', 'like', "%{$search}%");
                            });
                    });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            });

        $invoices = $query->orderByDesc('invoice_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Sales/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status']),
            'statuses' => [
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'issued', 'label' => 'Issued'],
                ['value' => 'partial', 'label' => 'Partial'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'cancelled', 'label' => 'Cancelled'],
            ],
        ]);
    }

    public function show(SalesInvoice $salesInvoice): Response
    {
        $salesInvoice->load(['salesOrder.customer', 'items.product', 'items.unit']);

        return Inertia::render('Sales/Invoices/Show', [
            'invoice' => $salesInvoice,
        ]);
    }

    public function print(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['salesOrder.customer', 'items.product', 'items.unit']);

        return view('print.invoice', ['invoice' => $salesInvoice]);
    }

    public function printV2(SalesInvoice $salesInvoice)
    {
        $salesInvoice->load(['salesOrder.customer', 'items.product', 'items.unit']);

        return view('print.invoice-v2', ['invoice' => $salesInvoice]);
    }

    public function publicValidate($uuid)
    {
        $invoice = SalesInvoice::with(['salesOrder.customer', 'items.product', 'items.unit'])
            ->where('id', $uuid)
            ->firstOrFail();

        return view('print.public-invoice-validation', ['invoice' => $invoice]);
    }

    public function confirm(SalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be confirmed.');
        }

        $salesInvoice->update(['status' => 'issued']);

        return back()->with('success', 'Invoice confirmed and issued.');
    }

    public function recordPayment(Request $request, SalesInvoice $salesInvoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$salesInvoice->balance,
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string',
            'reference' => 'nullable|string',
        ]);

        try {
            \DB::transaction(function () use ($salesInvoice, $validated) {
                $salesInvoice->addPayment($validated['amount']);

                // You could also create a Payment model here if needed in the future
            });

            return back()->with('success', 'Payment recorded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error recording payment: '.$e->getMessage());
        }
    }

    public function destroy(SalesInvoice $salesInvoice)
    {
        if ($salesInvoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be deleted.');
        }

        try {
            \DB::transaction(function () use ($salesInvoice) {
                // Return invoiced qty to SO items
                foreach ($salesInvoice->items as $item) {
                    $soItem = $item->salesOrderItem;
                    if ($soItem) {
                        $soItem->qty_invoiced -= $item->qty;
                        $soItem->save();
                    }

                    if ($item->delivery_order_id && $item->sales_order_item_id) {
                        $doItem = \App\Models\DeliveryOrderItem::where('delivery_order_id', $item->delivery_order_id)
                            ->where('sales_order_item_id', $item->sales_order_item_id)
                            ->first();

                        if ($doItem) {
                            $doItem->qty_invoiced -= $item->qty;
                            $doItem->save();
                        }
                    }
                }
                $salesInvoice->delete();
            });

            return back()->with('success', 'Invoice deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting invoice: '.$e->getMessage());
        }
    }
}
