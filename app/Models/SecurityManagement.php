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
     * Active field definitions that apply to a security of the given product type
     * (or, when the product has no fields of its own, the category-level fields).
     *
     * Product-specific fields are matched on product_id alone: a product type belongs
     * to exactly one market category, and historically some fields were saved with a
     * category_id that differs from the product's market_category_id. Filtering on
     * both columns silently dropped those fields on create/bulk upload while the
     * configuration endpoint (which filters on product_id only) still listed them.
     */
    public function scopeApplicableTo($query, ?int $categoryId, ?int $productId = null)
    {
        $query->where('status', 1);

        if ($productId && static::where('product_id', $productId)->where('status', 1)->exists()) {
            return $query->where('product_id', $productId);
        }

        return $query->whereNull('product_id')->where('category_id', $categoryId);
    }

    /**
     * Fields visible to a security: its product's own fields plus the category-level
     * ones, ordered so a product-specific definition wins over a category-level one.
     */
    public function scopeForProductOrCategory($query, ?int $categoryId, ?int $productId = null)
    {
        return $query->where(function ($q) use ($categoryId, $productId) {
            if ($productId) {
                $q->where('product_id', $productId);
            }
            $q->orWhere(function ($c) use ($categoryId) {
                $c->whereNull('product_id')->where('category_id', $categoryId);
            });
        })->orderByRaw('product_id IS NULL');
    }

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
