<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DayCountConvention;

class DayCountConventionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conventions = [
            [
                'name' => 'US (NASD) 30/360',
                'code' => 'US_30_360',
                'description' => 'US (NASD) 30/360'
            ],
            [
                'name' => 'Actual/Actual',
                'code' => 'ACT_ACT',
                'description' => 'Actual/Actual'
            ],
            [
                'name' => 'Actual/360',
                'code' => 'ACT_360',
                'description' => 'Actual/360'
            ],
            [
                'name' => 'Actual/365',
                'code' => 'ACT_365',
                'description' => 'Actual/365'
            ],
            [
                'name' => 'European 30/360',
                'code' => 'EU_30_360',
                'description' => 'European 30/360'
            ],
        ];

        foreach ($conventions as $convention) {
            DayCountConvention::updateOrCreate(
                ['code' => $convention['code']],
                [
                    'name' => $convention['name'],
                    'description' => $convention['description'],
                    'is_active' => true,
                    'created_by' => 1 // System user ID or default admin
                ]
            );
        }
    }
}
