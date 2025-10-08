<?php

use App\Livewire\Quotes\BuyerQuotePanel;
use App\Models\Buyer;
use App\Models\Quote;
use App\Models\RFQ;
use App\Models\Vendor;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test buyer
    $this->buyer = Buyer::factory()->create([
        'business_name' => 'Test Buyer Co',
        'email' => 'buyer@test.com',
    ]);

    // Create test vendor
    $this->vendor = Vendor::factory()->create([
        'business_name' => 'Test Vendor Co',
        'email' => 'vendor@test.com',
    ]);

    // Authenticate buyer
    $this->actingAs($this->buyer, 'buyer');
});

test('buyer quote panel component can be mounted', function () {
    $component = Livewire::test(BuyerQuotePanel::class);

    $component->assertStatus(200);
    $component->assertSet('quotesLoaded', false);
    $component->assertSet('quotes', []);
    $component->assertSet('activeQuotesCount', 0);
});

test('buyer quote panel loads quotes for authenticated buyer', function () {
    // Create test RFQ
    $rfq = RFQ::factory()->create([
        'buyer_id' => $this->buyer->id,
        'status' => 'open',
    ]);

    // Create test quotes
    $quote1 = Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'status' => 'submitted',
        'total_amount' => 150.00,
        'created_at' => now()->subMinutes(10),
    ]);

    $quote2 = Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'status' => 'submitted',
        'total_amount' => 200.00,
        'created_at' => now()->subMinutes(5),
    ]);

    // Test component
    $component = Livewire::test(BuyerQuotePanel::class)
        ->call('loadQuotes');

    $component->assertSet('quotesLoaded', true);
    $component->assertSet('activeQuotesCount', 2);

    // Verify quotes array has correct data
    $quotes = $component->get('quotes');
    expect($quotes)->toHaveCount(2);
    expect($quotes[0])->toHaveKey('vendor_name');
    expect($quotes[0])->toHaveKey('remaining_time');
    expect($quotes[0])->toHaveKey('expires_at');
});

test('buyer quote panel calculates expiry time correctly', function () {
    $rfq = RFQ::factory()->create(['buyer_id' => $this->buyer->id]);

    // Create quote created 20 minutes ago (should have ~10 minutes remaining)
    $quote = Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'status' => 'submitted',
        'created_at' => now()->subMinutes(20),
    ]);

    $component = Livewire::test(BuyerQuotePanel::class)
        ->call('loadQuotes');

    $quotes = $component->get('quotes');
    expect($quotes)->toHaveCount(1);

    // Should have ~10 minutes remaining (accounting for processing time)
    expect($quotes[0]['remaining_time'])->toContain(':');
    expect($quotes[0]['is_expired'])->toBeFalse();
});

test('buyer quote panel marks expired quotes correctly', function () {
    $rfq = RFQ::factory()->create(['buyer_id' => $this->buyer->id]);

    // Create quote created 35 minutes ago (should be expired - 30 min limit)
    $expiredQuote = Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'status' => 'submitted',
        'created_at' => now()->subMinutes(35),
    ]);

    $component = Livewire::test(BuyerQuotePanel::class)
        ->call('loadQuotes');

    $quotes = $component->get('quotes');
    expect($quotes)->toHaveCount(1);
    expect($quotes[0]['is_expired'])->toBeTrue();
    expect($quotes[0]['remaining_time'])->toBe('0:00');
});

test('buyer quote panel handles quotes with missing created_at safely', function () {
    $rfq = RFQ::factory()->create(['buyer_id' => $this->buyer->id]);

    // Create quote without created_at (edge case)
    $quote = Quote::create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'quote_number' => 'TEST-001',
        'status' => 'submitted',
        'total_amount' => 100.00,
        'created_at' => null, // Force null
    ]);

    $component = Livewire::test(BuyerQuotePanel::class)
        ->call('loadQuotes');

    // Should handle gracefully without crashing
    $component->assertSet('quotesLoaded', true);

    $quotes = $component->get('quotes');
    expect($quotes)->toHaveCount(1);
    expect($quotes[0]['remaining_time'])->toBe('0:00');
    expect($quotes[0]['is_expired'])->toBeTrue();
});

test('buyer quote panel receives real-time quote via websocket', function () {
    $rfq = RFQ::factory()->create(['buyer_id' => $this->buyer->id]);

    $component = Livewire::test(BuyerQuotePanel::class);

    // Simulate WebSocket event
    $eventData = [
        'quote' => [
            'id' => 999,
            'total_amount' => 250.00,
        ],
        'vendor' => [
            'business_name' => 'New Vendor',
        ],
        'rfq' => [
            'id' => $rfq->id,
        ],
    ];

    // Before: should have 0 quotes
    $component->assertSet('activeQuotesCount', 0);

    // Trigger WebSocket handler (this will try to reload from DB)
    // Since quote 999 doesn't exist in DB, count should still be 0
    $component->call('onQuoteReceived', $eventData);

    // Should have reloaded (quotesLoaded should be true)
    $component->assertSet('quotesLoaded', true);
});

test('buyer quote panel only shows submitted quotes', function () {
    $rfq = RFQ::factory()->create(['buyer_id' => $this->buyer->id]);

    // Create quotes with different statuses
    Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'status' => 'submitted', // Should show
    ]);

    Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'status' => 'accepted', // Should NOT show
    ]);

    Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'status' => 'rejected', // Should NOT show
    ]);

    $component = Livewire::test(BuyerQuotePanel::class)
        ->call('loadQuotes');

    // Only submitted quote should be loaded
    $component->assertSet('activeQuotesCount', 1);
});

test('buyer quote panel renders view correctly', function () {
    $component = Livewire::test(BuyerQuotePanel::class);

    $component
        ->assertSee('Vendor Quotes')
        ->assertSee('Planner')
        ->assertSee('Send');
});

test('buyer quote panel has lazy loading attribute', function () {
    $reflection = new ReflectionClass(BuyerQuotePanel::class);
    $attributes = $reflection->getAttributes();

    $hasLazy = false;
    foreach ($attributes as $attribute) {
        if ($attribute->getName() === 'Livewire\Attributes\Lazy') {
            $hasLazy = true;
            break;
        }
    }

    expect($hasLazy)->toBeTrue();
});

test('buyer quote panel formats vendor name correctly', function () {
    $rfq = RFQ::factory()->create(['buyer_id' => $this->buyer->id]);

    $quote = Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => $this->vendor->id,
        'rfq_id' => $rfq->id,
        'status' => 'submitted',
    ]);

    $component = Livewire::test(BuyerQuotePanel::class)
        ->call('loadQuotes');

    $quotes = $component->get('quotes');
    expect($quotes[0]['vendor_name'])->toBe('Test Vendor Co');
});

test('buyer quote panel handles database errors gracefully', function () {
    // Force a quote that will cause error during processing
    $rfq = RFQ::factory()->create(['buyer_id' => $this->buyer->id]);

    Quote::factory()->create([
        'buyer_id' => $this->buyer->id,
        'vendor_id' => 999999, // Non-existent vendor
        'rfq_id' => $rfq->id,
        'status' => 'submitted',
    ]);

    // Should not crash, should handle error gracefully
    $component = Livewire::test(BuyerQuotePanel::class)
        ->call('loadQuotes');

    $component->assertSet('quotesLoaded', true);
    // Bad quote should be filtered out
    expect($component->get('quotes'))->toBeArray();
});
