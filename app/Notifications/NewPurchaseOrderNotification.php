<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewPurchaseOrderNotification extends Notification
{
    use Queueable;

    protected PurchaseOrder $purchaseOrder;

    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_po',
            'icon' => 'shopping-cart',
            'color' => 'indigo',
            'title' => 'New Purchase Order',
            'message' => "Purchase Order {$this->purchaseOrder->po_number} has been created for you.",
            'po_id' => $this->purchaseOrder->id,
            'po_number' => $this->purchaseOrder->po_number,
            'total' => $this->purchaseOrder->total,
            'action_url' => "/portal/purchase-orders/{$this->purchaseOrder->id}",
            'action_text' => 'View Order',
        ];
    }
}
