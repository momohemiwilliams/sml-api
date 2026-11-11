<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingProductType;

class ProductTypeRequestRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $reason;
    public $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingProductType $pending, string $reason, $recipientName = 'Inputter')
    {
        $this->pending = $pending;
        $this->reason = $reason;
        $this->recipientName = $recipientName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Product Type Request Rejected',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.product-types.rejected',
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
