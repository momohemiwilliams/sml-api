<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SecurityManagementExchangeTradedDerivativeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            ['field_name' => 'Display Code', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Security', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Asset Class', 'field_type' => 'Text', 'required' => 1], 
            ['field_name' => 'Underlying TTM', 'field_type' => 'Text', 'required' => 0 ],
            ['field_name' => 'Underlying Security', 'field_type' => 'Text', 'required' => 0], 
            ['field_name' => 'Benchmark Tenor', 'field_type' => 'Text', 'required' => 1], 
            ['field_name' => 'Issue Date', 'field_type' => 'Float', 'required' => 1], 
            ['field_name' => 'Maturity Date', 'field_type' => 'Text', 'required' => 1], 
            ['field_name' => 'DTM', 'field_type' => 'Text', 'required' => 0], 
            ['field_name' => 'Term To Maturity “TTM”', 'field_type' => 'Text', 'required' => 0], 
            ['field_name' => 'Benchmark Tenor', 'field_type' => 'Text', 'required' => 0], 
        ];

        foreach ($fields as $field) {
            \App\Models\SecurityManagement::create([
                'category_id' => 3, // Exchange Traded Derivative category
                'field_name' => $field['field_name'],
                'field_type' => $field['field_type'],
                'required' => $field['required'],
                'status' => 1, // Active by default
            ]);
        }
    }
}
