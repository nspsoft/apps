<?php

namespace App\Imports\Purchasing;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GoodsReceiptsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Group rows by Supplier + Warehouse + Delivery Note + Date
        $groups = $rows->groupBy(function ($row) {
            return $row['supplier_code'] . '|' . $row['warehouse_name'] . '|' . $row['delivery_note_number'];
        });

        foreach ($groups as $groupKey => $items) {
            DB::transaction(function () use ($items) {
                $firstRow = $items->first();
                
                $supplier = Supplier::where('code', $firstRow['supplier_code'])->first();
                $warehouse = Warehouse::where('name', $firstRow['warehouse_name'])->first();
                
                if (!$supplier || !$warehouse) {
                    // Skip if supplier or warehouse not found
                    return;
                }

                // Try to find Purchase Order if provided
                $po = null;
                if (!empty($firstRow['po_number'])) {
                    $po = PurchaseOrder::where('po_number', $firstRow['po_number'])->first();
                }

                // Create Goods Receipt
                $receipt = GoodsReceipt::create([
                    'grn_number' => GoodsReceipt::generateGrnNumber(),
                    'receipt_date' => $this->parseDate($firstRow['receipt_date_yyyy_mm_dd']),
                    'supplier_id' => $supplier->id,
                    'warehouse_id' => $warehouse->id,
                    'purchase_order_id' => $po ? $po->id : null,
                    'delivery_note_number' => $firstRow['delivery_note_number'],
                    'status' => 'draft',
                    'received_by' => auth()->id(),
                ]);

                // Create Items
                foreach ($items as $item) {
                    $product = Product::where('code', $item['product_code'])->first();
                    
                    if ($product) {
                        // If PO exists, try to link to PO item
                        $poItemId = null;
                        if ($po) {
                            $poItem = $po->items()->where('product_id', $product->id)->first();
                            if ($poItem) {
                                $poItemId = $poItem->id;
                            }
                        }

                        $receipt->items()->create([
                            'product_id' => $product->id,
                            'purchase_order_item_id' => $poItemId,
                            'qty_received' => $item['qty_received'],
                            'qty_ordered' => 0, // In this context, we might not know ordered qty if no PO
                            'unit_cost' => $poItem ? $poItem->unit_price : ($product->buying_price ?? 0),
                        ]);
                    }
                }
            });
        }
    }

    private function parseDate($date)
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
        } catch (\Exception $e) {
            try {
                return Carbon::parse($date);
            } catch (\Exception $e) {
                return now();
            }
        }
    }
}
