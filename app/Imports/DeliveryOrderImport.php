<?php

namespace App\Imports;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\DeliveryOrder;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryOrderImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Group rows by SO Number
        $grouped = $rows->groupBy('so_number');

        foreach ($grouped as $soNumber => $items) {
            try {
                if (empty($soNumber)) continue;

                $so = SalesOrder::where('so_number', $soNumber)
                    ->whereIn('status', ['confirmed', 'processing', 'partial'])
                    ->first();

                if (!$so) {
                    Log::warning("DeliveryOrderImport: SO [{$soNumber}] not found or not in a deliverable status. Skipping.");
                    continue;
                }

                DB::transaction(function () use ($so, $items) {
                    // Generate DO Number
                    $lastDO = DeliveryOrder::orderBy('id', 'desc')->first();
                    $number = 'DO/' . date('Ymd') . '/' . str_pad(($lastDO ? $lastDO->id : 0) + 1, 4, '0', STR_PAD_LEFT);

                    $do = DeliveryOrder::create([
                        'do_number' => $number,
                        'sales_order_id' => $so->id,
                        'customer_id' => $so->customer_id,
                        'warehouse_id' => $so->warehouse_id,
                        'delivery_date' => now()->toDateString(),
                        'driver_name' => 'Imported',
                        'vehicle_number' => '-',
                        'shipping_address' => $so->shipping_address,
                        'status' => 'draft',
                    ]);

                    foreach ($items as $row) {
                        $product = Product::where('sku', $row['product_code'])->first();
                        if (!$product) {
                            Log::warning("DeliveryOrderImport: Product [{$row['product_code']}] not found. Skipping item.");
                            continue;
                        }

                        // Find matching SO item
                        $soItem = SalesOrderItem::where('sales_order_id', $so->id)
                            ->where('product_id', $product->id)
                            ->first();

                        if (!$soItem) {
                            Log::warning("DeliveryOrderImport: Product [{$row['product_code']}] not found in SO [{$so->so_number}]. Skipping item.");
                            continue;
                        }

                        $do->items()->create([
                            'sales_order_item_id' => $soItem->id,
                            'product_id' => $product->id,
                            'qty_ordered' => $soItem->qty,
                            'qty_delivered' => floatval($row['qty_delivered'] ?? 0),
                            'unit_id' => $soItem->unit_id,
                            'batch_number' => $row['batch_number'] ?? null,
                            'notes' => $row['notes'] ?? null,
                        ]);
                    }
                });
            } catch (\Exception $e) {
                Log::error("DeliveryOrderImport: Error processing SO [{$soNumber}]: " . $e->getMessage());
            }
        }
    }
}
