<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\RFQ;
use App\Models\Quote;
use Illuminate\Support\Collection;

class VendorRfqPanel extends Component
{
    public Collection $activeRfqs;
    public Collection $recentRfqs;
    public ?int $selectedRfqId = null;
    public ?array $selectedRfq = null;
    public int $newRfqCount = 0;
    public bool $showNotificationBadge = false;
    public string $filterUrgency = 'all';
    public string $sortBy = 'newest';

    public function mount()
    {
        $this->loadRfqs();
    }

    public function loadRfqs()
    {
        $vendor = auth('vendor')->user();

        // Get active RFQs that haven't been quoted by this vendor
        $query = RFQ::with(['buyer', 'items'])
            ->where('status', 'active')
            ->whereDoesntHave('quotes', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->where('delivery_date', '>=', now()->toDateString());

        // Apply urgency filter
        if ($this->filterUrgency !== 'all') {
            $query = $this->applyUrgencyFilter($query);
        }

        // Apply sorting
        $query = $this->applySorting($query);

        $this->activeRfqs = $query->get()->map(function ($rfq) {
            return $this->formatRfqData($rfq);
        });

        // Get recent RFQs (last 24 hours)
        $this->recentRfqs = RFQ::with(['buyer', 'items'])
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($rfq) {
                return $this->formatRfqData($rfq);
            });
    }

    #[On('echo:vendors.all,rfq.new')]
    public function onNewRfq($event)
    {
        // Add the new RFQ to the top of the list
        $newRfq = collect($event);

        // Prepend to active RFQs
        $this->activeRfqs->prepend($newRfq);

        // Update recent RFQs
        $this->recentRfqs->prepend($newRfq);
        $this->recentRfqs = $this->recentRfqs->take(5);

        // Show notification badge
        $this->newRfqCount++;
        $this->showNotificationBadge = true;

        // Play notification sound
        $this->dispatch('play-notification-sound');

        // Show toast notification
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => "New RFQ from {$event['buyer']['business_name']} - {$event['total_items']} items requested!",
            'duration' => 5000,
        ]);

        // Auto-select if no RFQ is currently selected
        if (!$this->selectedRfqId) {
            $this->selectRfq($event['rfq']['id']);
        }
    }

    public function selectRfq($rfqId)
    {
        $this->selectedRfqId = $rfqId;

        // Find the RFQ in our collections
        $rfq = $this->activeRfqs->firstWhere('rfq.id', $rfqId);

        if ($rfq) {
            $this->selectedRfq = $rfq->toArray();
            $this->showNotificationBadge = false;

            // Mark as viewed
            $this->dispatch('rfq-viewed', ['rfqId' => $rfqId]);
        }
    }

    public function startQuote($rfqId)
    {
        // Redirect to quote creation page
        return redirect()->route('vendor.quote.create', ['rfq' => $rfqId]);
    }

    public function filterByUrgency($urgency)
    {
        $this->filterUrgency = $urgency;
        $this->loadRfqs();
    }

    public function sortRfqs($sortBy)
    {
        $this->sortBy = $sortBy;
        $this->loadRfqs();
    }

    public function markAllAsRead()
    {
        $this->newRfqCount = 0;
        $this->showNotificationBadge = false;
    }

    private function formatRfqData($rfq)
    {
        return collect([
            'rfq' => [
                'id' => $rfq->id,
                'reference_number' => $rfq->reference_number ?? 'RFQ-' . str_pad($rfq->id, 6, '0', STR_PAD_LEFT),
                'delivery_date' => $rfq->delivery_date,
                'delivery_time' => $rfq->delivery_time ?? 'Morning',
                'special_instructions' => $rfq->special_instructions,
                'created_at' => $rfq->created_at->toISOString(),
                'time_ago' => $rfq->created_at->diffForHumans(),
                'status' => $rfq->status ?? 'active',
                'urgency' => $this->calculateUrgency($rfq->delivery_date),
            ],
            'items' => $rfq->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit ?? 'kg',
                    'category' => $item->category ?? 'Fresh Produce',
                    'notes' => $item->notes,
                ];
            })->toArray(),
            'buyer' => [
                'id' => $rfq->buyer->id,
                'business_name' => $rfq->buyer->business_name,
                'suburb' => $rfq->buyer->suburb ?? 'Sydney',
                'rating' => $rfq->buyer->rating ?? 5.0,
            ],
            'total_items' => $rfq->items->count(),
        ]);
    }

    private function calculateUrgency($deliveryDate): string
    {
        $days = now()->diffInDays($deliveryDate, false);

        if ($days < 0) return 'expired';
        if ($days == 0) return 'urgent';
        if ($days <= 2) return 'high';
        if ($days <= 7) return 'medium';

        return 'normal';
    }

    private function applyUrgencyFilter($query)
    {
        switch ($this->filterUrgency) {
            case 'urgent':
                return $query->whereDate('delivery_date', now()->toDateString());
            case 'high':
                return $query->whereBetween('delivery_date', [now()->toDateString(), now()->addDays(2)->toDateString()]);
            case 'medium':
                return $query->whereBetween('delivery_date', [now()->toDateString(), now()->addDays(7)->toDateString()]);
            default:
                return $query;
        }
    }

    private function applySorting($query)
    {
        switch ($this->sortBy) {
            case 'urgent':
                return $query->orderBy('delivery_date', 'asc');
            case 'items':
                return $query->withCount('items')->orderBy('items_count', 'desc');
            case 'newest':
            default:
                return $query->latest();
        }
    }

    public function render()
    {
        return view('livewire.vendor-rfq-panel');
    }
}