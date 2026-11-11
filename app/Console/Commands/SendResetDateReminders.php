<?php

namespace App\Console\Commands;

use App\Services\SecurityScheduleJobService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendResetDateReminders extends Command
{
    protected $signature = 'securities:send-reset-reminders {--date= : Send as of this date (Y-m-d), defaults to today}';

    protected $description = 'Email a reminder for securities whose coupon reset date has been reached';

    public function handle(SecurityScheduleJobService $service): int
    {
        $asOf = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        $sent = $service->sendResetReminders($asOf);

        $this->info("{$sent} reset date reminder(s) sent.");

        return self::SUCCESS;
    }
}
