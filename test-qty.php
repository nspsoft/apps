<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$components = \App\Models\WorkOrderComponent::with('workOrder', 'product')
    ->whereHas('workOrder', function($q) {
        $q->whereIn('wo_number', ['WO-202606-0230', 'WO-202607-0002']);
    })
    ->get();

foreach ($components as $comp) {
    echo "WO: {$comp->workOrder->wo_number} | SKU: {$comp->product->sku} | Qty Required: {$comp->qty_required} | Qty Consumed: {$comp->qty_consumed}\n";
}
