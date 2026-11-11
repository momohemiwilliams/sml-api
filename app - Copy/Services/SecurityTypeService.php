<?php

namespace App\Services;

use App\Models\SecurityType;
use App\Models\PendingSecurityType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Pagination\LengthAwarePaginator;

class SecurityTypeService
{
    protected $externalUserService;

    public function __construct(ExternalUserService $externalUserService)
    {
        $this->externalUserService = $externalUserService;
    }

    /**
     * Get list of security types.
     */
    public function getAllSecurityTypes()
    {
        $types = SecurityType::latest()->get();

        return $this->externalUserService->enrichWithUsers($types, [
            'created_by' => 'inputter', // Assuming created_by exists on SecurityType, check migration if possible? No, user didn't show model. Assuming standard audit.
            // Wait, SecurityType model wasn't shown fully. The controller doesn't show enrichment.
            // I'll stick to what I know. Pending needs it for sure.
        ]);
    }

    /**
     * Get pending requests.
     */
    public function getPendingRequests()
    {
        $pending = PendingSecurityType::with(['requester', 'selectedAuthoriser', 'securityType'])
            ->latest()
            ->get();

        return $this->externalUserService->enrichWithUsers($pending, [
            'requested_by' => 'inputter',
            'selected_authoriser_id' => 'authoriser'
        ]);
    }

    /**
     * Create a request to create a new security type.
     */
    public function createRequest(array $data): PendingSecurityType
    {
        return DB::transaction(function () use ($data) {
            $pending = PendingSecurityType::create([
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
     * Create a request to update an existing security type.
     */
    public function updateRequest(SecurityType $securityType, array $data): PendingSecurityType
    {
        return DB::transaction(function () use ($securityType, $data) {
            // Set the main security type to pending_approval
            $securityType->update(['approval_status' => 'pending_approval']);
            
            $pending = PendingSecurityType::create([
                'security_type_id' => $securityType->id,
                'name' => $data['name'] ?? $securityType->name,
                'code' => $data['code'] ?? $securityType->code,
                'description' => $data['description'] ?? $securityType->description,
                'is_active' => isset($data['is_active']) ? $data['is_active'] : $securityType->is_active,
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
     * Create a request to delete a security type.
     */
    public function deleteRequest(SecurityType $securityType, array $data): PendingSecurityType
    {
        return DB::transaction(function () use ($securityType, $data) {
            // Set the main security type to pending_approval
            $securityType->update(['approval_status' => 'pending_approval']);
            
            $pending = PendingSecurityType::create([
                'security_type_id' => $securityType->id,
                'name' => $securityType->name, // Snapshot
                'code' => $securityType->code,
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
    public function approveRequest(PendingSecurityType $pending): mixed
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending) {
            $result = null;

            switch ($pending->request_type) {
                case 'create':
                    $result = SecurityType::create([
                        'name' => $pending->name,
                        'code' => $pending->code,
                        'description' => $pending->description,
                        'is_active' => $pending->is_active,
                        'approval_status' => 'active',
                    ]);
                    break;

                case 'update':
                    $securityType = $pending->securityType;
                    if ($securityType) {
                        $securityType->update([
                            'name' => $pending->name,
                            'code' => $pending->code,
                            'description' => $pending->description,
                            'is_active' => $pending->is_active,
                            'approval_status' => 'active',
                        ]);
                        $result = $securityType;
                    }
                    break;

                case 'delete':
                    $securityType = $pending->securityType;
                    if ($securityType) {
                        // Soft delete
                        $securityType->delete();
                        $result = true;
                    }
                    break;
            }

            $pending->update(['approval_status' => 'approved']);

            // Notify Requester
            $this->notifyRequester($pending, 'approved');

            return $result; // Might need enrichment for result if controller uses it

        });
    }

    /**
     * Reject a pending request.
     */
    public function rejectRequest(PendingSecurityType $pending, string $reason): PendingSecurityType
    {
        if ($pending->approval_status !== 'pending') {
            throw new \Exception('Request is not pending.');
        }

        return DB::transaction(function () use ($pending, $reason) {
            // Revert the main security type approval_status back to active if it was an update/delete request
            if (in_array($pending->request_type, ['update', 'delete']) && $pending->securityType) {
                $pending->securityType->update(['approval_status' => 'active']);
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
    private function notifySelectedAuthoriser(PendingSecurityType $pending): void
    {
        $authoriser = $this->externalUserService->getUserById($pending->selected_authoriser_id);
        if ($authoriser && isset($authoriser['email'])) {
             Mail::to($authoriser['email'], $authoriser['name'] ?? null)
                ->send(new \App\Mail\SecurityTypeRequestPending($pending, $authoriser['name'] ?? 'Authoriser'));
        }
    }

    /**
     * Notify requester (Inputter) of approval/rejection.
     */
    private function notifyRequester(PendingSecurityType $pending, string $status): void
    {
        $requester = $this->externalUserService->getUserById($pending->requested_by);
        
        if (!$requester || !isset($requester['email'])) {
            return;
        }

        if ($status === 'approved') {
            Mail::to($requester['email'], $requester['name'] ?? null)
                ->send(new \App\Mail\SecurityTypeRequestApproved($pending, $requester['name'] ?? 'Inputter'));
        } elseif ($status === 'rejected') {
            Mail::to($requester['email'], $requester['name'] ?? null)
                ->send(new \App\Mail\SecurityTypeRequestRejected($pending, $pending->rejection_reason, $requester['name'] ?? 'Inputter'));
        }
    }
}
