<div class="order-card-panel" style="position: relative; display: flex !important; flex-direction: column !important;">
    <!-- Quote Panel Content -->
    <div id="quote-panel-content" style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
        <!-- Header -->
        <div class="order-card-header">
            <h3 class="order-card-title">Vendor Quotes</h3>
            <span class="quote-badge" id="quoteBadge">{{ $activeQuotesCount }}</span>
        </div>

        <!-- Quotes Container -->
        <div class="order-card-content" id="quotesContainer" style="flex: 1; overflow-y: auto !important;">
        @if(!$quotesLoaded)
            <!-- Loading state -->
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; text-align: center; color: #6b7280;">
                <div style="font-size: 12px; opacity: 0.7;">Loading quotes...</div>
            </div>
        @else
            @forelse($quotes as $index => $quote)
                <div class="quote-item vendor-quote-item"
                     wire:key="quote-{{ $quote['id'] ?? $index }}"
                     data-quote-id="{{ $quote['id'] }}"
                     data-vendor-id="{{ $quote['vendor_id'] ?? '' }}"
                     data-rfq-id="{{ $quote['rfq_id'] ?? '' }}"
                     data-created-at="{{ $quote['created_at'] ?? now() }}"
                     data-expires-at="{{ $quote['expires_at'] ?? '' }}"
                     data-quote-json="{{ json_encode([
                         'id' => $quote['id'],
                         'vendor' => $quote['vendor']['business_name'] ?? 'Unknown Vendor',
                         'vendor_id' => $quote['vendor_id'] ?? null,
                         'vendorId' => $quote['vendor_id'] ?? null,
                         'product' => $quote['rfq']['title'] ?? 'Multiple Items',
                         'price' => number_format($quote['total_amount'] ?? $quote['final_amount'] ?? 0, 2, '.', ''),
                         'total_amount' => $quote['total_amount'] ?? 0,
                         'final_amount' => $quote['final_amount'] ?? 0,
                         'expires_at' => $quote['expires_at'] ?? '',
                         'expiresAt' => $quote['expires_at'] ?? null,
                         'items' => $quote['items'] ?? [],
                         'notes' => $quote['notes'] ?? '',
                         'delivery_date' => $quote['delivery_date'] ?? 'Within 24 hours',
                         'rfq' => $quote['rfq'] ?? []
                     ]) }}">
                    <!-- Timer -->
                    <div class="quote-timer" id="timer-{{ $quote['id'] }}" data-expires="{{ $quote['expires_at'] ?? '' }}">
                        {{ $quote['remaining_time'] ?? '0:00' }}
                    </div>

                    <!-- Vendor Name -->
                    <div class="quote-vendor">
                        {{ $quote['vendor']['business_name'] ?? 'Unknown Vendor' }}
                    </div>

                    <!-- Price with Label -->
                    <div class="quote-price">
                        <span class="price-label">Price:</span>
                        <span class="price-value">${{ number_format($quote['total_amount'] ?? $quote['final_amount'] ?? 0, 2) }}</span>
                    </div>

                    <!-- Actions -->
                    <div class="quote-actions">
                        <button class="quote-action view" onclick="viewQuoteDetails({{ $quote['id'] }}, event)">
                            View
                        </button>
                        <button class="quote-action accept" onclick="acceptQuote({{ $quote['id'] }})">
                            Accept
                        </button>
                    </div>
                </div>
            @empty
                <div class="no-quotes" id="noQuotesMessage">
                    <p style="text-align: center; color: var(--text-tertiary); padding: 20px; font-size: 12px;">
                        Send your weekly planner to vendors to receive quotes
                    </p>
                </div>
            @endforelse
        @endif
        </div>
    </div>

    <!-- Footer - DEBUG VISIBLE -->
    <div class="order-card-footer" style="flex-shrink: 0 !important; background: red !important; height: 48px !important; z-index: 9999 !important; display: flex !important;">
        <button class="footer-btn planner-btn" onclick="openWeeklyPlanner()" title="Weekly Planner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <rect x="3" y="4" width="18" height="18" rx="2" stroke-width="1.5"/>
                <path d="M16 2v4M8 2v4M3 10h18" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M8 14h.01M8 18h.01M12 14h.01M12 18h.01M16 14h.01M16 18h.01" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span>Planner</span>
            <span id="plannerItemCount" class="mini-badge" style="display: none;">0</span>
        </button>
        <div class="footer-divider"></div>

        <!-- Freshhhy AI Shopping Assistant -->
        @livewire('chat.freshhhy')
    </div>

    <!-- Quote Details Modal (position:fixed overlays everything) -->
    <div id="quoteDetailsModal" class="quote-modal-overlay" style="
        display: none;
        position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(232, 235, 240, 0.95);
    backdrop-filter: blur(10px);
    z-index: 999999;
    opacity: 0;
    transition: opacity 0.15s ease;
