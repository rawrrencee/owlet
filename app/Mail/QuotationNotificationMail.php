<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public string $action
    ) {}

    public function envelope(): Envelope
    {
        $customerName = $this->quotation->customer?->full_name ?? 'No Customer';

        return new Envelope(
            subject: "Quotation {$this->action} - {$this->quotation->quotation_number} ({$customerName})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quotation-notification',
            with: [
                'quotation' => $this->quotation,
                'action' => $this->action,
                'customer' => $this->quotation->customer,
                'company' => $this->quotation->company,
                'items' => $this->quotation->items,
            ],
        );
    }
}
