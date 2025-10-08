<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewRFQBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $rfqData;
    public array $items;
    public array $buyer;

    public function __construct($rfq, $items, $buyer)
    {
        // Prepare RFQ data for broadcasting
        $this->rfqData = [
            'id' => $rfq->id,
            'reference_number' => $rfq->reference_number ?? 'RFQ-' . str_pad($rfq->id, 6, '0', STR_PAD_LEFT),
            'delivery_date' => $rfq->delivery_date,
            'delivery_time' => $rfq->delivery_time ?? 'Morning',
            'special_instructions' => $rfq->special_instructions,
            'created_at' => $rfq->created_at->toISOString(),
            'time_ago' => $rfq->created_at->diffForHumans(),
            'status' => $rfq->status ?? 'active',
            'urgency' => $this->calculateUrgency($rfq->delivery_date),
        ];

        // Prepare items data - items is already an array from JSON
        $this->items = collect($items)->map(function ($item) {
            // Handle both object and array formats
            if (is_object($item)) {
                return [
                    'id' => $item->id ?? null,
                    'product_name' => $item->product_name ?? $item->name ?? '',
                    'quantity' => $item->quantity ?? 0,
                    'unit' => $item->unit ?? 'kg',
                    'category' => $item->category ?? 'Fresh Produce',
                    'notes' => $item->notes ?? null,
                    'quality_grade' => $item->quality_grade ?? 'Premium',
                ];
            } else {
                return [
                    'id' => $item['id'] ?? null,
                    'product_name' => $item['product_name'] ?? $item['name'] ?? '',
                    'quantity' => $item['quantity'] ?? 0,
                    'unit' => $item['unit'] ?? 'kg',
                    'category' => $item['category'] ?? 'Fresh Produce',
                    'notes' => $item['notes'] ?? null,
                    'quality_grade' => $item['quality_grade'] ?? 'Premium',
                ];
            }
        })->toArray();

        // Prepare buyer data (limited info for privacy)
        $this->buyer = [
            'id' => $buyer->id,
            'business_name' => $buyer->business_name,
            'suburb' => $buyer->suburb ?? 'Sydney',
            'rating' => $buyer->rating ?? 5.0,
            'badge' => $this->getBuyerBadge($buyer),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            // Broadcast to all vendors channel
            new Channel('vendors.all'),
            // Also broadcast to specific vendor dashboard channel
            new Channel('vendor.dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'rfq.new';
    }

    public function broadcastWith(): array
    {
        return [
            'rfq' => $this->rfqData,
            'items' => $this->items,
            'buyer' => $this->buyer,
            'total_items' => count($this->items),
            'notification' => [
                'title' => 'New RFQ Available!',
                'message' => "{$this->buyer['business_name']} is requesting quotes for " . count($this->items) . " items",
                'type' => 'success',
                'icon' => '📬',
                'sound' => true,
                'vibrate' => true,
            ],
        ];
    }

    private function calculateUrgency($deliveryDate): string
    {
        $days = now()->diffInDays($deliveryDate, false);

        if ($days < 0) {
            return 'expired';
        } elseif ($days == 0) {
            return 'urgent';
        } elseif ($days <= 2) {
            return 'high';
        } elseif ($days <= 7) {
            return 'medium';
        }

        return 'normal';
    }

    private function getBuyerBadge($buyer): string
    {
        // Determine buyer badge based on order history or status
        try {
            $totalOrders = \App\Models\Order::where('buyer_id', $buyer->id)->count();
        } catch (\Exception $e) {
            // If orders table has issues, default to 0
            $totalOrders = 0;
        }

        if ($totalOrders >= 100) {
            return 'platinum';
        } elseif ($totalOrders >= 50) {
            return 'gold';
        } elseif ($totalOrders >= 20) {
            return 'silver';
        } elseif ($totalOrders >= 5) {
            return 'bronze';
        }

        return 'new';
    }
}