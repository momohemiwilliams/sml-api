<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\PendingAuctionResult;

class AuctionResultRequestApproved extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $approver;
    public $approverName;

    public function __construct(PendingAuctionResult $pending, $approver = null)
    {
        $this->pending = $pending;
        $this->approver = $approver;
        $this->approverName = $approver ? trim(($approver->firstname ?? '') . ' ' . ($approver->lastname ?? $approver->last_name ?? '')) : 'Authoriser';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Auction Result Request Approved - " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auction-result-request-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
