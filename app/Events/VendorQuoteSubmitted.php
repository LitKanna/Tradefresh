<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VendorQuoteSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $quoteData;

    public array $vendor;

    public array $rfqInfo;

    public array $items;

    public int $buyerId;

    public function __construct($quote, $vendor, $rfq)
    {
        // Log that the event was triggered
        \Log::info('🔔 VendorQuoteSubmitted event triggered', [
            'quote_id' => $quote->id,
            'vendor_id' => $vendor->id,
            'buyer_id' => $rfq->buyer_id,
            'rfq_id' => $rfq->id,
        ]);

        // Prepare quote data for broadcasting
        $this->quoteData = [
            'id' => $quote->id,
            'rfq_id' => $quote->rfq_id,
            'total_amount' => $quote->total_amount,
            'delivery_fee' => $quote->delivery_charge ?? 0,
            'notes' => $quote->notes ?? '',
            'validity_minutes' => 30, // Automatic 30-minute acceptance window
            'created_at' => $quote->created_at->toISOString(),
            'time_ago' => $quote->created_at->diffForHumans(),
            'status' => $quote->status ?? 'submitted',
            'expires_at' => isset($quote->validity_date) ? $quote->validity_date->toISOString() : now()->addMinutes(30)->toISOString(),
            'final_amount' => $quote->final_amount ?? $quote->total_amount,
        ];

        // Prepare vendor data
        $this->vendor = [
            'id' => $vendor->id,
            'business_name' => $vendor->business_name,
            'rating' => $vendor->rating ?? 4.5,
            'suburb' => $vendor->suburb ?? 'Sydney Markets',
            'delivery_time' => $vendor->delivery_time ?? '24-48 hours',
            'badge' => $this->getVendorBadge($vendor),
            'avatar' => $vendor->avatar ?? '/default-vendor.png',
        ];

        // Prepare RFQ reference info
        $rfqItems = $rfq->items;
        if (is_string($rfqItems)) {
            $rfqItems = json_decode($rfqItems, true) ?? [];
        } elseif (! is_array($rfqItems)) {
            $rfqItems = [];
        }

        $this->rfqInfo = [
            'id' => $rfq->id,
            'reference_number' => $rfq->rfq_number ?? 'RFQ-'.str_pad($rfq->id, 6, '0', STR_PAD_LEFT),
            'delivery_date' => $rfq->delivery_date,
            'item_count' => count($rfqItems),
        ];

        // Store buyer ID for channel broadcasting
        $this->buyerId = $rfq->buyer_id;

        // Prepare quoted items with prices from line_items
        $lineItems = is_string($quote->line_items) ? json_decode($quote->line_items, true) : $quote->line_items;
        $this->items = $lineItems ? array_map(function ($item) {
            return [
                'description' => $item['description'] ?? 'Quote Item',
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? 'unit',
                'unit_price' => $item['unit_price'] ?? 0,
                'total_price' => $item['total'] ?? ($item['quantity'] * $item['unit_price']),
                'availability' => 'in_stock',
            ];
        }, $lineItems) : [];
    }

    public function broadcastOn(): array
    {
        $channels = [
            // Use PUBLIC channels to avoid 403 auth issues
            new Channel('buyers.all'),
            new Channel('quotes.buyer.'.$this->buyerId),
        ];

        \Log::info('📡 Broadcasting QuoteReceived to PUBLIC channels', [
            'channel_1' => 'buyers.all',
            'channel_2' => 'quotes.buyer.'.$this->buyerId,
            'buyer_id' => $this->buyerId,
            'event_name' => 'QuoteReceived',
        ]);

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'QuoteReceived'; // Matches gist pattern exactly
    }

    public function broadcastWith(): array
    {
        return [
            'quote' => array_merge($this->quoteData, ['buyer_id' => $this->buyerId]),
            'vendor' => $this->vendor,
            'rfq' => $this->rfqInfo,
            'items' => $this->items,
            'buyerId' => $this->buyerId, // Add buyerId at root level for easy access
            'notification' => [
                'title' => 'New Quote Received!',
                'message' => "{$this->vendor['business_name']} submitted a quote for $".number_format($this->quoteData['total_amount'], 2),
                'type' => 'success',
                'icon' => '💰',
                'sound' => true,
                'priority' => $this->calculatePriority(),
            ],
        ];
    }

    private function getVendorBadge($vendor)
    {
        $totalOrders = $vendor->completed_orders ?? 0;

        if ($totalOrders >= 500) {
            return 'platinum';
        } elseif ($totalOrders >= 200) {
            return 'gold';
        } elseif ($totalOrders >= 50) {
            return 'silver';
        } elseif ($totalOrders >= 10) {
            return 'bronze';
        }

        return 'new';
    }

    private function calculatePriority()
    {
        // Higher priority for better prices or trusted vendors
        if ($this->vendor['badge'] === 'platinum' || $this->vendor['badge'] === 'gold') {
            return 'high';
        }

        return 'normal';
    }
}
