<?php

namespace App\Mail;

use App\Models\DeliveryOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryOrderNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public DeliveryOrder $order,
        public string $action
    ) {}

    public function envelope(): Envelope
    {
        $storeFrom = $this->order->storeFrom?->store_name ?? 'Unknown';
        $storeTo = $this->order->storeTo?->store_name ?? 'Unknown';

        return new Envelope(
            subject: "Delivery Order {$this->action} - {$this->order->order_number} ({$storeFrom} → {$storeTo})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.delivery-order-notification',
            with: [
                'order' => $this->order,
                'action' => $this->action,
                'storeFrom' => $this->order->storeFrom,
                'storeTo' => $this->order->storeTo,
                'items' => $this->order->items,
            ],
        );
    }
}
