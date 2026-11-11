<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityMasterData extends Model
{
    protected $table = 'security_master_data';

    protected $fillable = [
        'category_id',
        'product_id',
        'security_name',
        'status',
        'approval_status',
        'lifecycle_status',
        'redemption_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
        'redemption_date' => 'date:Y-m-d',
    ];

    /**
     * Get the market category
     */
    public function category()
    {
        return $this->belongsTo(MarketCategory::class, 'category_id');
    }

    /**
     * Get the product type
     */
    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'product_id');
    }

    /**
     * Get all field values for this security
     */
    public function fieldValues()
    {
        return $this->hasMany(SecurityMasterFieldValue::class, 'security_master_id');
    }

    /**
     * Get all amortisation schedules for this security
     */
    public function amortisationSchedules()
    {
        return $this->hasMany(AmortisationSchedule::class, 'security_master_id');
    }

    /**
     * Get all call schedules for this security (Callable option type)
     */
    public function callSchedules()
    {
        return $this->hasMany(CallSchedule::class, 'security_master_id');
    }

    /**
     * Get all coupon reset schedules for this security (Perpetual option type)
     */
    public function resetSchedules()
    {
        return $this->hasMany(ResetSchedule::class, 'security_master_id');
    }

    /**
     * Get field values with field definitions
     */
    public function getFieldsWithValuesAttribute()
    {
        return $this->fieldValues()->with('field')->get()->map(function($fieldValue) {
            return [
                'field_id' => $fieldValue->field_id,
                'field_name' => $fieldValue->field->field_name,
                'field_type' => $fieldValue->field->field_type,
                'field_value' => $fieldValue->field_value,
                'required' => $fieldValue->field->required,
            ];
        });
    }

    /**
     * Accessor: gets the security name from field_id 3
     */
    public function getSecurityNameAttribute($value)
    {
        // If the DB has a hardcoded security_name, it uses that if present, 
        // but we override it by pulling field_id 3 if available.
        $field = $this->fieldValues->where('field_id', 3)->first();
        return $field ? $field->field_value : $value;
    }
}
