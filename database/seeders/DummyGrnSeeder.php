<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyGrnSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = \App\Models\Company::first();
        $supplier = \App\Models\Supplier::first();
        $product = \App\Models\Product::first();
        $warehouseId = 4; // Finished Goods
        $user = \App\Models\User::first();

        if (!$company || !$supplier || !$product || !$user) {
            $this->command->error('Missing required data');
            return;
        }

        try {
            DB::beginTransaction();

            $poId = DB::table('purchase_orders')->insertGetId([
                'company_id' => $company->id,
                'po_number' => 'PO-DUMMY-' . rand(1000, 9999),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouseId,
                'order_date' => now(),
                'status' => 'ordered',
                'currency' => 'IDR',
                'exchange_rate' => 1,
                'subtotal' => 750000,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => 11,
                'tax_amount' => 82500,
                'total' => 832500,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $poItemId = DB::table('purchase_order_items')->insertGetId([
                'purchase_order_id' => $poId,
                'product_id' => $product->id,
                'description' => $product->name,
                'qty' => 50,
                'unit_price' => 15000,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'subtotal' => 750000,
                'qty_received' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $receiptId = DB::table('goods_receipts')->insertGetId([
                'company_id' => $company->id,
                'grn_number' => \App\Models\GoodsReceipt::generateGrnNumber(),
                'purchase_order_id' => $poId,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouseId,
                'receipt_date' => now(),
                'notes' => 'Dummy Receipt for Stock Testing',
                'status' => 'received',
                'received_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('goods_receipt_items')->insert([
                'goods_receipt_id' => $receiptId,
                'purchase_order_item_id' => $poItemId,
                'product_id' => $product->id,
                'qty_ordered' => 50,
                'qty_received' => 50,
                'unit_cost' => 15000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            $this->command->info("Created PO ID: {$poId} and GRN ID: {$receiptId}");
            $this->command->info("Product: {$product->name}");
            $this->command->info("Qty to add: 50");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Error creating dummy data: " . $e->getMessage());
        }
    }
}
