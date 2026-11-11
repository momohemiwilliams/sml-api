<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingSecurityMasterData;

class SecurityMasterRequestRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $reason;
    public $dashboardUrl;
    public $requester;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingSecurityMasterData $pending, $reason, $requester = null)
    {
        $this->pending = $pending;
        $this->reason = $reason;
        $this->requester = $requester;
        
        $this->dashboardUrl = config('app.frontend_url', config('app.url')) . '/admin/security-master'; 
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Security Master Request Rejected - " . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.security-master-request-rejected',
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
