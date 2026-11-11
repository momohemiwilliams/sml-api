<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TradingStatus;

class TradingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Trading',
                'code' => 'TRADING',
                'description' => 'Security is actively trading'
            ],
            [
                'name' => 'Non-Trading',
                'code' => 'NON_TRADING',
                'description' => 'Security is suspended or not trading'
            ],
        ];

        foreach ($statuses as $status) {
            TradingStatus::updateOrCreate(
                ['code' => $status['code']],
                [
                    'name' => $status['name'],
                    'description' => $status['description'],
                    'is_active' => true,
                    'created_by' => 1 // System user ID or default admin
                ]
            );
        }
    }
}
