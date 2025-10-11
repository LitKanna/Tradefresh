<div class="order-card-panel" style="position: relative;">
        <div class="order-card-header">
            <h3 class="order-card-title">Customer Requests</h3>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button wire:click="refreshRfqs" class="refresh-btn" title="Check for new requests">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 14px; height: 14px;">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="order-card-content" wire:poll.5s="loadRealRfqs">
            @if(count($pendingRfqs) > 0)
                <div class="quotes-list-container" style="flex: 1; overflow-y: auto; overflow-x: hidden;">
                    @foreach($pendingRfqs as $index => $rfq)
                        <div class="quote-item" data-rfq-id="{{ $rfq['id'] ?? $index }}" data-created-at="{{ $rfq['created_at'] ?? now() }}">
                            <!-- Timer -->
                            <div class="quote-timer" id="timer-{{ $rfq['id'] ?? $index }}" style="opacity: 0;">--:--</div>

                            <!-- Buyer Name -->
                            <div class="quote-vendor">{{ $rfq['business_name'] ?? 'Unknown Buyer' }}</div>

                            <!-- Product Request -->
                            <div class="quote-product">
                                @if(isset($rfq['items']) && count($rfq['items']) > 0)
                                    @if(count($rfq['items']) == 1)
                                        {{ $rfq['items'][0]['name'] }} - {{ $rfq['items'][0]['quantity'] }} {{ $rfq['items'][0]['unit'] }}
                                    @else
                                        {{ $rfq['items'][0]['name'] }} + {{ count($rfq['items']) - 1 }} more
                                    @endif
                                @else
                                    No items
                                @endif
                            </div>

                            <!-- Price Display -->
                            <div class="quote-price">
                                <span class="price-label">Request for:</span>
                                <span class="price-value">{{ \Carbon\Carbon::parse($rfq['delivery_date'])->format('M d') }}</span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="quote-actions">
                                <button class="quote-action view" wire:click="viewRFQ({{ $rfq['id'] ?? 0 }})">
                                    View
                                </button>
                                <button class="quote-action accept" wire:click="openQuoteModal({{ $rfq['id'] ?? 0 }})">
                                    Quote
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-quotes">
                    <p style="text-align: center; color: var(--text-tertiary); padding: 20px; font-size: 12px;">
                        Waiting for customer requests...
                    </p>
                </div>
            @endif
        </div>

        <!-- Footer with Actions -->
        <div class="order-card-footer">
            <button class="footer-btn planner-btn" onclick="alert('Manage products')" title="Manage Products">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-width="1.5"/>
                </svg>
                <span>Products</span>
            </button>
            <div class="footer-divider"></div>
            <button class="footer-btn send-btn" wire:click="refreshRfqs" title="Refresh">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="1.5"/>
                </svg>
                <span>Refresh</span>
            </button>
        </div>

    <!-- RFQ Details Modal -->
    @if($showRfqDetailsModal && $selectedRfqDetails)
    <div class="rfq-view-modal-overlay" wire:click.self="closeRfqDetailsModal">
        <div class="rfq-view-modal" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="rfq-modal-header">
                <div class="rfq-modal-title">
                    <svg viewBox="0 0 24 24" class="modal-icon">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                    <div>
                        <h3>Request Details</h3>
                        <span class="rfq-number">{{ $selectedRfqDetails['rfq_number'] ?? 'RFQ' }}</span>
                    </div>
                </div>
                <button class="modal-close-btn" wire:click="closeRfqDetailsModal">
                    <svg viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
            </div>

            <!-- Buyer Info Card -->
            <div class="rfq-info-card">
                <div class="info-card-header">
                    <div class="buyer-avatar">{{ substr($selectedRfqDetails['business_name'] ?? 'B', 0, 1) }}</div>
                    <div class="buyer-details">
                        <h4>{{ $selectedRfqDetails['business_name'] ?? 'Customer' }}</h4>
                        <p class="buyer-meta">
                            <span class="meta-item">
                                <svg viewBox="0 0 24 24" style="width: 12px; height: 12px;">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" fill="none"/>
                                    <path d="M12 6v6l4 2" stroke="currentColor"/>
                                </svg>
                                {{ $selectedRfqDetails['request_time'] ?? 'Just now' }}
                            </span>
                            <span class="meta-item urgency-{{ $selectedRfqDetails['urgency_class'] ?? 'normal' }}">
                                {{ $selectedRfqDetails['urgency'] ?? 'NORMAL' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Delivery Details -->
            <div class="rfq-section">
                <div class="section-label">
                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                        <path d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M9 11h.01M12 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                    Delivery Information
                </div>
                <div class="delivery-grid">
                    <div class="delivery-item">
                        <span class="delivery-label">Date</span>
                        <span class="delivery-value">{{ \Carbon\Carbon::parse($selectedRfqDetails['delivery_date'] ?? now())->format('M d, Y') }}</span>
                    </div>
                    <div class="delivery-item">
                        <span class="delivery-label">Time</span>
                        <span class="delivery-value">{{ $selectedRfqDetails['delivery_time'] ?? 'Morning' }}</span>
                    </div>
                    @if(isset($selectedRfqDetails['delivery_address']) && $selectedRfqDetails['delivery_address'])
                    <div class="delivery-item full-width">
                        <span class="delivery-label">Address</span>
                        <span class="delivery-value">{{ $selectedRfqDetails['delivery_address'] }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Items List -->
            <div class="rfq-section">
                <div class="section-label">
                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                        <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                    Requested Items ({{ count($selectedRfqDetails['items'] ?? []) }})
                </div>
                <div class="items-list">
                    @foreach(($selectedRfqDetails['items'] ?? []) as $item)
                    <div class="item-card">
                        <div class="item-icon">📦</div>
                        <div class="item-details">
                            <h5>{{ $item['name'] ?? 'Product' }}</h5>
                            <div class="item-specs">
                                <span class="spec-badge">{{ $item['quantity'] ?? 0 }} {{ $item['unit'] ?? 'units' }}</span>
                                @if(isset($item['notes']) && $item['notes'])
                                <span class="spec-note">{{ $item['notes'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Special Instructions -->
            @if(isset($selectedRfqDetails['special_instructions']) && $selectedRfqDetails['special_instructions'])
            <div class="rfq-section">
                <div class="section-label">
                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px;">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" fill="currentColor"/>
                    </svg>
                    Special Instructions
                </div>
                <div class="instructions-box">
                    {{ $selectedRfqDetails['special_instructions'] }}
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="rfq-modal-footer">
                <button class="rfq-btn rfq-btn-secondary" wire:click="closeRfqDetailsModal">
                    Close
                </button>
                <button class="rfq-btn rfq-btn-primary" wire:click="openQuoteModalFromDetails">
                    <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; margin-right: 6px;">
                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke="currentColor" fill="none" stroke-width="2"/>
                    </svg>
                    Send Quote
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Timer management for RFQ expiry
    window.rfqTimerIntervals = window.rfqTimerIntervals || {};

    function initializeRfqTimers() {
        Object.keys(window.rfqTimerIntervals).forEach(key => {
            if (window.rfqTimerIntervals[key]) {
                clearInterval(window.rfqTimerIntervals[key]);
                delete window.rfqTimerIntervals[key];
            }
        });

        const rfqItems = document.querySelectorAll('[data-rfq-id]');

        rfqItems.forEach(item => {
            const rfqId = item.dataset.rfqId;
            const createdAt = item.dataset.createdAt;

            if (!createdAt || createdAt === 'null') {
                return;
            }

            const createdDate = new Date(createdAt);
            const timerId = 'timer-' + rfqId;
            const timerElement = document.getElementById(timerId);

            if (timerElement && !window.rfqTimerIntervals[rfqId]) {
                const now = new Date();
                const elapsedMs = now - createdDate;
                const elapsedSeconds = Math.floor(elapsedMs / 1000);
                let totalSecondsRemaining = Math.max((30 * 60) - elapsedSeconds, 0);

                const updateTimer = () => {
                    if (totalSecondsRemaining > 0) {
                        const minutes = Math.floor(totalSecondsRemaining / 60);
                        const seconds = totalSecondsRemaining % 60;

                        timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                        timerElement.style.opacity = '1';

                        if (minutes <= 5) {
                            timerElement.classList.add('critical');
                        } else if (minutes <= 10) {
                            timerElement.classList.add('warning');
                        }

                        totalSecondsRemaining--;
                    } else {
                        if (window.rfqTimerIntervals[rfqId]) {
                            clearInterval(window.rfqTimerIntervals[rfqId]);
                            delete window.rfqTimerIntervals[rfqId];
                        }

                        timerElement.textContent = 'Expired';
                        item.style.opacity = '0.5';
                    }
                };

                updateTimer();

                if (totalSecondsRemaining > 0) {
                    window.rfqTimerIntervals[rfqId] = setInterval(updateTimer, 1000);
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initializeRfqTimers);

    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(initializeRfqTimers, 100);
        });
    }
</script>
@endpush
