<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MarketCategory;
use App\Models\ProductType;

class MarketCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Bonds Market Category
        $bondsMarket = MarketCategory::create([
            'name' => 'Bonds',
            'code' => 'BONDS',
            'description' => 'Fixed income securities with maturity greater than one year',
            'is_active' => true,
        ]);

        // Create Bills Market Category
        $billsMarket = MarketCategory::create([
            'name' => 'Bills',
            'code' => 'BILLS',
            'description' => 'Short-term debt instruments with maturity of one year or less',
            'is_active' => true,
        ]);

        // Bonds Market Product Types
        $bondsProducts = [
            ['name' => 'FGN Bond', 'code' => 'FGN_BOND', 'description' => 'Federal Government of Nigeria Bond'],
            ['name' => 'FGN Savings Bond', 'code' => 'FGN_SAVINGS_BOND', 'description' => 'Federal Government of Nigeria Savings Bond'],
            ['name' => 'FGN Green Bond', 'code' => 'FGN_GREEN_BOND', 'description' => 'Federal Government of Nigeria Green Bond'],
            ['name' => 'FGN Sukuk Bond', 'code' => 'FGN_SUKUK_BOND', 'description' => 'Federal Government of Nigeria Sukuk Bond'],
            ['name' => 'FGN Promissory Note', 'code' => 'FGN_PROM_NOTE', 'description' => 'Federal Government of Nigeria Promissory Note'],
            ['name' => 'FGN Eurobond', 'code' => 'FGN_EUROBOND', 'description' => 'Federal Government of Nigeria Eurobond'],
            ['name' => 'Agency Bond', 'code' => 'AGENCY_BOND', 'description' => 'Government Agency Bond'],
            ['name' => 'Sukuk Bond', 'code' => 'SUKUK_BOND', 'description' => 'Islamic Bond'],
            ['name' => 'Sub-National Bond', 'code' => 'SUBNATIONAL_BOND', 'description' => 'State and Local Government Bond'],
            ['name' => 'Supranational Bond', 'code' => 'SUPRANATIONAL_BOND', 'description' => 'International Organization Bond'],
            ['name' => 'Corporate Eurobond', 'code' => 'CORP_EUROBOND', 'description' => 'Corporate Eurobond'],
            ['name' => 'Private Bond', 'code' => 'PRIVATE_BOND', 'description' => 'Private Sector Bond'],
            ['name' => 'Commercial Paper', 'code' => 'COMMERCIAL_PAPER', 'description' => 'Short-term unsecured promissory note'],
        ];

        foreach ($bondsProducts as $product) {
            ProductType::create([
                'market_category_id' => $bondsMarket->id,
                'name' => $product['name'],
                'code' => $product['code'],
                'description' => $product['description'],
                'is_active' => true,
            ]);
        }

        // Bills Market Product Types
        $billsProducts = [
            ['name' => 'Treasury Bill', 'code' => 'T_BILL', 'description' => 'Nigerian Treasury Bill'],
            ['name' => 'OMO Bill', 'code' => 'OMO_BILL', 'description' => 'Open Market Operation Bill'],
            ['name' => 'CBN Special Bill', 'code' => 'CBN_SPECIAL_BILL', 'description' => 'Central Bank of Nigeria Special Bill'],
        ];

        foreach ($billsProducts as $product) {
            ProductType::create([
                'market_category_id' => $billsMarket->id,
                'name' => $product['name'],
                'code' => $product['code'],
                'description' => $product['description'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Market categories and product types seeded successfully!');
        $this->command->info('Bonds Market: 13 product types');
        $this->command->info('Bills Market: 3 product types');
    }
}
