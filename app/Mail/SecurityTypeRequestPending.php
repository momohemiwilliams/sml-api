<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingSecurityType;

class SecurityTypeRequestPending extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $dashboardUrl;
    public $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingSecurityType $pending, $recipientName = 'Authoriser')
    {
        $this->pending = $pending;
        $this->recipientName = $recipientName;
        $this->dashboardUrl = config('app.frontend_url', config('app.url')) . '/admin/security-types/pending'; 
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type = ucfirst($this->pending->request_type);
        return new Envelope(
            subject: "Action Required: New Security Type Request ({$type}) - " . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security-types.pending',
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
