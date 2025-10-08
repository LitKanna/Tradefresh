<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateVendorFromBuyerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if vendor already exists
        $existingVendor = DB::table('vendors')
            ->where('email', 'maruthi4a5@gmail.com')
            ->first();

        if (!$existingVendor) {
            // Create vendor account with same credentials as buyer
            DB::table('vendors')->insert([
                'abn' => '62944969621',
                'business_name' => 'MARUTHI FRESH PRODUCE',
                'business_type' => 'company', // Must be: company, partnership, sole_trader, or trust
                'vendor_category' => 'fruits_vegetables',
                'contact_name' => 'Maruthi Thappeta',
                'phone' => '0433975055',
                'email' => 'maruthi4a5@gmail.com',
                'email_verified_at' => now(),
                'password' => '$2y$12$KqxKvXzsV2BxZgIEaLA4F.koIVKyouLqbq0kc7L3FFJ7KYow5c.Di', // Same password hash
                'address' => 'Sydney Markets',
                'suburb' => 'Homebush West',
                'state' => 'NSW',
                'postcode' => '2140',
                'status' => 'active',
                'verification_status' => 'verified',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✅ Vendor account created successfully!');
            $this->command->info('Email: maruthi4a5@gmail.com');
            $this->command->info('Business: MARUTHI FRESH PRODUCE');
            $this->command->info('You can now login to the vendor dashboard with the same password!');
        } else {
            $this->command->info('⚠️ Vendor account already exists with this email.');

            // Update the vendor account to ensure it has correct details
            DB::table('vendors')
                ->where('email', 'maruthi4a5@gmail.com')
                ->update([
                    'business_name' => 'MARUTHI FRESH PRODUCE',
                    'contact_name' => 'Maruthi Thappeta',
                    'status' => 'active',
                    'verification_status' => 'verified',
                    'updated_at' => now(),
                ]);

            $this->command->info('✅ Vendor account updated successfully!');
        }

        // Also create some sample products for this vendor
        $vendorId = DB::table('vendors')
            ->where('email', 'maruthi4a5@gmail.com')
            ->value('id');

        if ($vendorId) {
            // Check if products already exist
            $existingProducts = DB::table('products')
                ->where('vendor_id', $vendorId)
                ->count();

            if ($existingProducts == 0) {
                // Add some sample products
                $products = [
                    [
                        'vendor_id' => $vendorId,
                        'name' => 'Fresh Tomatoes',
                        'description' => 'Premium quality fresh tomatoes',
                        'category' => 'vegetables',
                        'price' => 4.50,
                        'unit' => 'kg',
                        'quantity_available' => 250,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'vendor_id' => $vendorId,
                        'name' => 'Bananas',
                        'description' => 'Fresh yellow bananas',
                        'category' => 'fruits',
                        'price' => 2.80,
                        'unit' => 'kg',
                        'quantity_available' => 180,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'vendor_id' => $vendorId,
                        'name' => 'Iceberg Lettuce',
                        'description' => 'Crisp iceberg lettuce',
                        'category' => 'vegetables',
                        'price' => 3.00,
                        'unit' => 'unit',
                        'quantity_available' => 15,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'vendor_id' => $vendorId,
                        'name' => 'Carrots',
                        'description' => 'Fresh orange carrots',
                        'category' => 'vegetables',
                        'price' => 2.50,
                        'unit' => 'kg',
                        'quantity_available' => 120,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'vendor_id' => $vendorId,
                        'name' => 'Apples',
                        'description' => 'Crispy red apples',
                        'category' => 'fruits',
                        'price' => 3.80,
                        'unit' => 'kg',
                        'quantity_available' => 200,
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ];

                DB::table('products')->insert($products);
                $this->command->info('✅ Sample products added for vendor!');
            }
        }
    }
}