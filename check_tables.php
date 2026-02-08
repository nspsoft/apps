<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$tables = [
    'sales_order_items',
    'purchase_order_items',
    'quotation_items',
    'stock_movements',
    'goods_receipt_items',
    'delivery_order_items',
    'stock_adjustment_items',
    'stock_opname_items',
    'work_orders',
    'work_order_components',
    'bom_components',
    'boms',
    'material_consumptions',
    'sales_return_items',
    'purchase_return_items',
    'rfq_items',
    'purchase_request_items',
    'sales_invoice_items',
    'purchase_invoice_items',
];

foreach ($tables as $t) {
    echo "$t: " . (Schema::hasTable($t) ? 'OK' : 'FAIL') . PHP_EOL;
}
