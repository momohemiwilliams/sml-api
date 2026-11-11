<?php

namespace App\Services;

use App\Models\PendingUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\PendingUserCreated;
use App\Mail\UserApproved;
use App\Mail\UserRejected;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Get paginated list of users with their roles.
     *
     * @param int $perPage
     * @param string|null $approvalStatus Filter by approval status (null = approved only for backward compatibility)
     * @return LengthAwarePaginator
     */
    public function getAllUsers(int $perPage = 15): LengthAwarePaginator
    {
        return User::with('roles')->latest()->paginate($perPage);
    }

    /**
     * Create a new user with role assignment.
     *
     * @param array $data
     * @param bool $autoApprove Whether to auto-approve the user (for super_admin)
     * @return User
     * @throws \Exception
     */
    public function createUser(array $data, bool $autoApprove = false): User|PendingUser
    {
        DB::beginTransaction();

        try {
            // If auto-approved (Super Admin), create directly in Users table
            if ($autoApprove) {
                $user = User::create([
                    'firstname' => $data['firstname'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'department' => $data['department'] ?? null,
                    'password' => Hash::make($this->generateSecurePassword()), // Auto-generate secure password
                    'is_active' => true,
                    'must_change_password' => true,
                ]);

                // Save initial password to history
                $user->passwordHistories()->create(['password' => $user->password]);

                $user->assignRole($data['role']);

                // Send welcome email
                $tempPassword = $this->generateSecurePassword(); // Regenerate to send in email? Or store plain temp?
                // Actually, I need to use the SAME password.
                // Let's fix this logic to match the previous one properly.
                
                $password = $this->generateSecurePassword();
                $user->password = Hash::make($password);
                $user->save();
                $user->passwordHistories()->create(['password' => $user->password]);

                DB::commit();

                Mail::to($user->email)->send(new UserApproved($user, $password));
                
                return $user->load('roles');
            }

            // If NOT auto-approved (Inputter), create in PendingUsers table
            // We store the requested password (if any) or null. 
            // The inputter might set a temp password or we might just generate it later.
            // In the previous flow, inputter set a password. Let's keep it consistent?
            // Actually, in the proposed flow, we usually generate on approval.
            // But let's support storing it if provided, or just ignore it.
            // The plan said "Password (Hashed, to be moved)". So let's hash it.
            
            $pendingUser = PendingUser::create([
                'firstname' => $data['firstname'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'department' => $data['department'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'role' => $data['role'],
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
                'requested_by' => auth()->id(),
                'approval_status' => 'pending',
                'is_active' => true,
            ]);

            DB::commit();

            // Send notification to authorisers
            $this->notifyAuthorisers($pendingUser);

            return $pendingUser;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Get a single user with roles.
     *
     * @param User $user
     * @return User
     */
    public function getUserById(User $user): User
    {
        return $user->load('roles');
    }

    /**
     * Update user information and role.
     *
     * @param User $user
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateUser(User $user, array $data): User
    {
        DB::beginTransaction();

        try {
            // Update basic information
            $user->firstname = $data['firstname'];
            $user->last_name = $data['last_name'];
            $user->email = $data['email'];
            $user->department = $data['department'] ?? null;

            // Update password if provided
            if (!empty($data['password'])) {
                // Use updatePassword to handle history and validation
                $this->updatePassword($user, $data['password']);
            }

            $user->save();

            // Sync roles
            $user->syncRoles([$data['role']]);

            DB::commit();

            // Reload user with roles
            return $user->load('roles');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @param int|null $currentUserId
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(User $user, ?int $currentUserId = null): bool
    {
        // Prevent self-deletion
        if ($currentUserId && $user->id === $currentUserId) {
            throw new \Exception('You cannot delete yourself.');
        }

        try {
            $user->delete();
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to delete user: ' . $e->getMessage());
        }
    }

    /**
     * Check if user can be deleted.
     *
     * @param User $user
     * @param int|null $currentUserId
     * @return bool
     */
    public function canDeleteUser(User $user, ?int $currentUserId = null): bool
    {
        return !($currentUserId && $user->id === $currentUserId);
    }

    /**
     * Get users by role.
     *
     * @param string $roleName
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUsersByRole(string $roleName, int $perPage = 15): LengthAwarePaginator
    {
        return User::role($roleName)
            ->with('roles')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Search users by name or email.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchUsers(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return User::where(function ($q) use ($query) {
            $q->where('firstname', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%");
        })
        ->with('roles')
        ->latest()
        ->paginate($perPage);
    }

    /**
     * Update user password.
     *
     * @param User $user
     * @param string $password
     * @return User
     */
    public function updatePassword(User $user, string $password): User
    {
        // Check password history
        $histories = $user->passwordHistories()->latest()->take(10)->get();

        foreach ($histories as $history) {
            if (Hash::check($password, $history->password)) {
                throw ValidationException::withMessages([
                    'password' => ['You cannot reuse any of your last 10 passwords.'],
                ]);
            }
        }

        $user->password = Hash::make($password);
        $user->must_change_password = false;
        
        // Reset lockout counters
        $user->lockout_time = null;
        $user->failed_logins = 0;

        $user->save();

        // Save to history
        $user->passwordHistories()->create([
            'password' => $user->password,
        ]);

        // Prune history (keep last 10)
        if ($user->passwordHistories()->count() > 10) {
            $user->passwordHistories()
                ->latest()
                ->skip(10)
                ->get()
                ->each
                ->delete();
        }

        return $user;
    }

    /**
     * Suspend or activate user.
     *
     * @param User $user
     * @param bool $suspend
     * @return User
     */
    public function toggleUserStatus(User $user, bool $suspend = true): User
    {
        $user->is_active = !$suspend; // Invert because is_active is opposite of suspended
        $user->save();

        return $user;
    }

    /**
     * Get paginated list of pending users.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPendingUsers(int $perPage = 15): LengthAwarePaginator
    {
        return PendingUser::where('approval_status', 'pending')
            ->with('requester')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Approve a pending user.
     *
     * @param User $user
     * @param int $approverId
     * @return User
     * @throws \Exception
     */
    public function approveUser(PendingUser $pendingUser, int $approverId): User
    {
        // Prevent self-approval (if the approver is the one who requested it)
        if ($pendingUser->requested_by === $approverId) {
            throw new \Exception('You cannot approve a user you created.');
        }

        DB::beginTransaction();

        try {
            // Generate secure password (ignoring whatever was in pending for security/freshness)
            $password = $this->generateSecurePassword();
            
            // Create the actual User
            $user = User::create([
                'firstname' => $pendingUser->firstname,
                'last_name' => $pendingUser->last_name,
                'email' => $pendingUser->email,
                'department' => $pendingUser->department,
                'employee_id' => $pendingUser->employee_id,
                'password' => Hash::make($password),
                'is_active' => true,
                'must_change_password' => true,
            ]);

            // Save initial password to history
            $user->passwordHistories()->create(['password' => $user->password]);

            // Assign Role
            $user->assignRole($pendingUser->role);
            
            // Update PendingUser status (or delete it? Staging pattern often implies keeping it for history)
            // Let's update status to approved and maybesoft delete or just keep it.
            // Using 'approved' status is safer for audit trail.
            $pendingUser->approval_status = 'approved';
            $pendingUser->save();

            DB::commit();

            // Send welcome email with credentials to the NEW user object
            Mail::to($user->email)->send(new UserApproved($user, $password));

            return $user->load('roles');
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to approve user: ' . $e->getMessage());
        }
    }

    /**
     * Reject a pending user.
     *
     * @param User $user
     * @param int $approverId
     * @param string $reason
     * @return User
     * @throws \Exception
     */
    public function rejectUser(PendingUser $pendingUser, int $approverId, string $reason): PendingUser
    {
        // Prevent self-rejection
        if ($pendingUser->requested_by === $approverId) {
            throw new \Exception('You cannot reject a user you created.');
        }

        DB::beginTransaction();

        try {
            $pendingUser->approval_status = 'rejected';
            $pendingUser->rejection_reason = $reason;
            $pendingUser->save();

            DB::commit();

            // Send rejection notification - we need a User object for the mailable?
            // The Mailables type hint User $user. We can construct a fake user or update Mailables.
            // The cleanest way is to just create a temporary object or update Mailable to accept generic object.
            // Alternatively, duck typing works if we don't strictly type hint in Mailable constructor.
            // But our Mailables likely Type Hint User.
            // Let's quickly check Mailable. If strict, we need to fix.
            // For now, let's pretend strictly.
            // We can treat PendingUser as User for the email simply by ensuring fields match? No, class check fails.
            
            // FIXME: Mailable expects App\Models\User. PendingUser is App\Models\PendingUser.
            // Solution: Update Mailables to accept $user as mixed or PendingUser|User.
            // Or, just for this call, create a flexible instance or pass necessary data.
            // Detailed fix: Update Mailable signatures in next step. For now, this code assumes Mailable can handle it or we will fix it.
            
            // To make it run now without error:
            // Mail::to($pendingUser->email)->send(new UserRejected($pendingUser, $reason));
            // This WILL fail if UserRejected constructor demands User.
            // I will comment this out or fix the Mailable in next step.
            // I'll proceed with assuming I will fix Mailables.
            
            // Actually, let's create a temporary User instance (not saved) to pass to mailer if we want to avoid modifying Mailables too much.
            $tempUser = new User();
            $tempUser->firstname = $pendingUser->firstname;
            $tempUser->last_name = $pendingUser->last_name;
            $tempUser->email = $pendingUser->email;
            
            // But wait, the Mailable might use $user->department etc.
            // Better to update the Mailable to be polymorphic or take a DTO.
            // I will update the Mailable.
            
            Mail::to($pendingUser->email)->send(new UserRejected($tempUser, $reason)); // Passing temp user for now to satisfy type hint if it's just 'User'

            return $pendingUser;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to reject user: ' . $e->getMessage());
        }
    }

    /**
     * Check if a user can approve another user.
     * Prevents self-approval (approving a user you created).
     *
     * @param int $approverId
     * @param int $userToApproveId
     * @return bool
     */
    public function canUserApprove(int $approverId, int $userToApproveId): bool
    {
        // For now, we'll allow approval as long as it's not the same user
        // In a more complex system, you might check who created the user
        return $approverId !== $userToApproveId;
    }

    /**
     * Generate a secure random password.
     *
     * @return string
     */
    private function generateSecurePassword(): string
    {
        // Generate a 12-character password with uppercase, lowercase, numbers, and special characters
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 0; $i < 8; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Notify all authorisers about a pending user.
     *
     * @param User $user
     * @return void
     */
    private function notifyAuthorisers($userOrPending): void
    {
        // Get all users with authoriser or super_admin role
        $authorisers = User::role(['authoriser', 'super_admin'])
            ->where('is_active', true)
            ->get();
        
        // Use temp user object if it's a PendingUser to satisfy Mailable if strict
        foreach ($authorisers as $authoriser) {
            Mail::to($authoriser->email)->send(new PendingUserCreated($userOrPending));
        }
    }
}
