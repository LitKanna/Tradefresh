/**
 * Quote Timer System - Sydney Markets B2B
 * Handles 30-minute countdown timers for all active quotes
 * Livewire v3 + Reverb real-time integration
 */

// Global timer intervals storage
window.quoteTimerIntervals = window.quoteTimerIntervals || {};

/**
 * Initialize timers for all quote cards on page
 */
function initializeQuoteTimers() {
    console.log('⏱️ Initializing quote timers...');

    // Clear existing timers to prevent duplicates
    Object.keys(window.quoteTimerIntervals).forEach(quoteId => {
        clearInterval(window.quoteTimerIntervals[quoteId]);
    });
    window.quoteTimerIntervals = {};

    const quoteItems = document.querySelectorAll('.quote-item[data-quote-id]');
    console.log(`Found ${quoteItems.length} quotes to initialize timers for`);

    let activeCount = 0;

    quoteItems.forEach((item, index) => {
        const quoteId = item.dataset.quoteId;
        const expiresAt = item.dataset.expiresAt;

        console.log(`Quote ${quoteId}: expiresAt = ${expiresAt}`);

        // Calculate total seconds remaining
        let totalSecondsRemaining = 0;

        if (expiresAt && expiresAt !== 'null' && expiresAt !== 'undefined' && expiresAt !== '0') {
            // Use the expires_at timestamp from server (milliseconds)
            const expiryTime = parseInt(expiresAt);

            // SAFETY CHECK: Validate timestamp is in milliseconds (> 1 billion ms = valid date)
            if (expiryTime > 1000000000000) {
                const now = Date.now();
                totalSecondsRemaining = Math.max(0, Math.floor((expiryTime - now) / 1000));
                console.log(`✅ Quote ${quoteId}: Valid expiry=${new Date(expiryTime).toLocaleString()}, remaining=${totalSecondsRemaining}s`);
            } else {
                // Invalid timestamp - fallback to 30 minutes
                console.error(`❌ Quote ${quoteId}: Invalid expiry timestamp: ${expiryTime} - using 30min fallback`);
                totalSecondsRemaining = 30 * 60;
            }
        } else {
            // Fallback: calculate from created_at
            const createdAt = item.dataset.createdAt;
            if (!createdAt || createdAt === 'null' || createdAt === 'undefined') {
                return;
            }
            const createdDate = new Date(createdAt);
            const now = new Date();
            const elapsedMs = now - createdDate;
            const elapsedSeconds = Math.floor(elapsedMs / 1000);
            totalSecondsRemaining = Math.max((30 * 60) - elapsedSeconds, 0);
        }

        const timerId = 'timer-' + quoteId;
        const timerElement = document.getElementById(timerId);

        if (timerElement && !window.quoteTimerIntervals[quoteId]) {
            if (totalSecondsRemaining > 0) {
                activeCount++;
            }

            const updateTimer = () => {
                if (totalSecondsRemaining > 0) {
                    // Calculate minutes and seconds
                    const minutes = Math.floor(totalSecondsRemaining / 60);
                    const seconds = totalSecondsRemaining % 60;

                    // Update display
                    timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                    timerElement.style.opacity = '1';

                    // Update timer urgency states (Green → Warning → Critical)
                    if (minutes < 5) {
                        timerElement.className = 'quote-timer critical';
                    } else if (minutes < 10) {
                        timerElement.className = 'quote-timer warning';
                    } else {
                        timerElement.className = 'quote-timer';
                    }

                    // Sync modal timer if open
                    const modalTimer = document.getElementById('modalQuoteTimer');
                    if (modalTimer && modalTimer.dataset.quoteId == quoteId) {
                        modalTimer.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

                        const modalTimerParent = modalTimer.parentElement;
                        if (modalTimerParent) {
                            if (minutes < 5) {
                                modalTimerParent.style.color = '#000000';
                                modalTimerParent.style.fontWeight = '900';
                            } else if (minutes < 10) {
                                modalTimerParent.style.color = '#059669';
                                modalTimerParent.style.fontWeight = '800';
                            } else {
                                modalTimerParent.style.color = '#10B981';
                                modalTimerParent.style.fontWeight = '700';
                            }
                        }
                    }

                    // Decrement
                    totalSecondsRemaining--;
                } else {
                    // Timer expired
                    handleQuoteExpiry(quoteId, timerElement, item);
                }
            };

            // Initial update
            updateTimer();

            // Update every second if not expired
            if (totalSecondsRemaining > 0) {
                window.quoteTimerIntervals[quoteId] = setInterval(updateTimer, 1000);
                console.log(`✅ Timer started for quote ${quoteId} with ${totalSecondsRemaining}s remaining`);
            } else {
                console.log(`⏹️ Quote ${quoteId} already expired, not starting timer`);
            }
        }
    });

    console.log(`⏱️ Timer initialization complete. Active intervals: ${Object.keys(window.quoteTimerIntervals).length}`);

    // Update active quotes count
    updateQuoteCounts();
}

