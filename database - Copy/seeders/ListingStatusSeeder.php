<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ListingStatus;

class ListingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'Fully Listed (FL)',
                'code' => 'FL',
                'description' => 'Security is fully listed and tradable'
            ],
            [
                'name' => 'Permitted Trading (PT)',
                'code' => 'PT',
                'description' => 'Trading is permitted under specific conditions'
            ],
        ];

        foreach ($statuses as $status) {
            ListingStatus::updateOrCreate(
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
