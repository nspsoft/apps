<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Unit;
use App\Models\Category;
use Illuminate\Database\Seeder;

class SamplePipeProductsSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::first();
        $category = Category::first();

        $products = [
            ['sku' => 'PIPE-4IN-6M', 'name' => 'Steel Pipe Ø 4 inch x 6m'],
            ['sku' => 'PIPE-6IN-12M', 'name' => 'Steel Pipe Ø 6 inch x 12m'],
            ['sku' => 'ELB-90-4IN', 'name' => 'Elbow 90° Ø 4 inch'],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'unit_id' => $unit?->id,
                    'category_id' => $category?->id,
                    'is_active' => true,
                    'is_sold' => true,
                    'is_purchased' => true,
                ]
            );
        }
    }
}
