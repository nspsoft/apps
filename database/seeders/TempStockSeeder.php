<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TempStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouseId = 4; // Finished Goods
        $products = \App\Models\Product::all();

        foreach ($products as $product) {
            \App\Models\ProductStock::updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouseId],
                [
                    'qty_on_hand' => rand(10, 100),
                    'qty_reserved' => 0,
                    'qty_incoming' => 0,
                    'qty_outgoing' => 0,
                    'avg_cost' => 10000
                ]
            );
        }
    }
}
