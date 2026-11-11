<?php

namespace App\Services;

use App\Models\ProductType;
use App\Models\PendingProductType;
use Illuminate\Support\Facades\Log;

class ProductTypeActivityLogService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Log product type creation request
     */
    public function logProductTypeCreation(PendingProductType $pending, array $data): void
    {
        try {
            $userId = $data['requested_by'] ?? null;
            $properties = ['user_id' => $userId];

            if ($userId) {
                $user = $this->externalUserService->getUserById($userId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['market_category_id'] = $data['market_category_id'] ?? null;
            $properties['name'] = $data['name'] ?? null;
            $properties['code'] = $data['code'] ?? null;
            $properties['description'] = $data['description'] ?? null;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('product-type')
                ->performedOn($pending)
                ->withProperties($properties)
                ->log('Product Type creation request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log product type creation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log product type update request
     */
    public function logProductTypeUpdate(ProductType $productType, array $data): void
    {
        try {
            $userId = $data['requested_by'] ?? null;
            $properties = ['user_id' => $userId];

            if ($userId) {
                $user = $this->externalUserService->getUserById($userId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['market_category_id'] = $data['market_category_id'] ?? null;
            $properties['name'] = $data['name'] ?? null;
            $properties['code'] = $data['code'] ?? null;
            $properties['description'] = $data['description'] ?? null;
            $properties['is_active'] = $data['is_active'] ?? null;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('product-type')
                ->performedOn($productType)
                ->withProperties($properties)
                ->log('Product Type update request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log product type update', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log product type deletion request
     */
    public function logProductTypeDeletion(ProductType $productType, array $data): void
    {
        try {
            $userId = $data['requested_by'] ?? null;
            $properties = ['user_id' => $userId];

            if ($userId) {
                $user = $this->externalUserService->getUserById($userId);
                if ($user && isset($user['id'])) {
                    $properties['caused_by_user_id'] = $user['id'];
                    $properties['caused_by_user_name'] = $user['name'] ?? null;
                }
            }

            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('product-type')
                ->performedOn($productType)
                ->withProperties($properties)
                ->log('Product Type deletion request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log product type deletion', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log product type approval
     */
    public function logApproval(PendingProductType $pendingProductType, ?int $authoriserId): void
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

            activity('product-type')
                ->performedOn($pendingProductType)
                ->withProperties($properties)
                ->log('Product Type approval request approved');
        } catch (\Exception $e) {
            Log::error('Failed to log product type approval', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log product type rejection
     */
    public function logRejection(PendingProductType $pendingProductType, ?int $authoriserId, string $reason): void
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

            activity('product-type')
                ->performedOn($pendingProductType)
                ->withProperties($properties)
                ->log('Product Type approval request rejected');
        } catch (\Exception $e) {
            Log::error('Failed to log product type rejection', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log view operations
     */
    public function logView(string $action, array $properties = []): void
    {
        try {
            activity('product-type')
                ->withProperties($properties)
                ->log($action);
        } catch (\Exception $e) {
            Log::error('Failed to log product type view', ['error' => $e->getMessage()]);
        }
    }
}
