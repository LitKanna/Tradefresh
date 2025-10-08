<div>
    <!-- Quote Submission Modal -->
    @if($showQuoteModal && $selectedRFQ)
    <div class="quote-modal-overlay" wire:click.self="closeQuoteModal">
        <button class="modal-close" wire:click="closeQuoteModal">
            <svg viewBox="0 0 24 24">
                <path d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="quote-modal-container" onclick="event.stopPropagation()">
            <div class="modal-accent"></div>

            <div class="modal-content">
                <div class="modal-header-card">
                    <div class="modal-header-info">
                        <div class="modal-buyer-info">
                            <div class="modal-buyer-avatar">{{ substr($selectedRFQ['business_name'] ?? 'C', 0, 1) }}</div>
                            <div class="modal-buyer-details">
                                <h3>{{ $selectedRFQ['business_name'] ?? 'Customer' }}</h3>
                                <p>RFQ #{{ str_pad($selectedRFQ['id'] ?? '0', 4, '0', STR_PAD_LEFT) }}</p>
                            </div>
                        </div>
                        <div class="modal-delivery-info">
                            <div class="modal-delivery-label">DELIVERY</div>
                            <div class="modal-delivery-date">{{ \Carbon\Carbon::parse($selectedRFQ['delivery_date'] ?? now())->format('M d, Y') }}</div>
                        </div>
                    </div>
                </div>

                <div class="modal-items-section">
                    <div class="modal-items-title">Quote Items</div>
                    @foreach(($selectedRFQ['items'] ?? []) as $index => $item)
                        <div class="modal-item">
                            <div class="modal-item-info">
                                <span class="modal-item-icon">🍎</span>
                                <div class="modal-item-details">
                                    <h4>{{ $item['name'] ?? 'Item' }}</h4>
                                    <p>{{ $item['quantity'] }} {{ $item['unit'] ?? 'units' }}</p>
                                </div>
                            </div>
                            <div class="modal-item-price">
                                <span style="font-size: 12px; color: var(--neuro-text-secondary);">$</span>
                                <input type="number"
                                       wire:model.live="quoteItems.{{ $index }}.price"
                                       step="0.01"
                                       placeholder="0.00"
                                       class="modal-price-input">
                                <span class="modal-price-unit">/{{ $item['unit'] ?? 'unit' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="background: linear-gradient(135deg, #E8EBF0, #F3F4F6); border-radius: 12px; padding: 12px; text-align: center;">
                    <div style="font-size: 11px; color: var(--neuro-text-secondary); margin-bottom: 4px;">TOTAL QUOTE</div>
                    <div style="font-size: 24px; font-weight: 700; color: var(--neuro-accent);">${{ number_format($quoteTotalAmount ?? 0, 2) }}</div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" wire:click="closeQuoteModal">Cancel</button>
                <button class="modal-btn modal-btn-submit" wire:click="submitQuote">Send Quote</button>
            </div>
        </div>
    </div>
    @endif
</div>
