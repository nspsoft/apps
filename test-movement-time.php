<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$movements = \App\Models\StockMovement::whereIn('id', [7251, 7252, 7257, 7258])->get();
foreach ($movements as $m) {
    echo "ID: {$m->id} | Qty: {$m->qty} | Created: {$m->created_at}\n";
}
