<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'approval_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the product types for the market category
     */
    public function productTypes()
    {
        return $this->hasMany(ProductType::class);
    }

    /**
     * Get the security management fields for the market category
     */
    public function securityManagementFields()
    {
        return $this->hasMany(SecurityManagement::class, 'category_id');
    }

    /**
     * Scope to get only active market categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
