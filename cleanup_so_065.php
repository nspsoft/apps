<?php

$soNumber = 'SO/26/02/065';
echo "Cleaning up $soNumber...\n";

$so = \App\Models\SalesOrder::where('so_number', $soNumber)->with('items')->first();

if (!$so) {
    echo "SO not found.\n";
    exit;
}

$deductedStatuses = ['shipped', 'delivered', 'completed'];

foreach ($so->items as $item) {
    echo "Item: {$item->product->name} (Current Delivered: {$item->qty_delivered})\n";
    
    // Sum qty_delivered from all DO items linked to this SO item where DO status is deducted
    $actualDelivered = \App\Models\DeliveryOrderItem::where('sales_order_item_id', $item->id)
        ->whereHas('deliveryOrder', function ($query) use ($deductedStatuses) {
            $query->whereIn('status', $deductedStatuses);
        })
        ->sum('qty_delivered');
    
    echo "  Actual Delivered (from DOs): $actualDelivered\n";
    
    if (abs((float)$item->qty_delivered - (float)$actualDelivered) > 0.0001) {
        echo "  Updating to $actualDelivered...\n";
        $item->qty_delivered = $actualDelivered;
        $item->save();
    } else {
        echo "  Already correct.\n";
    }
}

echo "Done.\n";
