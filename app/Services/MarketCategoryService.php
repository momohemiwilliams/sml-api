<?php

namespace App\Services;

use App\Models\MarketCategory;
use App\Models\PendingMarketCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;

class MarketCategoryService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get list of market categories.
     */
    public function getAllCategories()
    {
        $categories = MarketCategory::latest()->get();

        return $this->externalUserService->enrichWithUsers($categories, [
             'created_by' => 'inputter',
        ]);
    }

    /**
     * Get all active market categories (for dropdowns).
     */
    public function getAllActiveCategories()
    {
        return MarketCategory::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Get list of pending requests.
     */
    public function getPendingRequests()
    {
        $pending = PendingMarketCategory::with(['requester', 'marketCategory'])
            ->latest()
            ->get();

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'Inputter',
            'selected_authoriser_id' => 'Authoriser'
        ]);
    }

    /**
     * Create a request to create a new market category.
     */
    public function createRequest(array $data): PendingMarketCategory
    {
     
        return DB::transaction(function () use ($data) {
            $pending = PendingMarketCategory::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'request_type' => 'create',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create a request to update an existing market category.
     */
    public function updateRequest(MarketCategory $category, array $data): PendingMarketCategory
    {
        return DB::transaction(function () use ($category, $data) {
            // Set the main category to pending_approval
            $category->update(['approval_status' => 'pending_approval']);
            
            $pending = PendingMarketCategory::create([
                'market_category_id' => $category->id,
                'name' => $data['name'] ?? $category->name,
                'code' => $data['code'] ?? $category->code,
                'description' => $data['description'] ?? $category->description,
                'is_active' => isset($data['is_active']) ? $data['is_active'] : $category->is_active,
                'request_type' => 'update',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

            $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Create a request to delete a market category.
     */
    public function deleteRequest(MarketCategory $category, array $data): PendingMarketCategory
    {
        return DB::transaction(function () use ($category, $data) {
            // Set the main category to pending_approval
            $category->update(['approval_status' => 'pending_approval']);
            
            $pending = PendingMarketCategory::create([
                'market_category_id' => $category->id,
                'name' => $category->name, // Snapshot for reference
                'code' => $category->code,
                'request_type' => 'delete',
                'requested_by' => $data['requested_by'],
                'selected_authoriser_id' => $data['authoriser_id'],
                'approval_status' => 'pending',
            ]);

             $this->notifySelectedAuthoriser($pending);

            return $pending;
        });
    }

    /**
     * Approve a pending request.
     */
    public function approveRequest(PendingMarketCategory $pending): mixed
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

      

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $result = MarketCategory::create([
                        'name' => $pending->name,
                        'code' => $pending->code,
                        'description' => $pending->description,
                        'is_active' => $pending->is_active,
                        'approval_status' => 'active',
                    ]);
                    break;

                case 'update':
                    $category = $pending->marketCategory;
                    if ($category) {
                        $category->update([
                            'name' => $pending->name,
                            'code' => $pending->code,
                            'description' => $pending->description,
                            'is_active' => $pending->is_active,
                            'approval_status' => 'active',
                        ]);
                        $result = $category;
                    }
                    break;

                case 'delete':
                    $category = $pending->marketCategory;
                    if ($category) {
                        // Perform soft delete (updates deleted_at column)
                        $category->delete();
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);

            // Notify Requester
            $this->notifyRequester($pending, 'approved');

            return $result;
        });
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingMarketCategory $pending, string $reason): PendingMarketCategory
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending, $reason) {
            // Revert the main category approval_status back to active if it was an update/delete request
            if (in_array($pending->request_type, ['update', 'delete']) && $pending->marketCategory) {
                $pending->marketCategory->update(['approval_status' => 'active']);
            }

            $pending->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            // Notify Requester
            $this->notifyRequester($pending, 'rejected');

            return $pending;
        });
    }

    /**
     * Notify authorisers.
     */

    private function notifySelectedAuthoriser(PendingMarketCategory $pending): void
    {
        $authoriser = $this->externalUserService->getUserById($pending->selected_authoriser_id);
        $requester = $this->externalUserService->getUserById($pending->requested_by);

        if ($authoriser && isset($authoriser['email'])) {
             \Log::info('Sending Market Category Pending email', [
                 'to' => $authoriser['email'],
                 'recipient_name' => $authoriser['name'] ?? 'Authoriser',
                 'requester_found' => $requester !== null,
                 'pending_id' => $pending->id
             ]);
             Mail::to($authoriser['email'], $authoriser['name'] ?? null)
                ->send(new \App\Mail\MarketCategoryRequestPending($pending, $requester, $authoriser['name'] ?? 'Authoriser'));
        }
    }

    /**
     * Notify requester (Inputter) of approval/rejection.
     */
    private function notifyRequester(PendingMarketCategory $pending, string $status): void
    {
        $requester = $this->externalUserService->getUserById($pending->requested_by);
        
        if (!$requester || !isset($requester['email'])) {
            return;
        }

        if ($status === 'approved') {
            Mail::to($requester['email'], $requester['name'] ?? null)
                ->send(new \App\Mail\MarketCategoryRequestApproved($pending, $requester, $requester['name'] ?? 'Inputter'));
        } elseif ($status === 'rejected') {
            Mail::to($requester['email'], $requester['name'] ?? null)
                ->send(new \App\Mail\MarketCategoryRequestRejected($pending, $pending->rejection_reason, $requester, $requester['name'] ?? 'Inputter'));
        }
    }
}
