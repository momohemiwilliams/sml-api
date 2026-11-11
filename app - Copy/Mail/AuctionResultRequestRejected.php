<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingAuctionResult;

class AuctionResultRequestRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $rejector;
    public $rejectorName;

    public function __construct(PendingAuctionResult $pending, $rejector = null)
    {
        $this->pending = $pending;
        $this->rejector = $rejector;
        $this->rejectorName = $rejector ? trim(($rejector->firstname ?? '') . ' ' . ($rejector->lastname ?? $rejector->last_name ?? '')) : 'Authoriser';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Auction Result Request Rejected - " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auction-result-request-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
