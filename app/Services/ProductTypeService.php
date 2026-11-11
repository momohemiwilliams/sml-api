<?php

namespace App\Services;

use App\Models\ProductType;
use App\Models\PendingProductType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductTypeService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get list of product types.
     */
    public function getAllProductTypes(): \Illuminate\Database\Eloquent\Collection
    {
        $types = ProductType::with('marketCategory')
            ->withCount('securities')
            ->latest()
            ->get();

        return $this->externalUserService->enrichWithUsers($types, [
             'created_by' => 'inputter',
        ]);
    }

    /**
     * Get pending requests for the current user (if authoriser) or all if super admin?
     * logic: Authorisers should ideally see requests assigned to them.
     */
    public function getPendingRequests(): \Illuminate\Database\Eloquent\Collection
    {
        $pending = PendingProductType::with(['requester', 'selectedAuthoriser', 'productType', 'marketCategory'])
            ->latest()
            ->get();

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'inputter',
            'selected_authoriser_id' => 'authoriser'
        ]);
    }

    /**
     * Create a request to create a new product type.
     */
    public function createRequest(array $data): PendingProductType
    {
        return DB::transaction(function () use ($data) {
            $pending = PendingProductType::create([
                'market_category_id' => $data['market_category_id'],
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
     * Create a request to update an existing product type.
     */
    public function updateRequest(ProductType $productType, array $data): PendingProductType
    {
        return DB::transaction(function () use ($productType, $data) {
            // Set the main product type to pending_approval
            $productType->update(['approval_status' => 'pending_approval']);
            
            $pending = PendingProductType::create([
                'product_type_id' => $productType->id,
                'market_category_id' => $data['market_category_id'] ?? $productType->market_category_id,
                'name' => $data['name'] ?? $productType->name,
                'code' => $data['code'] ?? $productType->code,
                'description' => $data['description'] ?? $productType->description,
                'is_active' => isset($data['is_active']) ? $data['is_active'] : $productType->is_active,
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
     * Create a request to delete a product type.
     */
    public function deleteRequest(ProductType $productType, array $data): PendingProductType
    {
        return DB::transaction(function () use ($productType, $data) {
            // Set the main product type to pending_approval
            $productType->update(['approval_status' => 'pending_approval']);
            
            $pending = PendingProductType::create([
                'product_type_id' => $productType->id,
                'market_category_id' => $productType->market_category_id,
                'name' => $productType->name, // Snapshot
                'code' => $productType->code,
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
    public function approveRequest(PendingProductType $pending): mixed
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $result = ProductType::create([
                        'market_category_id' => $pending->market_category_id,
                        'name' => $pending->name,
                        'code' => $pending->code,
                        'description' => $pending->description,
                        'is_active' => $pending->is_active,
                        'approval_status' => 'active',
                    ]);
                    break;

                case 'update':
                    $productType = $pending->productType;
                    if ($productType) {
                        $productType->update([
                            'market_category_id' => $pending->market_category_id,
                            'name' => $pending->name,
                            'code' => $pending->code,
                            'description' => $pending->description,
                            'is_active' => $pending->is_active,
                            'approval_status' => 'active',
                        ]);
                        $result = $productType;
                    }
                    break;

                case 'delete':
                    $productType = $pending->productType;
                    if ($productType) {
                        // Soft delete
                        $productType->delete();
                        $result = true;
                    }
                    break;
            }

            // If a product type was created, cascade approval to related security management fields
            if ($pending->request_type === 'create' && $result instanceof ProductType) {
                $this->cascadeApprovalToSecurityManagement($pending->id, $result->id);
            }

            $pending->update(['approval_status' => 'approved']);

            // Notify Requester
            $this->notifyRequester($pending, 'approved');

            return $result;
        });
    }

    /**
     * Cascade approval to related pending security management fields.
     */
    protected function cascadeApprovalToSecurityManagement($pendingProductTypeId, $newProductTypeId)
    {
        // Find all pending security management records that reference the pending product type ID
        $pendingFields = \App\Models\PendingSecurityManagement::where('product_id', $pendingProductTypeId)
            ->where('approval_status', 'pending')
            ->get();

        if ($pendingFields->isEmpty()) {
            return;
        }

        $securityService = app(SecurityManagementService::class);

        foreach ($pendingFields as $field) {
            // Update the product_id to the new real ID
            $field->update(['product_id' => $newProductTypeId]);
            
            // Approve it
            try {
                $securityService->approveRequest($field);
            } catch (\Exception $e) {
                // Log or ignore if individual field approval fails? 
                // Usually we want to continue for others.
                \Log::error("Failed to cascade approve security management field ID {$field->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingProductType $pending, string $reason): PendingProductType
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending, $reason) {
            // Revert the main product type approval_status back to active if it was an update/delete request
            if (in_array($pending->request_type, ['update', 'delete']) && $pending->productType) {
                $pending->productType->update(['approval_status' => 'active']);
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
     * Notify the selected authoriser.
     */
    private function notifySelectedAuthoriser(PendingProductType $pending): void
    {
        \Log::info('Attempting to notify authoriser', [
            'pending_id' => $pending->id,
            'request_type' => $pending->request_type,
            'authoriser_id' => $pending->selected_authoriser_id
        ]);

        // 1. Try External Service
        $authoriser = $this->externalUserService->getUserById($pending->selected_authoriser_id);
        
        // 2. Fallback to Local Users table
        if (!$authoriser) {
            $localUser = \App\Models\User::find($pending->selected_authoriser_id);
            if ($localUser) {
                \Log::info('Authoriser found in local users table', ['id' => $localUser->id]);
                $authoriser = [
                    'email' => $localUser->email,
                    'name' => trim($localUser->first_name . ' ' . $localUser->last_name),
                ];
            }
        }

        if (!$authoriser) {
            \Log::warning('Authoriser not found in external service or local database', [
                'authoriser_id' => $pending->selected_authoriser_id
            ]);
            return;
        }

        if (isset($authoriser['email']) && !empty($authoriser['email'])) {
             \Log::info('Sending pending request email to authoriser', [
                 'email' => $authoriser['email'],
                 'name' => $authoriser['name'] ?? 'Authoriser'
             ]);

             Mail::to($authoriser['email'], $authoriser['name'] ?? null)
                ->send(new \App\Mail\ProductTypeRequestPending($pending, $authoriser['name'] ?? 'Authoriser'));
        } else {
            \Log::warning('Authoriser found but has no email address', [
                'authoriser' => $authoriser
            ]);
        }
    }

    /**
     * Notify requester (Inputter) of approval/rejection.
     */
    private function notifyRequester(PendingProductType $pending, string $status): void
    {
        $requester = $this->externalUserService->getUserById($pending->requested_by);
        
        if (!$requester || !isset($requester['email'])) {
            return;
        }

        if ($status === 'approved') {
            Mail::to($requester['email'], $requester['name'] ?? null)
                ->send(new \App\Mail\ProductTypeRequestApproved($pending, $requester['name'] ?? 'Inputter'));
        } elseif ($status === 'rejected') {
            Mail::to($requester['email'], $requester['name'] ?? null)
                ->send(new \App\Mail\ProductTypeRequestRejected($pending, $pending->rejection_reason, $requester['name'] ?? 'Inputter'));
        }
    }
}
