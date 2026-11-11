<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallSchedule extends Model
{
    protected $fillable = [
        'security_master_id',
        'call_date',
        'call_price',
        'call_amount',
        'status',
        'exercised_date',
        'exercised_price',
        'exercised_amount',
        'reminded_at',
    ];

    protected $casts = [
        'call_date' => 'date:Y-m-d',
        'exercised_date' => 'date:Y-m-d',
        'reminded_at' => 'datetime',
    ];

    public function securityMasterData()
    {
        return $this->belongsTo(SecurityMasterData::class, 'security_master_id');
    }
}
