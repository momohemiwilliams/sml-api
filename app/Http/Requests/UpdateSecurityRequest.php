<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecurityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Get the ID from the route parameter
        $id = $this->route('security') ? $this->route('security')->id : null;
        
        return [
            'product_type_id' => 'required|exists:product_types,id',
            'isin' => 'required|string|size:12|unique:securities,isin,' . $id,
            'security_name' => 'required|string|max:255',
            'issuer' => 'required|string|max:255',
            'issuer_category' => 'nullable|string|max:255',
            
            // Dates
            'issue_date' => 'required|date',
            'maturity_date' => 'required|date|after:issue_date',
            'first_settlement_date' => 'nullable|date',
            'last_trading_date' => 'nullable|date',
            
            // Financials
            'face_value' => 'required|numeric|min:0',
            'issue_price' => 'nullable|numeric|min:0',
            'coupon_rate' => 'nullable|numeric|min:0|max:100',
            'coupon_type' => 'nullable|string|in:Fixed,Floating,Zero',
            'coupon_frequency' => 'nullable|string|in:Annual,Semi-Annual,Quarterly',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
            
            // Outstanding
            'outstanding_value' => 'nullable|numeric|min:0',
            'amount_issued' => 'nullable|numeric|min:0',
            'amount_outstanding' => 'nullable|numeric|min:0',
            
            // Ratings
            'rating_agency' => 'nullable|string|max:255',
            'local_rating' => 'nullable|string|max:255',
            'global_rating' => 'nullable|string|max:255',
            
            // Status
            'listing_status' => 'required|string|in:Listed,Unlisted',
            'status' => 'required|string|in:Active,Matured,Redeemed',
            'remarks' => 'nullable|string',
            
            // Calculated fields
            'tenor' => 'nullable|numeric',
            'ttm' => 'nullable|numeric',
            'effective_coupon' => 'nullable|numeric',
        ];
    }
}
