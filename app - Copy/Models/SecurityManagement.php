<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityManagement extends Model
{
    protected $table = 'security_management';

    protected $fillable = [
        'category_id',
        'product_id',
        'field_name',
        'field_type',
        'required',
        'status',
    ];

    protected $casts = [
        'required' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Get the market category that owns the security management field.
     */
    public function category()
    {
        return $this->belongsTo(MarketCategory::class, 'category_id');
    }

    /**
     * Get the product type associated with the security management field.
     */
    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'product_id');
    }
}
