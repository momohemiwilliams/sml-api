<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingSecurityMasterData extends Model
{
    use HasFactory;

    protected $table = 'pending_security_master_data';

    protected $fillable = [
        'security_master_id',
        'category_id',
        'product_id',
        'security_name',
        'status',
        'fields_data',
        'amortisation_schedule',
        'call_schedule',
        'reset_schedule',
        'event_data',
        'request_type',
        'requested_by',
        'selected_authoriser_id',
        'approval_status',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => 'boolean',
        'fields_data' => 'array',
        'amortisation_schedule' => 'array',
        'call_schedule' => 'array',
        'reset_schedule' => 'array',
        'event_data' => 'array',
    ];

    /**
     * Get the user who requested this action.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the authoriser (optional relation if we had local users, but keeping for structure).
     */
    public function authoriser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_authoriser_id');
    }

    /**
     * Get the main security master record (if update/delete).
     */
    public function mainRecord(): BelongsTo
    {
        return $this->belongsTo(SecurityMasterData::class, 'security_master_id');
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
}
