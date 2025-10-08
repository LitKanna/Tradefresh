<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SimpleTestSeeder extends Seeder
{
    public function run()
    {
        // Create test buyer
        $buyer = User::create([
            'name' => 'Test Buyer',
            'email' => 'buyer@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create test vendor
        $vendor = Vendor::create([
            'business_name' => 'Test Vendor Pty Ltd',
            'contact_name' => 'John Vendor',
            'email' => 'vendor@test.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'abn' => '12345678901',
            'business_type' => 'Wholesale',
            'vendor_category' => 'Fresh Produce',
            'phone' => '0412345678',
            'address' => '123 Market Street',
            'suburb' => 'Sydney',
            'state' => 'NSW',
            'postcode' => '2000',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        // Create test admin
        $admin = Admin::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);

        // Create categories if not exists
        $categories = [
            'Fresh Vegetables',
            'Fresh Fruits',
            'Dairy & Eggs',
            'Herbs & Spices',
            'Flowers',
        ];

        foreach ($categories as $categoryName) {
            Category::firstOrCreate(
                ['name' => $categoryName],
                ['slug' => str()->slug($categoryName)]
            );
        }

        // Create some test products
        $products = [
            [
                'name' => 'Fresh Tomatoes',
                'description' => 'Premium quality fresh tomatoes from local farms',
                'price' => 4.50,
                'unit' => 'kg',
                'vendor_id' => $vendor->id,
                'category_id' => Category::where('name', 'Fresh Vegetables')->first()->id ?? 1,
                'stock_quantity' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'Green Apples',
                'description' => 'Crisp and fresh green apples',
                'price' => 3.80,
                'unit' => 'kg',
                'vendor_id' => $vendor->id,
                'category_id' => Category::where('name', 'Fresh Fruits')->first()->id ?? 2,
                'stock_quantity' => 150,
                'is_active' => true,
            ],
            [
                'name' => 'Farm Fresh Eggs',
                'description' => 'Free-range eggs from local farms',
                'price' => 6.50,
                'unit' => 'dozen',
                'vendor_id' => $vendor->id,
                'category_id' => Category::where('name', 'Dairy & Eggs')->first()->id ?? 3,
                'stock_quantity' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Fresh Basil',
                'description' => 'Aromatic fresh basil leaves',
                'price' => 2.50,
                'unit' => 'bunch',
                'vendor_id' => $vendor->id,
                'category_id' => Category::where('name', 'Herbs & Spices')->first()->id ?? 4,
                'stock_quantity' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Red Roses',
                'description' => 'Beautiful fresh red roses',
                'price' => 35.00,
                'unit' => 'dozen',
                'vendor_id' => $vendor->id,
                'category_id' => Category::where('name', 'Flowers')->first()->id ?? 5,
                'stock_quantity' => 20,
                'is_active' => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }

        $this->command->info('Test data created successfully!');
        $this->command->info('Buyer: buyer@test.com / password');
        $this->command->info('Vendor: vendor@test.com / password');
        $this->command->info('Admin: admin@test.com / password');
    }
}
