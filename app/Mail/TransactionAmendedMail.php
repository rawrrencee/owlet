<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionAmendedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction,
        public string $changeSummary,
        public ?array $changeDetails = null
    ) {}

    public function envelope(): Envelope
    {
        $storeName = $this->transaction->store?->store_name ?? 'Unknown Store';
        $txnNumber = $this->transaction->transaction_number;

        return new Envelope(
            subject: "Transaction Amended - {$txnNumber} at {$storeName}",
        );
    }

    public function content(): Content
    {
        // Build version history: first 3 + ellipsis + last 3 (or all if <= 6)
        $totalVersions = $this->transaction->versions()->count();
        $showVersionEllipsis = false;

        if ($totalVersions <= 6) {
            $versionHistory = $this->transaction->versions()->with('changedByUser')->orderBy('version_number')->get();
        } else {
            $firstThree = $this->transaction->versions()->with('changedByUser')->orderBy('version_number')->limit(3)->get();
            $lastThree = $this->transaction->versions()->with('changedByUser')->reorder()->orderByDesc('version_number')->limit(3)->get();
            $versionHistory = $firstThree->concat($lastThree->sortBy('version_number')->values());
            $showVersionEllipsis = true;
        }

        return new Content(
            markdown: 'emails.transaction-amended',
            with: [
                'transaction' => $this->transaction,
                'store' => $this->transaction->store,
                'employee' => $this->transaction->employee,
                'customer' => $this->transaction->customer,
                'currency' => $this->transaction->currency,
                'items' => $this->transaction->items,
                'payments' => $this->transaction->payments,
                'changeSummary' => $this->changeSummary,
                'changeDetails' => $this->changeDetails,
                'versionHistory' => $versionHistory,
                'showVersionEllipsis' => $showVersionEllipsis,
            ],
        );
    }
}
