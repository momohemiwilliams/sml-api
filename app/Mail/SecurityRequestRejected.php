<?php

namespace App\Mail;

use App\Models\PendingSecurity;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SecurityRequestRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $pending;
    public $reason;
    public $recipientName;

    /**
     * Create a new message instance.
     */
    public function __construct(PendingSecurity $pending, string $reason, $recipientName = 'Inputter')
    {
        $this->pending = $pending;
        $this->reason = $reason;
        $this->recipientName = $recipientName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Security Request Rejected')
                    ->view('emails.securities.rejected');
    }
}
