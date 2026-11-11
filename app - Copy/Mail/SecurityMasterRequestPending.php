<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingSecurityMasterData;

class SecurityMasterRequestPending extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $dashboardUrl;
    public $requester;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingSecurityMasterData $pending, $requester = null)
    {
        $this->pending = $pending;
        $this->requester = $requester;
        
        // URL to the pending list page in Admin Dashboard
        $this->dashboardUrl = config('app.frontend_url', config('app.url')) . '/admin/security-master/pending'; 
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type = ucfirst($this->pending->request_type);
        return new Envelope(
            subject: "New Security Master Request: {$type} - " . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security-master-request-pending',
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
