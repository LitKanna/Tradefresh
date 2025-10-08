<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RFQSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = Buyer::all();
        $categories = Category::whereNotNull('parent_id')->get();
        $vendors = Vendor::all();

        if ($buyers->isEmpty() || $categories->isEmpty()) {
            $this->command->error('Please run BuyerSeeder and CategorySeeder first.');
            return;
        }

        $rfqTemplates = [
            [
                'title' => 'Weekly Fresh Produce Order - Fine Dining Restaurant',
                'description' => 'Looking for premium quality fresh produce for our award-winning restaurant. We need consistent supply of seasonal vegetables and fruits. Quality is paramount.',
                'category' => 'fruits',
                'urgency' => 'medium',
                'budget_min' => 800.00,
                'budget_max' => 1500.00,
                'delivery_days' => 3,
                'status' => 'open',
                'items' => [
                    ['product' => 'Premium Tomatoes', 'quantity' => 20, 'unit' => 'kg', 'specifications' => 'Vine ripened, uniform size'],
                    ['product' => 'Baby Spinach', 'quantity' => 15, 'unit' => 'kg', 'specifications' => 'Fresh, no wilting'],
                    ['product' => 'Mushrooms - Button', 'quantity' => 10, 'unit' => 'kg', 'specifications' => 'Fresh, medium size'],
                    ['product' => 'Red Capsicums', 'quantity' => 8, 'unit' => 'kg', 'specifications' => 'Bright red, firm'],
                ]
            ],
            [
                'title' => 'Monthly Meat Supply - Hotel Chain',
                'description' => 'Seeking reliable supplier for monthly meat requirements across 3 hotel properties. Need consistent quality and competitive pricing.',
                'category' => 'beef',
                'urgency' => 'low',
                'budget_min' => 5000.00,
                'budget_max' => 8000.00,
                'delivery_days' => 7,
                'status' => 'open',
                'items' => [
                    ['product' => 'Beef Scotch Fillet', 'quantity' => 50, 'unit' => 'kg', 'specifications' => 'MSA graded, consistent marbling'],
                    ['product' => 'Chicken Breast', 'quantity' => 80, 'unit' => 'kg', 'specifications' => 'Free range, trimmed'],
                    ['product' => 'Lamb Cutlets', 'quantity' => 30, 'unit' => 'kg', 'specifications' => 'French trim, even thickness'],
                ]
            ],
            [
                'title' => 'Fresh Seafood - Weekly Requirements',
                'description' => 'Established seafood restaurant looking for daily fresh fish and shellfish. Must be ocean fresh with full traceability.',
                'category' => 'fresh-fish',
                'urgency' => 'high',
                'budget_min' => 2000.00,
                'budget_max' => 3500.00,
                'delivery_days' => 2,
                'status' => 'open',
                'items' => [
                    ['product' => 'Atlantic Salmon', 'quantity' => 25, 'unit' => 'kg', 'specifications' => 'Sashimi grade, skin on'],
                    ['product' => 'King Prawns', 'quantity' => 15, 'unit' => 'kg', 'specifications' => 'Large size, shell on'],
                    ['product' => 'Barramundi Fillets', 'quantity' => 20, 'unit' => 'kg', 'specifications' => 'Pin bone removed'],
                ]
            ],
            [
                'title' => 'Dairy Products - Cafe Chain Monthly Order',
                'description' => 'Looking for reliable dairy supplier for our 5 cafe locations. Need consistent quality milk, cheese, and cream.',
                'category' => 'milk-cream',
                'urgency' => 'medium',
                'budget_min' => 1500.00,
                'budget_max' => 2500.00,
                'delivery_days' => 4,
                'status' => 'open',
                'items' => [
                    ['product' => 'Full Cream Milk', 'quantity' => 200, 'unit' => 'litre', 'specifications' => '3.5% fat content'],
                    ['product' => 'Heavy Cream', 'quantity' => 50, 'unit' => 'litre', 'specifications' => '35% fat content'],
                    ['product' => 'Cheddar Cheese', 'quantity' => 25, 'unit' => 'kg', 'specifications' => 'Block form, aged 12 months'],
                ]
            ],
            [
                'title' => 'Asian Vegetables and Ingredients',
                'description' => 'Authentic Asian restaurant requiring specialty vegetables and dry goods. Freshness and authenticity crucial.',
                'category' => 'vegetables',
                'urgency' => 'high',
                'budget_min' => 600.00,
                'budget_max' => 1000.00,
                'delivery_days' => 1,
                'status' => 'awarded',
                'items' => [
                    ['product' => 'Bok Choy', 'quantity' => 15, 'unit' => 'kg', 'specifications' => 'Baby bok choy, fresh'],
                    ['product' => 'Asian Eggplant', 'quantity' => 12, 'unit' => 'kg', 'specifications' => 'Long purple variety'],
                    ['product' => 'Water Spinach', 'quantity' => 8, 'unit' => 'kg', 'specifications' => 'Fresh, young shoots'],
                ]
            ],
        ];

        $createdRFQs = 0;

        foreach ($rfqTemplates as $index => $template) {
            // Find category
            $category = $categories->firstWhere('slug', $template['category']);
            if (!$category) {
                $category = $categories->where('parent_id', 1)->random(); // Fallback to any produce subcategory
            }

            // Select a random buyer
            $buyer = $buyers->random();

            // Create RFQ using direct DB insert to match actual migration structure
            $rfqId = DB::table('rfqs')->insertGetId([
                'rfq_number' => 'RFQ-' . str_pad($index + 1001, 6, '0', STR_PAD_LEFT),
                'buyer_id' => $buyer->id,
                'title' => $template['title'],
                'description' => $template['description'],
                'category_id' => $category->id,
                'status' => $template['status'],
                'urgency' => $template['urgency'],
                'delivery_date' => now()->addDays($template['delivery_days'])->format('Y-m-d'),
                'delivery_time' => ['Morning (6AM-10AM)', 'Afternoon (2PM-6PM)', 'Evening (6PM-8PM)'][rand(0, 2)],
                'delivery_address' => $buyer->shipping_address . ', ' . $buyer->shipping_suburb . ', ' . $buyer->shipping_state . ' ' . $buyer->shipping_postcode,
                'delivery_instructions' => $this->getDeliveryInstructions($buyer->business_type),
                'budget_min' => $template['budget_min'],
                'budget_max' => $template['budget_max'],
                'items' => json_encode($template['items']),
                'preferred_vendors' => json_encode($this->getPreferredVendors($vendors, $template['category'])),
                'requirements' => json_encode($this->getSpecialRequirements($template['category'])),
                'is_public' => rand(0, 10) > 2 ? 1 : 0, // 80% public
                'max_quotes' => rand(3, 8),
                'quote_count' => $template['status'] === 'open' ? rand(0, 5) : ($template['status'] === 'closed' ? rand(3, 8) : 0),
                'view_count' => rand(15, 200),
                'published_at' => $template['status'] !== 'draft' ? now()->subDays(rand(1, 14)) : null,
                'closes_at' => $template['status'] === 'open' ? now()->addDays(rand(2, 14)) : ($template['status'] === 'closed' ? now()->subDays(rand(1, 3)) : null),
                'awarded_at' => $template['status'] === 'awarded' ? now()->subDays(rand(1, 7)) : null,
                'awarded_vendor_id' => $template['status'] === 'awarded' ? $vendors->random()->id : null,
                'metadata' => json_encode([
                    'priority_level' => ['standard', 'high', 'urgent'][rand(0, 2)],
                    'payment_terms' => $buyer->payment_terms,
                    'delivery_window' => '2 hour window',
                    'quality_standards' => 'Restaurant grade'
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $createdRFQs++;
        }

        // Create some expired RFQs
        for ($i = 0; $i < 3; $i++) {
            $buyer = $buyers->random();
            $category = $categories->random();
            
            DB::table('rfqs')->insert([
                'rfq_number' => 'RFQ-' . str_pad(2000 + $i, 6, '0', STR_PAD_LEFT),
                'buyer_id' => $buyer->id,
                'title' => 'Weekly Supply Order - Expired',
                'description' => 'This RFQ has expired and needs to be reposted.',
                'category_id' => $category->id,
                'status' => 'expired',
                'urgency' => 'medium',
                'delivery_date' => now()->subDays(rand(7, 21))->format('Y-m-d'),
                'delivery_address' => $buyer->shipping_address,
                'budget_min' => rand(300, 1000),
                'budget_max' => rand(1000, 3000),
                'items' => json_encode([
                    ['product' => 'Mixed Items', 'quantity' => rand(10, 50), 'unit' => 'kg']
                ]),
                'is_public' => 1,
                'published_at' => now()->subDays(rand(14, 30)),
                'closes_at' => now()->subDays(rand(1, 7)),
                'view_count' => rand(50, 150),
                'quote_count' => rand(0, 2),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $createdRFQs++;
        }

        $this->command->info("RFQs seeded successfully. Created {$createdRFQs} RFQs.");
    }

    private function getDeliveryInstructions($businessType): string
    {
        $instructions = [
            'restaurant' => 'Please deliver to kitchen entrance. Call upon arrival. Unload quickly during service hours.',
            'cafe' => 'Use rear entrance. Early morning delivery preferred before 8AM. Stack pallets neatly.',
            'grocery' => 'Loading dock access. Delivery receipt required. Check expiry dates on arrival.',
            'distributor' => 'Warehouse entrance B. Quality inspection will be conducted. Paperwork must be complete.',
            'other' => 'Please call 30 minutes before delivery. Use main entrance. Handle with care.'
        ];

        return $instructions[$businessType] ?? $instructions['other'];
    }

    private function getPreferredVendors($vendors, $category): array
    {
        // Select 1-3 vendors that might specialize in this category
        $selectedVendors = $vendors->random(rand(1, 3));
        return $selectedVendors->pluck('id')->toArray();
    }

    private function getSpecialRequirements($category): array
    {
        $requirements = [
            'fruits' => [
                'Organic certification preferred',
                'Same day delivery required',
                'Quality inspection on delivery',
                'Consistent sizing important'
            ],
            'beef' => [
                'HACCP certification required',
                'Temperature controlled delivery',
                'Use by date minimum 3 days',
                'Vacuum packed preferred'
            ],
            'fresh-fish' => [
                'Daily fresh delivery essential',
                'Full traceability documentation',
                'Ice packed delivery required',
                'MSC certification preferred'
            ],
            'milk-cream' => [
                'Cold chain maintenance critical',
                'Consistent fat content required',
                'Minimal handling preferred',
                'Clear labeling essential'
            ]
        ];

        $categoryReqs = $requirements[$category] ?? [
            'Quality guarantee required',
            'Timely delivery essential',
            'Competitive pricing needed'
        ];

        // Return 2-4 random requirements
        return array_slice($categoryReqs, 0, rand(2, min(4, count($categoryReqs))));
    }
}