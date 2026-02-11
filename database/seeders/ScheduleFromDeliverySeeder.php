<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\DeliverySchedule;
use Carbon\Carbon;

class ScheduleFromDeliverySeeder extends Seeder
{
    /**
     * Generate realistic schedule data based on existing delivery orders.
     * 
     * Logic:
     * - For each (customer, product, date) combination in delivered orders,
     *   create a corresponding schedule record.
     * - Schedule qty = delivery qty * random multiplier (1.05 - 1.35)
     *   so that achievement is realistically ~74% - ~95%.
     * - Also adds some future schedule dates without delivery (upcoming planned).
     */
    public function run(): void
    {
        // Clear existing schedules
        DeliverySchedule::truncate();
        
        $this->command->info('Cleared existing delivery schedules.');
        
        // Get all delivered data grouped by customer, product, date
        $deliveries = DB::table('delivery_order_items')
            ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
            ->whereIn('delivery_orders.status', ['shipped', 'delivered', 'completed'])
            ->select(
                'delivery_orders.customer_id',
                'delivery_order_items.product_id',
                'delivery_orders.delivery_date',
                DB::raw('SUM(delivery_order_items.qty_delivered) as total_delivered')
            )
            ->groupBy('delivery_orders.customer_id', 'delivery_order_items.product_id', 'delivery_orders.delivery_date')
            ->get();
        
        $this->command->info("Found {$deliveries->count()} delivery groups to create schedules from.");
        
        if ($deliveries->count() === 0) {
            $this->command->warn('No delivery data found. Aborting.');
            return;
        }
        
        $records = [];
        $poCounter = 1;
        $customerProductPO = []; // track PO per customer+product pair
        
        foreach ($deliveries as $del) {
            $key = $del->customer_id . '-' . $del->product_id;
            
            // Assign a PO number per customer+product combination
            if (!isset($customerProductPO[$key])) {
                $customerProductPO[$key] = 'PO-' . str_pad($poCounter++, 4, '0', STR_PAD_LEFT);
            }
            
            // Schedule qty = delivery qty * multiplier (schedule should be >= delivery)
            // This makes achievement = delivery / schedule ≈ 74% - 95%
            $multiplier = 1 + (mt_rand(5, 35) / 100); // 1.05 to 1.35
            $scheduledQty = round($del->total_delivered * $multiplier, 2);
            
            $records[] = [
                'customer_id' => $del->customer_id,
                'product_id' => $del->product_id,
                'delivery_date' => $del->delivery_date,
                'qty_scheduled' => $scheduledQty,
                'po_number' => $customerProductPO[$key],
                'reference_number' => 'SCH-' . Carbon::parse($del->delivery_date)->format('Ymd') . '-' . $del->customer_id,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Also create some future schedule entries (upcoming 2 weeks) for realism
        // Use the same customer+product pairs as existing deliveries
        $uniquePairs = collect($deliveries)->unique(function ($item) {
            return $item->customer_id . '-' . $item->product_id;
        });
        
        $today = Carbon::today();
        foreach ($uniquePairs->take(15) as $pair) { // max 15 unique pairs for future
            $key = $pair->customer_id . '-' . $pair->product_id;
            $avgQty = collect($deliveries)
                ->filter(fn($d) => $d->customer_id == $pair->customer_id && $d->product_id == $pair->product_id)
                ->avg('total_delivered');
            
            // Create 3-5 future schedule dates
            $futureDays = collect(range(1, mt_rand(3, 5)))->map(fn($i) => $today->copy()->addDays($i * mt_rand(1, 3)));
            
            foreach ($futureDays as $futureDate) {
                // Vary the schedule qty around the average
                $variance = 1 + (mt_rand(-15, 15) / 100);
                $records[] = [
                    'customer_id' => $pair->customer_id,
                    'product_id' => $pair->product_id,
                    'delivery_date' => $futureDate->format('Y-m-d'),
                    'qty_scheduled' => round($avgQty * $variance, 2),
                    'po_number' => $customerProductPO[$key] ?? 'PO-FUTURE',
                    'reference_number' => 'SCH-' . $futureDate->format('Ymd') . '-' . $pair->customer_id,
                    'notes' => 'Planned delivery',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        // Insert in chunks
        $chunks = array_chunk($records, 500);
        foreach ($chunks as $chunk) {
            DB::table('delivery_schedules')->insert($chunk);
        }
        
        $this->command->info("Created " . count($records) . " schedule records.");
        $this->command->info("Schedule data now matches delivery data for realistic chart display.");
    }
}
