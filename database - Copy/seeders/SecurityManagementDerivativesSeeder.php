<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SecurityManagementDerivativesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fields = [
            ['field_name' => 'Fund Type', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Fund Manager’s name', 'field_type' => 'Text', 'required' => 1],
            ['field_name' => 'Fund Name', 'field_type' => 'Text', 'required' => 1], 

        ];


    
        foreach ($fields as $field) {
            \App\Models\SecurityManagement::create([
                'category_id' => 5, // Derivatives category
                'field_name' => $field['field_name'],
                'field_type' => $field['field_type'],
                'required' => $field['required'],
                'status' => 1, // Active by default
            ]);
        }
    }
}
