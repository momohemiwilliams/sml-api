<?php

namespace App\Services;

use App\Models\SecurityMasterData;
use App\Models\PendingSecurityMasterData;
use Illuminate\Support\Facades\Log;

class SecurityMasterDataActivityLogService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Log security master data creation request
     */
    public function logSecurityMasterCreation(PendingSecurityMasterData $pending, array $data): void
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

            $properties['category_id'] = $data['category_id'] ?? null;
            $properties['product_id'] = $data['product_id'] ?? null;
            $properties['status'] = $data['status'] ?? null;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;
            $properties['fields_count'] = count($data['fields'] ?? []);

            activity('security-master-data')
                ->performedOn($pending)
                ->withProperties($properties)
                ->log('Security Master Data creation request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log security master data creation', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log security master data update request
     */
    public function logSecurityMasterUpdate(SecurityMasterData $securityMaster, array $data): void
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

            $properties['security_name'] = $data['security_name'] ?? null;
            $properties['product_id'] = $data['product_id'] ?? null;
            $properties['status'] = $data['status'] ?? null;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;
            $properties['fields_count'] = count($data['fields'] ?? []);

            activity('security-master-data')
                ->performedOn($securityMaster)
                ->withProperties($properties)
                ->log('Security Master Data update request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log security master data update', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log security master data deletion request
     */
    public function logSecurityMasterDeletion(SecurityMasterData $securityMaster, array $data): void
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

            $properties['security_name'] = $securityMaster->security_name;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('security-master-data')
                ->performedOn($securityMaster)
                ->withProperties($properties)
                ->log('Security Master Data deletion request submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log security master data deletion', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log security master data approval
     */
    public function logApproval(PendingSecurityMasterData $pendingSecurityMaster, ?int $authoriserId): void
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

            $properties['request_type'] = $pendingSecurityMaster->request_type;

            activity('security-master-data')
                ->performedOn($pendingSecurityMaster)
                ->withProperties($properties)
                ->log('Security Master Data approval request approved');
        } catch (\Exception $e) {
            Log::error('Failed to log security master data approval', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log security master data rejection
     */
    public function logRejection(PendingSecurityMasterData $pendingSecurityMaster, ?int $authoriserId, string $reason): void
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

            $properties['request_type'] = $pendingSecurityMaster->request_type;

            activity('security-master-data')
                ->performedOn($pendingSecurityMaster)
                ->withProperties($properties)
                ->log('Security Master Data approval request rejected');
        } catch (\Exception $e) {
            Log::error('Failed to log security master data rejection', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log view operations
     */
    public function logView(string $action, array $properties = []): void
    {
        try {
            activity('security-master-data')
                ->withProperties($properties)
                ->log($action);
        } catch (\Exception $e) {
            Log::error('Failed to log security master data view', ['error' => $e->getMessage()]);
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

            $properties['category_id'] = $data['category_id'] ?? null;
            $properties['authoriser_id'] = $data['authoriser_id'] ?? null;

            activity('security-master-data')
                ->withProperties($properties)
                ->log('Security Master Data bulk upload submitted');
        } catch (\Exception $e) {
            Log::error('Failed to log security master data bulk upload', ['error' => $e->getMessage()]);
        }
    }
}
