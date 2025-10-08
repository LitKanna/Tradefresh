<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\BuyerFavoriteProduct;
use App\Models\BuyerFavoriteVendor;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = Buyer::all();
        $products = Product::where('is_active', true)->get();
        $vendors = Vendor::where('status', 'active')->get();

        if ($buyers->isEmpty() || $products->isEmpty() || $vendors->isEmpty()) {
            $this->command->error('Please run BuyerSeeder, ProductSeeder, and VendorSeeder first.');
            return;
        }

        $createdProductFavorites = 0;
        $createdVendorFavorites = 0;

        foreach ($buyers as $buyer) {
            // Each buyer has 3-10 favorite products
            $productFavoriteCount = rand(3, 10);
            $selectedProducts = $products->random($productFavoriteCount);

            foreach ($selectedProducts as $product) {
                BuyerFavoriteProduct::create([
                    'buyer_id' => $buyer->id,
                    'product_id' => $product->id,
                    'notes' => $this->getProductFavoriteNotes(),
                    'metadata' => json_encode([
                        'added_from' => ['search', 'category', 'vendor_page', 'recommendation'][rand(0, 3)],
                        'order_frequency' => ['weekly', 'bi-weekly', 'monthly'][rand(0, 2)],
                        'avg_order_quantity' => rand(5, 50)
                    ])
                ]);

                $createdProductFavorites++;
            }

            // Each buyer has 2-5 favorite vendors
            $vendorFavoriteCount = rand(2, 5);
            $selectedVendors = $vendors->random($vendorFavoriteCount);

            foreach ($selectedVendors as $vendor) {
                BuyerFavoriteVendor::create([
                    'buyer_id' => $buyer->id,
                    'vendor_id' => $vendor->id,
                    'notes' => $this->getVendorFavoriteNotes($vendor->business_name),
                    'metadata' => json_encode([
                        'reason' => ['quality', 'price', 'service', 'reliability'][rand(0, 3)],
                        'first_order_date' => now()->subDays(rand(30, 365))->format('Y-m-d'),
                        'total_orders' => rand(5, 50),
                        'avg_order_value' => rand(200, 2000)
                    ])
                ]);

                $createdVendorFavorites++;
            }
        }

        $this->command->info("Favorites seeded successfully. Created {$createdProductFavorites} product favorites and {$createdVendorFavorites} vendor favorites.");
    }

    private function getProductFavoriteNotes(): ?string
    {
        $notes = [
            null, // 40% chance of no notes
            null,
            "Regular order item - excellent quality",
            "Customer favorite - always in stock needed",
            "Premium quality, worth the price",
            "Consistent supplier, reliable quality",
            "Best price for this grade",
            "Perfect for our signature dishes"
        ];

        return $notes[array_rand($notes)];
    }

    private function getVendorFavoriteNotes($vendorName): ?string
    {
        $notes = [
            null, // 30% chance of no notes
            "Reliable supplier with consistent quality",
            "Excellent customer service and communication",
            "Always delivers on time, professional team",
            "Best pricing for bulk orders",
            "Flexible with custom orders and requirements",
            "Long-term trusted partner",
            "Quick response to urgent requests"
        ];

        return $notes[array_rand($notes)];
    }
}