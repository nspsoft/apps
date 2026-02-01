<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DeliveryOrder;
use App\Models\SalesInvoiceItem;

$doNumbers = ['DO-2601-0036', 'DO/20260201/0043'];

foreach ($doNumbers as $doNumber) {
    echo "--- Checking $doNumber ---" . PHP_EOL;
    $do = DeliveryOrder::where('do_number', $doNumber)->with('items')->first();
    if (!$do) {
        echo "DO not found." . PHP_EOL;
        continue;
    }

    echo "DO ID: " . $do->id . " Status: " . $do->status . " Invoice Status Attr: " . $do->invoice_status . PHP_EOL;
    foreach ($do->items as $it) {
        echo " - Item ID: " . $it->id . " Product: " . ($it->product->name ?? 'N/A') . " DEL: " . $it->qty_delivered . " INV: " . $it->qty_invoiced . PHP_EOL;
        
        $invoiceItems = SalesInvoiceItem::where('delivery_order_id', $do->id)
            ->where('product_id', $it->product_id)
            ->get();
        
        if ($invoiceItems->count() > 0) {
            echo "   * Found " . $invoiceItems->count() . " linked Invoice Items:" . PHP_EOL;
            foreach ($invoiceItems as $ii) {
                echo "     > Invoice ID: " . $ii->sales_invoice_id . " Qty: " . $ii->qty . " (Inv Item ID: " . $ii->id . ")" . PHP_EOL;
            }
        } else {
            echo "   * NO linked Invoice Items found using delivery_order_id." . PHP_EOL;
            
            // Try searching by SO Item ID
            $invoiceItemsBySO = SalesInvoiceItem::where('sales_order_item_id', $it->sales_order_item_id)
                ->get();
            if ($invoiceItemsBySO->count() > 0) {
                 echo "   * Found " . $invoiceItemsBySO->count() . " Invoice Items linked by SO Item ID:" . PHP_EOL;
                 foreach ($invoiceItemsBySO as $ii) {
                     echo "     > Invoice ID: " . $ii->sales_invoice_id . " Qty: " . $ii->qty . " (Inv Item ID: " . $ii->id . ")" . PHP_EOL;
                 }
            }
        }
    }
}
