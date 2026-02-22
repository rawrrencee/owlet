<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionRefundMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $refundSummary
    ) {}

    public function envelope(): Envelope
    {
        $storeName = $this->transaction->store?->store_name ?? 'Unknown Store';
        $txnNumber = $this->transaction->transaction_number;

        return new Envelope(
            subject: "Refund Processed - {$txnNumber} at {$storeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.transaction-refund',
            with: [
                'transaction' => $this->transaction,
                'store' => $this->transaction->store,
                'employee' => $this->transaction->employee,
                'customer' => $this->transaction->customer,
                'currency' => $this->transaction->currency,
                'items' => $this->transaction->items,
                'payments' => $this->transaction->payments,
                'refundSummary' => $this->refundSummary,
            ],
        );
    }
}
