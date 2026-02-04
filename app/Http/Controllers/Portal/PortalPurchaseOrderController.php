<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalPurchaseOrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        $orders = PurchaseOrder::where('supplier_id', $user->supplier_id)
            ->with(['items.product', 'warehouse'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Portal/PurchaseOrders/Index', [
            'orders' => $orders
        ]);
    }

    public function show(PurchaseOrder $order)
    {
        $user = auth()->user();

        // Security Check: Suppliers can only view their own POs
        if ($order->supplier_id !== $user->supplier_id) {
            abort(403, 'Unauthorized access to this Purchase Order.');
        }

        $order->load(['items.product', 'items.unit', 'warehouse']);

        return Inertia::render('Portal/PurchaseOrders/Show', [
            'order' => $order
        ]);
    }

    public function acknowledge(PurchaseOrder $order)
    {
        $user = auth()->user();

        if ($order->supplier_id !== $user->supplier_id) {
            abort(403);
        }

        $order->update(['status' => 'confirmed']); // Or 'acknowledged' if added to ENUM

        return back()->with('success', 'Purchase Order confirmed successfully.');
    }
    
    public function reject(PurchaseOrder $order, Request $request)
    {
        $user = auth()->user();

        if ($order->supplier_id !== $user->supplier_id) {
            abort(403);
        }
        
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $order->update([
            'status' => 'cancelled', // Or specific 'rejected' status
            'notes' => $order->notes . "\n[REJECTED by Supplier: {$request->reason}]"
        ]);

        return back()->with('success', 'Purchase Order rejected.');
    }
}
