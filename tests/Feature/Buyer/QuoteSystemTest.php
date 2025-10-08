<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\QuoteService;
use App\Services\OrderService;
use App\Repositories\QuoteRepository;
use App\Events\QuoteAccepted;
use App\Events\OrderCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Comprehensive test suite for Quote to Order workflow
 *
 * Tests cover:
 * - Quote acceptance flow
 * - Order creation
 * - Event firing
 * - Cache clearing
 * - Error handling
 * - Performance
 */
class QuoteSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $quoteService;
    protected $orderService;
    protected $quoteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->quoteRepository = new QuoteRepository();
        $this->quoteService = new QuoteService($this->quoteRepository);
        $this->orderService = new OrderService($this->quoteRepository);

        // Create test data
        $this->setupTestData();
    }

    /**
     * Test: Quote can be accepted and creates order
     */
    public function test_accepting_quote_creates_order()
    {
        Event::fake();

        $buyer = $this->createBuyer();
        $quote = $this->createPendingQuote($buyer->id);

        $this->actingAs($buyer);

        // Accept the quote
        $order = $this->orderService->createOrderFromQuote($quote->id, $buyer->id);

        // Assertions
        $this->assertNotNull($order);
        $this->assertEquals($quote->total_amount, $order['total_amount']);
        $this->assertEquals('pending', $order['status']);

        // Check quote status updated
        $updatedQuote = DB::table('quotes')->find($quote->id);
        $this->assertEquals('accepted', $updatedQuote->status);

        // Check events fired
        Event::assertDispatched(QuoteAccepted::class);
        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * Test: Expired quote cannot be accepted
     */
    public function test_expired_quote_cannot_be_accepted()
    {
        $this->expectException(\App\Exceptions\BusinessException::class);
        $this->expectExceptionMessage('Quote has expired');

        $buyer = $this->createBuyer();
        $quote = $this->createExpiredQuote($buyer->id);

        $this->actingAs($buyer);

        $this->orderService->createOrderFromQuote($quote->id, $buyer->id);
    }

    /**
     * Test: Other quotes are rejected when one is accepted
     */
    public function test_accepting_quote_rejects_other_quotes()
    {
        $buyer = $this->createBuyer();
        $rfq = $this->createRfq($buyer->id);
        $quote1 = $this->createQuoteForRfq($rfq->id);
        $quote2 = $this->createQuoteForRfq($rfq->id);
        $quote3 = $this->createQuoteForRfq($rfq->id);

        $this->actingAs($buyer);

        // Accept quote1
        $this->orderService->createOrderFromQuote($quote1->id, $buyer->id);

        // Check other quotes are rejected
        $quote2Updated = DB::table('quotes')->find($quote2->id);
        $quote3Updated = DB::table('quotes')->find($quote3->id);

        $this->assertEquals('rejected', $quote2Updated->status);
        $this->assertEquals('rejected', $quote3Updated->status);
        $this->assertEquals('Another quote was accepted', $quote2Updated->rejection_reason);
    }

    /**
     * Test: Cache is cleared after quote acceptance
     */
    public function test_cache_is_cleared_after_quote_acceptance()
    {
        $buyer = $this->createBuyer();
        $quote = $this->createPendingQuote($buyer->id);

        // Pre-populate cache
        Cache::put("quotes:buyer:{$buyer->id}:pending", 'test_data', 300);
        Cache::put("quotes:buyer:{$buyer->id}:stats", 'test_stats', 300);

        $this->actingAs($buyer);

        // Accept quote
        $this->orderService->createOrderFromQuote($quote->id, $buyer->id);

        // Check cache cleared
        $this->assertNull(Cache::get("quotes:buyer:{$buyer->id}:pending"));
        $this->assertNull(Cache::get("quotes:buyer:{$buyer->id}:stats"));
    }

    /**
     * Test: Repository caching works correctly
     */
    public function test_repository_caching_works()
    {
        $buyer = $this->createBuyer();
        $this->createPendingQuote($buyer->id);

        // First call - hits database
        $quotes1 = $this->quoteRepository->getPendingQuotesForBuyer($buyer->id);

        // Modify database directly
        DB::table('quotes')->where('id', $quotes1->first()->id)->update(['total_amount' => 99999]);

        // Second call - should hit cache
        $quotes2 = $this->quoteRepository->getPendingQuotesForBuyer($buyer->id);

        // Amount should be same as first call (from cache)
        $this->assertEquals($quotes1->first()->total_amount, $quotes2->first()->total_amount);
        $this->assertNotEquals(99999, $quotes2->first()->total_amount);

        // Clear cache and fetch again
        Cache::forget("quotes:buyer:{$buyer->id}:pending");
        $quotes3 = $this->quoteRepository->getPendingQuotesForBuyer($buyer->id);

        // Now should get updated value
        $this->assertEquals(99999, $quotes3->first()->total_amount);
    }

    /**
     * Test: Quote comparison works correctly
     */
    public function test_quote_comparison()
    {
        $buyer = $this->createBuyer();
        $rfq = $this->createRfq($buyer->id);

        $quote1 = $this->createQuoteForRfq($rfq->id, ['total_amount' => 1000]);
        $quote2 = $this->createQuoteForRfq($rfq->id, ['total_amount' => 800]);
        $quote3 = $this->createQuoteForRfq($rfq->id, ['total_amount' => 1200]);

        $comparison = $this->quoteService->compareQuotes(
            [$quote1->id, $quote2->id, $quote3->id],
            $buyer->id
        );

        $this->assertArrayHasKey('quotes', $comparison);
        $this->assertArrayHasKey('recommendation', $comparison);
        $this->assertEquals(3, count($comparison['quotes']));

        // Cheapest quote should be recommended
        $this->assertEquals($quote2->id, $comparison['recommendation']['quote_id']);
    }

    /**
     * Test: Order statistics are calculated correctly
     */
    public function test_order_statistics_calculation()
    {
        $buyer = $this->createBuyer();

        // Create some orders
        $this->createOrder($buyer->id, ['status' => 'pending', 'total_amount' => 1000]);
        $this->createOrder($buyer->id, ['status' => 'completed', 'total_amount' => 1500]);
        $this->createOrder($buyer->id, ['status' => 'completed', 'total_amount' => 2000]);
        $this->createOrder($buyer->id, ['status' => 'cancelled', 'total_amount' => 500]);

        $stats = $this->orderService->getOrderStatistics($buyer->id);

        $this->assertEquals(4, $stats['total_orders']);
        $this->assertEquals(1, $stats['pending_orders']);
        $this->assertEquals(2, $stats['completed_orders']);
        $this->assertEquals(4500, $stats['total_spent']); // Excludes cancelled
        $this->assertEquals(1500, $stats['average_order_value']);
    }

    /**
     * Test: Performance - Quote acceptance completes quickly
     */
    public function test_quote_acceptance_performance()
    {
        $buyer = $this->createBuyer();
        $quote = $this->createPendingQuote($buyer->id);

        $this->actingAs($buyer);

        $startTime = microtime(true);
        $this->orderService->createOrderFromQuote($quote->id, $buyer->id);
        $endTime = microtime(true);

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Should complete within 200ms
        $this->assertLessThan(200, $executionTime, 'Quote acceptance took too long');
    }

    /**
     * Test: Transaction rollback on failure
     */
    public function test_transaction_rollback_on_failure()
    {
        $buyer = $this->createBuyer();
        $quote = $this->createPendingQuote($buyer->id);

        // Mock a failure in order creation
        DB::shouldReceive('table')
            ->with('orders')
            ->andThrow(new \Exception('Database error'));

        try {
            $this->orderService->createOrderFromQuote($quote->id, $buyer->id);
        } catch (\Exception $e) {
            // Expected exception
        }

        // Quote should still be pending (transaction rolled back)
        $quoteAfter = DB::table('quotes')->find($quote->id);
        $this->assertEquals('pending', $quoteAfter->status);
    }

    /**
     * Helper Methods
     */
    protected function setupTestData()
    {
        // Create vendors
        DB::table('vendors')->insert([
            'id' => 1,
            'name' => 'Test Vendor 1',
            'email' => 'vendor1@test.com',
            'rating' => 4.5,
            'verified' => true,
            'created_at' => now()
        ]);

        DB::table('vendors')->insert([
            'id' => 2,
            'name' => 'Test Vendor 2',
            'email' => 'vendor2@test.com',
            'rating' => 4.0,
            'verified' => false,
            'created_at' => now()
        ]);
    }

    protected function createBuyer()
    {
        return DB::table('buyers')->insertGetId([
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => bcrypt('password'),
            'created_at' => now()
        ]);
    }

    protected function createRfq($buyerId)
    {
        return DB::table('rfqs')->insertGetId([
            'buyer_id' => $buyerId,
            'title' => 'Test RFQ',
            'description' => 'Test description',
            'estimated_budget' => 1000,
            'status' => 'open',
            'created_at' => now()
        ]);
    }

    protected function createPendingQuote($buyerId)
    {
        $rfqId = $this->createRfq($buyerId);
        return $this->createQuoteForRfq($rfqId);
    }

    protected function createQuoteForRfq($rfqId, $attributes = [])
    {
        $data = array_merge([
            'rfq_id' => $rfqId,
            'vendor_id' => 1,
            'total_amount' => 900,
            'status' => 'pending',
            'valid_until' => now()->addDays(7),
            'delivery_days' => 3,
            'created_at' => now()
        ], $attributes);

        $id = DB::table('quotes')->insertGetId($data);
        return DB::table('quotes')->find($id);
    }

    protected function createExpiredQuote($buyerId)
    {
        $rfqId = $this->createRfq($buyerId);
        return $this->createQuoteForRfq($rfqId, [
            'valid_until' => now()->subDay()
        ]);
    }

    protected function createOrder($buyerId, $attributes = [])
    {
        $data = array_merge([
            'order_number' => 'ORD-' . uniqid(),
            'buyer_id' => $buyerId,
            'vendor_id' => 1,
            'total_amount' => 1000,
            'status' => 'pending',
            'created_at' => now()
        ], $attributes);

        return DB::table('orders')->insertGetId($data);
    }
}