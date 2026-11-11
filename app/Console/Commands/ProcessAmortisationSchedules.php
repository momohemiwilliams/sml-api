<?php

namespace App\Console\Commands;

use App\Services\SecurityScheduleJobService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessAmortisationSchedules extends Command
{
    protected $signature = 'securities:process-amortisation {--date= : Process as of this date (Y-m-d), defaults to today}';

    protected $description = 'Recompute the outstanding value of amortising securities on their amortisation dates';

    public function handle(SecurityScheduleJobService $service): int
    {
        $asOf = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        $updated = $service->processAmortisation($asOf);

        foreach ($updated as $securityId => $outstanding) {
            $this->line("Security {$securityId}: outstanding value set to {$outstanding}");
        }

        $this->info(count($updated) . ' security outstanding value(s) updated.');

        return self::SUCCESS;
    }
}
