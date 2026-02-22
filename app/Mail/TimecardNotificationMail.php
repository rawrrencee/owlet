<?php

namespace App\Mail;

use App\Models\Timecard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TimecardNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Timecard $timecard,
        public string $action
    ) {}

    public function envelope(): Envelope
    {
        $employeeName = $this->timecard->employee?->full_name ?? 'Unknown Employee';
        $storeName = $this->timecard->store?->store_name ?? 'Unknown Store';

        return new Envelope(
            subject: "Timecard {$this->action} - {$employeeName} at {$storeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.timecard-notification',
            with: [
                'timecard' => $this->timecard,
                'action' => $this->action,
                'employee' => $this->timecard->employee,
                'store' => $this->timecard->store,
                'details' => $this->timecard->details,
            ],
        );
    }
}
