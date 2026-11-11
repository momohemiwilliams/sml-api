<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingMarketCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'market_category_id',
        'name',
        'code',
        'description',
        'is_active',
        'request_type', // create, update, delete
        'requested_by',
        'approval_status',
        'rejection_reason',
        'selected_authoriser_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who requested this action.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the market category (if update/delete).
     */
    public function marketCategory(): BelongsTo
    {
        return $this->belongsTo(MarketCategory::class, 'market_category_id')->withTrashed();
    }
}
