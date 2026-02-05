<?php

namespace App\Notifications;

use App\Models\PurchaseInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification
{
    use Queueable;

    protected PurchaseInvoice $invoice;
    protected float $amount;

    public function __construct(PurchaseInvoice $invoice, float $amount)
    {
        $this->invoice = $invoice;
        $this->amount = $amount;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_paid',
            'icon' => 'banknotes',
            'color' => 'green',
            'title' => 'Payment Received',
            'message' => "Payment of Rp " . number_format($this->amount, 0, ',', '.') . " has been received for invoice {$this->invoice->invoice_number}.",
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->amount,
            'action_url' => "/portal/invoices/{$this->invoice->id}",
            'action_text' => 'View Invoice',
        ];
    }
}