/**
 * Handle quote expiry
 */
function handleQuoteExpiry(quoteId, timerElement, quoteElement) {
    // Clear interval
    if (window.quoteTimerIntervals[quoteId]) {
        clearInterval(window.quoteTimerIntervals[quoteId]);
        delete window.quoteTimerIntervals[quoteId];
    }

    // Update timer display
    timerElement.textContent = 'Expired';
    timerElement.className = 'quote-timer';
    timerElement.style.color = '#6B7280';
    timerElement.style.fontWeight = '600';
    timerElement.style.opacity = '0.7';

    // Close modal if showing this quote
    const modalTimer = document.getElementById('modalQuoteTimer');
    if (modalTimer && modalTimer.dataset.quoteId == quoteId) {
        modalTimer.textContent = '0:00';
        const modalTimerParent = modalTimer.parentElement;
        if (modalTimerParent) {
            modalTimerParent.style.color = '#EF4444';
        }

        // Auto-close modal
        setTimeout(() => {
            if (typeof closeQuoteModal === 'function') {
                closeQuoteModal();
            }
        }, 1500);
    }

    // Fade out quote card
    setTimeout(() => {
        quoteElement.style.transition = 'opacity 0.5s';
        quoteElement.style.opacity = '0';

        setTimeout(() => {
            quoteElement.style.display = 'none';

            // Remove from activeQuotes array if exists
            if (window.activeQuotes) {
                const index = window.activeQuotes.findIndex(q => q.id == quoteId);
                if (index !== -1) {
                    window.activeQuotes.splice(index, 1);
                    console.log(`🗑️ Removed expired quote ${quoteId} from activeQuotes array`);
                }
            }

            // Update counts
            updateQuoteCounts();

            // Trigger Livewire refresh
            if (window.Livewire) {
                Livewire.dispatch('refreshQuotes');
            }
        }, 500);
    }, 2000);
}

/**
 * Update quote badge counts
 */
function updateQuoteCounts() {
    const visibleQuotes = document.querySelectorAll('.quote-item:not([style*="display: none"])');
    const activeCount = visibleQuotes.length;

    const quoteBadge = document.getElementById('quoteBadge');
    const activeQuotesCount = document.getElementById('activeQuotesCount');
    const quotesCount = document.getElementById('quotesCount');

    if (quoteBadge) quoteBadge.textContent = activeCount;
    if (activeQuotesCount) activeQuotesCount.textContent = activeCount;
    if (quotesCount) quotesCount.textContent = activeCount;
}

/**
 * Cleanup timers on component unmount
 */
function cleanupQuoteTimers() {
    Object.keys(window.quoteTimerIntervals).forEach(quoteId => {
        clearInterval(window.quoteTimerIntervals[quoteId]);
    });
    window.quoteTimerIntervals = {};
    console.log('🧹 All quote timers cleaned up');
}

// Auto-initialize on Livewire load
document.addEventListener('livewire:initialized', () => {
    console.log('Livewire initialized - setting up quote timer hooks');

    // Initialize timers after any Livewire update
    Livewire.hook('morph.updated', () => {
        console.log('Livewire morph updated - reinitializing timers');
        setTimeout(initializeQuoteTimers, 100);
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', cleanupQuoteTimers);
