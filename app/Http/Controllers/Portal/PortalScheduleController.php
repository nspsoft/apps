<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        // Get the month to display (defaults to current month)
        $month = $request->get('month', now()->format('Y-m'));
        $startOfMonth = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Get POs with expected delivery dates in this month
        $expectedDeliveries = PurchaseOrder::where('supplier_id', $user->supplier_id)
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_ORDERED,
                PurchaseOrder::STATUS_ACKNOWLEDGED,
                PurchaseOrder::STATUS_PARTIAL
            ])
            ->whereNotNull('expected_date')
            ->whereBetween('expected_date', [$startOfMonth, $endOfMonth])
            ->select('id', 'po_number', 'expected_date', 'status', 'total')
            ->get()
            ->map(fn($po) => [
                'id' => $po->id,
                'title' => $po->po_number,
                'date' => $po->expected_date->format('Y-m-d'),
                'type' => 'expected',
                'status' => $po->status,
                'amount' => $po->total,
            ]);

        // Get dispatched deliveries (actual shipments on the way)
        $dispatchedDeliveries = GoodsReceipt::where('supplier_id', $user->supplier_id)
            ->where('status', GoodsReceipt::STATUS_DISPATCHED)
            ->whereBetween('receipt_date', [$startOfMonth, $endOfMonth])
            ->with('purchaseOrder:id,po_number')
            ->select('id', 'grn_number', 'delivery_note_number', 'receipt_date', 'purchase_order_id')
            ->get()
            ->map(fn($gr) => [
                'id' => $gr->id,
                'title' => $gr->delivery_note_number ?: $gr->grn_number,
                'date' => $gr->receipt_date->format('Y-m-d'),
                'type' => 'dispatched',
                'po_number' => $gr->purchaseOrder?->po_number,
            ]);

        // Combine all events
        $events = $expectedDeliveries->concat($dispatchedDeliveries)->sortBy('date')->values();

        // Upcoming deliveries (next 14 days from today)
        $upcomingExpected = PurchaseOrder::where('supplier_id', $user->supplier_id)
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_ORDERED,
                PurchaseOrder::STATUS_ACKNOWLEDGED,
                PurchaseOrder::STATUS_PARTIAL
            ])
            ->whereNotNull('expected_date')
            ->whereBetween('expected_date', [now(), now()->addDays(14)])
            ->orderBy('expected_date')
            ->select('id', 'po_number', 'expected_date', 'status', 'total')
            ->take(10)
            ->get();

        return Inertia::render('Portal/Schedule/Index', [
            'events' => $events,
            'upcoming' => $upcomingExpected,
            'currentMonth' => $startOfMonth->format('Y-m'),
            'monthLabel' => $startOfMonth->format('F Y'),
        ]);
    }
}
