<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public PurchaseOrder $order,
        public string $action
    ) {}

    public function envelope(): Envelope
    {
        $supplierName = $this->order->supplier?->name ?? 'Unknown Supplier';

        return new Envelope(
            subject: "Purchase Order {$this->action} - {$this->order->order_number} ({$supplierName})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.purchase-order-notification',
            with: [
                'order' => $this->order,
                'action' => $this->action,
                'supplier' => $this->order->supplier,
                'store' => $this->order->store,
                'items' => $this->order->items,
            ],
        );
    }
}
