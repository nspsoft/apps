<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoutingController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Bom::with(['product', 'unit', 'operations'])
            ->withCount('operations')
            ->orderBy('code');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%")
                  ->orWhereHas('product', function ($pq) use ($request) {
                      $pq->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        return Inertia::render('Manufacturing/Routing/Index', [
            'routings' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }
}
