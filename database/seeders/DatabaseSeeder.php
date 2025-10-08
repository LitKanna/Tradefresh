<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call seeders in the correct order (respecting foreign key dependencies)
        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
            // Use REAL Australian vendor and product data
            RealAustralianVendorsSeeder::class,  // Real Sydney Markets vendors
            BuyerSeeder::class,
            RealAustralianProduceSeeder::class,  // Real Australian fresh produce with authentic pricing
            RFQSeeder::class,
            QuoteSeeder::class,
            OrderSeeder::class,
            VendorRatingSeeder::class,
            PriceAlertSeeder::class,
            FavoriteSeeder::class,
            DeliveryZoneSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
        $this->command->newLine();
        
        // Display login credentials
        $this->displayTestCredentials();
    }

    /**
     * Display test credentials for easy access
     */
    private function displayTestCredentials(): void
    {
        $this->command->info('==========================================');
        $this->command->info('TEST ACCOUNT CREDENTIALS');
        $this->command->info('==========================================');
        $this->command->newLine();
        
        $this->command->info('ADMIN ACCOUNTS:');
        $this->command->info('Email: admin@sydneymarkets.com | Password: admin123');
        $this->command->info('Email: manager@sydneymarkets.com | Password: manager123');
        $this->command->newLine();
        
        $this->command->info('VENDOR ACCOUNTS:');
        $this->command->info('Email: freshproduce@vendor.com | Password: vendor123');
        $this->command->info('Email: qualitymeats@vendor.com | Password: vendor123');
        $this->command->info('Email: oceanfresh@vendor.com | Password: vendor123');
        $this->command->newLine();
        
        $this->command->info('BUYER ACCOUNTS:');
        $this->command->info('Email: restaurant@buyer.com | Password: buyer123');
        $this->command->info('Email: cafe@buyer.com | Password: buyer123');
        $this->command->info('Email: hotel@buyer.com | Password: buyer123');
        $this->command->info('==========================================');
    }
}