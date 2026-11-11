<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingProductType extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'market_category_id',
        'name',
        'code',
        'description',
        'is_active',
        'request_type',
        'requested_by',
        'selected_authoriser_id',
        'approval_status',
        'rejection_reason',
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
     * Get the Authoriser selected for this action.
     */
    public function selectedAuthoriser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_authoriser_id');
    }

    /**
     * Get the product type (if update/delete).
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id')->withTrashed();
    }

    /**
     * Get the market category.
     */
    public function marketCategory(): BelongsTo
    {
        return $this->belongsTo(MarketCategory::class, 'market_category_id');
    }
}
