<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$subcontOrders = \App\Models\SubcontractOrder::with('purchaseOrder.items')->whereHas('workOrder', function($q) {
        $q->whereIn('wo_number', ['WO-202606-0230', 'WO-202607-0002']);
    })->get();

foreach ($subcontOrders as $so) {
    echo "Subcont Order: {$so->order_number} | WO: {$so->workOrder->wo_number}\n";
    foreach ($so->purchaseOrder->items as $poItem) {
        if ($poItem->work_order_id == $so->work_order_id) {
            echo "  PO Item Qty: {$poItem->qty} | WO ID: {$poItem->work_order_id}\n";
        }
    }
}
