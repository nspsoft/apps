<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReturn;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalReturnController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403);
        }

        $query = PurchaseReturn::where('supplier_id', $user->supplier_id)
            ->with(['purchaseOrder:id,po_number', 'warehouse:id,name']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $returns = $query->latest()->paginate(20);

        return Inertia::render('Portal/Returns/Index', [
            'returns' => $returns,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(PurchaseReturn $return)
    {
        $user = auth()->user();

        if ($return->supplier_id !== $user->supplier_id) {
            abort(403);
        }

        $return->load(['items.product', 'purchaseOrder', 'warehouse']);

        return Inertia::render('Portal/Returns/Show', [
            'returnDetails' => $return,
        ]);
    }
}
