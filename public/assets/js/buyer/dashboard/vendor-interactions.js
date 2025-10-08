/**
 * Vendor Dashboard Interactions
 * Handles vendor-specific functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Vendor Dashboard Interactions Loaded');

    // Initialize vendor components
    initializeVendorDashboard();
    initializeRFQHandlers();
    initializeInventoryHandlers();
    initializeQuickActions();
    initializeNotifications();
    initializeAutoRefresh();
});

/**
 * Initialize vendor dashboard
 */
function initializeVendorDashboard() {
    // Add vendor-specific class to body
    document.body.classList.add('vendor-dashboard');

    // Initialize tooltips
    initializeTooltips();

    // Initialize time counters
    updateRFQTimers();
    setInterval(updateRFQTimers, 60000); // Update every minute
}

/**
 * Initialize RFQ handlers
 */
function initializeRFQHandlers() {
    // Handle RFQ response buttons
    document.querySelectorAll('.rfq-action-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const rfqId = this.dataset.rfqId;
            handleRFQResponse(rfqId);
        });
    });

    // Handle RFQ item clicks
    document.querySelectorAll('.rfq-item').forEach(item => {
        item.addEventListener('click', function() {
            const rfqId = this.dataset.rfqId;
            if (rfqId) {
                viewRFQDetails(rfqId);
            }
        });
    });
}

/**
 * Initialize inventory handlers
 */
function initializeInventoryHandlers() {
    // Handle inventory item clicks
    document.querySelectorAll('.inventory-item').forEach(item => {
        item.addEventListener('click', function() {
            const productId = this.dataset.productId;
            if (productId) {
                viewProductDetails(productId);
            }
        });
    });

    // Handle add product button
    const addProductBtn = document.querySelector('.add-product-btn');
    if (addProductBtn) {
        addProductBtn.addEventListener('click', function() {
            openAddProductModal();
        });
    }

    // Handle stock update buttons
    document.querySelectorAll('.update-stock-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const productId = this.dataset.productId;
            updateProductStock(productId);
        });
    });
}

/**
 * Initialize quick actions
 */
function initializeQuickActions() {
    const quickActions = {
        'update-inventory': () => window.location.href = '/vendor/inventory',
        'view-analytics': () => window.location.href = '/vendor/analytics',
        'message-buyers': () => window.location.href = '/vendor/messages',
        'manage-products': () => window.location.href = '/vendor/products',
        'view-orders': () => window.location.href = '/vendor/orders',
        'financial-overview': () => window.location.href = '/vendor/financial'
    };

    document.querySelectorAll('.quick-action-btn').forEach(button => {
        const action = button.dataset.action;
        if (action && quickActions[action]) {
            button.addEventListener('click', quickActions[action]);
        }
    });
}

/**
 * Initialize notifications
 */
function initializeNotifications() {
    // Check for new notifications every 30 seconds
    setInterval(checkNewNotifications, 30000);

    // Handle notification bell click
    const notificationBell = document.querySelector('.vendor-notifications');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            toggleNotificationPanel();
        });
    }
}

/**
 * Initialize auto-refresh for live data
 */
function initializeAutoRefresh() {
    // Refresh dashboard stats every 2 minutes
    setInterval(() => {
        if (window.Livewire) {
            window.Livewire.emit('refreshDashboard');
        }
    }, 120000);

    // Update online status heartbeat
    setInterval(() => {
        updateOnlineStatus();
    }, 15000); // Every 15 seconds
}

/**
 * Update RFQ timers
 */
function updateRFQTimers() {
    document.querySelectorAll('.rfq-timer').forEach(timer => {
        const expiresAt = timer.dataset.expiresAt;
        if (expiresAt) {
            const remaining = calculateTimeRemaining(expiresAt);
            timer.textContent = remaining.formatted;

            // Add urgent class if less than 2 hours
            if (remaining.hours < 2 && remaining.hours >= 0) {
                timer.classList.add('urgent');
            } else {
                timer.classList.remove('urgent');
            }
        }
    });
}

/**
 * Calculate time remaining
 */
function calculateTimeRemaining(expiresAt) {
    const now = new Date();
    const expiry = new Date(expiresAt);
    const diff = expiry - now;

    if (diff <= 0) {
        return { hours: -1, minutes: 0, formatted: 'Expired' };
    }

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

    return {
        hours: hours,
        minutes: minutes,
        formatted: `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`
    };
}

/**
 * Handle RFQ response
 */
function handleRFQResponse(rfqId) {
    // Show quote modal or redirect to quote page
    if (window.Livewire) {
        window.Livewire.emit('respondToRfq', rfqId);
    } else {
        window.location.href = `/vendor/rfqs/${rfqId}/respond`;
    }
}

/**
 * View RFQ details
 */
function viewRFQDetails(rfqId) {
    window.location.href = `/vendor/rfqs/${rfqId}`;
}

/**
 * View product details
 */
function viewProductDetails(productId) {
    window.location.href = `/vendor/products/${productId}/edit`;
}

/**
 * Open add product modal
 */
function openAddProductModal() {
    if (window.Livewire) {
        window.Livewire.emit('openAddProductModal');
    } else {
        window.location.href = '/vendor/products/create';
    }
}

/**
 * Update product stock
 */
function updateProductStock(productId) {
    const newStock = prompt('Enter new stock quantity:');
    if (newStock !== null && !isNaN(newStock)) {
        if (window.Livewire) {
            window.Livewire.emit('updateStock', productId, newStock);
        } else {
            // Fallback to AJAX request
            fetch(`/vendor/products/${productId}/update-stock`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ stock_quantity: newStock })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    }
}

/**
 * Check for new notifications
 */
function checkNewNotifications() {
    fetch('/vendor/notifications/unread-count', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        updateNotificationBadge(data.count);
    })
    .catch(error => console.error('Error checking notifications:', error));
}

/**
 * Update notification badge
 */
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'block';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Toggle notification panel
 */
function toggleNotificationPanel() {
    const panel = document.querySelector('.notification-panel');
    if (panel) {
        panel.classList.toggle('show');
    }
}

/**
 * Update online status
 */
function updateOnlineStatus() {
    fetch('/vendor/heartbeat', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    }).catch(error => console.error('Heartbeat error:', error));
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
        });

        element.addEventListener('mouseleave', function() {
            const tooltips = document.querySelectorAll('.tooltip');
            tooltips.forEach(t => t.remove());
        });
    });
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-AU', {
        style: 'currency',
        currency: 'AUD'
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-AU', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
}

// Export functions for use in other scripts
window.vendorDashboard = {
    updateRFQTimers,
    handleRFQResponse,
    updateProductStock,
    checkNewNotifications,
    formatCurrency,
    formatDate
};