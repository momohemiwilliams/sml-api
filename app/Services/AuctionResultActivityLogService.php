<?php

namespace App\Services;

use App\Models\AuctionResult;
use App\Models\PendingAuctionResult;
use Illuminate\Support\Facades\Log;

class AuctionResultActivityLogService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Log auction result creation request
     */
    public function logAuctionResultCreation(PendingAuctionResult $pending, array $data): void
    {
        try {
            $userId = $data['created_by'] ?? $data['requested_by'] ?? null;
            $properties = ['user_id' => $userId];

            if ($userId) {
                $user = $this->externalUserService->getUserById($userId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['security_id'] = $data['security_id'] ?? null;
            $properties['auction_date'] = $data['auction_date'] ?? null;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('auction-result')
                ->performedOn($pending)
                ->withProperties($properties)
                ->log('Auction Result creation request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log auction result creation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log auction result update request
     */
    public function logAuctionResultUpdate(AuctionResult $auctionResult, array $data): void
    {
        try {
            $userId = $data['updated_by'] ?? $data['requested_by'] ?? null;
            $properties = ['user_id' => $userId];

            if ($userId) {
                $user = $this->externalUserService->getUserById($userId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['security_id'] = $data['security_id'] ?? null;
            $properties['auction_date'] = $data['auction_date'] ?? null;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('auction-result')
                ->performedOn($auctionResult)
                ->withProperties($properties)
                ->log('Auction Result update request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log auction result update', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log auction result deletion request
     */
    public function logAuctionResultDeletion(AuctionResult $auctionResult, array $data): void
    {
        try {
            $userId = $data['updated_by'] ?? null;
            $properties = ['user_id' => $userId];

            if ($userId) {
                $user = $this->externalUserService->getUserById($userId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['security_id'] = $auctionResult->security_id;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('auction-result')
                ->performedOn($auctionResult)
                ->withProperties($properties)
                ->log('Auction Result deletion request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log auction result deletion', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log auction result approval
     */
    public function logApproval(PendingAuctionResult $pendingAuctionResult, ?int $authoriserId): void
    {
        try {
            $properties = ['authoriser_id' => $authoriserId];

            if ($authoriserId) {
                $user = $this->externalUserService->getUserById($authoriserId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['request_type'] = $pendingAuctionResult->request_type;

            activity('auction-result')
                ->performedOn($pendingAuctionResult)
                ->withProperties($properties)
                ->log('Auction Result approval request approved');
        } catch (\Exception $e) {
            Log::error('Failed to log auction result approval', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log auction result rejection
     */
    public function logRejection(PendingAuctionResult $pendingAuctionResult, ?int $authoriserId, string $reason): void
    {
        try {
            $properties = [
                'authoriser_id' => $authoriserId,
                'reason' => $reason
            ];

            if ($authoriserId) {
                $user = $this->externalUserService->getUserById($authoriserId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['request_type'] = $pendingAuctionResult->request_type;

            activity('auction-result')
                ->performedOn($pendingAuctionResult)
                ->withProperties($properties)
                ->log('Auction Result approval request rejected');
        } catch (\Exception $e) {
            Log::error('Failed to log auction result rejection', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log view operations
     */
    public function logView(string $action, array $properties = []): void
    {
        try {
            activity('auction-result')
                ->withProperties($properties)
                ->log($action);
        } catch (\Exception $e) {
            Log::error('Failed to log auction result view', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log bulk upload operations
     */
    public function logBulkUpload(array $data): void
    {
        try {
            $userId = $data['created_by'] ?? null;
            $properties = ['user_id' => $userId];

            if ($userId) {
                $user = $this->externalUserService->getUserById($userId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('auction-result')
                ->withProperties($properties)
                ->log('Auction Result bulk upload submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log auction result bulk upload', ['error' => $e->getMessage()]);
        }
    }
}
