<?php

namespace Tests\Feature\Buyer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\Category;
use App\Models\Rfq;
use App\Models\Quote;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * RFQ (REQUEST FOR QUOTE) SYSTEM TEST SUITE
 * 
 * Test Authority: Core B2B marketplace functionality
 * Coverage Target: 100% of RFQ workflows
 * Data Strategy: Real Sydney Markets data
 * 
 * @author Test Architect Agent
 * @version 1.0.0
 */
class RfqSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $buyer;
    protected $vendors;
    protected $products;
    protected $categories;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create real Sydney Markets categories
        $this->categories = $this->createRealCategories();
        
        // Create real vendor accounts
        $this->vendors = $this->createRealVendors();
        
        // Create real products
        $this->products = $this->createRealProducts();
        
        // Create authenticated buyer
        $this->buyer = Buyer::factory()->create([
            'business_name' => 'Woolworths Group Limited',
            'abn' => '88000014675'
        ]);
        
        $this->actingAs($this->buyer, 'buyer');
    }

    /**
     * ========================================
     * RFQ CREATION TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_create_rfq_with_multiple_products()
    {
        $rfqData = [
            'title' => 'Weekly Fresh Produce Order',
            'description' => 'Regular weekly supply for 50 stores',
            'delivery_date' => now()->addDays(7)->format('Y-m-d'),
            'delivery_location' => 'Woolworths Distribution Centre, Eastern Creek',
            'items' => [
                [
                    'product_id' => $this->products[0]->id,
                    'quantity' => 1000,
                    'unit' => 'kg',
                    'specifications' => 'Grade A, properly packed'
                ],
                [
                    'product_id' => $this->products[1]->id,
                    'quantity' => 500,
                    'unit' => 'boxes',
                    'specifications' => '10kg boxes, refrigerated transport'
                ]
            ],
            'payment_terms' => '30 days',
            'special_requirements' => 'HACCP certified vendors only'
        ];
        
        $response = $this->postJson('/api/buyer/rfqs', $rfqData);
        
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'RFQ created successfully'
        ]);
        
        // Verify RFQ is saved
        $this->assertDatabaseHas('rfqs', [
            'buyer_id' => $this->buyer->id,
            'title' => $rfqData['title'],
            'status' => 'open',
            'delivery_date' => $rfqData['delivery_date']
        ]);
        
        // Verify RFQ items are saved
        $rfq = Rfq::latest()->first();
        $this->assertDatabaseHas('rfq_items', [
            'rfq_id' => $rfq->id,
            'product_id' => $this->products[0]->id,
            'quantity' => 1000
        ]);
    }

    /** @test */
    public function test_rfq_validates_minimum_quantity_requirements()
    {
        $rfqData = [
            'title' => 'Small Order Test',
            'items' => [
                [
                    'product_id' => $this->products[0]->id,
                    'quantity' => 0.5, // Below minimum
                    'unit' => 'kg'
                ]
            ]
        ];
        
        $response = $this->postJson('/api/buyer/rfqs', $rfqData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.quantity']);
        $response->assertJson([
            'errors' => [
                'items.0.quantity' => ['Minimum order quantity is 1 kg']
            ]
        ]);
    }

    /** @test */
    public function test_rfq_delivery_date_must_be_future()
    {
        $rfqData = [
            'title' => 'Test RFQ',
            'delivery_date' => now()->subDay()->format('Y-m-d'), // Past date
            'items' => [
                ['product_id' => $this->products[0]->id, 'quantity' => 100]
            ]
        ];
        
        $response = $this->postJson('/api/buyer/rfqs', $rfqData);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['delivery_date']);
    }

    /**
     * ========================================
     * VENDOR NOTIFICATION TESTS
     * ========================================
     */

    /** @test */
    public function test_rfq_notifies_relevant_vendors_only()
    {
        // Create vendors with specific categories
        $fruitVendor = Vendor::factory()->create();
        $fruitVendor->categories()->attach(
            Category::where('name', 'Fresh Fruits')->first()
        );
        
        $meatVendor = Vendor::factory()->create();
        $meatVendor->categories()->attach(
            Category::where('name', 'Meat & Poultry')->first()
        );
        
        // Create RFQ for fruits only
        $rfqData = [
            'title' => 'Fruit Supply RFQ',
            'category_id' => Category::where('name', 'Fresh Fruits')->first()->id,
            'items' => [
                [
                    'product_id' => Product::where('category_id', 
                        Category::where('name', 'Fresh Fruits')->first()->id
                    )->first()->id,
                    'quantity' => 100
                ]
            ]
        ];
        
        $response = $this->postJson('/api/buyer/rfqs', $rfqData);
        
        // Verify fruit vendor received notification
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'App\Models\Vendor',
            'notifiable_id' => $fruitVendor->id,
            'type' => 'NewRfqNotification'
        ]);
        
        // Verify meat vendor did NOT receive notification
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => 'App\Models\Vendor',
            'notifiable_id' => $meatVendor->id,
            'type' => 'NewRfqNotification'
        ]);
    }

    /** @test */
    public function test_rfq_notification_contains_correct_details()
    {
        $vendor = $this->vendors[0];
        
        $rfqData = [
            'title' => 'Urgent Produce Order',
            'items' => [
                ['product_id' => $this->products[0]->id, 'quantity' => 500]
            ]
        ];
        
        $response = $this->postJson('/api/buyer/rfqs', $rfqData);
        $rfq = Rfq::latest()->first();
        
        $notification = DB::table('notifications')
            ->where('notifiable_id', $vendor->id)
            ->first();
        
        $data = json_decode($notification->data, true);
        
        $this->assertEquals($rfq->id, $data['rfq_id']);
        $this->assertEquals('Urgent Produce Order', $data['title']);
        $this->assertEquals($this->buyer->business_name, $data['buyer_name']);
        $this->assertArrayHasKey('deadline', $data);
    }

    /**
     * ========================================
     * QUOTE SUBMISSION TESTS
     * ========================================
     */

    /** @test */
    public function test_vendor_can_submit_quote_for_rfq()
    {
        $rfq = Rfq::factory()->create([
            'buyer_id' => $this->buyer->id,
            'status' => 'open'
        ]);
        
        $vendor = $this->vendors[0];
        $this->actingAs($vendor, 'vendor');
        
        $quoteData = [
            'rfq_id' => $rfq->id,
            'total_amount' => 5000.00,
            'validity_days' => 7,
            'delivery_terms' => 'FOB Sydney Markets',
            'payment_terms' => '30 days net',
            'items' => [
                [
                    'rfq_item_id' => $rfq->items[0]->id,
                    'unit_price' => 5.00,
                    'quantity' => 1000,
                    'total' => 5000.00
                ]
            ],
            'notes' => 'Premium quality guaranteed'
        ];
        
        $response = $this->postJson('/api/vendor/quotes', $quoteData);
        
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Quote submitted successfully'
        ]);
        
        // Verify quote is saved
        $this->assertDatabaseHas('quotes', [
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id,
            'total_amount' => 5000.00,
            'status' => 'submitted'
        ]);
        
        // Verify buyer is notified
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->buyer->id,
            'type' => 'NewQuoteNotification'
        ]);
    }

    /** @test */
    public function test_vendor_cannot_quote_on_closed_rfq()
    {
        $rfq = Rfq::factory()->create([
            'buyer_id' => $this->buyer->id,
            'status' => 'closed'
        ]);
        
        $vendor = $this->vendors[0];
        $this->actingAs($vendor, 'vendor');
        
        $response = $this->postJson('/api/vendor/quotes', [
            'rfq_id' => $rfq->id,
            'total_amount' => 5000.00
        ]);
        
        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'This RFQ is no longer accepting quotes'
        ]);
    }

    /** @test */
    public function test_vendor_cannot_submit_duplicate_quotes()
    {
        $rfq = Rfq::factory()->create([
            'buyer_id' => $this->buyer->id,
            'status' => 'open'
        ]);
        
        $vendor = $this->vendors[0];
        
        // Create first quote
        Quote::factory()->create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id
        ]);
        
        $this->actingAs($vendor, 'vendor');
        
        $response = $this->postJson('/api/vendor/quotes', [
            'rfq_id' => $rfq->id,
            'total_amount' => 6000.00
        ]);
        
        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'You have already submitted a quote for this RFQ'
        ]);
    }

    /**
     * ========================================
     * QUOTE COMPARISON TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_view_all_quotes_for_rfq()
    {
        $rfq = Rfq::factory()->create([
            'buyer_id' => $this->buyer->id
        ]);
        
        // Create multiple quotes from different vendors
        $quotes = [];
        foreach ($this->vendors->take(3) as $vendor) {
            $quotes[] = Quote::factory()->create([
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'total_amount' => rand(4000, 6000)
            ]);
        }
        
        $response = $this->getJson("/api/buyer/rfqs/{$rfq->id}/quotes");
        
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        
        // Verify quotes are sorted by price (lowest first)
        $prices = collect($response->json('data'))->pluck('total_amount');
        $this->assertEquals($prices->sort()->values(), $prices);
    }

    /** @test */
    public function test_quote_comparison_shows_vendor_ratings()
    {
        $rfq = Rfq::factory()->create([
            'buyer_id' => $this->buyer->id
        ]);
        
        $vendor = $this->vendors[0];
        $vendor->update(['average_rating' => 4.5, 'total_reviews' => 120]);
        
        Quote::factory()->create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $vendor->id
        ]);
        
        $response = $this->getJson("/api/buyer/rfqs/{$rfq->id}/quotes");
        
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'vendor_rating' => 4.5,
            'vendor_reviews' => 120
        ]);
    }

    /**
     * ========================================
     * QUOTE ACCEPTANCE TESTS
     * ========================================
     */

    /** @test */
    public function test_buyer_can_accept_quote()
    {
        $rfq = Rfq::factory()->create([
            'buyer_id' => $this->buyer->id,
            'status' => 'open'
        ]);
        
        $quote = Quote::factory()->create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $this->vendors[0]->id,
            'total_amount' => 5000.00,
            'status' => 'submitted'
        ]);
        
        $response = $this->postJson("/api/buyer/quotes/{$quote->id}/accept");
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quote accepted successfully',
            'order_id' => true
        ]);
        
        // Verify quote status updated
        $quote->refresh();
        $this->assertEquals('accepted', $quote->status);
        
        // Verify RFQ is closed
        $rfq->refresh();
        $this->assertEquals('closed', $rfq->status);
        
        // Verify order is created
        $this->assertDatabaseHas('orders', [
            'buyer_id' => $this->buyer->id,
            'vendor_id' => $this->vendors[0]->id,
            'quote_id' => $quote->id,
            'total_amount' => 5000.00,
            'status' => 'pending'
        ]);
        
        // Verify vendor is notified
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->vendors[0]->id,
            'type' => 'QuoteAcceptedNotification'
        ]);
    }

    /** @test */
    public function test_accepting_quote_rejects_other_quotes()
    {
        $rfq = Rfq::factory()->create([
            'buyer_id' => $this->buyer->id
        ]);
        
        $acceptedQuote = Quote::factory()->create([
            'rfq_id' => $rfq->id,
            'vendor_id' => $this->vendors[0]->id
        ]);
        
        $rejectedQuotes = [];
        foreach ($this->vendors->slice(1, 2) as $vendor) {
            $rejectedQuotes[] = Quote::factory()->create([
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'status' => 'submitted'
            ]);
        }
        
        $response = $this->postJson("/api/buyer/quotes/{$acceptedQuote->id}/accept");
        
        $response->assertStatus(200);
        
        // Verify other quotes are rejected
        foreach ($rejectedQuotes as $quote) {
            $quote->refresh();
            $this->assertEquals('rejected', $quote->status);
            
            // Verify vendors are notified of rejection
            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $quote->vendor_id,
                'type' => 'QuoteRejectedNotification'
            ]);
        }
    }

    /** @test */
    public function test_buyer_can_negotiate_quote()
    {
        $quote = Quote::factory()->create([
            'vendor_id' => $this->vendors[0]->id,
            'total_amount' => 5000.00
        ]);
        
        $negotiationData = [
            'proposed_amount' => 4500.00,
            'message' => 'Can you match competitor pricing at $4.50 per kg?',
            'requested_changes' => [
                'delivery_terms' => 'Free delivery to our warehouse',
                'payment_terms' => '45 days net'
            ]
        ];
        
        $response = $this->postJson("/api/buyer/quotes/{$quote->id}/negotiate", $negotiationData);
        
        $response->assertStatus(200);
        
        // Verify negotiation is recorded
        $this->assertDatabaseHas('quote_negotiations', [
            'quote_id' => $quote->id,
            'proposed_amount' => 4500.00,
            'status' => 'pending'
        ]);
        
        // Verify vendor is notified
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->vendors[0]->id,
            'type' => 'QuoteNegotiationNotification'
        ]);
    }

    /**
     * ========================================
     * RFQ ANALYTICS TESTS
     * ========================================
     */

    /** @test */
    public function test_rfq_tracks_vendor_response_rate()
    {
        $rfq = Rfq::factory()->create([
            'buyer_id' => $this->buyer->id
        ]);
        
        // 3 vendors notified, 2 respond with quotes
        $notifiedVendors = $this->vendors->take(3);
        foreach ($notifiedVendors as $vendor) {
            DB::table('rfq_invitations')->insert([
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'sent_at' => now()
            ]);
        }
        
        // 2 vendors submit quotes
        foreach ($notifiedVendors->take(2) as $vendor) {
            Quote::factory()->create([
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id
            ]);
        }
        
        $response = $this->getJson("/api/buyer/rfqs/{$rfq->id}/analytics");
        
        $response->assertStatus(200);
        $response->assertJson([
            'vendors_invited' => 3,
            'vendors_responded' => 2,
            'response_rate' => 66.67,
            'average_quote_amount' => true,
            'lowest_quote' => true,
            'highest_quote' => true
        ]);
    }

    /**
     * ========================================
     * PERFORMANCE TESTS
     * ========================================
     */

    /** @test */
    public function test_rfq_creation_handles_large_product_lists()
    {
        $items = [];
        for ($i = 0; $i < 100; $i++) {
            $items[] = [
                'product_id' => $this->products->random()->id,
                'quantity' => rand(10, 1000),
                'unit' => 'kg'
            ];
        }
        
        $startTime = microtime(true);
        
        $response = $this->postJson('/api/buyer/rfqs', [
            'title' => 'Large Order Test',
            'items' => $items
        ]);
        
        $executionTime = microtime(true) - $startTime;
        
        $response->assertStatus(201);
        $this->assertLessThan(2, $executionTime, 'RFQ creation took more than 2 seconds');
        
        // Verify all items were saved
        $rfq = Rfq::latest()->first();
        $this->assertEquals(100, $rfq->items()->count());
    }

    /** @test */
    public function test_quote_comparison_loads_efficiently()
    {
        $rfq = Rfq::factory()->create(['buyer_id' => $this->buyer->id]);
        
        // Create 50 quotes
        foreach ($this->vendors->take(50) as $vendor) {
            Quote::factory()->create([
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id
            ]);
        }
        
        $startTime = microtime(true);
        
        $response = $this->getJson("/api/buyer/rfqs/{$rfq->id}/quotes");
        
        $executionTime = microtime(true) - $startTime;
        
        $response->assertStatus(200);
        $this->assertLessThan(0.5, $executionTime, 'Quote loading exceeded 500ms');
        
        // Verify N+1 query problem doesn't exist
        DB::enableQueryLog();
        $this->getJson("/api/buyer/rfqs/{$rfq->id}/quotes");
        $queryCount = count(DB::getQueryLog());
        $this->assertLessThan(10, $queryCount, 'Too many database queries');
    }

    /**
     * ========================================
     * HELPER METHODS
     * ========================================
     */

    private function createRealCategories()
    {
        $categories = [
            'Fresh Fruits',
            'Fresh Vegetables', 
            'Meat & Poultry',
            'Seafood',
            'Dairy & Eggs',
            'Herbs & Spices',
            'Nuts & Dried Fruits',
            'Flowers & Plants'
        ];
        
        foreach ($categories as $name) {
            Category::create([
                'name' => $name,
                'slug' => str_slug($name),
                'description' => "Premium {$name} from Sydney Markets"
            ]);
        }
        
        return Category::all();
    }

    private function createRealVendors()
    {
        $vendors = [];
        
        $realVendorData = [
            ['name' => 'Fresh Direct Produce', 'abn' => '12345678901'],
            ['name' => 'Sydney Fresh Markets', 'abn' => '98765432109'],
            ['name' => 'Premium Fruits Co', 'abn' => '11223344556'],
            ['name' => 'Quality Vegetables Pty Ltd', 'abn' => '66778899001'],
            ['name' => 'Organic Harvest Sydney', 'abn' => '55443322110']
        ];
        
        foreach ($realVendorData as $data) {
            $vendors[] = Vendor::factory()->create([
                'business_name' => $data['name'],
                'abn' => $data['abn'],
                'status' => 'active'
            ]);
        }
        
        return collect($vendors);
    }

    private function createRealProducts()
    {
        $products = [];
        
        $realProductData = [
            ['name' => 'Bananas Cavendish', 'category' => 'Fresh Fruits', 'unit' => 'kg'],
            ['name' => 'Apples Pink Lady', 'category' => 'Fresh Fruits', 'unit' => 'kg'],
            ['name' => 'Tomatoes Roma', 'category' => 'Fresh Vegetables', 'unit' => 'kg'],
            ['name' => 'Potatoes Brushed', 'category' => 'Fresh Vegetables', 'unit' => 'kg'],
            ['name' => 'Chicken Breast', 'category' => 'Meat & Poultry', 'unit' => 'kg']
        ];
        
        foreach ($realProductData as $data) {
            $category = Category::where('name', $data['category'])->first();
            $products[] = Product::factory()->create([
                'name' => $data['name'],
                'category_id' => $category->id,
                'unit_of_measure' => $data['unit'],
                'min_order_quantity' => 1,
                'status' => 'active'
            ]);
        }
        
        return collect($products);
    }
}