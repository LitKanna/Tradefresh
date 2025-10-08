<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Vendor;
use App\Models\RFQ;
use App\Models\Buyer;
use App\Models\Product;
use Livewire\Livewire;
use App\Livewire\Vendor\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VendorDashboardModalTest extends TestCase
{
    use RefreshDatabase;

    protected $vendor;
    protected $buyer;
    protected $rfq;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test vendor
        $this->vendor = Vendor::factory()->create([
            'business_name' => 'Test Vendor Co',
            'email' => 'vendor@test.com'
        ]);

        // Create test buyer
        $this->buyer = Buyer::factory()->create([
            'business_name' => 'Test Buyer Inc',
            'email' => 'buyer@test.com'
        ]);

        // Create test RFQ with items
        $this->rfq = RFQ::factory()->create([
            'buyer_id' => $this->buyer->id,
            'status' => 'open',
            'items' => [
                ['product_name' => 'Tomatoes', 'quantity' => 10, 'unit' => 'KG'],
                ['product_name' => 'Lettuce', 'quantity' => 5, 'unit' => 'BOX']
            ],
            'delivery_date' => now()->addDays(3)->format('Y-m-d'),
            'notes' => 'Please provide fresh produce only'
        ]);
    }

    /** @test */
    public function vendor_dashboard_loads_successfully()
    {
        $response = $this->actingAs($this->vendor, 'vendor')
            ->get('/vendor/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('vendor.dashboard');
    }

    /** @test */
    public function vendor_dashboard_displays_rfq_requests()
    {
        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->assertSee($this->buyer->business_name)
            ->assertSee('Tomatoes')
            ->assertSee('10 KG')
            ->assertSee('Send Quote')
            ->assertSee('View Details');
    }

    /** @test */
    public function view_details_modal_opens_with_correct_rfq_data()
    {
        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->call('showRfqDetails', $this->rfq->id)
            ->assertSet('showRfqDetailsModal', true)
            ->assertSet('selectedRfqDetails.id', $this->rfq->id)
            ->assertSet('selectedRfqDetails.buyer.business_name', $this->buyer->business_name);
    }

    /** @test */
    public function view_details_modal_displays_all_rfq_information()
    {
        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->call('showRfqDetails', $this->rfq->id)
            ->assertSee('Customer Request Details')
            ->assertSee($this->buyer->business_name)
            ->assertSee('Tomatoes')
            ->assertSee('10 KG')
            ->assertSee('Lettuce')
            ->assertSee('5 BOX')
            ->assertSee('Please provide fresh produce only');
    }

    /** @test */
    public function send_quote_modal_opens_from_rfq_list()
    {
        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->call('openQuoteModal', $this->rfq->id)
            ->assertSet('showQuoteModal', true)
            ->assertSet('selectedRfq.id', $this->rfq->id);
    }

    /** @test */
    public function send_quote_modal_opens_from_details_modal()
    {
        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->call('showRfqDetails', $this->rfq->id)
            ->call('openQuoteModalFromDetails')
            ->assertSet('showQuoteModal', true)
            ->assertSet('showRfqDetailsModal', false)
            ->assertSet('selectedRfq.id', $this->rfq->id);
    }

    /** @test */
    public function quote_price_calculation_works_correctly()
    {
        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->call('openQuoteModal', $this->rfq->id)
            ->set('quoteItems.0.price', '2.50')
            ->set('quoteItems.1.price', '15.00')
            ->assertSeeHtml('$25.00') // 10 * 2.50 for tomatoes
            ->assertSeeHtml('$75.00'); // 5 * 15.00 for lettuce
    }

    /** @test */
    public function quote_submission_creates_quote_with_correct_total()
    {
        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->call('openQuoteModal', $this->rfq->id)
            ->set('quoteItems.0.price', '2.50')
            ->set('quoteItems.1.price', '15.00')
            ->set('validUntil', now()->addDays(7)->format('Y-m-d'))
            ->set('notes', 'Fresh produce guaranteed')
            ->call('submitQuote')
            ->assertSet('showQuoteModal', false)
            ->assertDispatchedBrowserEvent('quote-submitted');

        // Verify quote was created in database
        $this->assertDatabaseHas('quotes', [
            'rfq_id' => $this->rfq->id,
            'vendor_id' => $this->vendor->id,
            'total_amount' => 100.00, // (10 * 2.50) + (5 * 15.00)
            'notes' => 'Fresh produce guaranteed'
        ]);
    }

    /** @test */
    public function quote_items_default_to_zero_price_not_empty_string()
    {
        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->call('openQuoteModal', $this->rfq->id)
            ->assertSet('quoteItems.0.price', 0)
            ->assertSet('quoteItems.1.price', 0);
    }

    /** @test */
    public function modals_close_when_close_button_clicked()
    {
        $component = Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class);

        // Test closing details modal
        $component->call('showRfqDetails', $this->rfq->id)
            ->assertSet('showRfqDetailsModal', true)
            ->call('closeRfqDetailsModal')
            ->assertSet('showRfqDetailsModal', false);

        // Test closing quote modal
        $component->call('openQuoteModal', $this->rfq->id)
            ->assertSet('showQuoteModal', true)
            ->call('closeQuoteModal')
            ->assertSet('showQuoteModal', false);
    }

    /** @test */
    public function vendor_can_search_inventory_products()
    {
        // Create some products
        Product::factory()->create(['name' => 'Apple']);
        Product::factory()->create(['name' => 'Banana']);
        Product::factory()->create(['name' => 'Carrot']);

        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->set('searchInventory', 'app')
            ->assertSee('Apple')
            ->assertDontSee('Banana')
            ->assertDontSee('Carrot');
    }

    /** @test */
    public function realtime_rfq_updates_refresh_list()
    {
        $component = Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class);

        // Initial RFQ count
        $component->assertSee($this->buyer->business_name);

        // Create new RFQ
        $newRfq = RFQ::factory()->create([
            'buyer_id' => $this->buyer->id,
            'status' => 'open',
            'items' => [
                ['product_name' => 'Oranges', 'quantity' => 20, 'unit' => 'KG']
            ]
        ]);

        // Simulate real-time update
        $component->call('refreshRfqs')
            ->assertSee('Oranges')
            ->assertSee('20 KG');
    }

    /** @test */
    public function urgent_rfqs_display_urgency_indicator()
    {
        // Create urgent RFQ (delivery within 24 hours)
        $urgentRfq = RFQ::factory()->create([
            'buyer_id' => $this->buyer->id,
            'status' => 'open',
            'delivery_date' => now()->addHours(12)->format('Y-m-d'),
            'items' => [
                ['product_name' => 'Urgent Item', 'quantity' => 1, 'unit' => 'EA']
            ]
        ]);

        Livewire::actingAs($this->vendor, 'vendor')
            ->test(Dashboard::class)
            ->assertSee('Urgent Item')
            ->assertSee('URGENT');
    }
}