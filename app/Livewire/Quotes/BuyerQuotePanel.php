<?php

namespace App\Livewire\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class BuyerQuotePanel extends Component
{
    // UI State
    public bool $quotesLoaded = false;

    // Data
    public array $quotes = [];

    public int $activeQuotesCount = 0;

    // Placeholder removed - component loads instantly with dashboard

    /**
     * Mount - Load quotes immediately (single round trip)
     */
    public function mount(): void
    {
        // Load quotes immediately on component mount
        $this->loadQuotes();
    }

    /**
     * WebSocket listeners for real-time quote updates
     */
    public function getListeners(): array
    {
        $buyer = Auth::guard('buyer')->user();

        if (! $buyer) {
            return [
                'refreshQuotes' => 'loadQuotes',
            ];
        }

        Log::info('Setting up BuyerQuotePanel listeners', ['buyer_id' => $buyer->id]);

        return [
            // Real-time quote reception from vendors
            "echo:buyer.{$buyer->id},quote.received" => 'onQuoteReceived',

            // Manual refresh trigger
            'refreshQuotes' => 'loadQuotes',
        ];
    }

    /**
     * Load quotes for authenticated buyer
     */
    public function loadQuotes(): void
    {
        try {
            $buyer = Auth::guard('buyer')->user();

            if (! $buyer) {
                Log::warning('No buyer authenticated in BuyerQuotePanel::loadQuotes');
                $this->quotes = [];
                $this->activeQuotesCount = 0;
                $this->quotesLoaded = true;

                return;
            }

            Log::info('Loading quotes for buyer in BuyerQuotePanel', ['buyer_id' => $buyer->id]);

            // Load ALL submitted quotes for this buyer
            $quotesQuery = Quote::where('buyer_id', $buyer->id)
                ->where('status', 'submitted')
                ->with(['vendor', 'rfq'])
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('Raw quotes from database', ['count' => $quotesQuery->count()]);

            // OPTIMIZATION: Calculate now() once outside the loop
            $currentTime = now();
            $expiryMinutes = 30;

            $this->quotes = $quotesQuery->map(function ($quote) use ($currentTime, $expiryMinutes) {
                try {
                    $quoteArray = $quote->toArray();

                    // Add calculated fields for display (with error handling)
                    $createdAt = $quote->created_at;
                    if ($createdAt) {
                        // OPTIMIZATION: Use timestamps for faster calculation
                        $expiryTimestamp = $createdAt->timestamp + ($expiryMinutes * 60);
                        $remainingSeconds = max(0, $expiryTimestamp - $currentTime->timestamp);

                        $minutes = floor($remainingSeconds / 60);
                        $seconds = $remainingSeconds % 60;

                        $quoteArray['remaining_time'] = sprintf('%d:%02d', $minutes, $seconds);
                        $quoteArray['expires_at'] = $expiryTimestamp * 1000; // Milliseconds for JavaScript
                        $quoteArray['is_expired'] = $remainingSeconds <= 0;
                    } else {
                        // Default values if no created_at
                        $quoteArray['remaining_time'] = '0:00';
                        $quoteArray['expires_at'] = $currentTime->timestamp * 1000;
                        $quoteArray['is_expired'] = true;
                    }

                    // Ensure vendor data is properly formatted
                    if (isset($quoteArray['vendor'])) {
                        $quoteArray['vendor_name'] = $quoteArray['vendor']['business_name'] ?? 'Unknown Vendor';
                    } else {
                        $quoteArray['vendor_name'] = 'Unknown Vendor';
                    }

                    // Ensure amounts are properly formatted
                    $quoteArray['total_amount'] = floatval($quote->total_amount ?? 0);
                    $quoteArray['final_amount'] = floatval($quote->final_amount ?? $quote->total_amount ?? 0);

                    return $quoteArray;
                } catch (\Exception $e) {
                    // If this quote fails, log it and skip it
                    Log::error('Failed to process quote', [
                        'quote_id' => $quote->id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    return null;  // Skip this quote
                }
            })->filter()->toArray();  // Remove null quotes

            $this->activeQuotesCount = count($this->quotes);
            $this->quotesLoaded = true;

            Log::info('Quotes loaded successfully in BuyerQuotePanel', [
                'count' => $this->activeQuotesCount,
                'quote_ids' => collect($this->quotes)->pluck('id')->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading quotes in BuyerQuotePanel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->quotes = [];
            $this->activeQuotesCount = 0;
            $this->quotesLoaded = true;
        }
    }

    /**
     * Handle incoming quote from vendor via WebSocket
     */
    public function onQuoteReceived($event): void
    {
        Log::info('=== QUOTE RECEIVED IN BUYER QUOTE PANEL ===', [
            'event_data' => $event,
            'buyer_id' => Auth::guard('buyer')->id(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Reload all quotes
        $this->loadQuotes();

        // Play notification sound
        $this->dispatch('play-notification-sound');

        // Show toast notification
        if (isset($event['vendor']['business_name'])) {
            $this->dispatch('show-toast', [
                'type' => 'success',
                'title' => 'New Quote Received!',
                'message' => "You have received a new quote from {$event['vendor']['business_name']}",
                'duration' => 5000,
            ]);
        }

        Log::info('=== BUYER QUOTE PANEL AFTER RELOAD ===', [
            'quotes_count' => $this->activeQuotesCount,
            'quote_ids' => collect($this->quotes)->pluck('id')->toArray(),
        ]);
    }

    /**
     * Render the buyer quote panel
     */
    public function render()
    {
        return view('livewire.quotes.buyer-quote-panel');
    }
}
