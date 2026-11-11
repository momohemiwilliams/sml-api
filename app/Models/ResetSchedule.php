<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResetSchedule extends Model
{
    protected $fillable = [
        'security_master_id',
        'reset_date',
        'new_coupon_rate',
        'benchmark',
        'spread',
        'status',
        'applied_date',
        'applied_rate',
        'applied_benchmark',
        'applied_spread',
        'reminded_at',
    ];

    protected $casts = [
        'reset_date' => 'date:Y-m-d',
        'applied_date' => 'date:Y-m-d',
        'reminded_at' => 'datetime',
    ];

    public function securityMasterData()
    {
        return $this->belongsTo(SecurityMasterData::class, 'security_master_id');
    }
}
