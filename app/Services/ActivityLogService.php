<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ActivityLogService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Log a market category creation request
     *
     * @param mixed $pending The pending market category record
     * @param array $data The validated request data
     * @return void
     */
    public function logMarketCategoryCreation($pending, array $data): void
    {
        try {
            $user = $this->externalUserService->getUserById($data['requested_by']);
            
            $activity = activity('market-category')
                ->performedOn($pending)
                ->withProperties($data);
            
            if ($user && isset($user['id'])) {
                $activity->withProperties(['caused_by_user_id' => $user['id'], 'caused_by_user_name' => $user['name'] ?? null]);
            }
            
            $activity->log('Created market category request');
        } catch (\Exception $e) {
            // Log the error but don't throw - we don't want logging to fail the main operation
            Log::error('Failed to log market category creation', [
                'error' => $e->getMessage(),
                'pending_id' => $pending->id ?? null,
                'requested_by' => $data['requested_by'] ?? null
            ]);
        }
    }

    /**
     * Log a market category update request
     *
     * @param mixed $marketCategory The market category being updated
     * @param array $data The validated request data
     * @return void
     */
    public function logMarketCategoryUpdate($marketCategory, array $data): void
    {
        try {
            $user = $this->externalUserService->getUserById($data['requested_by']);
            
            $activity = activity('market-category')
                ->performedOn($marketCategory)
                ->withProperties($data);
            
            if ($user && isset($user['id'])) {
                $activity->withProperties(['caused_by_user_id' => $user['id'], 'caused_by_user_name' => $user['name'] ?? null]);
            }
            
            $activity->log('Created market category update request');
        } catch (\Exception $e) {
            Log::error('Failed to log market category update', [
                'error' => $e->getMessage(),
                'category_id' => $marketCategory->id ?? null,
                'requested_by' => $data['requested_by'] ?? null
            ]);
        }
    }

    /**
     * Log a market category deletion request
     *
     * @param mixed $marketCategory The market category being deleted
     * @param array $data The validated request data
     * @return void
     */
    public function logMarketCategoryDeletion($marketCategory, array $data): void
    {
        try {
            $user = $this->externalUserService->getUserById($data['requested_by']);
            
            $activity = activity('market-category')
                ->performedOn($marketCategory)
                ->withProperties($data);
            
            if ($user && isset($user['id'])) {
                $activity->withProperties(['caused_by_user_id' => $user['id'], 'caused_by_user_name' => $user['name'] ?? null]);
            }
            
            $activity->log('Created market category deletion request');
        } catch (\Exception $e) {
            Log::error('Failed to log market category deletion', [
                'error' => $e->getMessage(),
                'category_id' => $marketCategory->id ?? null,
                'requested_by' => $data['requested_by'] ?? null
            ]);
        }
    }

    /**
     * Log a market category approval
     *
     * @param mixed $pendingMarketCategory The pending market category being approved
     * @param int|null $authoriserId The ID of the user approving
     * @return void
     */
    public function logApproval($pendingMarketCategory, ?int $authoriserId): void
    {
        try {
            if (!$authoriserId) {
                activity('market-category')
                    ->performedOn($pendingMarketCategory)
                    ->withProperties([
                        'request_type' => $pendingMarketCategory->request_type ?? null,
                        'approved_by_id' => null
                    ])
                    ->log('Approved market category request');
                return;
            }
            
            $user = $this->externalUserService->getUserById($authoriserId);
            
            $properties = [
                'request_type' => $pendingMarketCategory->request_type ?? null,
                'approved_by_id' => $authoriserId
            ];
            
            if ($user && isset($user['id'])) {
                $properties['caused_by_user_id'] = $user['id'];
                $properties['caused_by_user_name'] = $user['name'] ?? null;
            }
            
            activity('market-category')
                ->performedOn($pendingMarketCategory)
                ->withProperties($properties)
                ->log('Approved market category request');
        } catch (\Exception $e) {
            Log::error('Failed to log market category approval', [
                'error' => $e->getMessage(),
                'pending_id' => $pendingMarketCategory->id ?? null,
                'authoriser_id' => $authoriserId
            ]);
        }
    }

    /**
     * Log a market category rejection
     *
     * @param mixed $pendingMarketCategory The pending market category being rejected
     * @param int|null $authoriserId The ID of the user rejecting
     * @param string $reason The rejection reason
     * @return void
     */
    public function logRejection($pendingMarketCategory, ?int $authoriserId, string $reason): void
    {
        try {
            if (!$authoriserId) {
                activity('market-category')
                    ->performedOn($pendingMarketCategory)
                    ->withProperties([
                        'request_type' => $pendingMarketCategory->request_type ?? null,
                        'rejection_reason' => $reason,
                        'rejected_by_id' => null
                    ])
                    ->log('Rejected market category request');
                return;
            }
            
            $user = $this->externalUserService->getUserById($authoriserId);
            
            $properties = [
                'request_type' => $pendingMarketCategory->request_type ?? null,
                'rejection_reason' => $reason,
                'rejected_by_id' => $authoriserId
            ];
            
            if ($user && isset($user['id'])) {
                $properties['caused_by_user_id'] = $user['id'];
                $properties['caused_by_user_name'] = $user['name'] ?? null;
            }
            
            activity('market-category')
                ->performedOn($pendingMarketCategory)
                ->withProperties($properties)
                ->log('Rejected market category request');
        } catch (\Exception $e) {
            Log::error('Failed to log market category rejection', [
                'error' => $e->getMessage(),
                'pending_id' => $pendingMarketCategory->id ?? null,
                'authoriser_id' => $authoriserId
            ]);
        }
    }

    /**
     * Log a view action (read-only)
     *
     * @param string $action Description of the action
     * @param array $properties Additional properties to log
     * @return void
     */
    public function logView(string $action, array $properties = []): void
    {
        try {
            activity('market-category')
                ->withProperties($properties)
                ->log($action);
        } catch (\Exception $e) {
            Log::error('Failed to log view action', [
                'error' => $e->getMessage(),
                'action' => $action
            ]);
        }
    }
}
