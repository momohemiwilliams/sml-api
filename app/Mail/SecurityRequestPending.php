<?php

namespace App\Mail;

use App\Models\PendingSecurity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SecurityRequestPending extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingSecurity $pending, $recipientName = 'Authoriser')
    {
        $this->pending = $pending;
        $this->recipientName = $recipientName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Security Request Pending Approval')
                    ->view('emails.securities.pending');
    }
}
