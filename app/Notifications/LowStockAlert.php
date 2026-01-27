<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public $product;
    public $stock;
    public $minStock;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product, $stock, $minStock)
    {
        $this->product = $product;
        $this->stock = $stock;
        $this->minStock = $minStock;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'title' => 'Low Stock Alert',
            'message' => "Product {$this->product->name} ({$this->product->sku}) is below minimum stock.",
            'product_id' => $this->product->id,
            'current_stock' => $this->stock,
            'min_stock' => $this->minStock,
            'link' => route('products.show', $this->product),
        ];
    }
}
