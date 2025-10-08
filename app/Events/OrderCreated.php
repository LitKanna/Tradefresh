<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an order is created
 * 
 * Listeners can handle:
 * - Email notifications to vendor
 * - SMS notifications to buyer
 * - Inventory updates
 * - Commission calculations
 * - Analytics tracking
 */
class OrderCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct($order)
    {
        $this->order = $order;
    }
}