<?php

namespace Tests\Feature;

use Tests\TestCase;

class VendorDashboardManualTest extends TestCase
{
    /** @test */
    public function vendor_dashboard_page_loads_successfully()
    {
        // Test that vendor dashboard page loads without authentication (temporarily)
        $response = $this->get('/vendor/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('vendor.dashboard');
        $response->assertSee('Vendor Dashboard');
        $response->assertSee('Customer Requests');
    }

    /** @test */
    public function vendor_dashboard_shows_livewire_component()
    {
        $response = $this->get('/vendor/dashboard');

        $response->assertStatus(200);
        $response->assertSeeLivewire('vendor.dashboard');
    }

    /** @test */
    public function vendor_dashboard_includes_necessary_javascript()
    {
        $response = $this->get('/vendor/dashboard');

        $response->assertStatus(200);
        // Check for WebSocket setup
        $response->assertSee('Echo.channel');
        $response->assertSee('rfq.new');
        $response->assertSee('showRfqNotification');
    }

    /** @test */
    public function vendor_dashboard_has_modal_markup()
    {
        $response = $this->get('/vendor/dashboard');

        $response->assertStatus(200);
        // Check for modal-related elements
        $response->assertSee('modal-overlay');
        $response->assertSee('Send Quote');
        $response->assertSee('View Details');
    }
}