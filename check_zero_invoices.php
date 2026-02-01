<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;

$zeroInvoices = SalesInvoice::where('total', 0)->with('items')->get();

echo "Found " . $zeroInvoices->count() . " invoices with total 0.\n\n";

foreach ($zeroInvoices as $inv) {
    echo "Invoice: " . $inv->invoice_number . " (ID: " . $inv->id . ")\n";
    echo "Date: " . $inv->invoice_date->format('Y-m-d') . "\n";
    echo "Items Count: " . $inv->items->count() . "\n";
    
    foreach ($inv->items as $item) {
        echo "  - Item ID: " . $item->id . ", Qty: " . $item->qty . ", Subtotal: " . $item->subtotal . "\n";
    }
    echo "-----------------------------------\n";
}
