<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AuctionResult extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable, LogsActivity;

    protected $fillable = [
        'security_id',
        'auction_number',
        'auction_date',
        'value_date',
        'day_of_week',
        'tenor_days',
        'amount_offered',
        'amount_subscribed',
        'amount_allotted',
        'amount_sold',
        'non_competitive_sales',
        'total_amount_sold',
        'stop_rate',
        'marginal_rate',
        'true_yield',
        'bid_cover_ratio',
        'subscription_level',
        'auction_type',
        'status',
        'approval_status',
        'remarks',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'auction_date' => 'date',
        'value_date' => 'date',
        'amount_offered' => 'decimal:2',
        'amount_subscribed' => 'decimal:2',
        'amount_allotted' => 'decimal:2',
        'amount_sold' => 'decimal:2',
        'non_competitive_sales' => 'decimal:2',
        'total_amount_sold' => 'decimal:2',
        'stop_rate' => 'decimal:4',
        'marginal_rate' => 'decimal:4',
        'true_yield' => 'decimal:4',
        'bid_cover_ratio' => 'decimal:4',
        'subscription_level' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function security()
    {
        return $this->belongsTo(SecurityMasterData::class, 'security_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pendingRequests()
    {
        return $this->hasMany(PendingAuctionResult::class, 'auction_result_id');
    }

    public function calculateRatios()
    {
        // Bid Cover Ratio = Amount Subscribed / Total Amount Sold (or Allotted?)
        // Usually: Total Bids / Total Sold
        if ($this->total_amount_sold > 0) {
            $this->bid_cover_ratio = $this->amount_subscribed / $this->total_amount_sold;
        }

        // Subscription Level = (Amount Subscribed / Amount Offered) * 100
        if ($this->amount_offered > 0) {
            $this->subscription_level = ($this->amount_subscribed / $this->amount_offered) * 100;
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
