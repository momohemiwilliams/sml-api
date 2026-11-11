<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SecurityManagementBillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            ['field_name' => 'Issue Category', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Issuer', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Security Type', 'field_type' => 'Text', 'required' => 1], // Dropdown: T-Bill, OMO Bill, Special Bill
            ['field_name' => 'ISIN', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Description', 'field_type' => 'Text', 'required' => 1], // Primary Key for matching
            ['field_name' => 'Maturity Date', 'field_type' => 'Text', 'required' => 1], // Date field
            ['field_name' => 'Outstanding Value', 'field_type' => 'Float', 'required' => 1], // Auto-filled or manual
            ['field_name' => 'Trading Status', 'field_type' => 'Text', 'required' => 1], // Dropdown: Trading, Non-Trading
        ];

        foreach ($fields as $field) {
            \App\Models\SecurityManagement::create([
                'category_id' => 2, // Bills category
                'field_name' => $field['field_name'],
                'field_type' => $field['field_type'],
                'required' => $field['required'],
                'status' => 1, // Active by default
            ]);
        }
    }
}
