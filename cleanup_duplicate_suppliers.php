<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use App\Models\GoodsReceipt;
use Illuminate\Support\Facades\DB;

echo "=== SUPPLIER CLEANUP - DELETE DUPLICATES WITHOUT TRANSACTIONS ===\n\n";

// Find duplicate supplier names
$duplicates = Supplier::select('name', DB::raw('COUNT(*) as count'))
    ->groupBy('name')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isEmpty()) {
    echo "No duplicate suppliers found.\n";
    exit;
}

echo "Found " . $duplicates->count() . " supplier names with duplicates:\n";
foreach ($duplicates as $dup) {
    echo "  - {$dup->name} ({$dup->count} entries)\n";
}

$deletedCount = 0;
$keptCount = 0;

foreach ($duplicates as $dup) {
    $suppliers = Supplier::where('name', $dup->name)->get();
    
    echo "\nProcessing: {$dup->name}\n";
    
    foreach ($suppliers as $supplier) {
        // Check if has transactions
        $hasPO = PurchaseOrder::where('supplier_id', $supplier->id)->exists();
        $hasInvoice = PurchaseInvoice::where('supplier_id', $supplier->id)->exists();
        $hasGR = GoodsReceipt::whereHas('purchaseOrder', function($q) use ($supplier) {
            $q->where('supplier_id', $supplier->id);
        })->exists();
        $hasUser = \App\Models\User::where('supplier_id', $supplier->id)->exists();
        
        $hasTransactions = $hasPO || $hasInvoice || $hasGR || $hasUser;
        
        if ($hasTransactions) {
            echo "  [KEEP] ID:{$supplier->id} - Has transactions (PO:{$hasPO}, INV:{$hasInvoice}, GR:{$hasGR}, User:{$hasUser})\n";
            $keptCount++;
        } else {
            echo "  [DELETE] ID:{$supplier->id} - No transactions\n";
            $supplier->forceDelete();
            $deletedCount++;
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Deleted: {$deletedCount} suppliers\n";
echo "Kept: {$keptCount} suppliers\n";
echo "Done!\n";
