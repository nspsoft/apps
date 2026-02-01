<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        $user = User::first();
        $warehouse = Warehouse::where('is_default', true)->first() ?? Warehouse::first();
        $products = Product::where('is_sold', true)->get();

        if ($products->isEmpty()) {
            $this->command->error('No products found for sales. Please run DemoDataSeeder.');
            return;
        }

        // Cleanup existing data to avoid duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        SalesOrderItem::truncate();
        SalesOrder::truncate();
        Quotation::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Create Customers
        $customers = [
            ['name' => 'PT Astra International', 'code' => 'CUST-001'],
            ['name' => 'Waskita Karya Tbk', 'code' => 'CUST-002'],
            ['name' => 'Adhi Karya Tbk', 'code' => 'CUST-003'],
            ['name' => 'PT Pertamina', 'code' => 'CUST-004'],
            ['name' => 'Indofood Sukses Makmur', 'code' => 'CUST-005'],
            ['name' => 'Mayora Indah Tbk', 'code' => 'CUST-006'],
        ];

        foreach ($customers as $cust) {
            Customer::firstOrCreate(
                ['code' => $cust['code']],
                [
                    'company_id' => $company->id,
                    'name' => $cust['name'],
                    'email' => strtolower(str_replace(' ', '', $cust['name'])) . '@example.com',
                    'phone' => '021-' . rand(1111111, 8888888),
                    'address' => 'Jl. Jenderal Sudirman No. ' . rand(1, 200),
                    'city' => 'Jakarta',
                    'is_active' => true,
                ]
            );
        }

        $customerModels = Customer::all();

        // 2. Create Sales Orders (Last 6 Months)
        $statuses = ['draft', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

        for ($i = 0; $i < 60; $i++) {
            $date = Carbon::now()->subDays(rand(1, 180));
            $customer = $customerModels->random();
            $status = $statuses[array_rand($statuses)];

            // Older orders should be delivered or cancelled
            if ($date->lt(Carbon::now()->subMonths(1))) {
                $status = rand(0, 10) > 1 ? 'delivered' : 'cancelled';
            }

            $so = SalesOrder::create([
                'company_id' => $company->id,
                'so_number' => 'SO-' . $date->format('ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => $date,
                'delivery_date' => $date->copy()->addDays(rand(3, 14)),
                'status' => $status,
                'currency' => 'IDR',
                'exchange_rate' => 1,
                'subtotal' => 0,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => 11,
                'tax_amount' => 0,
                'total' => 0,
                'created_by' => $user->id,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $itemCount = rand(1, 4);
            $subtotal = 0;
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $qty = rand(5, 50);
                $price = $product->selling_price > 0 ? $product->selling_price : rand(50000, 1000000);
                $rowTotal = $qty * $price;

                $deliveredQty = 0;
                if ($status === 'delivered') {
                    $deliveredQty = $qty;
                } elseif ($status === 'shipped') {
                    $deliveredQty = rand(floor($qty * 0.5), $qty); // Partial delivery simulation
                }

                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'qty' => $qty,
                    'unit_id' => $product->unit_id,
                    'unit_price' => $price,
                    'subtotal' => $rowTotal,
                    'qty_delivered' => $deliveredQty,
                ]);
                $subtotal += $rowTotal;
            }

            $tax = $subtotal * 0.11;
            $so->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total' => $subtotal + $tax,
            ]);
        }

        // 3. Create Quotations (Recent)
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subDays(rand(0, 10));
            $status = rand(0, 5) > 1 ? 'sent' : 'draft';

            Quotation::create([
                'number' => 'QUO-' . $date->format('ym') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customerModels->random()->id,
                'quotation_date' => $date,
                'valid_until' => $date->copy()->addDays(30),
                'status' => $status,
                'subtotal' => rand(5000000, 50000000),
                'tax' => 0, // Will be calculated if needed or just set
                'total' => rand(5000000, 50000000),
                'created_by' => $user->id,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }

        $this->command->info('Sales data seeded successfully.');
    }
}
