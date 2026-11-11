<?php

namespace App\Console\Commands;

use App\Services\SecurityScheduleJobService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCallDateReminders extends Command
{
    protected $signature = 'securities:send-call-reminders {--date= : Send as of this date (Y-m-d), defaults to today}';

    protected $description = 'Email a reminder for securities whose call date has been reached';

    public function handle(SecurityScheduleJobService $service): int
    {
        $asOf = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        $sent = $service->sendCallReminders($asOf);

        $this->info("{$sent} call date reminder(s) sent.");

        return self::SUCCESS;
    }
}
