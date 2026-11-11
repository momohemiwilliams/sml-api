<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponFrequency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'frequency_per_year',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'frequency_per_year' => 'integer',
    ];

    /**
     * Scope a query to only include active frequencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
