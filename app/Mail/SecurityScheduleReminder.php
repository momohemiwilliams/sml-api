<?php

namespace App\Mail;

use App\Models\SecurityMasterData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecurityScheduleReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $security;
    public $schedule;
    public $type; // 'call' or 'reset'
    public $dashboardUrl;

    /**
     * @param \App\Models\CallSchedule|\App\Models\ResetSchedule $schedule
     */
    public function __construct(SecurityMasterData $security, $schedule, string $type)
    {
        $this->security = $security;
        $this->schedule = $schedule;
        $this->type = $type;
        $this->dashboardUrl = config('app.frontend_url', config('app.url')) . '/admin/security-master';
    }

    public function envelope(): Envelope
    {
        $label = $this->type === 'call' ? 'Call Date' : 'Coupon Reset Date';

        return new Envelope(
            subject: "Reminder: {$label} for {$this->security->security_name} - " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.security-schedule-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
