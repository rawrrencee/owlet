<?php

namespace App\Mail;

use App\Models\Stocktake;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StocktakeCompletedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Stocktake $stocktake
    ) {}

    public function envelope(): Envelope
    {
        $storeName = $this->stocktake->store?->store_name ?? 'Unknown Store';
        $employeeName = $this->stocktake->employee?->full_name ?? 'Unknown Employee';

        return new Envelope(
            subject: "Stocktake at {$storeName} - {$employeeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.stocktake-completed',
            with: [
                'stocktake' => $this->stocktake,
                'employee' => $this->stocktake->employee,
                'store' => $this->stocktake->store,
                'items' => $this->stocktake->items,
            ],
        );
    }
}
