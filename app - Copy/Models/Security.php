<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Security extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, LogsActivity;

    protected $fillable = [
        'product_type_id',
        'isin',
        'security_name',
        'issuer',
        'issuer_category',
        'issue_category',
        'security_type_id',
        'description',
        'issue_date',
        'maturity_date',
        'first_settlement_date',
        'last_trading_date',
        'face_value',
        'issue_price',
        'coupon_rate',
        'coupon',
        'coupon_type',
        'coupon_frequency',
        'frm',
        'frb',
        'frbv',
        'coupon_floor',
        'coupon_cap',
        'discount_rate',
        'tenor',
        'effective_coupon',
        'fgn_benchmark_yield',
        'issue_size',
        'ttm',
        'day_count_convention',
        'day_count_basis',
        'option_type',
        'call_date',
        'yield_at_issue',
        'interest_determination_date',
        'outstanding_value',
        'amount_issued',
        'amount_outstanding',
        'rating_agency',
        'local_rating',
        'global_rating',
        'rating_1_agency',
        'rating_1',
        'rating_1_issuance_date',
        'rating_1_expiration_date',
        'rating_2_agency',
        'rating_2',
        'rating_2_issuance_date',
        'rating_2_expiration_date',
        'final_rating',
        'listing_status',
        'status',
        'remarks',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
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
        'face_value' => 'decimal:2',
        'issue_price' => 'decimal:2',
        'issue_size' => 'decimal:2',
        'coupon_rate' => 'decimal:4',
        'coupon' => 'decimal:4',
        'frm' => 'decimal:4',
        'frbv' => 'decimal:4',
        'coupon_floor' => 'decimal:4',
        'coupon_cap' => 'decimal:4',
        'discount_rate' => 'decimal:4',
        'effective_coupon' => 'decimal:4',
        'fgn_benchmark_yield' => 'decimal:4',
        'ttm' => 'decimal:4',
        'outstanding_value' => 'decimal:2',
        'amount_issued' => 'decimal:2',
        'amount_outstanding' => 'decimal:2',
        'tenor' => 'integer',
        'coupon_frequency' => 'integer',
        'day_count_basis' => 'integer',
        'approved_at' => 'datetime',
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
     * Get the product type that owns the security
     */
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }

    /**
     * Get the security type that owns the security
     */
    public function securityType()
    {
        return $this->belongsTo(SecurityType::class);
    }

    /**
     * Get the auction results for the security
     */
    public function auctionResults()
    {
        return $this->hasMany(AuctionResult::class);
    }

    /**
     * Get the user who created the security
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the security
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who approved the security
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['isin', 'security_name', 'issuer', 'issue_date', 'maturity_date', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope to get only active securities
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope to get only matured securities
     */
    public function scopeMatured($query)
    {
        return $query->where('status', 'Matured');
    }

    /**
     * Scope to filter by product type
     */
    public function scopeByProductType($query, $productTypeId)
    {
        return $query->where('product_type_id', $productTypeId);
    }

    /**
     * Scope to filter by issuer
     */
    public function scopeByIssuer($query, $issuer)
    {
        return $query->where('issuer', 'like', "%{$issuer}%");
    }

    /**
     * Calculate tenor in years
     */
    public function calculateTenor()
    {
        if ($this->issue_date && $this->maturity_date) {
            $issueDate = \Carbon\Carbon::parse($this->issue_date);
            $maturityDate = \Carbon\Carbon::parse($this->maturity_date);
            return $maturityDate->diffInYears($issueDate);
        }
        return null;
    }

    /**
     * Calculate time to maturity
     */
    public function calculateTTM()
    {
        if ($this->maturity_date) {
            $maturityDate = \Carbon\Carbon::parse($this->maturity_date);
            $today = \Carbon\Carbon::today();
            
            if ($maturityDate->isFuture()) {
                return $today->diffInDays($maturityDate) / 365;
            }
        }
        return 0;
    }

    /**
     * Check if security is matured
     */
    public function isMatured()
    {
        if ($this->maturity_date) {
            return \Carbon\Carbon::parse($this->maturity_date)->isPast();
        }
        return false;
    }
}
