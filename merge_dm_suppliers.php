<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;

echo "=== MERGE PT. DM Indonesia DUPLICATES ===\n\n";

$keepId = 80;  // The supplier to keep
$mergeIds = [81, 82, 83];  // Suppliers to merge into keepId

DB::beginTransaction();

try {
    // 1. Update Purchase Orders
    $poUpdated = PurchaseOrder::whereIn('supplier_id', $mergeIds)
        ->update(['supplier_id' => $keepId]);
    echo "Updated {$poUpdated} Purchase Orders\n";

    // 2. Update Purchase Invoices
    $invUpdated = PurchaseInvoice::whereIn('supplier_id', $mergeIds)
        ->update(['supplier_id' => $keepId]);
    echo "Updated {$invUpdated} Purchase Invoices\n";

    // 3. Delete duplicate suppliers
    $deleted = Supplier::whereIn('id', $mergeIds)->forceDelete();
    echo "Deleted {$deleted} duplicate suppliers\n";

    DB::commit();
    echo "\n=== MERGE COMPLETED SUCCESSFULLY ===\n";
    
    // Verify
    $remaining = Supplier::where('name', 'like', '%DM Indonesia%')->count();
    echo "Remaining PT. DM Indonesia suppliers: {$remaining}\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
