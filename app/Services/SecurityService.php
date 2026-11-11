<?php

namespace App\Services;

use App\Models\Security;
use App\Models\PendingSecurity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class SecurityService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get paginated list of securities.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllSecurities(int $perPage = 15): LengthAwarePaginator
    {
        $securities = Security::with(['productType', 'securityType', 'creator', 'approver'])
            ->latest()
            ->paginate($perPage);

        return $this->externalUserService->enrichWithUsers($securities, [
            'created_by' => 'inputter',
            'approved_by' => 'authoriser'
        ]);
    }

    /**
     * Get a single security with enriched details.
     */
    public function getSecurity(Security $security): Security
    {
        $security->load(['productType', 'securityType', 'creator', 'approver']);
        
        return $this->enrichSingle($security, [
            'created_by' => 'inputter',
            'approved_by' => 'authoriser'
        ]);
    }

    /**
     * Get pending requests.
     */
    public function getPendingRequests(int $perPage = 15): LengthAwarePaginator
    {
        $pending = PendingSecurity::with(['requester', 'selectedAuthoriser', 'security', 'productType', 'securityType'])
            ->latest()
            ->paginate($perPage);

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'inputter',
            'selected_authoriser_id' => 'authoriser'
        ]);
    }

    /**
     * Create a request to create a new security.
     */
    public function createRequest(array $data): PendingSecurity
    {
        return DB::transaction(function () use ($data) {
            $pending = PendingSecurity::create([
                'issue_category' => $data['issue_category'] ?? null,
                'issuer' => $data['issuer'] ?? null,
                'security_type_id' => $data['security_type_id'] ?? null,
                'isin' => $data['isin'] ?? null,
                'description' => $data['description'] ?? null,
                'issue_date' => $data['issue_date'] ?? null,
                'maturity_date' => $data['maturity_date'] ?? null,
                'tenor' => $data['tenor'] ?? null,
                'coupon' => $data['coupon'] ?? null,
                'coupon_type' => $data['coupon_type'] ?? null,
                'frm' => $data['frm'] ?? null,
                'frb' => $data['frb'] ?? null,
                'frbv' => $data['frbv'] ?? null,
                'coupon_floor' => $data['coupon_floor'] ?? null,
                'coupon_cap' => $data['coupon_cap'] ?? null,
                'coupon_frequency' => $data['coupon_frequency'] ?? null,
                'effective_coupon' => $data['effective_coupon'] ?? null,
                'fgn_benchmark_yield' => $data['fgn_benchmark_yield'] ?? null,
                'issue_size' => $data['issue_size'] ?? null,
                'outstanding_value' => $data['outstanding_value'] ?? null,
                'ttm' => $data['ttm'] ?? null,
                'day_count_convention' => $data['day_count_convention'] ?? null,
                'day_count_basis' => $data['day_count_basis'] ?? null,
                'option_type' => $data['option_type'] ?? null,
                'call_date' => $data['call_date'] ?? null,
                'yield_at_issue' => $data['yield_at_issue'] ?? null,
                'interest_determination_date' => $data['interest_determination_date'] ?? null,
                'listing_status' => $data['listing_status'] ?? null,
                'rating_1_agency' => $data['rating_1_agency'] ?? null,
                'rating_1' => $data['rating_1'] ?? null,
                'rating_1_issuance_date' => $data['rating_1_issuance_date'] ?? null,
                'rating_1_expiration_date' => $data['rating_1_expiration_date'] ?? null,
                'rating_2_agency' => $data['rating_2_agency'] ?? null,
                'rating_2' => $data['rating_2'] ?? null,
                'rating_2_issuance_date' => $data['rating_2_issuance_date'] ?? null,
                'rating_2_expiration_date' => $data['rating_2_expiration_date'] ?? null,
                'final_rating' => $data['final_rating'] ?? null,
                'product_type_id' => $data['product_type_id'] ?? null,
                'first_settlement_date' => $data['first_settlement_date'] ?? null,
                'last_trading_date' => $data['last_trading_date'] ?? null,
                'face_value' => $data['face_value'] ?? null,
                'issue_price' => $data['issue_price'] ?? null,
                'discount_rate' => $data['discount_rate'] ?? null,
                'amount_issued' => $data['amount_issued'] ?? null,
                'amount_outstanding' => $data['amount_outstanding'] ?? null,
                'status' => $data['status'] ?? 'Active',
                'remarks' => $data['remarks'] ?? null,
                'request_type' => 'create',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $this->enrichSingle($pending, [
                'requested_by' => 'inputter',
                'selected_authoriser_id' => 'authoriser'
            ]);
        });
    }

    /**
     * Create a request to update an existing security.
     */
    public function updateRequest(Security $security, array $data): PendingSecurity
    {
        return DB::transaction(function () use ($security, $data) {
            // Merge with existing security data
            $mergedData = array_merge($security->toArray(), $data);

            $pending = PendingSecurity::create([
                'security_id' => $security->id,
                'issue_category' => $mergedData['issue_category'] ?? null,
                'issuer' => $mergedData['issuer'] ?? null,
                'security_type_id' => $mergedData['security_type_id'] ?? null,
                'isin' => $mergedData['isin'] ?? null,
                'description' => $mergedData['description'] ?? null,
                'issue_date' => $mergedData['issue_date'] ?? null,
                'maturity_date' => $mergedData['maturity_date'] ?? null,
                'tenor' => $mergedData['tenor'] ?? null,
                'coupon' => $mergedData['coupon'] ?? null,
                'coupon_type' => $mergedData['coupon_type'] ?? null,
                'frm' => $mergedData['frm'] ?? null,
                'frb' => $mergedData['frb'] ?? null,
                'frbv' => $mergedData['frbv'] ?? null,
                'coupon_floor' => $mergedData['coupon_floor'] ?? null,
                'coupon_cap' => $mergedData['coupon_cap'] ?? null,
                'coupon_frequency' => $mergedData['coupon_frequency'] ?? null,
                'effective_coupon' => $mergedData['effective_coupon'] ?? null,
                'fgn_benchmark_yield' => $mergedData['fgn_benchmark_yield'] ?? null,
                'issue_size' => $mergedData['issue_size'] ?? null,
                'outstanding_value' => $mergedData['outstanding_value'] ?? null,
                'ttm' => $mergedData['ttm'] ?? null,
                'day_count_convention' => $mergedData['day_count_convention'] ?? null,
                'day_count_basis' => $mergedData['day_count_basis'] ?? null,
                'option_type' => $mergedData['option_type'] ?? null,
                'call_date' => $mergedData['call_date'] ?? null,
                'yield_at_issue' => $mergedData['yield_at_issue'] ?? null,
                'interest_determination_date' => $mergedData['interest_determination_date'] ?? null,
                'listing_status' => $mergedData['listing_status'] ?? null,
                'rating_1_agency' => $mergedData['rating_1_agency'] ?? null,
                'rating_1' => $mergedData['rating_1'] ?? null,
                'rating_1_issuance_date' => $mergedData['rating_1_issuance_date'] ?? null,
                'rating_1_expiration_date' => $mergedData['rating_1_expiration_date'] ?? null,
                'rating_2_agency' => $mergedData['rating_2_agency'] ?? null,
                'rating_2' => $mergedData['rating_2'] ?? null,
                'rating_2_issuance_date' => $mergedData['rating_2_issuance_date'] ?? null,
                'rating_2_expiration_date' => $mergedData['rating_2_expiration_date'] ?? null,
                'final_rating' => $mergedData['final_rating'] ?? null,
                'product_type_id' => $mergedData['product_type_id'] ?? null,
                'first_settlement_date' => $mergedData['first_settlement_date'] ?? null,
                'last_trading_date' => $mergedData['last_trading_date'] ?? null,
                'face_value' => $mergedData['face_value'] ?? null,
                'issue_price' => $mergedData['issue_price'] ?? null,
                'discount_rate' => $mergedData['discount_rate'] ?? null,
                'amount_issued' => $mergedData['amount_issued'] ?? null,
                'amount_outstanding' => $mergedData['amount_outstanding'] ?? null,
                'status' => $mergedData['status'] ?? 'Active',
                'remarks' => $mergedData['remarks'] ?? null,
                'request_type' => 'update',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $this->enrichSingle($pending, [
                'requested_by' => 'inputter',
                'selected_authoriser_id' => 'authoriser'
            ]);
        });
    }

    /**
     * Create a request to delete a security.
     */
    public function deleteRequest(Security $security, array $data): PendingSecurity
    {
        return DB::transaction(function () use ($security, $data) {
            $pending = PendingSecurity::create([
                'security_id' => $security->id,
                'isin' => $security->isin,
                'description' => $security->description,
                'issuer' => $security->issuer,
                'request_type' => 'delete',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $this->enrichSingle($pending, [
                'requested_by' => 'inputter',
                'selected_authoriser_id' => 'authoriser'
            ]);
        });
    }

    /**
     * Approve a pending request.
     */
    public function approveRequest(PendingSecurity $pending): mixed
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $result = Security::create($this->prepareSecurityData($pending));
                    break;

                case 'update':
                    $security = $pending->security;
                    if ($security) {
                        $security->update($this->prepareSecurityData($pending));
                        $result = $security;
                    }
                    break;

                case 'delete':
                    $security = $pending->security;
                    if ($security) {
                        $security->delete();
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);


            // Notify Requester
            $this->notifyRequester($pending, 'approved');

            if ($result instanceof Security) {
                return $this->enrichSingle($result, [
                    'created_by' => 'inputter',
                    'approved_by' => 'authoriser'
                ]);
            }

            return $result;
        });
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingSecurity $pending, string $reason): PendingSecurity
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        $pending->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Notify Requester
        $this->notifyRequester($pending, 'rejected');

        return $this->enrichSingle($pending, [
            'requested_by' => 'inputter',
            'selected_authoriser_id' => 'authoriser'
        ]);
    }

    /**
     * Calculate derived fields.
     */
    private function calculateFields(array $data): array
    {
        // Calculate Tenor
        if (isset($data['issue_date']) && isset($data['maturity_date'])) {
            $data['tenor'] = $this->calculateTenor($data['issue_date'], $data['maturity_date']);
        }

        // Calculate TTM
        if (isset($data['maturity_date'])) {
            $data['ttm'] = $this->calculateTTM($data['maturity_date'], $data['day_count_basis'] ?? 1);
        }

        // Calculate Day Count Basis
        if (isset($data['day_count_convention'])) {
            $data['day_count_basis'] = $this->calculateDayCountBasis($data['day_count_convention']);
        }

        // Calculate Effective Coupon
        if (isset($data['coupon_type'])) {
            $data['effective_coupon'] = $this->calculateEffectiveCoupon($data);
        }

        // Calculate Final Rating
        $data['final_rating'] = $this->calculateFinalRating($data);

        return $data;
    }

    /**
     * Calculate tenor in years.
     */
    private function calculateTenor($issueDate, $maturityDate): int
    {
        $issue = Carbon::parse($issueDate);
        $maturity = Carbon::parse($maturityDate);
        return $maturity->year - $issue->year;
    }

    /**
     * Calculate time to maturity.
     */
    private function calculateTTM($maturityDate, $dayCountBasis): float
    {
        $maturity = Carbon::parse($maturityDate);
        $today = Carbon::now();

        if ($maturity->isFuture()) {
            // Simple calculation - can be enhanced with proper YEARFRAC implementation
            return $today->diffInDays($maturity) / 365;
        }

        return 0;
    }

    /**
     * Calculate day count basis from convention.
     */
    private function calculateDayCountBasis(string $convention): int
    {
        $mapping = [
            'US (NASD) 30/360' => 0,
            'Actual/Actual' => 1,
            'Actual/360' => 2,
            'Actual/365' => 3,
            'European 30/360' => 4,
        ];

        return $mapping[$convention] ?? 1;
    }

    /**
     * Calculate effective coupon.
     */
    private function calculateEffectiveCoupon(array $data): ?float
    {
        $couponType = $data['coupon_type'] ?? null;
        $coupon = $data['coupon'] ?? null;

        if ($couponType === 'Fixed') {
            return $coupon;
        }

        if ($couponType === 'Floating') {
            $frm = $data['frm'] ?? 0;
            $frbv = $data['frbv'] ?? 0;
            $cc = $data['coupon_cap'] ?? null;
            $cf = $data['coupon_floor'] ?? null;

            $calculated = $frm + $frbv;

            if ($cc !== null && $calculated > $cc) {
                return $cc;
            }

            if ($cf !== null && $calculated < $cf) {
                return $cf;
            }

            return $calculated;
        }

        return $coupon;
    }

    /**
     * Calculate final rating.
     */
    private function calculateFinalRating(array $data): ?string
    {
        $rating1 = $data['rating_1'] ?? null;
        $rating1Agency = $data['rating_1_agency'] ?? null;
        $rating2 = $data['rating_2'] ?? null;
        $rating2Agency = $data['rating_2_agency'] ?? null;

        $finalRating = '';

        if ($rating1 && $rating1Agency) {
            $finalRating .= "$rating1/$rating1Agency";
        }

        if ($rating2 && $rating2Agency) {
            if ($finalRating) {
                $finalRating .= '; ';
            }
            $finalRating .= "$rating2/$rating2Agency";
        }

        return $finalRating ?: null;
    }

    /**
     * Prepare security data from pending request.
     */
    private function prepareSecurityData(PendingSecurity $pending): array
    {
        return [
            'issue_category' => $pending->issue_category,
            'issuer' => $pending->issuer,
            'security_type_id' => $pending->security_type_id,
            'isin' => $pending->isin,
            'description' => $pending->description,
            'issue_date' => $pending->issue_date,
            'maturity_date' => $pending->maturity_date,
            'tenor' => $pending->tenor,
            'coupon' => $pending->coupon,
            'coupon_type' => $pending->coupon_type,
            'frm' => $pending->frm,
            'frb' => $pending->frb,
            'frbv' => $pending->frbv,
            'coupon_floor' => $pending->coupon_floor,
            'coupon_cap' => $pending->coupon_cap,
            'coupon_frequency' => $pending->coupon_frequency,
            'effective_coupon' => $pending->effective_coupon,
            'fgn_benchmark_yield' => $pending->fgn_benchmark_yield,
            'issue_size' => $pending->issue_size,
            'outstanding_value' => $pending->outstanding_value,
            'ttm' => $pending->ttm,
            'day_count_convention' => $pending->day_count_convention,
            'day_count_basis' => $pending->day_count_basis,
            'option_type' => $pending->option_type,
            'call_date' => $pending->call_date,
            'yield_at_issue' => $pending->yield_at_issue,
            'interest_determination_date' => $pending->interest_determination_date,
            'listing_status' => $pending->listing_status,
            'rating_1_agency' => $pending->rating_1_agency,
            'rating_1' => $pending->rating_1,
            'rating_1_issuance_date' => $pending->rating_1_issuance_date,
            'rating_1_expiration_date' => $pending->rating_1_expiration_date,
            'rating_2_agency' => $pending->rating_2_agency,
            'rating_2' => $pending->rating_2,
            'rating_2_issuance_date' => $pending->rating_2_issuance_date,
            'rating_2_expiration_date' => $pending->rating_2_expiration_date,
            'final_rating' => $pending->final_rating,
            'product_type_id' => $pending->product_type_id,
            'first_settlement_date' => $pending->first_settlement_date,
            'last_trading_date' => $pending->last_trading_date,
            'face_value' => $pending->face_value,
            'issue_price' => $pending->issue_price,
            'discount_rate' => $pending->discount_rate,
            'amount_issued' => $pending->amount_issued,
            'amount_outstanding' => $pending->amount_outstanding,
            'status' => $pending->status,
            'remarks' => $pending->remarks,
        ];
    }

    /**
     * Notify the selected authoriser.
     */
    private function notifySelectedAuthoriser(PendingSecurity $pending): void
    {
        $authoriser = $this->externalUserService->getUserById($pending->selected_authoriser_id);
        if ($authoriser && isset($authoriser['email'])) {
            Mail::to($authoriser['email'], $authoriser['name'] ?? null)
                ->send(new \App\Mail\SecurityRequestPending($pending, $authoriser['name'] ?? 'Authoriser'));
        }
    }

    /**
     * Notify requester (Inputter) of approval/rejection.
     */
    private function notifyRequester(PendingSecurity $pending, string $status): void
    {
        $requester = $this->externalUserService->getUserById($pending->requested_by);
        
        if (!$requester || !isset($requester['email'])) {
            return;
        }

        if ($status === 'approved') {
            Mail::to($requester['email'], $requester['name'] ?? null)
                ->send(new \App\Mail\SecurityRequestApproved($pending, $requester['name'] ?? 'Inputter'));
        } elseif ($status === 'rejected') {
            Mail::to($requester['email'], $requester['name'] ?? null)
                ->send(new \App\Mail\SecurityRequestRejected($pending, $pending->rejection_reason, $requester['name'] ?? 'Inputter'));
        }
    }

    /**
     * Helper to enrich a single object
     */
    private function enrichSingle($item, array $mappings)
    {
        $user = $this->externalUserService->getUserById($item->{$mappings[array_key_first($mappings)]} ?? 0); // Optimization check? No, just mapping.
        
        // Actually reusing the service logic is consistently better
        // But the service method expects a collection or paginator for 'enrichWithUsers'
        // Let's just do it manually here for single items or make a service method.
        // I added getUserById in service.

        foreach ($mappings as $localField => $targetField) {
            if (isset($item->$localField)) {
                $item->$targetField = $this->externalUserService->getUserById($item->$localField);
            }
        }
        return $item;
    }
}
