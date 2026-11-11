<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OptionType;

class OptionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Option-Free',
                'code' => 'OPTION_FREE',
                'description' => 'Plain vanilla bond with no embedded options',
                'has_call_date' => false,
            ],
            [
                'name' => 'Amortising',
                'code' => 'AMORTISING',
                'description' => 'Principal is paid down over the life of the bond',
                'has_call_date' => false,
            ],
            [
                'name' => 'Callable',
                'code' => 'CALLABLE',
                'description' => 'Issuer has the right to redeem the bond prior to maturity',
                'has_call_date' => true,
            ],
        ];

        foreach ($types as $type) {
            OptionType::updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'has_call_date' => $type['has_call_date'],
                    'is_active' => true,
                    'created_by' => 1 // System user ID or default admin
                ]
            );
        }
    }
}
