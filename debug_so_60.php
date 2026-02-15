<?php
$number = 'SO/26/02/060';
$so = App\Models\SalesOrder::where('so_number', $number)->first();

if ($so) {
    echo "SO: {$so->so_number} (ID: {$so->id})\n";
    foreach($so->items as $item) {
        echo "Item: {$item->product->name}\n";
        echo "  Qty: {$item->qty}\n";
        echo "  Delivered (DB): {$item->qty_delivered}\n";
        echo "  Reserved (Calc): {$item->reserved_qty}\n";
    }
    echo "Delivery Orders:\n";
    foreach($so->deliveryOrders as $do) {
        echo "- DO: {$do->do_number} | Status: {$do->status} | ID: {$do->id}\n";
        foreach($do->items as $item) {
            echo "   Item: {$item->product->name} | Qty: {$item->qty_delivered}\n";
        }
    }
} else {
    echo "SO Not Found\n";
}
