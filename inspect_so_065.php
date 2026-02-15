<?php

$soNumber = 'SO/26/02/065';
$so = \App\Models\SalesOrder::where('so_number', $soNumber)->with('items.deliveryOrderItems.deliveryOrder')->first();

if (!$so) {
    echo "SO $soNumber not found.\n";
    exit;
}

echo "Inspecting $soNumber (ID: {$so->id})\n";

foreach ($so->items as $item) {
    echo "\nItem: {$item->product->name} (ID: {$item->id})\n";
    echo "  Ordered: {$item->qty}\n";
    echo "  SO Delivered: {$item->qty_delivered}\n";
    
    $calculatedDelivered = 0;
    
    foreach ($item->deliveryOrderItems as $doi) {
        $do = $doi->deliveryOrder;
        $status = $do->status;
        $isDeducted = in_array($status, ['shipped', 'delivered', 'completed']);
        
        echo "    - DO: {$do->do_number} (Status: {$status}) | Qty: {$doi->qty_delivered}";
        if ($isDeducted) {
            echo " [COUNTS]\n";
            $calculatedDelivered += $doi->qty_delivered;
        } else {
            echo " [IGNORED]\n";
        }
    }
    
    echo "  Calculated Total: $calculatedDelivered\n";
    if (abs($calculatedDelivered - $item->qty_delivered) > 0.0001) {
        echo "  [MISMATCH DETECTED] Diff: " . ($item->qty_delivered - $calculatedDelivered) . "\n";
    }
}
