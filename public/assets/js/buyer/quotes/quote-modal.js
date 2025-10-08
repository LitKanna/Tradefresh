/**
 * Quote Modal System - Sydney Markets B2B
 * Handles quote detail viewing and acceptance
 * Livewire v3 integration
 */

/**
 * View quote details - Opens modal with full quote information
 */
window.viewQuoteDetails = function(quoteId, event) {
    if (event) {
        event.stopPropagation();
    }

    console.log('📋 viewQuoteDetails called for quote:', quoteId);

    // Find quote in activeQuotes array or from DOM
    let quote = null;

    // Try activeQuotes array first
    if (window.activeQuotes && window.activeQuotes.length > 0) {
        quote = window.activeQuotes.find(q => q.id == quoteId);
        console.log('Found quote in activeQuotes:', quote);
    }

    // Fallback: Get quote from DOM data attribute
    if (!quote) {
        const quoteElement = document.querySelector(`.quote-item[data-quote-id="${quoteId}"]`);
        if (quoteElement && quoteElement.dataset.quoteJson) {
            try {
                quote = JSON.parse(quoteElement.dataset.quoteJson);
                console.log('Found quote from DOM data-quote-json:', quote);
            } catch (e) {
                console.error('Failed to parse quote JSON from DOM:', e);
            }
        }
    }

    if (!quote) {
        console.error('Quote not found! QuoteId:', quoteId);
        alert('Quote not found. Please refresh the page.');
        return;
    }

    showQuoteModal(quote);
};

/**
 * Show quote modal with details
 */
function showQuoteModal(quote) {
    console.log('Opening quote modal for:', quote);

    const modal = document.getElementById('quoteDetailsModal');
    if (!modal) {
        console.error('Quote modal element not found!');
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }

    // Update modal content
    const content = document.getElementById('quoteDetailsContent');
    if (!content) {
        console.error('Modal content element not found!');
        alert('Error loading quote details. Please refresh the page.');
        return;
    }

    // Use quote's actual price
    const totalAmount = parseFloat(quote.price) || parseFloat(quote.total_amount) || 0;

    content.innerHTML = `
        <!-- Header with vendor info and price -->
        <div style="
            background: #E8EBF0;
            border-radius: 16px;
            padding: 16px;
            box-shadow: inset 2px 2px 5px rgba(163, 177, 198, 0.5),
                        inset -2px -2px 5px rgba(255, 255, 255, 0.7);
        ">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="
                        width: 40px; height: 40px;
                        background: linear-gradient(135deg, #10B981, #059669);
                        border-radius: 12px;
                        display: flex; align-items: center; justify-content: center;
                        font-size: 18px; color: white; font-weight: bold;
                    ">${quote.vendor ? quote.vendor.charAt(0) : 'V'}</div>
                    <div>
                        <div style="font-size: 16px; font-weight: 600; color: #1F2937;">${quote.vendor}</div>
                        <div style="font-size: 12px; color: #6B7280;">Quote #${String(quote.id).padStart(4, '0')}</div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 20px; font-weight: 700; color: #10B981;">$${totalAmount.toFixed(2)}</div>
                    <div style="font-size: 11px; color: #F59E0B; display: flex; align-items: center; gap: 4px; justify-content: flex-end;">
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <span id="modalQuoteTimer" data-quote-id="${quote.id}" data-expires="${quote.expiresAt || Date.now() + 30*60*1000}">${calculateRemainingTime(quote.expiresAt)}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items list -->
        <div style="
            background: #E8EBF0;
            border-radius: 16px;
            padding: 12px;
            box-shadow: 3px 3px 6px rgba(163, 177, 198, 0.5),
                        -3px -3px 6px rgba(255, 255, 255, 0.7);
        ">
            <div style="font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 8px;">ITEMS</div>
            ${quote.items && quote.items.length > 0 ? quote.items.slice(0, 3).map((item) => `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: #F3F4F6; border-radius: 8px; margin-bottom: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 14px;">${getProductEmoji(item.description)}</span>
                        <div>
                            <div style="font-size: 13px; font-weight: 500; color: #1F2937;">${item.description || 'Item'}</div>
                            <div style="font-size: 11px; color: #6B7280;">${item.quantity} ${item.unit}</div>
                        </div>
                    </div>
                    <div style="font-size: 13px; font-weight: 600; color: #1F2937;">$${((item.quantity || 0) * (item.unit_price || 0)).toFixed(2)}</div>
                </div>
            `).join('') : '<div style="text-align: center; color: #9CA3AF; padding: 12px; font-size: 13px;">No items specified</div>'}
            ${quote.items && quote.items.length > 3 ? `<div style="text-align: center; color: #6B7280; font-size: 11px; padding: 4px;">+${quote.items.length - 3} more items</div>` : ''}
        </div>

        <!-- Delivery info -->
        <div style="
            background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        ">
            <svg width="16" height="16" fill="#059669" viewBox="0 0 20 20">
                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
            </svg>
            <div>
                <div style="font-size: 11px; font-weight: 600; color: #059669;">DELIVERY</div>
                <div style="font-size: 12px; color: #047857;">${quote.delivery_date || 'Within 24 hours'}</div>
            </div>
        </div>
    `;

    // Store quote ID for accept button
    modal.dataset.modalQuoteId = quote.id;

    // Show modal with fade animation
    modal.style.display = 'block';
    modal.style.zIndex = '999999';
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);
}

