<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingMarketCategory;

class MarketCategoryRequestApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $requester;
    public $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingMarketCategory $pending, $requester = null, $recipientName = 'Inputter')
    {
        $this->pending = $pending;
        $this->requester = $requester;
        $this->recipientName = $recipientName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Market Category Request Approved',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.market-categories.approved',
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
