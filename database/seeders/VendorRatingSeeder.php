<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorRating;
use Illuminate\Database\Seeder;

class VendorRatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = Buyer::all();
        $vendors = Vendor::all();
        $completedOrders = Order::where('status', 'completed')->get();

        if ($buyers->isEmpty() || $vendors->isEmpty()) {
            $this->command->error('Please run BuyerSeeder and VendorSeeder first.');
            return;
        }

        $createdRatings = 0;

        // Create ratings for about 30% of completed orders
        $ordersToRate = $completedOrders->random(min(15, $completedOrders->count()));

        foreach ($ordersToRate as $order) {
            $qualityRating = rand(35, 50) / 10; // 3.5 to 5.0
            $deliveryRating = rand(35, 50) / 10;
            $serviceRating = rand(35, 50) / 10;
            $overallRating = round(($qualityRating + $deliveryRating + $serviceRating) / 3, 1);

            VendorRating::create([
                'vendor_id' => $order->vendor_id,
                'buyer_id' => $order->buyer_id,
                'order_id' => $order->id,
                'overall_rating' => $overallRating,
                'quality_rating' => $qualityRating,
                'delivery_rating' => $deliveryRating,
                'service_rating' => $serviceRating,
                'review_text' => $this->generateReviewText($overallRating),
                'is_verified' => true,
                'is_featured' => rand(0, 10) > 8, // 20% chance of being featured
                'helpful_votes' => rand(0, 15),
                'metadata' => json_encode([
                    'order_value' => $order->total_amount,
                    'delivery_on_time' => $deliveryRating >= 4.0,
                    'would_recommend' => $overallRating >= 4.0
                ])
            ]);

            $createdRatings++;
        }

        // Update vendor ratings based on reviews
        foreach ($vendors as $vendor) {
            $ratings = VendorRating::where('vendor_id', $vendor->id);
            $avgRating = $ratings->avg('overall_rating');
            $totalReviews = $ratings->count();

            if ($totalReviews > 0) {
                $vendor->update([
                    'rating' => round($avgRating, 2),
                    'total_reviews' => $totalReviews
                ]);
            }
        }

        $this->command->info("Vendor ratings seeded successfully. Created {$createdRatings} ratings.");
    }

    private function generateReviewText($rating): string
    {
        if ($rating >= 4.5) {
            $reviews = [
                "Excellent quality products and reliable delivery. Highly recommended!",
                "Outstanding service and fresh products. Will definitely order again.",
                "Premium quality and professional service. Exceeded expectations.",
                "Fantastic supplier! Quality is consistently excellent.",
                "Top-notch products and service. Very impressed with the quality."
            ];
        } elseif ($rating >= 4.0) {
            $reviews = [
                "Good quality products and reliable service. Minor room for improvement.",
                "Solid supplier with consistent quality. Delivery was on time.",
                "Good experience overall. Products were fresh and well-packaged.",
                "Reliable supplier with good customer service. Products as expected.",
                "Good quality and service. Would use again for future orders."
            ];
        } elseif ($rating >= 3.5) {
            $reviews = [
                "Average quality products. Service was acceptable but could be better.",
                "Products were okay but not exceptional. Delivery was delayed slightly.",
                "Reasonable quality for the price. Some items better than others.",
                "Mixed experience. Some products were good, others just average.",
                "Adequate service and product quality. Room for improvement."
            ];
        } else {
            $reviews = [
                "Quality was below expectations. Several items were not fresh.",
                "Poor service and product quality. Would not recommend.",
                "Disappointing experience. Products arrived damaged and late.",
                "Quality control issues with this order. Several items unusable.",
                "Service was poor and products did not meet standards."
            ];
        }

        return $reviews[array_rand($reviews)];
    }
}