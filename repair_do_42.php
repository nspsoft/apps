<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$doItem = \App\Models\DeliveryOrderItem::find(42);
if (!$doItem) { echo "DO Item 42 not found.\n"; exit; }

echo "Checking DO Item 42. Current Invoiced: {$doItem->qty_invoiced}\n";

// Find invoices using this DO + SO Item combination
$realInvoiced = \App\Models\SalesInvoiceItem::whereHas('salesInvoice', function($q) {
        $q->whereNull('deleted_at'); // Active invoices only
    })
    ->where('delivery_order_id', $doItem->delivery_order_id)
    ->where('sales_order_item_id', $doItem->sales_order_item_id)
    ->sum('qty');

echo "Calculated Real Invoiced Qty: {$realInvoiced}\n";

$doItem->qty_invoiced = $realInvoiced;
$doItem->save();

echo "Corrected DO Item 42 Invoiced Qty to {$doItem->qty_invoiced}.\n";
