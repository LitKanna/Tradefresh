<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = \App\Models\Supplier::all();
        
        if ($suppliers->isEmpty()) {
            $this->command->info('No suppliers found. Please run SuppliersTableSeeder first.');
            return;
        }

        $products = [
            // Fresh Produce Co products
            [
                'name' => 'Organic Bananas',
                'sku' => 'ORG-BAN-001',
                'description' => 'Premium organic bananas, hand-selected for quality',
                'supplier_id' => $suppliers->where('name', 'Fresh Produce Co')->first()->id ?? 1,
                'category' => 'Fruits',
                'price' => 3.50,
                'cost_price' => 2.10,
                'unit' => 'kg',
                'stock_quantity' => 250,
                'min_stock_level' => 50,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Fresh Lettuce',
                'sku' => 'FRS-LET-001',
                'description' => 'Crisp iceberg lettuce, perfect for salads',
                'supplier_id' => $suppliers->where('name', 'Fresh Produce Co')->first()->id ?? 1,
                'category' => 'Vegetables',
                'price' => 2.80,
                'cost_price' => 1.40,
                'unit' => 'head',
                'stock_quantity' => 180,
                'min_stock_level' => 30,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Premium Beef Mince',
                'sku' => 'PRE-BEF-001',
                'description' => 'Premium quality beef mince, 85% lean',
                'supplier_id' => $suppliers->where('name', 'Metro Meats')->first()->id ?? 2,
                'category' => 'Beef',
                'price' => 18.50,
                'cost_price' => 12.80,
                'unit' => 'kg',
                'stock_quantity' => 95,
                'min_stock_level' => 20,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Atlantic Salmon Fillets',
                'sku' => 'ATL-SAL-001',
                'description' => 'Fresh Atlantic salmon fillets, skin-on',
                'supplier_id' => $suppliers->where('name', 'Ocean Fresh Seafood')->first()->id ?? 3,
                'category' => 'Fish',
                'price' => 35.90,
                'cost_price' => 28.50,
                'unit' => 'kg',
                'stock_quantity' => 45,
                'min_stock_level' => 10,
                'is_active' => true,
                'is_featured' => true,
            ],
            [
                'name' => 'Full Cream Milk',
                'sku' => 'FCM-MIL-001',
                'description' => 'Fresh full cream milk, locally sourced',
                'supplier_id' => $suppliers->where('name', 'Dairy Direct')->first()->id ?? 4,
                'category' => 'Milk',
                'price' => 1.80,
                'cost_price' => 1.20,
                'unit' => 'litre',
                'stock_quantity' => 320,
                'min_stock_level' => 100,
                'is_active' => true,
                'is_featured' => false,
            ],
            [
                'name' => 'Organic Brown Rice',
                'sku' => 'ORG-BRC-001',
                'description' => 'Certified organic brown rice, long grain',
                'supplier_id' => $suppliers->where('name', 'Grain & More')->first()->id ?? 5,
                'category' => 'Grains',
                'price' => 4.80,
                'cost_price' => 3.20,
                'unit' => 'kg',
                'stock_quantity' => 8,
                'min_stock_level' => 10,
                'is_active' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($products as $productData) {
            \App\Models\Product::create($productData);
        }

        // Connect some products to buyers
        $buyers = \App\Models\Buyer::all();
        $productIds = \App\Models\Product::pluck('id')->toArray();

        foreach ($buyers as $buyer) {
            // Randomly connect 3-6 products to each buyer
            $randomProducts = array_slice($productIds, 0, rand(3, 6));
            
            foreach ($randomProducts as $productId) {
                $buyer->products()->attach($productId, [
                    'quantity_ordered' => rand(5, 50),
                    'last_price' => \App\Models\Product::find($productId)->price ?? 0,
                    'last_ordered_at' => now()->subDays(rand(1, 60)),
                    'is_favorite' => rand(0, 1),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
