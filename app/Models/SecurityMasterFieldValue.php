<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityMasterFieldValue extends Model
{
    protected $fillable = [
        'security_master_id',
        'field_id',
        'field_value',
    ];

    /**
     * Get the security master record
     */
    public function securityMaster()
    {
        return $this->belongsTo(SecurityMasterData::class, 'security_master_id');
    }

    /**
     * Get the field definition
     */
    public function field()
    {
        return $this->belongsTo(SecurityManagement::class, 'field_id');
    }
}
