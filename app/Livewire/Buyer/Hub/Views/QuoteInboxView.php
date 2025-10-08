<?php

namespace App\Livewire\Buyer\Hub\Views;

use App\Models\Quote;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Quote Inbox View - Vendor Quote Management
 *
 * FEATURES:
 * - List all pending quotes
 * - Sort/filter options
 * - Quick actions (accept/reject/view)
 * - Chat with vendor button
 * - Countdown timers
 *
 * UX PRINCIPLE: Clean list with clear actions
 */
class QuoteInboxView extends Component
{
    // Data
    public array $quotes = [];

    public int $quotesCount = 0;

    // UI state
    public bool $quotesLoaded = false;

    public string $sortBy = 'time'; // time, price, rating

    public ?int $highlightedQuoteId = null;

    // Listeners
    protected $listeners = [
        'highlight-quote' => 'highlightQuote',
    ];

    /**
     * Mount - Load quotes
     */
    public function mount(): void
    {
        $this->loadQuotes();
    }

    /**
     * Load quotes from database
     */
    public function loadQuotes(): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            $this->quotes = [];
            $this->quotesCount = 0;
            $this->quotesLoaded = true;

            return;
        }

        try {
            Log::info('Loading quotes in hub inbox', ['buyer_id' => $buyer->id]);

            // Base query - SQLite compatible
            $query = Quote::where('buyer_id', $buyer->id)
                ->where('status', 'submitted')
                ->with(['vendor', 'rfq'])
                ->where('created_at', '>', now()->subMinutes(30)); // Only last 30 mins

            // Apply sorting
            switch ($this->sortBy) {
                case 'price':
                    $query->orderBy('total_amount', 'asc'); // Cheapest first
                    break;
                case 'rating':
                    $query->join('vendors', 'quotes.vendor_id', '=', 'vendors.id')
                        ->where('quotes.status', 'submitted') // Specify table for ambiguous column
                        ->orderBy('vendors.rating', 'desc') // Highest rated first
                        ->select('quotes.*'); // Prevent column conflicts
                    break;
                default:
                    $query->orderBy('created_at', 'desc'); // Newest first
            }

            $quotesCollection = $query->get();

            // Transform to array with calculated fields
            $currentTime = now();

            $this->quotes = $quotesCollection->map(function ($quote) use ($currentTime) {
                $createdAt = $quote->created_at;
                $expiryMinutes = 30;
                $expiryTimestamp = $createdAt->timestamp + ($expiryMinutes * 60);
                $remainingSeconds = max(0, $expiryTimestamp - $currentTime->timestamp);

                $minutes = floor($remainingSeconds / 60);
                $seconds = $remainingSeconds % 60;

                return [
                    'id' => $quote->id,
                    'rfq_id' => $quote->rfq_id,
                    'vendor_id' => $quote->vendor_id,
                    'vendor_name' => $quote->vendor->business_name ?? 'Unknown Vendor',
                    'vendor_rating' => $quote->vendor->rating ?? 0,
                    'rfq_number' => $quote->rfq->rfq_number ?? 'Unknown',
                    'total_amount' => $quote->total_amount,
                    'final_amount' => $quote->final_amount ?? $quote->total_amount,
                    'remaining_time' => sprintf('%d:%02d', $minutes, $seconds),
                    'expires_at' => $expiryTimestamp * 1000, // JS milliseconds
                    'is_expired' => $remainingSeconds <= 0,
                    'delivery_date' => $quote->proposed_delivery_date?->format('l, d M Y') ?? 'TBD',
                    'delivery_time' => $quote->proposed_delivery_time ?? '6AM',
                    'created_at' => $quote->created_at->diffForHumans(),
                ];
            })->toArray();

            $this->quotesCount = count($this->quotes);
            $this->quotesLoaded = true;

            Log::info('Quotes loaded in hub inbox', [
                'count' => $this->quotesCount,
                'quote_ids' => collect($this->quotes)->pluck('id')->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading quotes in hub inbox', [
                'buyer_id' => $buyer->id ?? null,
                'error' => $e->getMessage(),
            ]);

            $this->quotes = [];
            $this->quotesCount = 0;
            $this->quotesLoaded = true;
        }
    }

    /**
     * Change sorting
     */
    public function sortQuotes(string $sortBy): void
    {
        $this->sortBy = $sortBy;
        $this->loadQuotes();
    }

    /**
     * Accept quote
     */
    public function acceptQuote(int $quoteId): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            return;
        }

        try {
            $quote = Quote::where('id', $quoteId)
                ->where('buyer_id', $buyer->id)
                ->firstOrFail();

            // Accept
            $quote->update(['status' => 'accepted', 'accepted_at' => now()]);

            // Close RFQ
            $quote->rfq->update(['status' => 'closed', 'closed_at' => now()]);

            // Reject others
            Quote::where('rfq_id', $quote->rfq_id)
                ->where('id', '!=', $quote->id)
                ->update(['status' => 'rejected', 'rejected_at' => now()]);

            // Reload quotes
            $this->loadQuotes();

            // Notify parent hub
            $this->dispatch('refreshHub');

            // Show success
            $this->dispatch('show-toast', [
                'type' => 'success',
                'title' => 'Quote Accepted!',
                'message' => "Order confirmed with {$quote->vendor->business_name}",
                'duration' => 5000,
            ]);

            Log::info('Quote accepted from inbox', [
                'buyer_id' => $buyer->id,
                'quote_id' => $quoteId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to accept quote', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Failed to accept quote',
                'duration' => 3000,
            ]);
        }
    }

    /**
     * Chat with vendor about quote
     */
    public function chatWithVendor(int $vendorId): void
    {
        // Switch to messaging view with this vendor selected
        $this->dispatch('switch-to-messaging');
        $this->dispatch('open-vendor-chat', vendorId: $vendorId);
    }

    /**
     * Highlight specific quote (when coming from AI view)
     */
    public function highlightQuote($quoteId): void
    {
        $this->highlightedQuoteId = $quoteId;

        // Remove highlight after 3 seconds
        $this->dispatch('remove-highlight-after', delay: 3000);
    }

    /**
     * Remove highlight
     */
    public function removeHighlight(): void
    {
        $this->highlightedQuoteId = null;
    }

    /**
     * Render view
     */
    public function render()
    {
        return view('livewire.buyer.hub.views.quote-inbox');
    }
}
