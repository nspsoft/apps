<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

try {
  $inv = \App\Models\SalesInvoice::where('invoice_number', 'INV-202602-0016')->with('items')->first();
  if (!$inv) { echo "Invoice not found\n"; exit; }
  echo "Deleting Invoice {$inv->id} status {$inv->status}\n";
  
  \DB::transaction(function () use ($inv) {
      foreach ($inv->items as $item) {
          $soItem = $item->salesOrderItem;
          if ($soItem) {
               echo "Reverting SO Item {$soItem->id} (Current Invoiced: {$soItem->qty_invoiced})\n";
               $soItem->qty_invoiced -= $item->qty;
               $soItem->save();
               echo "Reverted SO Item {$soItem->id} (New Invoiced: {$soItem->qty_invoiced})\n";
          }
          
          if ($item->delivery_order_id) {
               echo "Checking DO Item {$item->delivery_order_id}\n";
               $doItem = \App\Models\DeliveryOrderItem::find($item->delivery_order_id);
               if ($doItem) {
                   echo "Reverting DO Item {$doItem->id} (Current Invoiced: {$doItem->qty_invoiced})\n";
                   $doItem->qty_invoiced -= $item->qty;
                   $doItem->save();
                   echo "Reverted DO Item {$doItem->id} (New Invoiced: {$doItem->qty_invoiced})\n";
               } else {
                   echo "DO Item {$item->delivery_order_id} NOT FOUND\n";
               }
          }
      }
      $inv->delete();
      echo "Invoice deleted.\n";
  });
  echo "Deleted successfully.\n";
} catch (\Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
  echo $e->getTraceAsString();
}
