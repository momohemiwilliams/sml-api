<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PendingSecurity extends Model
{
    use HasFactory;

    protected $table = 'pending_securities';
    protected $fillable = [
        'security_id',
        'issue_category',
        'issuer',
        'security_type_id',
        'isin',
        'description',
        'issue_date',
        'maturity_date',
        'tenor',
        'coupon',
        'coupon_type',
        'frm',
        'frb',
        'frbv',
        'coupon_floor',
        'coupon_cap',
        'coupon_frequency',
        'effective_coupon',
        'fgn_benchmark_yield',
        'issue_size',
        'outstanding_value',
        'ttm',
        'day_count_convention',
        'day_count_basis',
        'option_type',
        'call_date',
        'yield_at_issue',
        'interest_determination_date',
        'listing_status',
        'rating_1_agency',
        'rating_1',
        'rating_1_issuance_date',
        'rating_1_expiration_date',
        'rating_2_agency',
        'rating_2',
        'rating_2_issuance_date',
        'rating_2_expiration_date',
        'final_rating',
        'product_type_id',
        'first_settlement_date',
        'last_trading_date',
        'face_value',
        'issue_price',
        'discount_rate',
        'amount_issued',
        'amount_outstanding',
        'status',
        'remarks',
        'request_type',
        'requested_by',
        'selected_authoriser_id',
        'approval_status',
        'rejection_reason',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'maturity_date' => 'date',
        'first_settlement_date' => 'date',
        'last_trading_date' => 'date',
        'call_date' => 'date',
        'interest_determination_date' => 'date',
        'rating_1_issuance_date' => 'date',
        'rating_1_expiration_date' => 'date',
        'rating_2_issuance_date' => 'date',
        'rating_2_expiration_date' => 'date',
        'tenor' => 'integer',
        'coupon_frequency' => 'integer',
        'day_count_basis' => 'integer',
        'coupon' => 'decimal:4',
        'frm' => 'decimal:4',
        'frbv' => 'decimal:4',
        'coupon_floor' => 'decimal:4',
        'coupon_cap' => 'decimal:4',
        'effective_coupon' => 'decimal:4',
        'fgn_benchmark_yield' => 'decimal:4',
        'ttm' => 'decimal:4',
        'discount_rate' => 'decimal:4',
        'issue_size' => 'decimal:2',
        'outstanding_value' => 'decimal:2',
        'face_value' => 'decimal:2',
        'issue_price' => 'decimal:2',
        'amount_issued' => 'decimal:2',
        'amount_outstanding' => 'decimal:2',
    ];

    /**
     * Attributes to append to model's array form
     */
    protected $appends = ['product_type_name', 'security_type_name'];

    /**
     * Get the product type name
     */
    public function getProductTypeNameAttribute(): ?string
    {
        return $this->productType?->name;
    }

    /**
     * Get the security type name
     */
    public function getSecurityTypeNameAttribute(): ?string
    {
        return $this->securityType?->name;
    }

    /**
     * Get the security this pending request relates to
     */
    public function security()
    {
        return $this->belongsTo(Security::class);
    }

    /**
     * Get the user who requested this action
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the selected authoriser
     */
    public function selectedAuthoriser()
    {
        return $this->belongsTo(User::class, 'selected_authoriser_id');
    }

    /**
     * Get the product type
     */
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Get the security type
     */
    public function securityType()
    {
        return $this->belongsTo(SecurityType::class);
    }
}