/**
 * Close quote modal
 */
window.closeQuoteModal = function() {
    const modal = document.getElementById('quoteDetailsModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
};

/**
 * Accept quote from modal
 */
window.acceptQuoteFromModal = function() {
    const modal = document.getElementById('quoteDetailsModal');
    const quoteId = modal ? modal.dataset.modalQuoteId : null;
    if (quoteId) {
        acceptQuote(quoteId);
        closeQuoteModal();
    }
};

/**
 * Accept quote - Main acceptance logic
 */
window.acceptQuote = function(quoteId) {
    console.log('Accepting quote:', quoteId);

    // TODO: Implement via Livewire method
    // For now, show confirmation
    if (confirm('Accept this quote and create an order?')) {
        // Call Livewire method
        if (window.Livewire) {
            Livewire.dispatch('accept-quote', { quoteId: quoteId });
        }

        // Remove quote from UI
        const quoteElement = document.querySelector(`.quote-item[data-quote-id="${quoteId}"]`);
        if (quoteElement) {
            quoteElement.style.transition = 'opacity 0.3s';
            quoteElement.style.opacity = '0';
            setTimeout(() => {
                quoteElement.remove();
                updateQuoteCounts();
            }, 300);
        }

        // Show success toast
        showToast('success', 'Quote accepted successfully!', 'Order Created');
    }
};

/**
 * Calculate remaining time from expiry timestamp
 */
function calculateRemainingTime(expiresAt) {
    if (!expiresAt) {
        return '30:00';
    }

    const now = Date.now();
    const remaining = Math.max(0, expiresAt - now);
    const minutes = Math.floor(remaining / 60000);
    const seconds = Math.floor((remaining % 60000) / 1000);

    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

/**
 * Get product emoji for display
 */
function getProductEmoji(productName) {
    if (!productName) return '📦';

    const name = productName.toLowerCase();

    // Vegetables
    if (name.includes('tomato')) return '🍅';
    if (name.includes('lettuce') || name.includes('salad')) return '🥬';
    if (name.includes('carrot')) return '🥕';
    if (name.includes('potato')) return '🥔';
    if (name.includes('onion')) return '🧅';
    if (name.includes('garlic')) return '🧄';
    if (name.includes('pepper') || name.includes('capsicum')) return '🌶️';
    if (name.includes('broccoli')) return '🥦';
    if (name.includes('corn')) return '🌽';
    if (name.includes('mushroom')) return '🍄';

    // Fruits
    if (name.includes('apple')) return '🍎';
    if (name.includes('banana')) return '🍌';
    if (name.includes('orange')) return '🍊';
    if (name.includes('lemon')) return '🍋';
    if (name.includes('strawberr')) return '🍓';
    if (name.includes('grape')) return '🍇';
    if (name.includes('watermelon')) return '🍉';
    if (name.includes('peach')) return '🍑';
    if (name.includes('mango')) return '🥭';
    if (name.includes('pineapple')) return '🍍';

    // Herbs & Spices
    if (name.includes('basil') || name.includes('herb')) return '🌿';

    // Default
    return '📦';
}

console.log('✅ Quote modal system loaded');
