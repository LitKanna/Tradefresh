<?php

namespace App\Livewire\Quotes;

use App\Models\RFQ;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class VendorRfqPanel extends Component
{
    // RFQ Data
    public Collection $pendingRfqs;

    public int $newRfqCount = 0;

    public bool $showNewRfqBadge = false;

    // Modal State
    public bool $showRfqDetailsModal = false;

    public ?array $selectedRfqDetails = null;

    public function mount(): void
    {
        $this->loadRealRfqs();
        $this->newRfqCount = 0;
        $this->showNewRfqBadge = false;
    }

    /**
     * WebSocket listeners for real-time RFQ updates
     */
    public function getListeners(): array
    {
        $vendorId = auth('vendor')->id();

        if (! $vendorId) {
            return [
                'refreshRfqs' => 'loadRealRfqs',
            ];
        }

        return [
            // Real-time RFQ updates from buyers
            'echo:vendors.all,rfq.new' => 'onNewRfq',

            // Manual refresh trigger
            'refreshRfqs' => 'loadRealRfqs',
        ];
    }

    /**
     * Handle new RFQ broadcast via WebSocket
     */
    #[On('echo:vendors.all,rfq.new')]
    public function onNewRfq($event): void
    {
        Log::info('=== NEW RFQ RECEIVED IN VENDOR RFQ PANEL ===', [
            'event_data' => $event,
            'vendor_id' => auth('vendor')->id(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Reload RFQs from database
        $this->loadRealRfqs();

        // Play notification sound
        $this->dispatch('play-notification-sound');

        // Show toast notification
        if (isset($event['buyer']['business_name'], $event['total_items'])) {
            $this->dispatch('show-toast', [
                'type' => 'success',
                'title' => 'New RFQ Available!',
                'message' => "{$event['buyer']['business_name']} is requesting quotes for {$event['total_items']} items",
                'duration' => 5000,
            ]);
        }

        // Increment new count
        $this->newRfqCount++;
        $this->showNewRfqBadge = true;

        Log::info('=== VENDOR RFQ PANEL AFTER RELOAD ===', [
            'rfqs_count' => $this->pendingRfqs->count(),
            'new_count' => $this->newRfqCount,
        ]);
    }

    /**
     * Load RFQs from database (only those vendor hasn't quoted on)
     */
    public function loadRealRfqs(): void
    {
        try {
            $vendor = auth('vendor')->user();

            $rfqs = RFQ::with(['buyer'])
                ->where('status', 'open')
                ->where('delivery_date', '>=', now()->toDateString())
                ->where('created_at', '>=', now()->subMinutes(30)); // Only show RFQs from last 30 minutes

            // Only filter by vendor quotes if vendor is authenticated
            if ($vendor) {
                $rfqs->whereDoesntHave('quotes', function ($q) use ($vendor) {
                    $q->where('vendor_id', $vendor->id);
                });
            }

            $rfqs = $rfqs->latest()->get();

            // Filter to ensure we only show non-expired RFQs
            $this->pendingRfqs = $rfqs->filter(function ($rfq) {
                return $rfq->created_at && $rfq->created_at->diffInMinutes(now()) < 30;
            })->map(function ($rfq) {
                $items = $rfq->items ?? [];

                return [
                    'id' => $rfq->id,
                    'rfq_number' => $rfq->rfq_number ?? 'RFQ-'.str_pad($rfq->id, 6, '0', STR_PAD_LEFT),
                    'buyer_id' => $rfq->buyer_id,
                    'business_name' => $rfq->buyer->business_name ?? 'Unknown Buyer',
                    'request_time' => $rfq->created_at->diffForHumans(),
                    'urgency' => $this->calculateUrgencyText($rfq->delivery_date),
                    'urgency_class' => $this->calculateUrgency($rfq->delivery_date),
                    'delivery_date' => $rfq->delivery_date,
                    'delivery_time' => $rfq->delivery_time ?? 'Morning',
                    'delivery_address' => $rfq->delivery_address ?? '',
                    'special_instructions' => $rfq->delivery_instructions ?? $rfq->special_instructions ?? '',
                    'created_at' => $rfq->created_at ? $rfq->created_at->toISOString() : now()->toISOString(),
                    'created_at_full' => $rfq->created_at,
                    'items' => collect($items ?: [])->map(function ($item) {
                        return [
                            'name' => $item['product_name'] ?? $item['name'] ?? 'Unknown Product',
                            'quantity' => $item['quantity'] ?? 0,
                            'unit' => $item['unit'] ?? 'kg',
                            'notes' => $item['notes'] ?? null,
                        ];
                    })->toArray(),
                ];
            })->values();

            Log::info('RFQs loaded in VendorRfqPanel', [
                'count' => $this->pendingRfqs->count(),
                'rfq_ids' => $this->pendingRfqs->pluck('id')->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading RFQs in VendorRfqPanel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->pendingRfqs = collect([]);
        }
    }

    /**
     * Calculate urgency class for styling
     */
    private function calculateUrgency(string $deliveryDate): string
    {
        $days = now()->diffInDays($deliveryDate, false);

        if ($days < 0) {
            return 'expired';
        }
        if ($days == 0) {
            return 'urgent';
        }
        if ($days <= 2) {
            return 'high';
        }
        if ($days <= 7) {
            return 'medium';
        }

        return 'normal';
    }

    /**
     * Calculate urgency text for display
     */
    private function calculateUrgencyText(string $deliveryDate): string
    {
        $urgency = $this->calculateUrgency($deliveryDate);

        return match ($urgency) {
            'urgent' => 'URGENT',
            'high' => 'HIGH',
            'medium' => 'MEDIUM',
            default => 'NORMAL',
        };
    }

    /**
     * View RFQ details (opens details modal)
     */
    public function viewRFQ(int $rfqId): void
    {
        $this->selectedRfqDetails = $this->pendingRfqs->firstWhere('id', $rfqId);

        if ($this->selectedRfqDetails) {
            $this->showRfqDetailsModal = true;
        }
    }

    /**
     * Close RFQ details modal
     */
    public function closeRfqDetailsModal(): void
    {
        $this->showRfqDetailsModal = false;
        $this->selectedRfqDetails = null;
    }

    /**
     * Open quote modal from details view (dispatches to parent)
     */
    public function openQuoteModalFromDetails(): void
    {
        if ($this->selectedRfqDetails) {
            $rfqId = $this->selectedRfqDetails['id'];
            $this->closeRfqDetailsModal();

            // Dispatch to parent dashboard to open quote modal
            $this->dispatch('open-quote-modal', rfqId: $rfqId);
        }
    }

    /**
     * Refresh RFQs manually
     */
    public function refreshRfqs(): void
    {
        $this->loadRealRfqs();

        $this->dispatch('rfqs-refreshed');

        $count = $this->pendingRfqs->count();
        if ($count > 0) {
            session()->flash('success', "Found {$count} active customer requests");
        } else {
            session()->flash('info', 'No active customer requests at the moment');
        }
    }

    /**
     * Dispatch open quote modal event to parent
     */
    public function openQuoteModal(int $rfqId): void
    {
        $this->dispatch('open-quote-modal', rfqId: $rfqId);
    }

    public function render()
    {
        return view('livewire.quotes.vendor-rfq-panel', [
            'pendingRfqs' => $this->pendingRfqs,
            'newRfqCount' => $this->newRfqCount,
            'showNewRfqBadge' => $this->showNewRfqBadge,
        ]);
    }
}
