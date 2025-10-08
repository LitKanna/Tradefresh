<?php

namespace App\Services;

use App\Models\RFQ;
use App\Models\Buyer;
use App\Events\NewRFQBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RFQService
{
    /**
     * Create a new RFQ and broadcast it to all vendors
     */
    public function createRFQ(array $data, int $buyerId)
    {
        return DB::transaction(function () use ($data, $buyerId) {
            // Get the buyer
            $buyer = Buyer::findOrFail($buyerId);

            // Create the RFQ (only using columns that exist in the database)
            $rfq = RFQ::create([
                'buyer_id' => $buyerId,
                'rfq_number' => $this->generateReferenceNumber(),
                'title' => $data['title'] ?? 'Quote Request',
                'description' => $data['description'] ?? '',
                'delivery_date' => $data['delivery_date'],
                'delivery_time' => $data['delivery_time'] ?? 'Morning',
                'delivery_address' => $data['delivery_address'] ?? $buyer->address ?? 'Sydney Markets',
                'delivery_instructions' => $data['delivery_instructions'] ?? $data['special_instructions'] ?? null,
                'status' => $data['status'] ?? 'open',
                'urgency' => $data['urgency'] ?? 'medium',
                'is_public' => $data['is_public'] ?? true,
                'budget_min' => $data['budget_range_min'] ?? null,
                'budget_max' => $data['budget_range_max'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'items' => $data['items'] ?? [], // Store items as JSON array (will be auto-converted)
                'closes_at' => now()->addDays(3), // RFQ closes in 3 days
                'published_at' => now(),
            ]);

            // Items are already an array due to the 'array' cast in the model
            $items = $rfq->items ?? [];

            // Broadcast the RFQ to all online vendors
            $this->broadcastRFQ($rfq, $items, $buyer);

            // Log the activity
            Log::info('RFQ created and broadcasted', [
                'rfq_id' => $rfq->id,
                'buyer_id' => $buyerId,
                'items_count' => count($items),
                'delivery_date' => $rfq->delivery_date,
            ]);

            return $rfq;
        });
    }

    /**
     * Create RFQ from weekly planner
     */
    public function createRFQFromPlanner(array $plannerData, int $buyerId)
    {
        $buyer = Buyer::findOrFail($buyerId);

        // Transform planner data to RFQ format (using only existing database columns)
        $rfqData = [
            'title' => 'Weekly Order - ' . now()->format('W/Y'),
            'description' => 'Weekly order from planner',
            'delivery_date' => $plannerData['delivery_date'] ?? now()->addDays(2)->toDateString(),
            'delivery_time' => $plannerData['delivery_time'] ?? 'Morning',
            'delivery_address' => $plannerData['delivery_address'] ?? $buyer->address ?? 'Sydney Markets',
            'delivery_instructions' => $plannerData['special_instructions'] ?? null,
            'status' => 'open',
            'urgency' => 'medium',
            'is_public' => true,
            'items' => $plannerData['items'] ?? [], // Items are already formatted correctly from controller
        ];

        return $this->createRFQ($rfqData, $buyerId);
    }

    /**
     * Transform planner items to RFQ items format
     */
    private function transformPlannerItems(array $plannerItems)
    {
        $items = [];
        foreach ($plannerItems as $day => $dayItems) {
            foreach ($dayItems as $item) {
                // Check if this item already exists in our array
                $existingIndex = array_search($item['product_name'], array_column($items, 'product_name'));

                if ($existingIndex !== false) {
                    // Add to existing quantity
                    $items[$existingIndex]['quantity'] += $item['quantity'];
                } else {
                    // Add new item
                    $items[] = [
                        'product_name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? 'kg',
                        'category' => $item['category'] ?? 'Fresh Produce',
                        'notes' => $item['notes'] ?? null,
                        'quality_grade' => 'Premium',
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * Broadcast RFQ to all online vendors
     */
    private function broadcastRFQ($rfq, $items, $buyer)
    {
        try {
            // Dispatch the broadcast event
            event(new NewRFQBroadcast($rfq, $items, $buyer));

            // Also send push notifications if configured
            // $this->sendPushNotifications($rfq, $buyer);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast RFQ', [
                'rfq_id' => $rfq->id,
                'error' => $e->getMessage(),
            ]);

            // Don't fail the RFQ creation if broadcast fails
            return false;
        }
    }

    /**
     * Generate unique reference number
     */
    private function generateReferenceNumber()
    {
        $prefix = 'RFQ';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get buyer's RFQs
     */
    public function getBuyerRFQs(int $buyerId, array $filters = [])
    {
        $query = RFQ::with(['items', 'quotes'])
            ->where('buyer_id', $buyerId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('reference_number', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->latest()->paginate(10);
    }

    /**
     * Get active RFQs for vendors
     */
    public function getActiveRFQsForVendor(int $vendorId)
    {
        return RFQ::with(['buyer', 'items'])
            ->where('status', 'active')
            ->where('delivery_date', '>=', now()->toDateString())
            ->whereDoesntHave('quotes', function ($q) use ($vendorId) {
                $q->where('vendor_id', $vendorId);
            })
            ->latest()
            ->get();
    }

    /**
     * Cancel an RFQ
     */
    public function cancelRFQ(int $rfqId, int $buyerId, string $reason = null)
    {
        $rfq = RFQ::where('id', $rfqId)
            ->where('buyer_id', $buyerId)
            ->firstOrFail();

        $rfq->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // Notify vendors who have quoted
        // $this->notifyVendorsOfCancellation($rfq);

        return $rfq;
    }

    /**
     * Close an RFQ (after selecting a quote)
     */
    public function closeRFQ(int $rfqId, int $selectedQuoteId = null)
    {
        $rfq = RFQ::findOrFail($rfqId);

        $rfq->update([
            'status' => 'closed',
            'closed_at' => now(),
            'selected_quote_id' => $selectedQuoteId,
        ]);

        return $rfq;
    }
}