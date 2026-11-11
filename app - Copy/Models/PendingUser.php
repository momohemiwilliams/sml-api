<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'firstname',
        'last_name',
        'email',
        'department',
        'employee_id',
        'role',
        'password',
        'requested_by',
        'approval_status',
        'rejection_reason',
        'is_active',
    ];

    /**
     * Get the user who requested this account.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->last_name}";
    }
}
