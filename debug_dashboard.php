<?php

use App\Models\SalesOrder;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Current Time: " . now()->toDateTimeString() . "\n";
echo "Current Month: " . now()->month . "\n";
echo "Current Year: " . now()->year . "\n";

$allOrders = SalesOrder::count();
echo "Total Orders: $allOrders\n";

$currentMonthOrders = SalesOrder::whereMonth('order_date', now()->month)
    ->whereYear('order_date', now()->year)
    ->get();

echo "Current Month Orders: " . $currentMonthOrders->count() . "\n";

if ($currentMonthOrders->count() > 0) {
    echo "First Order Total: " . $currentMonthOrders->first()->total . "\n";
    echo "First Order Status: " . $currentMonthOrders->first()->status . "\n";
} else {
    $latestOrder = SalesOrder::latest('order_date')->first();
    if ($latestOrder) {
        echo "Latest Order Date: " . $latestOrder->order_date->toDateString() . "\n";
    }
}

$nonDraftCancelled = SalesOrder::whereNotIn('status', ['cancelled', 'draft'])
            ->whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->sum('total');

echo "Calculated Revenue: $nonDraftCancelled\n";
