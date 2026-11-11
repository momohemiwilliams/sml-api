<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingAuctionResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'auction_result_id',
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
        'remarks',
        'request_type',
        'requested_by',
        'selected_authoriser_id',
        'approval_status',
        'rejection_reason',
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
    ];

    public function security()
    {
        return $this->belongsTo(SecurityMasterData::class, 'security_id');
    }

    public function mainRecord()
    {
        return $this->belongsTo(AuctionResult::class, 'auction_result_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'selected_authoriser_id');
    }
}
