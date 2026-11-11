<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingMarketCategory;

class MarketCategoryRequestPending extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $dashboardUrl;
    public $requester;
    public $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingMarketCategory $pending, $requester = null, $recipientName = 'Authoriser')
    {
        $this->pending = $pending;
        $this->requester = $requester;
        $this->recipientName = $recipientName;
        
        // URL to the pending list page in Admin Dashboard
        $this->dashboardUrl = config('app.frontend_url', config('app.url')) . '/admin/market-categories/pending'; 
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type = ucfirst($this->pending->request_type);
        return new Envelope(
            subject: "New Market Category Request: {$type} - " . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.market-category-request-pending',
            text: 'emails.market-category-request-pending-text',
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
