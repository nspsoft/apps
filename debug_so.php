<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\SalesOrder;

$so = SalesOrder::where('so_number', 'SO/26/01/012')->with(['items', 'invoices.items'])->first();

if (!$so) {
    echo "SO not found.\n";
    exit;
}

echo "SO ID: {$so->id}\n";
foreach($so->items as $item) {
    echo "Item ID {$item->id}: Ordered={$item->qty}, Delivered={$item->qty_delivered}, Invoiced={$item->qty_invoiced}\n";
}

echo "\nInvoices:\n";
foreach($so->invoices as $inv) {
    echo "Invoice ID {$inv->id} - {$inv->invoice_number} ({$inv->status}):\n";
    foreach($inv->items as $invItem) {
        echo " - InvItem ID {$invItem->id} (SO Item {$invItem->sales_order_item_id}): Qty={$invItem->qty}\n";
    }
}
