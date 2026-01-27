<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock and send alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = \App\Models\Product::with('stocks')
            ->where('is_active', true)
            ->where('min_stock', '>', 0)
            ->get();

        $count = 0;
        foreach ($products as $product) {
            $currentStock = $product->stocks->sum('qty_on_hand');
            
            if ($currentStock <= $product->min_stock) {
                 // Check if we already have an unread notification for this product
                 // This prevents spamming on every run
                 $exists = \Illuminate\Support\Facades\DB::table('notifications')
                    ->where('type', 'App\Notifications\LowStockAlert')
                    ->where('data', 'like', '%"product_id":' . $product->id . ',%')
                    ->whereNull('read_at')
                    ->exists();

                 if (!$exists) {
                     // Notify all users (for MVP just the first one or all)
                     $users = \App\Models\User::all();
                     
                     foreach ($users as $user) {
                        $user->notify(new \App\Notifications\LowStockAlert($product, $currentStock, $product->min_stock));
                     }
                     
                     $this->info("Alert sent for {$product->name} (Stock: {$currentStock}, Min: {$product->min_stock})");
                     $count++;
                 }
            }
        }
        
        $this->info("Check complete. Sent {$count} alerts.");
    }
}
