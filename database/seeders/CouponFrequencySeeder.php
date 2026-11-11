<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CouponFrequency;

class CouponFrequencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $frequencies = [
            [
                'name' => 'Annually',
                'code' => 'ANNUALLY',
                'frequency_per_year' => 1,
                'description' => 'Paid once a year'
            ],
            [
                'name' => 'Semi-annually',
                'code' => 'SEMI_ANNUALLY',
                'frequency_per_year' => 2,
                'description' => 'Paid twice a year'
            ],
            [
                'name' => 'Quarterly',
                'code' => 'QUARTERLY',
                'frequency_per_year' => 4,
                'description' => 'Paid four times a year'
            ],
            [
                'name' => 'Monthly',
                'code' => 'MONTHLY',
                'frequency_per_year' => 12,
                'description' => 'Paid every month'
            ],
        ];

        foreach ($frequencies as $frequency) {
            CouponFrequency::updateOrCreate(
                ['code' => $frequency['code']],
                [
                    'name' => $frequency['name'],
                    'frequency_per_year' => $frequency['frequency_per_year'],
                    'description' => $frequency['description'],
                    'is_active' => true,
                    'created_by' => 1 // System user ID or default admin
                ]
            );
        }
    }
}
