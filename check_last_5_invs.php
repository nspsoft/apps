<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SalesInvoice;

$invoices = SalesInvoice::latest()->limit(5)->with('customer')->get();
foreach ($invoices as $inv) {
    echo "Invoice: " . $inv->invoice_number . PHP_EOL;
    echo "  Customer: " . ($inv->customer->name ?? 'N/A') . PHP_EOL;
    echo "  Created: " . $inv->created_at . PHP_EOL;
    echo "  Notes: " . $inv->notes . PHP_EOL;
}
