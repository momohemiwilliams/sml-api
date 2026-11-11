<?php

namespace App\Services;

use App\Models\AuctionResult;
use App\Models\PendingAuctionResult;
use App\Mail\AuctionResultRequestPending;
use App\Mail\AuctionResultRequestApproved;
use App\Mail\AuctionResultRequestRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuctionResultService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get paginated pending requests
     */
    public function getPendingRequests(int $perPage = 15)
    {
        $pending = PendingAuctionResult::with(['requester', 'security.fieldValues', 'mainRecord'])
            ->latest()
            ->paginate($perPage);

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'Inputter',
            'selected_authoriser_id' => 'Authoriser'
        ]);
    }

    /**
     * Create Creation Request
     */
    public function createRequest(array $data)
    {
        return DB::transaction(function () use ($data) {
            $nonComp = $data['non_competitive_sales'] ?? 0;
            $amtSold = $data['amount_sold'] ?? 0;
            $totalSold = $amtSold + $nonComp;
            
            $data['total_amount_sold'] = $totalSold;

            $temp = new AuctionResult($data);
            $temp->calculateRatios();
            
            if (isset($data['bid_cover_ratio']) && $data['bid_cover_ratio'] !== null) {
                $temp->bid_cover_ratio = $data['bid_cover_ratio'];
            }
            
            $dayOfWeek = \Carbon\Carbon::parse($data['auction_date'])->format('l');

            $pending = PendingAuctionResult::create(array_merge($data, [
                'day_of_week' => $dayOfWeek,
                'total_amount_sold' => $totalSold,
                'bid_cover_ratio' => $temp->bid_cover_ratio,
                'subscription_level' => $temp->subscription_level,
                'request_type' => 'create',
                'requested_by' => $data['created_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]));

            // Notify Authoriser
            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create Update Request
     */
    public function updateRequest(AuctionResult $mainRecord, array $data)
    {
        return DB::transaction(function () use ($mainRecord, $data) {
             $nonComp = $data['non_competitive_sales'] ?? $mainRecord->non_competitive_sales;
             $amtSold = $data['amount_sold'] ?? $mainRecord->amount_sold;
             $totalSold = $amtSold + $nonComp;
             
             $data['total_amount_sold'] = $totalSold;
             
             $temp = new AuctionResult(array_merge($mainRecord->toArray(), $data));
             $temp->calculateRatios();
             
             if (isset($data['bid_cover_ratio']) && $data['bid_cover_ratio'] !== null) {
                 $temp->bid_cover_ratio = $data['bid_cover_ratio'];
             }
             
             $dayOfWeek = isset($data['auction_date']) 
                ? \Carbon\Carbon::parse($data['auction_date'])->format('l') 
                : $mainRecord->day_of_week;

            $pending = PendingAuctionResult::create(array_merge($data, [
                'auction_result_id' => $mainRecord->id,
                'security_id' => $data['security_id'] ?? $mainRecord->security_id,
                'day_of_week' => $dayOfWeek,
                'total_amount_sold' => $totalSold,
                'bid_cover_ratio' => $temp->bid_cover_ratio,
                'subscription_level' => $temp->subscription_level,
                'request_type' => 'update',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]));

            // Mark the main record as pending
            $mainRecord->update(['approval_status' => 'pending']);

            // Notify Authoriser
            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create Delete Request
     */
    public function deleteRequest(AuctionResult $mainRecord, array $data)
    {
        return DB::transaction(function () use ($mainRecord, $data) {
            $pending = PendingAuctionResult::create([
                'auction_result_id' => $mainRecord->id,
                'security_id' => $mainRecord->security_id,
                'auction_date' => $mainRecord->auction_date,
                'amount_offered' => $mainRecord->amount_offered,
                'value_date' => $mainRecord->value_date,
                'tenor_days' => $mainRecord->tenor_days,
                'amount_subscribed' => $mainRecord->amount_subscribed,
                'amount_allotted' => $mainRecord->amount_allotted,
                'amount_sold' => $mainRecord->amount_sold,
                'non_competitive_sales' => $mainRecord->non_competitive_sales,
                'total_amount_sold' => $mainRecord->total_amount_sold,
                'stop_rate' => $mainRecord->stop_rate,
                'request_type' => 'delete',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            // Mark the main record as pending
            $mainRecord->update(['approval_status' => 'pending']);

            // Notify Authoriser
            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Approve Request
     */
    public function approveRequest(PendingAuctionResult $pending)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;
            $data = $pending->toArray();
            
            $exclude = ['id', 'created_at', 'updated_at', 'deleted_at', 'request_type', 'approval_status', 'rejection_reason', 'requested_by', 'selected_authoriser_id', 'auction_result_id'];
            $attributes = array_diff_key($data, array_flip($exclude));
            
            $attributes['approved_by'] = auth()->id();
            $attributes['approved_at'] = now();
            // Approving means the record is no longer pending
            $attributes['approval_status'] = 'approved';

            switch ($pending->request_type) {
                case 'create':
                    $attributes['created_by'] = $pending->requested_by;
                    $attributes['status'] = 'Completed'; 
                    $result = AuctionResult::create($attributes);
                    if ($this->isNewIssue($pending->auction_type)) {
                        // A new issue sets the opening Outstanding Value
                        $this->setOutstandingValue($pending->security_id, $this->toBillions($pending->total_amount_sold));
                    } else {
                        // A re-opening adds to the existing Outstanding Value
                        $this->updateOutstandingValue($pending->security_id, $this->toBillions($pending->total_amount_sold));
                    }
                    break;

                case 'update':
                    $main = $pending->mainRecord;
                    if ($main) {
                        // Calculate difference so we only add/subtract the change
                        $oldSold = $this->toBillions($main->total_amount_sold);
                        $newSold = $this->toBillions($pending->total_amount_sold);
                        $difference = $newSold - $oldSold;

                        $attributes['updated_by'] = $pending->requested_by;
                        $main->update($attributes);
                        $result = $main;

                        if ($difference != 0) {
                            $this->updateOutstandingValue($pending->security_id ?? $main->security_id, $difference);
                        }
                    }
                    break;

                case 'delete':
                    $main = $pending->mainRecord;
                    if ($main) {
                        $oldSold = $this->toBillions($main->total_amount_sold);
                        $this->updateOutstandingValue($main->security_id, -$oldSold);
                        
                        $main->delete();
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);
            
            // Notify Requester
            if ($pending->requested_by) {
                $requester = $this->externalUserService->getUserById($pending->requested_by);
                if ($requester && isset($requester['email'])) {
                    Mail::to($requester['email'], $requester['name'] ?? null)->send(new AuctionResultRequestApproved($pending, auth()->user()));
                }
            }

            return $result;
        });
    }

    /**
     * Reject Request
     */
    public function rejectRequest(PendingAuctionResult $pending, string $reason)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        $pending->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason
        ]);
        
        // Check if there are other pending requests for the same main record
        if ($pending->auction_result_id) {
            $mainRecord = $pending->mainRecord;
            if ($mainRecord) {
                $hasOtherPending = \App\Models\PendingAuctionResult::where('auction_result_id', $mainRecord->id)
                    ->where('id', '!=', $pending->id)
                    ->where('approval_status', 'pending')
                    ->exists();
                
                if (!$hasOtherPending) {
                    $mainRecord->update(['approval_status' => 'approved']);
                }
            }
        }
        
        // Notify Requester
        if ($pending->requested_by) {
            $requester = $this->externalUserService->getUserById($pending->requested_by);
            if ($requester && isset($requester['email'])) {
                Mail::to($requester['email'], $requester['name'] ?? null)->send(new AuctionResultRequestRejected($pending, auth()->user()));
            }
        }

        return $pending;
    }
    protected function notifySelectedAuthoriser(PendingAuctionResult $pending)
    {
        if ($pending->selected_authoriser_id) {
            $authoriser = $this->externalUserService->getUserById($pending->selected_authoriser_id);
            if ($authoriser && isset($authoriser['email'])) {
                Mail::to($authoriser['email'], $authoriser['name'] ?? null)->send(new AuctionResultRequestPending($pending, auth()->user()));
            }
        }
    }

    /**
     * New (a.k.a. Primary) issues set the opening Outstanding Value;
     * re-openings (a.k.a. Secondary) add to it.
     */
    protected function isNewIssue($auctionType): bool
    {
        return in_array(strtolower(trim((string) $auctionType)), ['new', 'primary'], true);
    }

    /**
     * Auction amounts are captured in ₦'mm; Outstanding Value is held in ₦'bn.
     */
    protected function toBillions($amountInMillions): float
    {
        return floatval($amountInMillions ?? 0) / 1000;
    }

    /**
     * Keep up to 6 decimals (₦1,000 precision) so small auctions aren't rounded away.
     */
    protected function formatBillions(float $value): string
    {
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }

    /**
     * Adds an amount to the Outstanding Value for a given security_id.
     */
    protected function updateOutstandingValue($securityId, $amountToAdd)
    {
        if (!$securityId || !$amountToAdd) return;

        $fieldValue = $this->findOutstandingValueField($securityId);

        if ($fieldValue) {
            $currentValue = floatval(str_replace(',', '', $fieldValue->field_value));
            $newValue = $currentValue + floatval($amountToAdd);

            $fieldValue->update(['field_value' => $this->formatBillions($newValue)]);
            $fieldValue->securityMaster?->touch();
        }
    }

    /**
     * Sets the Outstanding Value for a given security_id.
     */
    protected function setOutstandingValue($securityId, $value)
    {
        if (!$securityId) return;

        $fieldValue = $this->findOutstandingValueField($securityId);

        if ($fieldValue) {
            $fieldValue->update(['field_value' => $this->formatBillions(floatval($value))]);
            $fieldValue->securityMaster?->touch();
        }
    }

    /**
     * Field ids differ per product type, so resolve the Outstanding Value
     * field by name for the security's product. Row is locked for the
     * duration of the approval transaction.
     */
    protected function findOutstandingValueField($securityId)
    {
        $security = \App\Models\SecurityMasterData::find($securityId);
        if (!$security) return null;

        $fieldId = \App\Models\SecurityManagement::where('product_id', $security->product_id)
            ->where('field_name', 'like', 'Outstanding Value%')
            ->value('id');

        $fieldValue = $fieldId
            ? \App\Models\SecurityMasterFieldValue::where('security_master_id', $securityId)
                ->where('field_id', $fieldId)
                ->lockForUpdate()
                ->first()
            : null;

        if (!$fieldValue) {
            \Illuminate\Support\Facades\Log::warning('Outstanding Value field not found for security; auction approval did not update it.', [
                'security_id' => $securityId,
                'product_id' => $security->product_id,
            ]);
        }

        return $fieldValue;
    }
}

