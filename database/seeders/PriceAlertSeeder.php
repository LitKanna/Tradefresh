<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PriceAlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = Buyer::all();
        $products = Product::where('is_active', true)->get();

        if ($buyers->isEmpty() || $products->isEmpty()) {
            $this->command->error('Please run BuyerSeeder and ProductSeeder first.');
            return;
        }

        $createdAlerts = 0;

        // Create price alerts for buyers
        foreach ($buyers as $buyer) {
            // Each buyer gets 2-6 price alerts
            $alertCount = rand(2, 6);
            $selectedProducts = $products->random($alertCount);

            foreach ($selectedProducts as $product) {
                // Set alert price 5-15% below current price
                $discountPercent = rand(5, 15) / 100;
                $targetPrice = $product->price * (1 - $discountPercent);

                PriceAlert::create([
                    'buyer_id' => $buyer->id,
                    'product_id' => $product->id,
                    'target_price' => round($targetPrice, 2),
                    'current_price' => $product->price,
                    'is_active' => rand(0, 10) > 1, // 90% active
                    'is_triggered' => rand(0, 10) > 7, // 30% triggered
                    'notification_sent' => rand(0, 10) > 5, // 50% notifications sent
                    'triggered_at' => rand(0, 10) > 7 ? now()->subDays(rand(1, 14)) : null,
                    'metadata' => json_encode([
                        'alert_type' => ['price_drop', 'stock_available', 'seasonal_special'][rand(0, 2)],
                        'notification_method' => ['email', 'sms', 'both'][rand(0, 2)],
                        'frequency' => ['immediate', 'daily', 'weekly'][rand(0, 2)]
                    ])
                ]);

                $createdAlerts++;
            }
        }

        $this->command->info("Price alerts seeded successfully. Created {$createdAlerts} price alerts.");
    }
}