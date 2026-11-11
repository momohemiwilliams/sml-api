<?php

namespace App\Services;

use App\Models\SecurityManagement;
use App\Models\PendingSecurityManagement;
use App\Models\MarketCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SecurityManagementService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get paginated list of pending requests.
     */
    public function getPendingRequests(int $perPage = 15)
    {
        $pending = PendingSecurityManagement::with(['requester', 'category', 'productType', 'mainRecord'])
            ->latest()
            ->paginate($perPage);

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'Inputter',
            'selected_authoriser_id' => 'Authoriser'
        ]);
    }

    /**
     * Directly create a security management field.
     */
    public function store(array $data)
    {
        $productId = $data['product_id'] ?? null;

        return SecurityManagement::create([
            'category_id' => $this->categoryIdFor($productId, $data['category_id']),
            'product_id' => $productId,
            'field_name' => $data['field_name'],
            'field_type' => $data['field_type'],
            'required' => $data['required'] ?? false,
            'status' => $data['status'] ?? 1,
        ]);
    }

    /**
     * Directly update a security management field.
     */
    public function update(SecurityManagement $field, array $data)
    {
        $field->update([
            'product_id' => array_key_exists('product_id', $data) ? $data['product_id'] : $field->product_id,
            'field_name' => array_key_exists('field_name', $data) ? $data['field_name'] : $field->field_name,
            'field_type' => array_key_exists('field_type', $data) ? $data['field_type'] : $field->field_type,
            'required' => array_key_exists('required', $data) ? $data['required'] : $field->required,
            'status' => array_key_exists('status', $data) ? $data['status'] : $field->status,
        ]);

        return $field;
    }

    /**
     * Directly delete a security management field.
     */
    public function delete(SecurityManagement $field)
    {
        return $field->delete();
    }

    /**
     * Create a new security management creation request
     */
    public function createRequest(array $data)
    {
        return DB::transaction(function () use ($data) {
            $pending = PendingSecurityManagement::create([
                'category_id' => $data['category_id'],
                'product_id' => $data['product_id'] ?? null,
                'field_name' => $data['field_name'],
                'field_type' => $data['field_type'],
                'required' => $data['required'] ?? false,
                'status' => $data['status'] ?? 1,
                'request_type' => 'create',
                'requested_by' => $data['created_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            // Notifications can be added here
            return $pending;
        });
    }

    /**
     * Create update request
     */
    public function updateRequest(SecurityManagement $field, array $data)
    {
        return DB::transaction(function () use ($field, $data) {
            // $field->update(['status' => 0]); // Should we enhance locking? Or just let it be.

            $pending = PendingSecurityManagement::create([
                'security_management_id' => $field->id,
                'category_id' => $field->category_id,
                'product_id' => array_key_exists('product_id', $data) ? $data['product_id'] : $field->product_id,
                'field_name' => array_key_exists('field_name', $data) ? $data['field_name'] : $field->field_name,
                'field_type' => array_key_exists('field_type', $data) ? $data['field_type'] : $field->field_type,
                'required' => array_key_exists('required', $data) ? $data['required'] : $field->required,
                'status' => array_key_exists('status', $data) ? $data['status'] : $field->status,
                'request_type' => 'update',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            return $pending;
        });
    }

    /**
     * Create delete request
     */
    public function deleteRequest(SecurityManagement $field, array $data)
    {
        return DB::transaction(function () use ($field, $data) {
            $pending = PendingSecurityManagement::create([
                'security_management_id' => $field->id,
                'category_id' => $field->category_id,
                'product_id' => $field->product_id,
                'field_name' => $field->field_name,
                'field_type' => $field->field_type,
                'required' => $field->required,
                'status' => $field->status,
                'request_type' => 'delete',
                'requested_by' => $data['updated_by'],
                'selected_authoriser_id' => $data['authoriser_id'] ?? null,
                'approval_status' => 'pending',
            ]);

            return $pending;
        });
    }

    /**
     * Approve a pending request.
     * 
     * If the product_id references a pending product type (not yet in the main table),
     * it will be auto-approved and moved to the main product_types table first.
     */
    public function approveRequest(PendingSecurityManagement $pending)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            // Check if product_id refers to a pending product type that needs to be approved first
            $resolvedProductId = $this->resolveProductId($pending->product_id);

            switch ($pending->request_type) {
                case 'create':
                    $field = SecurityManagement::create([
                        'category_id' => $this->categoryIdFor($resolvedProductId, $pending->category_id),
                        'product_id' => $resolvedProductId,
                        'field_name' => $pending->field_name,
                        'field_type' => $pending->field_type,
                        'required' => $pending->required,
                        'status' => $pending->status,
                        'created_by' => $pending->requested_by,
                    ]);
                    $result = $field;
                    break;

                case 'update':
                    $field = $pending->mainRecord;
                    if ($field) {
                        $field->update([
                            'category_id' => $this->categoryIdFor($resolvedProductId, $field->category_id),
                            'product_id' => $resolvedProductId,
                            'field_name' => $pending->field_name,
                            'field_type' => $pending->field_type,
                            'required' => $pending->required,
                            'status' => $pending->status,
                        ]);
                        $result = $field;
                    }
                    break;

                case 'delete':
                    $field = $pending->mainRecord;
                    if ($field) {
                        $field->delete();
                        $result = true;
                    }
                    break;
            }

            // Update the product_id on the pending record if it was resolved to a new ID
            $pending->update([
                'approval_status' => 'approved',
                'product_id' => $resolvedProductId,
            ]);
            
            // Notify Requester logic here

            return $result;
        });
    }

    /**
     * A product-specific field must carry its product's market category. Clients have
     * submitted field definitions with a category_id that did not match the product
     * (e.g. Bonds fields attached to a Money Market product), which made those fields
     * invisible to create/bulk-upload lookups that filtered on both columns.
     */
    protected function categoryIdFor($productId, $fallbackCategoryId)
    {
        if (!$productId) {
            return $fallbackCategoryId;
        }

        $marketCategoryId = \App\Models\ProductType::withTrashed()->where('id', $productId)->value('market_category_id');

        return $marketCategoryId ?: $fallbackCategoryId;
    }

    /**
     * Resolve a product_id: if it references a PendingProductType (not in the main table),
     * auto-approve it and return the new product type's ID.
     */
    protected function resolveProductId($productId)
    {
        if (!$productId) {
            return null;
        }

        // Check if it already exists in the main product_types table
        $existingProduct = \App\Models\ProductType::find($productId);
        if ($existingProduct) {
            return $productId; // Already in main table, no action needed
        }

        // Check if it's a pending product type
        $pendingProduct = \App\Models\PendingProductType::where('id', $productId)
            ->where('approval_status', 'pending')
            ->first();

        if ($pendingProduct) {
            // Auto-approve the pending product type and move to main table
            $newProduct = \App\Models\ProductType::create([
                'market_category_id' => $pendingProduct->market_category_id,
                'name' => $pendingProduct->name,
                'code' => $pendingProduct->code,
                'description' => $pendingProduct->description,
                'is_active' => $pendingProduct->is_active,
                'approval_status' => 'active',
            ]);

            $pendingProduct->update(['approval_status' => 'approved']);

            return $newProduct->id;
        }

        // product_id doesn't match anything — return as-is
        return $productId;
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingSecurityManagement $pending, string $reason)
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending, $reason) {
            $pending->update([
                'approval_status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            // Notify Requester logic here

            return $pending;
        });
    }
}
