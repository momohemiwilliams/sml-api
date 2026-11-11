<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuctionResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('auction_result') ? $this->route('auction_result')->id : null;

        return [
            'security_id' => 'required|exists:security_master_data,id',
            'auction_number' => 'nullable|string|unique:auction_results,auction_number,' . $id,
            'auction_date' => 'required|date',
            'value_date' => 'required|date|after_or_equal:auction_date',
            'tenor_days' => 'required|integer|min:1',
            
            'amount_offered' => 'required|numeric|min:0',
            'amount_subscribed' => 'required|numeric|min:0',
            'amount_allotted' => 'nullable|numeric|min:0',
            'amount_sold' => 'required|numeric|min:0',
            'non_competitive_sales' => 'nullable|numeric|min:0',
            
            'stop_rate' => 'required|numeric|min:0|max:100',
            'marginal_rate' => 'nullable|numeric|min:0|max:100',
            'true_yield' => 'nullable|numeric|min:0|max:100',
            
            'auction_type' => 'nullable',
            'status' => 'nullable',
            'remarks' => 'nullable|string',
            'requested_by' => 'nullable|integer',
            'authoriser_id' => 'nullable|integer',
        ];
    }
}
