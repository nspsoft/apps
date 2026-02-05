<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\User;

$suppliers = Supplier::where('name', 'like', '%DM Indonesia%')->get();

echo "=== PT. DM Indonesia DUPLICATES ===\n\n";

foreach ($suppliers as $s) {
    $po = PurchaseOrder::where('supplier_id', $s->id)->count();
    $user = User::where('supplier_id', $s->id)->count();
    echo "ID:{$s->id} | Email: {$s->email} | PO: {$po} | Users: {$user}\n";
}
