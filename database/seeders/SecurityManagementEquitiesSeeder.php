<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SecurityManagementEquitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            ['field_name' => 'UNDR Name', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'UNDR Description', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'UFS Name', 'field_type' => 'Text', 'required' => 1], 
            ['field_name' => 'Issued Date', 'field_type' => 'Text', 'required' => 0 ],
            ['field_name' => 'Unique Identification', 'field_type' => 'Text', 'required' => 0], 
            ['field_name' => 'Issued Amount', 'field_type' => 'Text', 'required' => 1], 
            ['field_name' => 'No. of UNDR Issued', 'field_type' => 'Float', 'required' => 1], 
            ['field_name' => 'Issued Price', 'field_type' => 'Text', 'required' => 1], 
            ['field_name' => 'Tenor', 'field_type' => 'Text', 'required' => 0], 
            ['field_name' => 'Conversion Ratio', 'field_type' => 'Text', 'required' => 0], 
            ['field_name' => 'Listing Status', 'field_type' => 'Text', 'required' => 0], 
        ];


        foreach ($fields as $field) {
            \App\Models\SecurityManagement::create([
                'category_id' => 4, // Equities category
                'field_name' => $field['field_name'],
                'field_type' => $field['field_type'],
                'required' => $field['required'],
                'status' => 1, // Active by default
            ]);
        }
    }
}
