<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendors = Vendor::all();
        $rfqs = DB::table('rfqs')->whereIn('status', ['open', 'closed', 'awarded'])->get();

        if ($vendors->isEmpty() || $rfqs->isEmpty()) {
            $this->command->error('Please run VendorSeeder and RFQSeeder first.');
            return;
        }

        $createdQuotes = 0;

        foreach ($rfqs as $rfq) {
            // Create 2-6 quotes per RFQ
            $quotesCount = rand(2, min(6, $rfq->max_quotes ?? 6));
            $selectedVendors = $vendors->random($quotesCount);

            foreach ($selectedVendors as $index => $vendor) {
                $items = json_decode($rfq->items, true) ?? [];
                $quoteItems = [];
                $totalAmount = 0;

                // Create quote items based on RFQ items
                foreach ($items as $item) {
                    $unitPrice = $this->calculateUnitPrice($item['product'], $rfq->budget_max, count($items));
                    $lineTotal = $unitPrice * $item['quantity'];
                    $totalAmount += $lineTotal;

                    $quoteItems[] = [
                        'product_name' => $item['product'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'],
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        'specifications' => $item['specifications'] ?? 'Standard quality',
                        'brand' => $vendor->business_name . ' Premium',
                        'origin' => 'Australia'
                    ];
                }

                // Calculate amounts using the correct column names
                $deliveryCharge = $vendor->delivery_fee ?? 15.00;
                $taxAmount = ($totalAmount + $deliveryCharge) * 0.1;
                $discountAmount = 0; // No discounts for now
                $finalAmount = $totalAmount + $deliveryCharge + $taxAmount - $discountAmount;

                $status = $this->getQuoteStatus($rfq->status, $index === 0);

                DB::table('quotes')->insert([
                    'quote_number' => 'QUO-' . str_pad(($createdQuotes + 1000), 6, '0', STR_PAD_LEFT),
                    'rfq_id' => $rfq->id,
                    'vendor_id' => $vendor->id,
                    'buyer_id' => $rfq->buyer_id,
                    'status' => $status,
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'delivery_charge' => $deliveryCharge,
                    'discount_amount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'line_items' => json_encode($quoteItems),
                    'notes' => $this->generateQuoteNotes($vendor->business_name),
                    'terms_conditions' => json_encode([
                        'payment_terms' => $vendor->payment_terms,
                        'minimum_order' => $vendor->minimum_order_value,
                        'delivery_fee' => $deliveryCharge,
                        'warranty' => '100% satisfaction guarantee',
                        'cancellation' => '24 hours notice required',
                        'returns' => 'Fresh products accepted within 2 hours'
                    ]),
                    'validity_date' => now()->addDays(7)->format('Y-m-d'),
                    'proposed_delivery_date' => $rfq->delivery_date,
                    'proposed_delivery_time' => ['Morning (6AM-10AM)', 'Afternoon (2PM-6PM)', 'Evening (6PM-8PM)'][rand(0, 2)],
                    'payment_terms_days' => $this->getPaymentTermsDays($vendor->payment_terms),
                    'payment_method' => 'Bank Transfer',
                    'is_negotiable' => rand(0, 10) > 7, // 30% negotiable
                    'submitted_at' => now()->subDays(rand(1, 10)),
                    'metadata' => json_encode([
                        'preparation_time' => rand(2, 24) . ' hours',
                        'minimum_order_value' => $vendor->minimum_order_value,
                        'quality_guarantee' => '100% satisfaction or full refund',
                        'special_notes' => 'All products are fresh and meet quality standards'
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $createdQuotes++;
            }

            // Update RFQ quote count
            DB::table('rfqs')->where('id', $rfq->id)->update(['quote_count' => $quotesCount]);
        }

        $this->command->info("Quotes seeded successfully. Created {$createdQuotes} quotes.");
    }

    private function calculateUnitPrice($productName, $maxBudget, $itemCount): float
    {
        // Base pricing logic
        $basePrices = [
            'tomatoes' => 5.50,
            'spinach' => 4.80,
            'mushrooms' => 9.20,
            'capsicum' => 7.80,
            'beef' => 35.00,
            'chicken' => 12.50,
            'lamb' => 28.00,
            'salmon' => 32.00,
            'prawns' => 55.00,
            'barramundi' => 35.00,
            'milk' => 1.80,
            'cream' => 5.50,
            'cheese' => 22.00,
            'bread' => 4.50,
            'apples' => 4.20,
            'carrots' => 3.20,
            'bananas' => 5.50,
            'bok choy' => 6.80,
            'eggplant' => 8.20,
            'water spinach' => 5.40
        ];

        // Find base price by checking if product name contains key words
        $basePrice = 10.00; // Default price
        foreach ($basePrices as $key => $price) {
            if (str_contains(strtolower($productName), $key)) {
                $basePrice = $price;
                break;
            }
        }

        // Add some variation (±20%)
        $variation = (rand(-20, 20) / 100);
        return round($basePrice * (1 + $variation), 2);
    }

    private function getQuoteStatus($rfqStatus, $isFirst): string
    {
        if ($rfqStatus === 'awarded') {
            return $isFirst ? 'accepted' : 'rejected';
        }

        if ($rfqStatus === 'closed') {
            $statuses = ['under_review', 'rejected'];
            return $statuses[rand(0, 1)];
        }

        return 'submitted'; // For open RFQs
    }

    private function generateQuoteNotes($vendorName): string
    {
        $notes = [
            "Thank you for considering {$vendorName} for your requirements. We guarantee fresh, quality products with reliable delivery.",
            "All prices include quality assurance. {$vendorName} has been supplying premium products for over 10 years.",
            "We offer flexible delivery times and can accommodate special requests. Contact us for any modifications needed.",
            "Premium quality guaranteed. All products meet or exceed industry standards. Same day delivery available for urgent orders.",
            "{$vendorName} specializes in restaurant-quality products. We understand the importance of consistency and reliability."
        ];

        return $notes[array_rand($notes)];
    }

    private function getPaymentTermsDays($paymentTerms): int
    {
        $termDays = [
            'prepaid' => 0,
            'net_7' => 7,
            'net_14' => 14,
            'net_30' => 30,
            'net_60' => 60
        ];

        return $termDays[$paymentTerms] ?? 30;
    }
}