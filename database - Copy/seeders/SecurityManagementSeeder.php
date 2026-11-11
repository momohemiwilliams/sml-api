<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SecurityManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            ['field_name' => 'Issue Category', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Issuer', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Security Type', 'field_type' => 'Text', 'required' => 1], // Dropdown
            ['field_name' => 'ISIN', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Description', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Issue Date', 'field_type' => 'Text', 'required' => 1], // Date field
            ['field_name' => 'Maturity Date', 'field_type' => 'Text', 'required' => 1], // Date field
            ['field_name' => 'Tenor', 'field_type' => 'Int', 'required' => 0], // Calculated
            ['field_name' => 'Coupon (%)', 'field_type' => 'Float', 'required' => 1],
            ['field_name' => 'Coupon Type', 'field_type' => 'Text', 'required' => 1], // Dropdown
            ['field_name' => 'Coupon Frequency', 'field_type' => 'Int', 'required' => 1],
            ['field_name' => 'Effective Coupon (%)', 'field_type' => 'Float', 'required' => 0], // Calculated
            ['field_name' => 'FGN Benchmark Yield at Issue (%)', 'field_type' => 'Float', 'required' => 0],
            ['field_name' => 'Issue Size (₦\'bn)', 'field_type' => 'Float', 'required' => 1],
            ['field_name' => 'Outstanding Value (₦\'bn)', 'field_type' => 'Float', 'required' => 1],
            ['field_name' => 'TTM', 'field_type' => 'Float', 'required' => 0], // Calculated
            ['field_name' => 'Day Count Convention', 'field_type' => 'Text', 'required' => 1], // Dropdown
            ['field_name' => 'Day Count Basis', 'field_type' => 'Int', 'required' => 0], // Auto-filled
            ['field_name' => 'Option Type', 'field_type' => 'Text', 'required' => 1], // Dropdown
            ['field_name' => 'Yield at Issue', 'field_type' => 'Text', 'required' => 0],
            ['field_name' => 'Interest Determination Date', 'field_type' => 'Text', 'required' => 0], // Date field
            ['field_name' => 'Listing Status', 'field_type' => 'Text', 'required' => 1], // Dropdown
            ['field_name' => 'Rating 1 Agency', 'field_type' => 'Text', 'required' => 0],
            ['field_name' => 'Rating 1', 'field_type' => 'Text', 'required' => 0],
            ['field_name' => 'Rating 1 Issuance Date', 'field_type' => 'Text', 'required' => 0], // Date field
            ['field_name' => 'Rating 1 Expiration Date', 'field_type' => 'Text', 'required' => 0], // Date field
            ['field_name' => 'Rating 2 Agency', 'field_type' => 'Text', 'required' => 0],
            ['field_name' => 'Rating 2', 'field_type' => 'Text', 'required' => 0],
            ['field_name' => 'Rating 2 Issuance Date', 'field_type' => 'Text', 'required' => 0], // Date field
            ['field_name' => 'Rating 2 Expiration Date', 'field_type' => 'Text', 'required' => 0], // Date field
            ['field_name' => 'Final Rating', 'field_type' => 'Text', 'required' => 0], // Calculated/Concatenated
            // Additional fields for Floating Coupon Type
            ['field_name' => 'Floating Rate Margin (FRM)', 'field_type' => 'Float', 'required' => 0],
            ['field_name' => 'Floating Rate Benchmark (FRB)', 'field_type' => 'Text', 'required' => 0],
            ['field_name' => 'Floating Rate Benchmark Value (FRBV)', 'field_type' => 'Float', 'required' => 0],
            ['field_name' => 'Coupon Floor (CF)', 'field_type' => 'Float', 'required' => 0],
            ['field_name' => 'Coupon Cap (CC)', 'field_type' => 'Float', 'required' => 0],
            ['field_name' => 'Call Date', 'field_type' => 'Text', 'required' => 0], // Date field for Callable option
        ];

        foreach ($fields as $field) {
            \App\Models\SecurityManagement::create([
                'category_id' => 1, // Bonds category
                'field_name' => $field['field_name'],
                'field_type' => $field['field_type'],
                'required' => $field['required'],
                'status' => 1, // Active by default
            ]);
        }
    }
}
