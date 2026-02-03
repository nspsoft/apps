<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$item = \App\Models\DeliveryOrderItem::find(42);
if ($item) {
    echo "DO Item 42: Qty Delivered: {$item->qty_delivered}, Qty Invoiced: {$item->qty_invoiced}\n";
} else {
    echo "DO Item 42 not found.\n";
}
