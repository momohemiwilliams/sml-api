<?php

namespace App\Services;

use App\Models\SecurityMasterData;
use App\Models\PendingSecurityMasterData;
use App\Models\SecurityMasterFieldValue;
use App\Models\SecurityManagement;
use App\Models\MarketCategory;
use App\Models\AmortisationSchedule;
use App\Models\CallSchedule;
use App\Models\ResetSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Utils\Constants;

class SecurityMasterDataService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get paginated list of pending requests.
     */
   public function getPendingRequests()
{
    $pending = PendingSecurityMasterData::with(['requester', 'category', 'productType', 'mainRecord'])
        ->latest()
        ->get(); // removed paginate

    return $this->externalUserService->enrichWithUsers($pending, [
        'requested_by' => 'Inputter',
        'selected_authoriser_id' => 'Authoriser'
    ]);
}
    /**
     * Create a new security master creation request
     */
    public function createRequest(array $data)
    {
        // Validate category exists
        $category = MarketCategory::find($data['category_id']);
        if (!$category) {
            throw new \Exception('Market category not found');
        }

        // Validate fields
        $this->validateFields($data['category_id'], $data['product_id'] ?? null, $data['fields'] ?? []);

        return DB::transaction(function () use ($data) {
            $pending = PendingSecurityMasterData::create([
                'category_id' => $data['category_id'],
                'product_id' => $data['product_id'],
                'security_name' => $data['security_name'] ?? null,
                'status' => $data['status'] ?? 1,
                'fields_data' => $data['fields'] ?? [],
                'amortisation_schedule' => $data['amortisation_schedule_data'] ?? null,
                'call_schedule' => $data['call_schedule_data'] ?? null,
                'reset_schedule' => $data['reset_schedule_data'] ?? null,
                'request_type' => 'create',
                'requested_by' => $data['created_by'], // Assuming created_by passes user ID
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create update request
     */
    public function updateRequest(SecurityMasterData $security, array $data)
    {
        // Validate fields if provided
        if (isset($data['fields'])) {
            $this->validateFields($security->category_id, $data['product_id'] ?? $security->product_id, $data['fields']);
        }

        return DB::transaction(function () use ($security, $data) {
            // Set main record to pending_approval (optional, depending on requirement)
            // But usually we just lock it from other updates? 
            // For now, let's just mark it if we want, or just rely on pending request existence.
            $security->update(['approval_status' => 'pending']);

            $pending = PendingSecurityMasterData::create([
                'security_master_id' => $security->id,
                'category_id' => $security->category_id,
                'product_id' => $data['product_id'] ?? $security->product_id,
                'security_name' => $data['security_name'] ?? $security->security_name,
                'status' => $data['status'] ?? $security->status,
                'fields_data' => $data['fields'] ?? null, // Only store if changing? Or store all?
                // If fields are passed, store them. 
                'amortisation_schedule' => $data['amortisation_schedule_data'] ?? null,
                'call_schedule' => $data['call_schedule_data'] ?? null,
                'reset_schedule' => $data['reset_schedule_data'] ?? null,
                'request_type' => 'update',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create delete request
     */
    public function deleteRequest(SecurityMasterData $security, array $data)
    {
        return DB::transaction(function () use ($security, $data) {
            $security->update(['approval_status' => 'pending']);

            $pending = PendingSecurityMasterData::create([
                'security_master_id' => $security->id,
                'category_id' => $security->category_id,
                'security_name' => $security->security_name,
                'status' => $security->status,
                'request_type' => 'delete',
                'requested_by' => $data['updated_by'], // Assuming passed in same field or we need a param
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create a call exercise request (issuer calls the security on a scheduled call date)
     */
    public function callRequest(SecurityMasterData $security, CallSchedule $call, array $data)
    {
        $this->assertSecurityAcceptsEvent($security);

        if ($call->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'call_schedule' => ["This call date has already been marked as {$call->status}."]
            ]);
        }

        // A call amount means a partial redemption; none means the whole bond is called
        $amount = $data['call_amount'] ?? $call->call_amount;
        $impact = $this->callImpact($security, $amount !== null ? (float)$amount : null, 'call_amount');

        return DB::transaction(function () use ($security, $call, $data, $amount, $impact) {
            $security->update(['approval_status' => 'pending']);

            $pending = PendingSecurityMasterData::create([
                'security_master_id' => $security->id,
                'category_id' => $security->category_id,
                'product_id' => $security->product_id,
                'security_name' => $security->security_name,
                'status' => $security->status,
                'event_data' => [
                    'call_schedule_id' => $call->id,
                    'scheduled_call_date' => $call->call_date?->format('Y-m-d'),
                    'exercise_date' => $data['exercise_date'] ?? $call->call_date?->format('Y-m-d'),
                    'call_price' => $data['call_price'] ?? $call->call_price,
                    'call_amount' => $amount !== null ? (float)$amount : null,
                    'previous_outstanding' => $impact['previous_outstanding'],
                    'new_outstanding' => $impact['new_outstanding'],
                    'full_redemption' => $impact['full_redemption'],
                ],
                'request_type' => 'call',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create a coupon reset request (apply the actual rate on a scheduled reset date)
     */
    public function resetRequest(SecurityMasterData $security, ResetSchedule $reset, array $data)
    {
        $this->assertSecurityAcceptsEvent($security);

        if ($reset->status !== 'scheduled') {
            throw ValidationException::withMessages([
                'reset_schedule' => ["This reset date has already been marked as {$reset->status}."]
            ]);
        }

        $hasEarlierPendingReset = ResetSchedule::where('security_master_id', $security->id)
            ->where('status', 'scheduled')
            ->whereDate('reset_date', '<', $reset->reset_date->format('Y-m-d'))
            ->exists();

        if ($hasEarlierPendingReset) {
            throw ValidationException::withMessages([
                'reset_schedule' => ['An earlier reset date has not been applied yet. Resets must be applied in date order.']
            ]);
        }

        return DB::transaction(function () use ($security, $reset, $data) {
            $security->update(['approval_status' => 'pending']);

            $pending = PendingSecurityMasterData::create([
                'security_master_id' => $security->id,
                'category_id' => $security->category_id,
                'product_id' => $security->product_id,
                'security_name' => $security->security_name,
                'status' => $security->status,
                'event_data' => [
                    'reset_schedule_id' => $reset->id,
                    'scheduled_reset_date' => $reset->reset_date?->format('Y-m-d'),
                    'applied_date' => $data['applied_date'] ?? $reset->reset_date?->format('Y-m-d'),
                    'new_coupon_rate' => (float)$data['new_coupon_rate'],
                    'benchmark' => $data['benchmark'] ?? $reset->benchmark,
                    'spread' => $data['spread'] ?? $reset->spread,
                    'previous_coupon_rate' => $this->getCouponFieldValue($security)?->field_value,
                ],
                'request_type' => 'reset',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Approve a pending request.
     */
    public function approveRequest(PendingSecurityMasterData $pending)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $security = SecurityMasterData::create([
                        'category_id' => $pending->category_id,
                        'product_id' => $pending->product_id,
                        'security_name' => $pending->security_name,
                        'status' => $pending->status,
                        'approval_status' => 'approved',
                        'created_by' => $pending->requested_by,
                    ]);

                    // Create field values
                    if ($pending->fields_data) {
                        foreach ($pending->fields_data as $fieldData) {
                            SecurityMasterFieldValue::create([
                                'security_master_id' => $security->id,
                                'field_id' => $fieldData['field_id'],
                                'field_value' => $fieldData['field_value'],
                            ]);
                        }
                    }

                    // Create amortisation schedule
                    if ($pending->amortisation_schedule) {
                        foreach ($pending->amortisation_schedule as $scheduleData) {
                            AmortisationSchedule::create([
                                'security_master_id' => $security->id,
                                'date' => $scheduleData['date'],
                                'scheduled_coupon_cashflow' => $scheduleData['scheduled_coupon_cashflow'],
                                'scheduled_principal_cashflow' => $scheduleData['scheduled_principal_cashflow'],
                                'outstanding_amount' => $scheduleData['outstanding_amount'],
                            ]);
                        }
                    }

                    // Create call schedule
                    if ($pending->call_schedule) {
                        foreach ($pending->call_schedule as $callData) {
                            CallSchedule::create([
                                'security_master_id' => $security->id,
                                'call_date' => $callData['call_date'],
                                'call_price' => $callData['call_price'] ?? null,
                                'call_amount' => $callData['call_amount'] ?? null,
                            ]);
                        }
                    }

                    // Create coupon reset schedule
                    if ($pending->reset_schedule) {
                        $this->createResetSchedules($security->id, $pending->reset_schedule);
                    }

                    $result = $security;
                    break;

                case 'update':
                    $security = $pending->mainRecord;
                    if ($security) {
                        $security->update([
                            'security_name' => $pending->security_name,
                            'product_id' => $pending->product_id ?? $security->product_id,
                            'status' => $pending->status,
                            'approval_status' => 'approved',
                            'updated_by' => $pending->requested_by,
                        ]);

                        // Update fields
                        if ($pending->fields_data) {
                            foreach ($pending->fields_data as $fieldData) {
                                SecurityMasterFieldValue::updateOrCreate(
                                    [
                                        'security_master_id' => $security->id,
                                        'field_id' => $fieldData['field_id'],
                                    ],
                                    [
                                        'field_value' => $fieldData['field_value'],
                                    ]
                                );
                            }
                        }

                        // Update amortisation schedule if provided
                        if ($pending->amortisation_schedule) {
                            // Delete old schedules
                            AmortisationSchedule::where('security_master_id', $security->id)->delete();
                            
                            // Insert new ones
                            foreach ($pending->amortisation_schedule as $scheduleData) {
                                AmortisationSchedule::create([
                                    'security_master_id' => $security->id,
                                    'date' => $scheduleData['date'],
                                    'scheduled_coupon_cashflow' => $scheduleData['scheduled_coupon_cashflow'],
                                    'scheduled_principal_cashflow' => $scheduleData['scheduled_principal_cashflow'],
                                    'outstanding_amount' => $scheduleData['outstanding_amount'],
                                ]);
                            }
                        }

                        // Update call schedule if provided
                        if ($pending->call_schedule) {
                            CallSchedule::where('security_master_id', $security->id)->delete();

                            foreach ($pending->call_schedule as $callData) {
                                CallSchedule::create([
                                    'security_master_id' => $security->id,
                                    'call_date' => $callData['call_date'],
                                    'call_price' => $callData['call_price'] ?? null,
                                    'call_amount' => $callData['call_amount'] ?? null,
                                ]);
                            }
                        }

                        // Update coupon reset schedule if provided
                        if ($pending->reset_schedule) {
                            ResetSchedule::where('security_master_id', $security->id)->delete();
                            $this->createResetSchedules($security->id, $pending->reset_schedule);
                        }

                        $result = $security;
                    }
                    break;

                case 'call':
                    $security = $pending->mainRecord;
                    $event = $pending->event_data ?? [];
                    $call = $security ? CallSchedule::where('security_master_id', $security->id)->find($event['call_schedule_id'] ?? null) : null;

                    if (!$security || !$call || $call->status !== 'scheduled') {
                        throw new \Exception('The call date for this request is no longer available.');
                    }

                    // Re-evaluate against the outstanding value at approval time (it may have
                    // changed since the request, e.g. through the amortisation job)
                    $amount = $event['call_amount'] ?? null;
                    try {
                        $impact = $this->callImpact($security, $amount !== null ? (float)$amount : null, 'call_amount');
                    } catch (ValidationException $e) {
                        throw new \Exception(collect($e->errors())->flatten()->first());
                    }
                    $pending->event_data = array_merge($event, $impact);

                    $call->update([
                        'status' => 'exercised',
                        'exercised_date' => $event['exercise_date'],
                        'exercised_price' => $event['call_price'],
                        'exercised_amount' => $amount,
                    ]);

                    if ($impact['new_outstanding'] !== null) {
                        $this->setOutstandingFieldValue($security, $impact['new_outstanding']);
                    }

                    if ($impact['full_redemption']) {
                        // The security is redeemed: remaining calls and resets will not happen
                        CallSchedule::where('security_master_id', $security->id)
                            ->where('status', 'scheduled')
                            ->update(['status' => 'not_exercised']);

                        ResetSchedule::where('security_master_id', $security->id)
                            ->where('status', 'scheduled')
                            ->update(['status' => 'cancelled']);

                        $security->update([
                            'lifecycle_status' => 'called',
                            'redemption_date' => $event['exercise_date'],
                            'status' => false,
                            'approval_status' => 'approved',
                            'updated_by' => $pending->requested_by,
                        ]);
                    } else {
                        // Partial redemption: the security stays active for further calls
                        $security->update([
                            'approval_status' => 'approved',
                            'updated_by' => $pending->requested_by,
                        ]);
                    }

                    $result = $security->fresh(['fieldValues.field', 'callSchedules', 'resetSchedules']);
                    break;

                case 'reset':
                    $security = $pending->mainRecord;
                    $event = $pending->event_data ?? [];
                    $reset = $security ? ResetSchedule::where('security_master_id', $security->id)->find($event['reset_schedule_id'] ?? null) : null;

                    if (!$security || !$reset || $reset->status !== 'scheduled') {
                        throw new \Exception('The reset date for this request is no longer available.');
                    }

                    $reset->update([
                        'status' => 'applied',
                        'applied_date' => $event['applied_date'],
                        'applied_rate' => $event['new_coupon_rate'],
                        'applied_benchmark' => $event['benchmark'] ?? null,
                        'applied_spread' => $event['spread'] ?? null,
                    ]);

                    $this->setCouponFieldValue($security, $event['new_coupon_rate']);

                    $security->update([
                        'approval_status' => 'approved',
                        'updated_by' => $pending->requested_by,
                    ]);

                    $result = $security->fresh(['fieldValues.field', 'resetSchedules']);
                    break;

                case 'delete':
                    $security = $pending->mainRecord;
                    if ($security) {
                        $security->delete(); // Assuming soft delete or hard delete? 
                        // SecurityMasterData model doesn't use SoftDeletes in my previous view, 
                        // but MarketCategory did. I'll assume hard delete for now or standard delete.
                        // Wait, migration had 'onDelete cascade', so field values go too.
                        // If I want soft delete, I'd need to add it. For now, strict delete.
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);

            $this->notifyRequester($pending, 'approved');

            return $result;
        });
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingSecurityMasterData $pending, string $reason)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending, $reason) {
            // Revert the main record approval_status
            if (in_array($pending->request_type, ['update', 'delete', 'call', 'reset']) && $pending->mainRecord) {
                $hasOtherPending = \App\Models\PendingSecurityMasterData::where('security_master_id', $pending->mainRecord->id)
                    ->where('id', '!=', $pending->id)
                    ->where('approval_status', 'pending')
                    ->exists();

                if (!$hasOtherPending) {
                    $pending->mainRecord->update(['approval_status' => 'approved']);
                }
            }

            $pending->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            $this->notifyRequester($pending, 'rejected');

            return $pending;
        });
    }

    /**
     * Get all securities with pagination (Existing method preserved/adjusted)
     */
 public function getAllSecurities($categoryId = null, $perPage = null)
{
    $query = SecurityMasterData::with(['category', 'fieldValues.field', 'amortisationSchedules', 'callSchedules', 'resetSchedules']);

    if ($categoryId) {
        $query->where('category_id', $categoryId);
    }

    $query->orderBy('created_at', 'desc');

    if ($perPage) {
        return $query->paginate($perPage); 
    }

    return $query->get();
}

    /**
     * Get single security (Existing method)
     */
    public function getSecurity($id)
    {
        return SecurityMasterData::with(['category', 'fieldValues.field', 'amortisationSchedules', 'callSchedules', 'resetSchedules'])->findOrFail($id);
    }

    // Helpers

    /**
     * Which schedule uploads the submitted Option Type requires.
     * The Option Type value may be an OptionType id, name or code.
     *
     * @return array{amortisation: bool, call: bool, reset: bool}
     */
    public function requiredSchedules($fields): array
    {
        $required = ['amortisation' => false, 'call' => false, 'reset' => false];

        $optionTypeFieldIds = SecurityManagement::where('field_name', 'Option Type')->pluck('id')->all();
        if (empty($optionTypeFieldIds) || !is_array($fields)) {
            return $required;
        }

        $value = null;
        foreach ($fields as $field) {
            if (isset($field['field_id']) && in_array($field['field_id'], $optionTypeFieldIds)) {
                $value = trim((string)($field['field_value'] ?? ''));
                break;
            }
        }

        if ($value === null || $value === '') {
            return $required;
        }

        $required['amortisation'] = in_array(strtolower($value), ['amortised', 'moratorium']);

        $optionType = is_numeric($value)
            ? \App\Models\OptionType::find($value)
            : \App\Models\OptionType::where('name', $value)->orWhere('code', $value)->first();

        if ($optionType) {
            $required['call'] = (bool)$optionType->has_call_date;
            $required['reset'] = (bool)$optionType->has_reset_date;
        } else {
            $required['call'] = stripos($value, 'callable') !== false;
            $required['reset'] = stripos($value, 'perpetual') !== false;
        }

        return $required;
    }

    /**
     * Ensure a call/reset event can be raised against the security
     */
    protected function assertSecurityAcceptsEvent(SecurityMasterData $security): void
    {
        if (($security->lifecycle_status ?? 'active') !== 'active') {
            throw ValidationException::withMessages([
                'security' => ["This security is {$security->lifecycle_status} and can no longer be changed."]
            ]);
        }

        $hasPending = PendingSecurityMasterData::where('security_master_id', $security->id)
            ->where('approval_status', 'pending')
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'security' => ['This security already has a request awaiting approval.']
            ]);
        }
    }

    /**
     * Resolve the 'Coupon (%)' field definition that applies to the security
     */
    protected function getCouponField(SecurityMasterData $security): ?SecurityManagement
    {
        return SecurityManagement::where('field_name', 'Coupon (%)')
            ->forProductOrCategory($security->category_id, $security->product_id) // Prefer product-specific definition
            ->first();
    }

    protected function getCouponFieldValue(SecurityMasterData $security): ?SecurityMasterFieldValue
    {
        $field = $this->getCouponField($security);

        return $field
            ? SecurityMasterFieldValue::where('security_master_id', $security->id)->where('field_id', $field->id)->first()
            : null;
    }

    /**
     * Work out what a call does to the security's Outstanding Value.
     * $amount null = full call; otherwise a partial redemption of that amount (₦'bn).
     *
     * @return array{previous_outstanding: ?float, new_outstanding: ?float, full_redemption: bool}
     */
    protected function callImpact(SecurityMasterData $security, ?float $amount, string $errorKey): array
    {
        $current = $this->getOutstandingFieldValue($security)?->field_value;
        $previous = is_numeric($current) ? round((float)$current, 4) : null;

        if ($amount === null) {
            return [
                'previous_outstanding' => $previous,
                'new_outstanding' => $previous !== null ? 0.0 : null,
                'full_redemption' => true,
            ];
        }

        if ($previous === null) {
            throw ValidationException::withMessages([
                $errorKey => ['This security has no Outstanding Value recorded, so a partial call amount cannot be applied.']
            ]);
        }

        if ($amount - $previous > 0.00005) {
            throw ValidationException::withMessages([
                $errorKey => ["The call amount ({$amount}) is more than the current Outstanding Value ({$previous})."]
            ]);
        }

        $new = max(0.0, round($previous - $amount, 4));

        return [
            'previous_outstanding' => $previous,
            'new_outstanding' => $new,
            'full_redemption' => $new <= 0.00005,
        ];
    }

    /**
     * Resolve the Outstanding Value field (name prefix from config) for the security
     */
    protected function getOutstandingField(SecurityMasterData $security): ?SecurityManagement
    {
        return SecurityManagement::where('field_name', 'like', config('security_events.outstanding_field_name', 'Outstanding Value') . '%')
            ->forProductOrCategory($security->category_id, $security->product_id) // Prefer product-specific definition
            ->first();
    }

    protected function getOutstandingFieldValue(SecurityMasterData $security): ?SecurityMasterFieldValue
    {
        $field = $this->getOutstandingField($security);

        return $field
            ? SecurityMasterFieldValue::where('security_master_id', $security->id)->where('field_id', $field->id)->first()
            : null;
    }

    protected function setOutstandingFieldValue(SecurityMasterData $security, float $value): void
    {
        $field = $this->getOutstandingField($security);

        if ($field) {
            SecurityMasterFieldValue::updateOrCreate(
                ['security_master_id' => $security->id, 'field_id' => $field->id],
                ['field_value' => (string)$value]
            );
        }
    }

    protected function setCouponFieldValue(SecurityMasterData $security, $rate): void
    {
        $field = $this->getCouponField($security);

        if ($field) {
            SecurityMasterFieldValue::updateOrCreate(
                ['security_master_id' => $security->id, 'field_id' => $field->id],
                ['field_value' => (string)$rate]
            );
        }
    }

    protected function createResetSchedules(int $securityId, array $resetSchedule): void
    {
        foreach ($resetSchedule as $resetData) {
            ResetSchedule::create([
                'security_master_id' => $securityId,
                'reset_date' => $resetData['reset_date'],
                'new_coupon_rate' => $resetData['new_coupon_rate'] ?? null,
                'benchmark' => $resetData['benchmark'] ?? null,
                'spread' => $resetData['spread'] ?? null,
            ]);
        }
    }

    /**
     * Active field definitions for a security of this category/product, keyed by id.
     * Shared with the bulk importer so both resolve exactly the same set of fields
     * as the product-type configuration endpoint.
     */
    public function fieldDefinitionsFor($categoryId, $productId)
    {
        return SecurityManagement::applicableTo($categoryId ? (int)$categoryId : null, $productId ? (int)$productId : null)
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    protected function validateFields($categoryId, $productId, $fields)
    {
        $fieldDefinitions = $this->fieldDefinitionsFor($categoryId, $productId);

        $this->validateRequiredFields($fieldDefinitions, $fields ?? []);
        $this->validateFieldTypes($fieldDefinitions, $fields ?? []);
    }

    protected function validateRequiredFields($fieldDefinitions, $fields)
    {
        $providedFieldIds = collect($fields)->pluck('field_id')->toArray();
        $requiredFields = $fieldDefinitions->where('required', true);

        foreach ($requiredFields as $requiredField) {
            if (!in_array($requiredField->id, $providedFieldIds)) {
                throw ValidationException::withMessages([
                    'fields' => ["The field '{$requiredField->field_name}' is required."]
                ]);
            }
        }
    }

    protected function validateFieldTypes($fieldDefinitions, $fields)
    {
        foreach ($fields as $fieldData) {
            $fieldDef = $fieldDefinitions->get($fieldData['field_id']);
            
            if (!$fieldDef) {
                 // Check if it's strictly required to fail if extra field sent, or just ignore?
                 // Original code threw exception.
                throw ValidationException::withMessages([
                    'fields' => ["Field ID {$fieldData['field_id']} does not exist for this category."]
                ]);
            }

            $value = $fieldData['field_value'];

            if (empty($value) && !$fieldDef->required) {
                continue;
            }

            switch ($fieldDef->field_type) {
                case 'Int':
                    if (is_null($value) || $value === '') break;
                    if (!is_numeric($value) || !ctype_digit(strval($value))) {
                        throw ValidationException::withMessages([
                            'fields' => ["The field '{$fieldDef->field_name}' must be an integer."]
                        ]);
                    }
                    break;
                case 'Float':
                case 'Decimal':
                    if (is_null($value) || $value === '') break;
                    if (!is_numeric($value)) {
                        throw ValidationException::withMessages([
                            'fields' => ["The field '{$fieldDef->field_name}' must be a number."]
                        ]);
                    }
                    break;
                case 'Text':
                    // Heuristic: If it's a 'Text' field but has 'Date' in the name, validate as date format if not empty
                    if (stripos($fieldDef->field_name, 'Date') !== false && !empty($value)) {
                        try {
                            \Carbon\Carbon::parse($value);
                        } catch (\Exception $e) {
                            throw ValidationException::withMessages([
                                'fields' => ["The field '{$fieldDef->field_name}' must be a valid date format."]
                            ]);
                        }
                    }
                    if (is_array($value) || is_object($value)) {
                        throw ValidationException::withMessages([
                            'fields' => ["The field '{$fieldDef->field_name}' must be text."]
                        ]);
                    }
                    break;
                case 'Date':
                    if (is_null($value) || $value === '') break;
                    try {
                        \Carbon\Carbon::parse($value);
                    } catch (\Exception $e) {
                        throw ValidationException::withMessages([
                            'fields' => ["The field '{$fieldDef->field_name}' must be a valid date."]
                        ]);
                    }
                    break;
            }
        }
    }

    private function notifySelectedAuthoriser(PendingSecurityMasterData $pending)
    {
        if (!$pending->selected_authoriser_id) return;

        $authoriser = $this->externalUserService->getUserById($pending->selected_authoriser_id);
        $requester = $this->externalUserService->getUserById($pending->requested_by);

        if ($authoriser && isset($authoriser['email'])) {
             Mail::to($authoriser['email'])->send(new \App\Mail\SecurityMasterRequestPending($pending, $requester));
        }
    }

    private function notifyRequester(PendingSecurityMasterData $pending, string $status)
    {
        $requester = $this->externalUserService->getUserById($pending->requested_by);
        
        if (!$requester || !isset($requester['email'])) {
            return;
        }

        if ($status === 'approved') {
            Mail::to($requester['email'])->send(new \App\Mail\SecurityMasterRequestApproved($pending, $requester));
        } elseif ($status === 'rejected') {
            Mail::to($requester['email'])->send(new \App\Mail\SecurityMasterRequestRejected($pending, $pending->rejection_reason, $requester));
        }
    }
}
