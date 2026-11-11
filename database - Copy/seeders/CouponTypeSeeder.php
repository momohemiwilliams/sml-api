<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CouponType;

class CouponTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Fixed',
                'code' => 'FIX',
            ],
            [
                'name' => 'Float',
                'code' => 'FLO',
            ],
           
        ];

        foreach ($types as $type) {
            CouponType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'is_active' => true,
                    'created_by' => 1 // System user ID or default admin
                ]
            );
        }
    }
}
