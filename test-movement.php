<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$subcontOrders = \App\Models\SubcontractOrder::whereHas('workOrder', function($q) {
        $q->whereIn('wo_number', ['WO-202606-0230', 'WO-202607-0002']);
    })->get();

foreach ($subcontOrders as $order) {
    echo "Subcontract Order: {$order->order_number}\n";
    $movements = \App\Models\StockMovement::where('reference_type', \App\Models\SubcontractOrder::class)
        ->where('reference_id', $order->id)
        ->with('product')
        ->get();
    foreach ($movements as $m) {
        echo "  Movement ID: {$m->id} | Product: {$m->product->name} | Qty: {$m->qty}\n";
    }
}
