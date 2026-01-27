<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = DB::select('describe purchase_order_items');
foreach ($columns as $col) {
    echo $col->Field . " | " . $col->Type . " | " . ($col->Null === 'NO' ? 'NOT NULL' : 'NULL') . PHP_EOL;
}