">
    <!-- Floating close button -->
    <button onclick="closeQuoteModal()" style="
        position: absolute;
        top: 20px; right: 20px;
        width: 36px; height: 36px;
        background: #E8EBF0;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5),
                    -4px -4px 8px rgba(255, 255, 255, 0.7);
        transition: all 0.2s ease;
        z-index: 10;
    "
    onmouseover="this.style.boxShadow='inset 2px 2px 5px rgba(163, 177, 198, 0.5), inset -2px -2px 5px rgba(255, 255, 255, 0.7)'"
    onmouseout="this.style.boxShadow='4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7)'">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
            <path d="M18 6L6 18M6 6L18 18" stroke="#6B7280" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
    </button>

    <!-- Modal card -->
    <div class="quote-modal-container" style="
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 420px;
        max-height: 80vh;
        background: #E8EBF0;
        border-radius: 24px;
        box-shadow: 20px 20px 40px rgba(163, 177, 198, 0.5),
                    -20px -20px 40px rgba(255, 255, 255, 0.7);
        display: flex;
        flex-direction: column;
    ">
        <!-- Content area -->
        <div class="quote-modal-body" id="quoteDetailsContent" style="
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        ">
            <!-- Content injected by JavaScript -->
        </div>

        <!-- Action buttons -->
        <div style="
            display: flex;
            gap: 12px;
            padding: 20px 24px;
            border-top: 1px solid rgba(163, 177, 198, 0.2);
        ">
            <button onclick="acceptQuoteFromModal()" style="
                flex: 1.5;
                padding: 10px;
                background: linear-gradient(135deg, #10B981, #059669);
                border: none;
                border-radius: 12px;
                font-size: 13px;
                font-weight: 600;
                color: white;
                cursor: pointer;
                box-shadow: 4px 4px 8px rgba(16, 185, 129, 0.3),
                            -4px -4px 8px rgba(255, 255, 255, 0.7);
                transition: all 0.15s ease;
            "
            onmouseover="this.style.background='linear-gradient(135deg, #059669, #047857)'; this.style.boxShadow='6px 6px 12px rgba(16, 185, 129, 0.4), -6px -6px 12px rgba(255, 255, 255, 0.8)'"
            onmouseout="this.style.background='linear-gradient(135deg, #10B981, #059669)'; this.style.boxShadow='4px 4px 8px rgba(16, 185, 129, 0.3), -4px -4px 8px rgba(255, 255, 255, 0.7)'">
                Accept Quote
            </button>
        </div>
    </div>
</div>

@push('scripts')
<!-- Quote System JavaScript -->
<script src="{{ asset('assets/js/buyer/quotes/quote-timers.js') }}"></script>
<script src="{{ asset('assets/js/buyer/quotes/quote-modal.js') }}"></script>
<script>
    // Initialize activeQuotes array from DOM
    window.activeQuotes = [];

    // Initialize timers when component loads
    document.addEventListener('livewire:initialized', () => {
        setTimeout(() => {
            syncActiveQuotes();
            initializeQuoteTimers();
        }, 100);
    });

    // Re-initialize after Livewire updates (new quote received)
    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'quotes.buyer-quote-panel') {
            setTimeout(() => {
                syncActiveQuotes();
                initializeQuoteTimers();
            }, 100);
        }
    });

    // Sync activeQuotes array from DOM
    function syncActiveQuotes() {
        const quoteElements = document.querySelectorAll('.quote-item[data-quote-id]');
        window.activeQuotes = Array.from(quoteElements).map((element) => {
            const quoteJson = element.getAttribute('data-quote-json');
            if (quoteJson) {
                try {
                    return JSON.parse(quoteJson);
                } catch (e) {
                    console.error('Failed to parse quote JSON:', e);
                    return null;
                }
            }
            return null;
        }).filter(q => q !== null);

        console.log(`📊 Synced ${window.activeQuotes.length} quotes from DOM`);
    }
</script>
@endpush
