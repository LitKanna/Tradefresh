<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Vendor;
use App\Vendor\Livewire\Dashboard;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VendorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a vendor for testing
        $this->vendor = Vendor::factory()->create([
            'email' => 'test@vendor.com',
            'business_name' => 'Test Vendor Business',
        ]);
    }

    public function test_vendor_dashboard_component_renders(): void
    {
        $this->actingAs($this->vendor, 'vendor');

        Livewire::test(Dashboard::class)
            ->assertSee('Inventory Management')
            ->assertSee('RFQ Requests')
            ->assertSee('Today\'s Revenue')
            ->assertSee('Active Orders')
            ->assertSee('Pending RFQs')
            ->assertSee('Low Stock Items')
            ->assertStatus(200);
    }

    public function test_vendor_dashboard_loads_stats(): void
    {
        $this->actingAs($this->vendor, 'vendor');

        Livewire::test(Dashboard::class)
            ->assertSet('stats.todaySales.value', 12500)
            ->assertSet('stats.activeOrders', 8)
            ->assertSet('stats.pendingRfqs', 3)
            ->assertSet('stats.lowStockCount', 5);
    }

    public function test_vendor_dashboard_loads_inventory(): void
    {
        $this->actingAs($this->vendor, 'vendor');

        Livewire::test(Dashboard::class)
            ->assertSee('Fresh Tomatoes')
            ->assertSee('Organic Lettuce')
            ->assertSee('Red Apples')
            ->assertSee('Fresh Carrots')
            ->assertSee('PRD-1001')
            ->assertSee('PRD-1002')
            ->assertSee('PRD-1003')
            ->assertSee('PRD-1004');
    }

    public function test_vendor_dashboard_loads_pending_rfqs(): void
    {
        $this->actingAs($this->vendor, 'vendor');

        Livewire::test(Dashboard::class)
            ->assertSee('Sydney Grocers')
            ->assertSee('Fresh Mart')
            ->assertSee('City Markets')
            ->assertSee('2 hours ago')
            ->assertSee('4 hours ago')
            ->assertSee('45 minutes ago');
    }

    public function test_vendor_dashboard_add_product_button(): void
    {
        $this->actingAs($this->vendor, 'vendor');

        Livewire::test(Dashboard::class)
            ->call('addProduct')
            ->assertSet('showProductManager', true)
            ->assertDispatched('product-manager-opened');
    }

    public function test_vendor_dashboard_respond_to_rfq(): void
    {
        $this->actingAs($this->vendor, 'vendor');

        Livewire::test(Dashboard::class)
            ->call('respondToRfq', 1)
            ->assertSessionHas('info', 'RFQ response for #1 coming soon!');
    }

    public function test_vendor_dashboard_refresh_stats(): void
    {
        $this->actingAs($this->vendor, 'vendor');

        Livewire::test(Dashboard::class)
            ->call('refreshStats')
            ->assertSet('stats.todaySales.value', 12500);
    }

    public function test_vendor_dashboard_page_loads(): void
    {
        $this->actingAs($this->vendor, 'vendor');

        $response = $this->get('/vendor/dashboard');

        $response->assertStatus(200)
            ->assertViewIs('vendor.dashboard')
            ->assertSeeLivewire('vendor.dashboard');
    }
}
