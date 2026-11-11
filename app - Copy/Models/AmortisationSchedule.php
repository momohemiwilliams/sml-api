<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AmortisationSchedule extends Model
{
    protected $fillable = [
        'security_master_id',
        'date',
        'scheduled_coupon_cashflow',
        'scheduled_principal_cashflow',
        'outstanding_amount',
    ];

    public function securityMasterData()
    {
        return $this->belongsTo(SecurityMasterData::class, 'security_master_id');
    }
}
