<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseOrderItem;
use App\Models\WorkOrder;
use App\Models\Bom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FulfillmentAnalysisController extends Controller
{
    /**
     * Analyze fulfillment requirements for extracted PO items
     */
    public function analyze(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0',
        ]);

        $analysisResults = [];
        $summary = [
            'total_items' => 0,
            'items_ok' => 0,
            'items_warning' => 0,
            'items_critical' => 0,
        ];

        foreach ($request->items as $item) {
            $productId = $item['product_id'] ?? null;
            $requiredQty = floatval($item['qty']);
            
            if (!$productId) {
                // Product not matched, skip analysis
                $analysisResults[] = [
                    'product_id' => null,
                    'product_name' => $item['product_name'],
                    'required_qty' => $requiredQty,
                    'current_stock' => 0,
                    'incoming_po' => 0,
                    'in_production' => 0,
                    'available' => 0,
                    'gap' => -$requiredQty,
                    'priority' => 'red',
                    'product_type' => 'unknown',
                    'is_manufactured' => false,
                    'is_purchased' => false,
                    'has_bom' => false,
                    'recommendation' => [
                        'action' => 'match_first',
                        'qty' => 0,
                        'message' => 'Please match product first to get recommendation',
                    ],
                ];
                $summary['total_items']++;
                $summary['items_critical']++;
                continue;
            }

            $product = Product::with('stocks')->find($productId);
            if (!$product) {
                continue;
            }

            // Calculate current available stock
            $currentStock = $product->stocks->sum('qty_on_hand');
            $reservedStock = $product->stocks->sum('qty_reserved');
            $availableStock = $currentStock - $reservedStock;

            // Calculate incoming from pending Purchase Orders (not fully received)
            $incomingPO = PurchaseOrderItem::where('product_id', $productId)
                ->whereHas('purchaseOrder', function ($q) {
                    $q->whereIn('status', ['submitted', 'approved', 'ordered', 'partial']);
                })
                ->get()
                ->sum(function ($poItem) {
                    return max(0, $poItem->qty - $poItem->qty_received);
                });

            // Calculate in-production from active Work Orders
            $inProduction = WorkOrder::where('product_id', $productId)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->get()
                ->sum(function ($wo) {
                    return max(0, $wo->qty_planned - $wo->qty_produced);
                });

            // Total available (current + incoming + in_production)
            $totalAvailable = $availableStock + $incomingPO + $inProduction;
            
            // Calculate gap
            $gap = $totalAvailable - $requiredQty;

            // Determine priority
            $priority = 'green';
            if ($gap < 0) {
                $priority = 'red';
            } elseif ($availableStock < $requiredQty && $gap >= 0) {
                $priority = 'yellow'; // Stock available but only with incoming/production
            }

            // Check if product has BOM (can be manufactured)
            $hasBom = Bom::where('product_id', $productId)->where('is_active', true)->exists();

            // Generate recommendation
            $recommendation = $this->generateRecommendation(
                $product, 
                $requiredQty, 
                $availableStock, 
                $gap,
                $hasBom
            );

            $analysisResults[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'required_qty' => $requiredQty,
                'current_stock' => $currentStock,
                'reserved_stock' => $reservedStock,
                'available_stock' => $availableStock,
                'incoming_po' => $incomingPO,
                'in_production' => $inProduction,
                'total_available' => $totalAvailable,
                'gap' => $gap,
                'priority' => $priority,
                'product_type' => $product->type ?? 'unknown',
                'is_manufactured' => $product->is_manufactured ?? false,
                'is_purchased' => $product->is_purchased ?? false,
                'has_bom' => $hasBom,
                'lead_time_days' => $product->lead_time_days ?? 0,
                'recommendation' => $recommendation,
            ];

            // Update summary
            $summary['total_items']++;
            if ($priority === 'green') {
                $summary['items_ok']++;
            } elseif ($priority === 'yellow') {
                $summary['items_warning']++;
            } else {
                $summary['items_critical']++;
            }
        }

        return response()->json([
            'success' => true,
            'items' => $analysisResults,
            'summary' => $summary,
        ]);
    }

    /**
     * Generate recommendation based on product configuration and stock
     */
    private function generateRecommendation(
        Product $product, 
        float $requiredQty, 
        float $availableStock, 
        float $gap,
        bool $hasBom
    ): array {
        $shortage = abs(min(0, $gap));
        
        // If no shortage, no action needed
        if ($shortage <= 0) {
            return [
                'action' => 'fulfill_from_stock',
                'qty' => 0,
                'message' => 'Stock sufficient - can fulfill directly from inventory',
                'type' => 'success',
            ];
        }

        // Determine best action based on product configuration
        $isManufactured = $product->is_manufactured ?? false;
        $isPurchased = $product->is_purchased ?? true;

        // Priority 1: Manufacture if product has BOM and is_manufactured
        if ($isManufactured && $hasBom) {
            $unitName = $product->unit->name ?? 'PCS';
            return [
                'action' => 'create_work_order',
                'qty' => ceil($shortage),
                'message' => "Create Work Order for {$shortage} {$unitName}",
                'type' => 'manufacture',
            ];
        }

        // Priority 2: Purchase if is_purchased
        if ($isPurchased) {
            $unitName = $product->unit->name ?? 'PCS';
            return [
                'action' => 'create_purchase_order',
                'qty' => ceil($shortage),
                'message' => "Create Purchase Order for {$shortage} {$unitName}",
                'type' => 'purchase',
            ];
        }

        // Fallback: Manual review needed
        $unitName = $product->unit->name ?? 'PCS';
        return [
            'action' => 'manual_review',
            'qty' => ceil($shortage),
            'message' => "Need {$shortage} {$unitName} - manual review required",
            'type' => 'warning',
        ];
    }
}
