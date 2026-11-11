<?php

namespace App\Services;

use App\Mail\SecurityScheduleReminder;
use App\Models\AmortisationSchedule;
use App\Models\CallSchedule;
use App\Models\ResetSchedule;
use App\Models\SecurityManagement;
use App\Models\SecurityMasterData;
use App\Models\SecurityMasterFieldValue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Daily jobs driven by the amortisation, call and reset schedules.
 * Each schedule row is stamped when handled, so runs are idempotent and
 * a missed day is caught up on the next run.
 */
class SecurityScheduleJobService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Apply the outstanding value of every amortisation date that has been reached.
     *
     * @return array [security_id => new outstanding value]
     */
    public function processAmortisation(?Carbon $asOf = null): array
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $updated = [];

        $dueBySecurity = AmortisationSchedule::whereNull('processed_at')
            ->whereDate('date', '<=', $asOf->format('Y-m-d'))
            ->whereHas('securityMasterData', fn($q) => $q->where('lifecycle_status', 'active'))
            ->orderBy('date')
            ->get()
            ->groupBy('security_master_id');

        foreach ($dueBySecurity as $securityId => $rows) {
            $security = SecurityMasterData::find($securityId);

            DB::transaction(function () use ($security, $rows, &$updated) {
                $field = $this->findField($security, config('security_events.outstanding_field_name'));
                $fieldValue = $field
                    ? SecurityMasterFieldValue::where('security_master_id', $security->id)->where('field_id', $field->id)->first()
                    : null;

                $outstanding = is_numeric($fieldValue?->field_value) ? (float)$fieldValue->field_value : null;

                foreach ($rows as $row) {
                    if ($row->outstanding_amount !== null) {
                        $outstanding = (float)$row->outstanding_amount;
                    } elseif ($outstanding !== null) {
                        // No outstanding figure on the schedule: derive it from the principal repaid
                        $outstanding = max(0, $outstanding - (float)$row->scheduled_principal_cashflow);
                    }

                    $row->update(['processed_at' => now()]);
                }

                if (!$field) {
                    Log::warning("Amortisation: security {$security->id} has no Outstanding Value field; schedule rows marked processed without update.");
                    return;
                }

                if ($outstanding !== null) {
                    SecurityMasterFieldValue::updateOrCreate(
                        ['security_master_id' => $security->id, 'field_id' => $field->id],
                        ['field_value' => (string)$outstanding]
                    );
                    $updated[$security->id] = $outstanding;

                    Log::info("Amortisation: security {$security->id} outstanding value set to {$outstanding}.");
                }
            });
        }

        return $updated;
    }

    /**
     * Email a reminder for every scheduled call date that has been reached.
     *
     * @return int number of reminders sent
     */
    public function sendCallReminders(?Carbon $asOf = null): int
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        $due = CallSchedule::where('status', 'scheduled')
            ->whereNull('reminded_at')
            ->whereDate('call_date', '<=', $asOf->format('Y-m-d'))
            ->whereHas('securityMasterData', fn($q) => $q->where('lifecycle_status', 'active'))
            ->orderBy('call_date')
            ->get();

        return $this->sendReminders($due, 'call');
    }

    /**
     * Email a reminder for every scheduled coupon reset date that has been reached.
     *
     * @return int number of reminders sent
     */
    public function sendResetReminders(?Carbon $asOf = null): int
    {
        $asOf = ($asOf ?? now())->copy()->startOfDay();

        $due = ResetSchedule::where('status', 'scheduled')
            ->whereNull('reminded_at')
            ->whereDate('reset_date', '<=', $asOf->format('Y-m-d'))
            ->whereHas('securityMasterData', fn($q) => $q->where('lifecycle_status', 'active'))
            ->orderBy('reset_date')
            ->get();

        return $this->sendReminders($due, 'reset');
    }

    protected function sendReminders($rows, string $type): int
    {
        $sent = 0;

        foreach ($rows as $row) {
            $security = $row->securityMasterData;
            $recipients = $this->reminderRecipients($security);

            if (empty($recipients)) {
                Log::warning("{$type} reminder: no recipients for security {$security->id}; set SECURITY_EVENT_REMINDER_EMAILS.");
                continue; // Not stamped, so it is retried on the next run
            }

            try {
                Mail::to($recipients)->send(new SecurityScheduleReminder($security, $row, $type));
                $row->update(['reminded_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                Log::error("{$type} reminder failed for security {$security->id}: " . $e->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Configured distribution list plus the security's creator
     */
    protected function reminderRecipients(SecurityMasterData $security): array
    {
        $recipients = config('security_events.reminder_recipients', []);

        if ($security->created_by) {
            $creator = $this->externalUserService->getUserById((int)$security->created_by);
            if (!empty($creator['email'])) {
                $recipients[] = $creator['email'];
            }
        }

        return array_values(array_unique(array_map('strtolower', $recipients)));
    }

    /**
     * Resolve a field definition (by name prefix) for the security, preferring product-specific fields
     */
    protected function findField(SecurityMasterData $security, string $namePrefix): ?SecurityManagement
    {
        return SecurityManagement::where('field_name', 'like', $namePrefix . '%')
            ->forProductOrCategory($security->category_id, $security->product_id)
            ->first();
    }
}
