<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$wo = \App\Models\WorkOrder::with('bom.components.product')->where('wo_number', 'WO-202606-0230')->first();
echo "WO: {$wo->wo_number} | Planned: {$wo->qty_planned} | BOM Qty: {$wo->bom->qty}\n";
foreach ($wo->bom->components as $bc) {
    echo "  BOM Component: {$bc->product->name} | Required Qty in BOM: {$bc->required_qty}\n";
}
