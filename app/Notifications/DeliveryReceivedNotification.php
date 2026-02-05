<?php

namespace App\Notifications;

use App\Models\GoodsReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DeliveryReceivedNotification extends Notification
{
    use Queueable;

    protected GoodsReceipt $delivery;

    public function __construct(GoodsReceipt $delivery)
    {
        $this->delivery = $delivery;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'delivery_received',
            'icon' => 'truck',
            'color' => 'emerald',
            'title' => 'Delivery Received',
            'message' => "Your delivery {$this->delivery->delivery_note_number} has been received by the warehouse.",
            'delivery_id' => $this->delivery->id,
            'grn_number' => $this->delivery->grn_number,
            'delivery_note_number' => $this->delivery->delivery_note_number,
            'action_url' => "/portal/deliveries/{$this->delivery->id}",
            'action_text' => 'View Details',
        ];
    }
}
