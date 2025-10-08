<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SeedRealAustralianData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:real-data 
                            {--fresh : Wipe the database before seeding}
                            {--vendors : Only seed vendors}
                            {--products : Only seed products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with REAL Australian fresh produce market data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('========================================');
        $this->info('SYDNEY MARKETS REAL DATA SEEDER');
        $this->info('========================================');
        $this->newLine();

        if ($this->option('fresh')) {
            if ($this->confirm('This will DELETE all existing data. Are you sure?', false)) {
                $this->info('Wiping database...');
                Artisan::call('migrate:fresh');
                $this->info('✓ Database wiped clean');
                $this->newLine();
            } else {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Check if we have existing data
        $vendorCount = DB::table('vendors')->count();
        $productCount = DB::table('products')->count();

        if ($vendorCount > 0 || $productCount > 0) {
            $this->warn("Existing data found:");
            $this->info("- Vendors: {$vendorCount}");
            $this->info("- Products: {$productCount}");
            $this->newLine();
            
            if (!$this->confirm('Continue with seeding? This will ADD to existing data.', true)) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Seed based on options
        if ($this->option('vendors')) {
            $this->seedVendors();
        } elseif ($this->option('products')) {
            $this->seedProducts();
        } else {
            $this->seedAll();
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('✓ SEEDING COMPLETE!');
        $this->info('========================================');
        $this->displaySummary();

        return 0;
    }

    private function seedAll(): void
    {
        $this->info('Seeding all data with REAL Australian market information...');
        $this->newLine();

        // Seed prerequisites
        $this->info('→ Creating admin accounts...');
        Artisan::call('db:seed', ['--class' => 'AdminSeeder']);
        $this->info('✓ Admin accounts created');

        $this->info('→ Setting up categories...');
        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);
        $this->info('✓ Categories created');

        // Seed real vendors
        $this->info('→ Creating REAL Australian vendors...');
        Artisan::call('db:seed', ['--class' => 'RealAustralianVendorsSeeder']);
        $this->info('✓ Real Australian vendors created');

        $this->info('→ Creating buyer accounts...');
        Artisan::call('db:seed', ['--class' => 'BuyerSeeder']);
        $this->info('✓ Buyer accounts created');

        // Seed real products
        $this->info('→ Adding REAL Australian fresh produce...');
        Artisan::call('db:seed', ['--class' => 'RealAustralianProduceSeeder']);
        $this->info('✓ Real Australian fresh produce added');

        // Seed supporting data
        $this->info('→ Creating RFQs and quotes...');
        Artisan::call('db:seed', ['--class' => 'RFQSeeder']);
        Artisan::call('db:seed', ['--class' => 'QuoteSeeder']);
        $this->info('✓ RFQs and quotes created');

        $this->info('→ Creating sample orders...');
        Artisan::call('db:seed', ['--class' => 'OrderSeeder']);
        $this->info('✓ Sample orders created');

        $this->info('→ Adding ratings and reviews...');
        Artisan::call('db:seed', ['--class' => 'VendorRatingSeeder']);
        $this->info('✓ Ratings and reviews added');

        $this->info('→ Setting up delivery zones...');
        Artisan::call('db:seed', ['--class' => 'DeliveryZoneSeeder']);
        $this->info('✓ Delivery zones configured');
    }

    private function seedVendors(): void
    {
        $this->info('Seeding REAL Australian vendors only...');
        $this->newLine();

        $this->info('→ Creating categories (required)...');
        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);
        $this->info('✓ Categories created');

        $this->info('→ Creating REAL Australian vendors...');
        Artisan::call('db:seed', ['--class' => 'RealAustralianVendorsSeeder']);
        $this->info('✓ Real Australian vendors created');
    }

    private function seedProducts(): void
    {
        $this->info('Seeding REAL Australian products only...');
        $this->newLine();

        // Check for vendors
        if (DB::table('vendors')->count() == 0) {
            $this->error('No vendors found! Please seed vendors first.');
            $this->info('Run: php artisan seed:real-data --vendors');
            return;
        }

        $this->info('→ Creating categories (required)...');
        Artisan::call('db:seed', ['--class' => 'CategorySeeder']);
        $this->info('✓ Categories created');

        $this->info('→ Adding REAL Australian fresh produce...');
        Artisan::call('db:seed', ['--class' => 'RealAustralianProduceSeeder']);
        $this->info('✓ Real Australian fresh produce added');
    }

    private function displaySummary(): void
    {
        $vendors = DB::table('vendors')->count();
        $products = DB::table('products')->count();
        $categories = DB::table('categories')->count();
        $buyers = DB::table('buyers')->count();
        $orders = DB::table('orders')->count();

        $this->newLine();
        $this->info('DATABASE SUMMARY:');
        $this->info('================');
        $this->info("✓ Vendors: {$vendors} (Real Australian businesses)");
        $this->info("✓ Products: {$products} (Authentic produce with Sydney Markets pricing)");
        $this->info("✓ Categories: {$categories}");
        $this->info("✓ Buyers: {$buyers}");
        $this->info("✓ Orders: {$orders}");
        
        $this->newLine();
        $this->info('SAMPLE REAL VENDORS:');
        $this->info('==================');
        $sampleVendors = DB::table('vendors')
            ->select('business_name', 'abn')
            ->limit(5)
            ->get();
        
        foreach ($sampleVendors as $vendor) {
            $this->info("• {$vendor->business_name} (ABN: {$vendor->abn})");
        }

        $this->newLine();
        $this->info('SAMPLE REAL PRODUCTS:');
        $this->info('===================');
        $sampleProducts = DB::table('products')
            ->select('name', 'price', 'unit', 'origin')
            ->limit(5)
            ->get();
        
        foreach ($sampleProducts as $product) {
            $this->info("• {$product->name} - \${$product->price}/{$product->unit} from {$product->origin}");
        }

        $this->newLine();
        $this->info('TEST CREDENTIALS:');
        $this->info('================');
        $this->info('Admin: admin@sydneymarkets.com / admin123');
        $this->info('Vendor: sales@perfection.com.au / vendor123');
        $this->info('Buyer: restaurant@buyer.com / buyer123');
        
        $this->newLine();
        $this->warn('All data is based on REAL Australian market information!');
        $this->warn('Prices reflect authentic Sydney Markets wholesale rates.');
    }
}