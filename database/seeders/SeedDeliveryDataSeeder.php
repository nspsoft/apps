<?php

namespace Database\Seeders;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\DeliverySchedule;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedDeliveryDataSeeder extends Seeder
{
    private int $doCounter = 0;

    public function run(): void
    {
        $startOfMonth = Carbon::create(2026, 2, 1);
        $endOfMonth = Carbon::create(2026, 2, 28);

        // Initialize counter from existing DO numbers
        $year = date('Y');
        $month = date('m');
        $prefix = "DO-{$year}{$month}-";
        $lastDo = DeliveryOrder::where('do_number', 'like', "{$prefix}%")
            ->orderByRaw("CAST(SUBSTRING(do_number, -4) AS UNSIGNED) DESC")
            ->first();
        $this->doCounter = $lastDo ? ((int) substr($lastDo->do_number, -4)) : 0;

        // Get forecast customer IDs
        $forecastCustIds = DB::table('sales_forecasts')
            ->whereBetween('period', [$startOfMonth, $endOfMonth])
            ->select('customer_id')
            ->groupBy('customer_id')
            ->pluck('customer_id')
            ->toArray();

        $this->command->info("Found " . count($forecastCustIds) . " forecast customers. Starting DO# from: {$this->doCounter}");

        // Get default warehouse
        $warehouseId = DB::table('warehouses')
            ->where('name', 'LIKE', '%Finished%')
            ->value('id') ?? DB::table('warehouses')->value('id') ?? 1;

        $userId = DB::table('users')->value('id') ?? 1;

        foreach ($forecastCustIds as $customerId) {
            $salesOrders = SalesOrder::where('customer_id', $customerId)
                ->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->with('items.product:id,name,sku', 'customer:id,name,address')
                ->get();

            if ($salesOrders->isEmpty()) {
                $this->command->warn(" Customer ID:{$customerId} has no SOs this month, skipping.");
                continue;
            }

            $customerName = $salesOrders->first()->customer->name ?? "Customer {$customerId}";
            $this->command->info(" Processing: {$customerName} ({$salesOrders->count()} SOs)");

            // Pick 50-80% of SOs
            $count = max(1, (int) ($salesOrders->count() * rand(50, 80) / 100));
            $deliverableSOs = $salesOrders->random($count);

            foreach ($deliverableSOs as $so) {
                // Skip if DO already exists for this SO
                if (DeliveryOrder::where('sales_order_id', $so->id)->exists()) {
                    $this->command->line("   Skipping SO#{$so->so_number} (DO already exists)");
                    continue;
                }

                // Delivery date: 3-14 days after order date
                $deliveryDate = Carbon::parse($so->order_date)->addDays(rand(3, 14));
                if ($deliveryDate->gt($endOfMonth)) {
                    $deliveryDate = $endOfMonth->copy()->subDays(rand(0, 3));
                }

                $this->doCounter++;
                $doNumber = $prefix . str_pad($this->doCounter, 4, '0', STR_PAD_LEFT);

                try {
                    $do = DeliveryOrder::create([
                        'company_id'       => $so->company_id ?? 1,
                        'do_number'        => $doNumber,
                        'sales_order_id'   => $so->id,
                        'customer_id'      => $customerId,
                        'warehouse_id'     => $warehouseId,
                        'delivery_date'    => $deliveryDate,
                        'status'           => $this->randomStatus(),
                        'shipping_name'    => $so->customer->name ?? 'Customer',
                        'shipping_address' => $so->customer->address ?? '-',
                        'driver_name'      => $this->randomDriver(),
                        'vehicle_number'   => $this->randomPlate(),
                        'prepared_by'      => $userId,
                        'notes'            => "Auto-generated delivery for SO#{$so->so_number}",
                    ]);

                    // Create DO Items from SO Items
                    foreach ($so->items as $soItem) {
                        $deliveryRatio = rand(60, 100) / 100;
                        $qtyDelivered = round($soItem->qty * $deliveryRatio, 2);

                        DeliveryOrderItem::create([
                            'delivery_order_id'    => $do->id,
                            'sales_order_item_id'  => $soItem->id,
                            'product_id'           => $soItem->product_id,
                            'qty_ordered'          => $soItem->qty,
                            'qty_delivered'         => $qtyDelivered,
                            'unit_id'              => $soItem->unit_id,
                            'notes'                => null,
                        ]);
                    }

                    $this->command->line("   Created {$doNumber} ({$so->items->count()} items)");
                } catch (\Exception $e) {
                    $this->command->error("   Failed {$doNumber}: " . $e->getMessage());
                }
            }

            // === DELIVERY SCHEDULES ===
            $existingSchedules = DeliverySchedule::where('customer_id', $customerId)
                ->whereBetween('delivery_date', [$startOfMonth, $endOfMonth])
                ->count();

            if ($existingSchedules === 0) {
                $forecastProducts = DB::table('sales_forecasts')
                    ->where('customer_id', $customerId)
                    ->whereBetween('period', [$startOfMonth, $endOfMonth])
                    ->select('product_id', DB::raw('SUM(qty_forecast) as total_forecast'))
                    ->groupBy('product_id')
                    ->get();

                foreach ($forecastProducts as $fp) {
                    $numEntries = rand(2, 4);
                    $totalScheduled = $fp->total_forecast * rand(40, 85) / 100;
                    $remainingQty = $totalScheduled;

                    for ($i = 0; $i < $numEntries && $remainingQty > 0; $i++) {
                        $qty = ($i === $numEntries - 1)
                            ? $remainingQty
                            : round($remainingQty * rand(25, 50) / 100, 2);

                        $scheduleDate = $startOfMonth->copy()->addDays(rand(0, 27));

                        DeliverySchedule::create([
                            'customer_id'      => $customerId,
                            'product_id'       => $fp->product_id,
                            'delivery_date'    => $scheduleDate,
                            'qty_scheduled'    => round($qty, 2),
                            'po_number'        => 'PO-' . strtoupper(substr(md5(rand()), 0, 6)),
                            'notes'            => 'Auto-seeded schedule',
                            'created_by'       => $userId,
                        ]);

                        $remainingQty -= $qty;
                    }
                }
                $this->command->info("   Created delivery schedules for {$forecastProducts->count()} products");
            } else {
                $this->command->line("   Schedules already exist ({$existingSchedules}), skipping.");
            }
        }

        $this->command->info("\n=== Summary ===");
        $this->command->info("Total DOs: " . DeliveryOrder::count());
        $this->command->info("Total DO Items: " . DeliveryOrderItem::count());
        $this->command->info("Total Schedules: " . DeliverySchedule::count());
    }

    private function randomStatus(): string
    {
        $statuses = ['shipped', 'delivered', 'completed', 'delivered', 'completed'];
        return $statuses[array_rand($statuses)];
    }

    private function randomDriver(): string
    {
        $drivers = ['Agus Supriyanto', 'Budi Prayitno', 'Cahyo Susanto', 'Dedi Hermawan', 'Eko Prasetyo'];
        return $drivers[array_rand($drivers)];
    }

    private function randomPlate(): string
    {
        $prefix = ['B', 'L', 'AB', 'D', 'H'];
        return $prefix[array_rand($prefix)] . ' ' . rand(1000, 9999) . ' ' . chr(rand(65, 90)) . chr(rand(65, 90));
    }
}
