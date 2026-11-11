<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'market_category_id',
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
     * Get the market category that owns the product type
     */
    public function marketCategory()
    {
        return $this->belongsTo(MarketCategory::class);
    }

    /**
     * Get the securities for the product type
     */
    public function securities()
    {
        return $this->hasMany(Security::class);
    }

    /**
     * Scope to get only active product types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by market category
     */
    public function scopeByMarketCategory($query, $marketCategoryId)
    {
        return $query->where('market_category_id', $marketCategoryId);
    }
}
