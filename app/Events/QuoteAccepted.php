<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a quote is accepted
 * 
 * This event allows multiple systems to react independently:
 * - Send notifications
 * - Update inventory
 * - Generate invoices
 * - Track analytics
 * - Update vendor metrics
 */
class QuoteAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $quote;
    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct($quote, $order)
    {
        $this->quote = $quote;
        $this->order = $order;
    }
}