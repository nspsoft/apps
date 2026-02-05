<?php

use App\Models\User;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\RfqSupplier;
use App\Models\PurchaseReturn;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::find(3);
echo "User: " . $user->name . " (Supplier ID: " . $user->supplier_id . ")\n";
echo "Email: " . $user->email . "\n";

$supplierId = $user->supplier_id;

// Check RFQs
$rfqCount = Rfq::whereHas('targetSuppliers', function($q) use ($supplierId) {
    $q->where('supplier_id', $supplierId);
})->count();
echo "RFQs: " . $rfqCount . "\n";

if ($rfqCount == 0) {
    echo "Creating Dummy RFQ...\n";
    $rfq = Rfq::create([
        'rfq_number' => 'RFQ-' . date('Ymd') . '-001',
        'title' => 'Urgent Request for Steel Pipes',
        'description' => 'We need a quotation for 500 units of Steel Pipe X-200 by end of week.',
        'deadline' => now()->addDays(5),
        'status' => 'open',
        'created_by' => 1
    ]);
    
    RfqItem::create([
        'rfq_id' => $rfq->id,
        'product_name' => 'Steel Pipe X-200',
        'qty_required' => 500,
        'unit' => 'pcs',
        'specifications' => 'ISO 9001 certified, diameter 50mm'
    ]);

    RfqSupplier::create([
        'rfq_id' => $rfq->id,
        'supplier_id' => $supplierId,
        'status' => 'pending'
    ]);
    echo "Dummy RFQ Created.\n";
}

// Check Returns
$returnCount = PurchaseReturn::where('supplier_id', $supplierId)->count();
echo "Returns: " . $returnCount . "\n";

if ($returnCount == 0) {
    echo "Creating Dummy Return...\n";
    // Find a PO to attach to
    $po = PurchaseOrder::where('supplier_id', $supplierId)->first();
    if (!$po) {
        // Create dummy PO if none exists (unlikely given previous output but safe)
        $po = PurchaseOrder::create([
            'po_number' => 'PO-DEMO-001',
            'supplier_id' => $supplierId,
            'status' => 'approved',
            'order_date' => now()->subMonth(),
             'total_amount' => 1000000
        ]);
    }

    // Get warehouse and product
    $warehouse = App\Models\Warehouse::first();
    $product = App\Models\Product::first();

    $return = PurchaseReturn::create([
        'number' => 'RET-' . date('Ymd') . '-001',
        'purchase_order_id' => $po->id,
        'supplier_id' => $supplierId,
        'warehouse_id' => $warehouse ? $warehouse->id : 1,
        'return_date' => now()->subDays(2),
        'reason' => 'Damaged goods upon arrival',
        'status' => 'pending',
        'total_amount' => 500000,
        'created_by' => 1
    ]);

    if ($product) {
        \App\Models\PurchaseReturnItem::create([
            'purchase_return_id' => $return->id,
            'product_id' => $product->id,
            'qty' => 10,
            'unit_price' => 50000,
            'total_price' => 500000
        ]);
    }
    echo "Dummy Return Created.\n";
}

// Reset password to known value if needed, but risky. Let's assume 'password'.
// Actually, let's just confirm we have data.
