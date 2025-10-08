{{-- Quote Inbox View - Vendor Quote Management --}}
<div class="quote-inbox-view">

    {{-- Subheader with Sort Options --}}
    <div class="inbox-subheader">
        <div class="inbox-title">
            Pending Quotes
            @if($quotesCount > 0)
                <span class="count-badge">({{ $quotesCount }})</span>
            @endif
        </div>

        <div class="sort-controls">
            <button
                wire:click="sortQuotes('time')"
                class="sort-btn {{ $sortBy === 'time' ? 'active' : '' }}"
                title="Sort by time"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>
            </button>

            <button
                wire:click="sortQuotes('price')"
                class="sort-btn {{ $sortBy === 'price' ? 'active' : '' }}"
                title="Sort by price"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </button>

            <button
                wire:click="sortQuotes('rating')"
                class="sort-btn {{ $sortBy === 'rating' ? 'active' : '' }}"
                title="Sort by vendor rating"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Quote List --}}
    <div class="quote-list-container">
        @if(!$quotesLoaded)
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <div>Loading quotes...</div>
            </div>

        @else
            @forelse($quotes as $quote)
                <div
                    wire:key="quote-{{ $quote['id'] }}"
                    class="quote-card {{ $highlightedQuoteId === $quote['id'] ? 'highlighted' : '' }} {{ $quote['is_expired'] ? 'expired' : '' }}"
                    data-quote-id="{{ $quote['id'] }}"
                    data-expires-at="{{ $quote['expires_at'] }}"
                >
                    {{-- Timer --}}
                    <div class="quote-timer {{ $quote['is_expired'] ? 'expired' : '' }}">
                        {{ $quote['remaining_time'] }}
                    </div>

                    {{-- Vendor Info --}}
                    <div class="quote-vendor-info">
                        <div class="vendor-name">{{ $quote['vendor_name'] }}</div>
                        @if($quote['vendor_rating'] > 0)
                            <div class="vendor-rating">
                                ⭐ {{ number_format($quote['vendor_rating'], 1) }}
                            </div>
                        @endif
                    </div>

                    {{-- RFQ Reference --}}
                    <div class="quote-rfq-ref">
                        RFQ #{{ $quote['rfq_number'] }}
                    </div>

                    {{-- Price --}}
                    <div class="quote-price-display">
                        <span class="price-label">Total:</span>
                        <span class="price-value">${{ number_format($quote['final_amount'], 2) }}</span>
                    </div>

                    {{-- Delivery Info --}}
                    <div class="quote-delivery-info">
                        📅 {{ $quote['delivery_date'] }} at {{ $quote['delivery_time'] }}
                    </div>

                    {{-- Actions --}}
                    <div class="quote-actions">
                        <button
                            wire:click="acceptQuote({{ $quote['id'] }})"
                            class="quote-action-btn accept"
                            title="Accept this quote"
                        >
                            Accept
                        </button>

                        <button
                            wire:click="chatWithVendor({{ $quote['vendor_id'] }})"
                            class="quote-action-btn chat"
                            title="Chat with vendor"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                            </svg>
                        </button>
                    </div>
                </div>

            @empty
                <div class="empty-state">
                    <div class="empty-icon">📬</div>
                    <div class="empty-text">No pending quotes</div>
                    <div class="empty-hint">Create an RFQ using the AI assistant to receive quotes</div>
                </div>
            @endforelse
        @endif
    </div>

    {{-- Footer --}}
    @if($quotesCount > 0)
        <div class="inbox-footer">
            {{ $quotesCount }} {{ $quotesCount === 1 ? 'quote' : 'quotes' }} pending
        </div>
    @endif

</div>

{{-- Quote Timer Script --}}
@push('scripts')
<script>
// Update timers every second
setInterval(() => {
    document.querySelectorAll('.quote-card').forEach(card => {
        const quoteId = card.getAttribute('data-quote-id');
        const timerEl = card.querySelector('.quote-timer');

        if (timerEl) {
            const expiresAt = parseInt(card.getAttribute('data-expires-at'));

            if (expiresAt) {
                const now = Date.now();
                const remaining = Math.max(0, Math.floor((expiresAt - now) / 1000));

                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;

                timerEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                // Mark as urgent if < 5 mins
                if (remaining < 300) {
                    timerEl.classList.add('urgent');
                }

                // Mark as expired
                if (remaining === 0) {
                    timerEl.classList.add('expired');
                    card.classList.add('expired');
                }
            }
        }
    });
}, 1000);
</script>
@endpush
