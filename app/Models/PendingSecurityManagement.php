<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingSecurityManagement extends Model
{
    use HasFactory;

    protected $table = 'pending_security_management';

    protected $fillable = [
        'security_management_id',
        'category_id',
        'product_id',
        'field_name',
        'field_type',
        'required',
        'status',
        'request_type',
        'requested_by',
        'selected_authoriser_id',
        'approval_status',
        'rejection_reason',
    ];

    protected $casts = [
        'required' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Get the main security management record (if update/delete).
     */
    public function mainRecord(): BelongsTo
    {
        return $this->belongsTo(SecurityManagement::class, 'security_management_id');
    }

    /**
     * Get the market category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketCategory::class, 'category_id')->withTrashed();
    }

    /**
     * Get the product type.
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_id')->withTrashed();
    }

    /**
     * Get the user who requested this action.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the authoriser.
     */
    public function authoriser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_authoriser_id');
    }
}
