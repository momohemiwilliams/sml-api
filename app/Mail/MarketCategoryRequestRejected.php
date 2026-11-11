<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingMarketCategory;

class MarketCategoryRequestRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $reason;
    public $requester;
    public $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingMarketCategory $pending, string $reason, $requester = null, $recipientName = 'Inputter')
    {
        $this->pending = $pending;
        $this->reason = $reason;
        $this->requester = $requester;
        $this->recipientName = $recipientName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Market Category Request Rejected',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.market-categories.rejected',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
