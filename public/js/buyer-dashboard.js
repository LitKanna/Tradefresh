// Sydney Markets Buyer Dashboard JavaScript
// Clean, minimal JavaScript for essential UI interactions only

// Weekly Planner Data Storage
let weeklyPlanner = {
    monday: [],
    tuesday: [],
    wednesday: [],
    thursday: [],
    friday: [],
    saturday: [],
    sunday: []
};

let currentDay = 'monday';

// User Menu Functions
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('active');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const userIcon = event.target.closest('.user-icon-container');

    if (!userIcon && dropdown) {
        dropdown.classList.remove('active');
    }
});

// Logout Function
async function handleLogout() {
    try {
        const response = await fetch('/buyer/logout', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });

        if (response.ok) {
            window.location.href = '/buyer/login';
        }
    } catch (error) {
        console.error('Logout error:', error);
    }
}

// Quote Actions
function viewQuoteDetails(quoteId, event) {
    if (event) event.stopPropagation();
    console.log('View quote:', quoteId);
    // Implement quote details modal if needed
}

async function acceptQuote(quoteId) {
    try {
        const response = await fetch(`/api/buyer/quotes/${quoteId}/accept`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showToast('Quote accepted successfully!', 'success');
            // Refresh Livewire component
            if (window.Livewire) {
                const component = Livewire.first();
                if (component) {
                    component.call('refreshQuotes');
                }
            }
        } else {
            showToast(data.message || 'Failed to accept quote', 'error');
        }
    } catch (error) {
        console.error('Error accepting quote:', error);
        showToast('Error accepting quote', 'error');
    }
}

// Weekly Planner Functions
function openWeeklyPlanner() {
    const modal = document.getElementById('weeklyPlannerModal');
    if (modal) {
        modal.classList.add('active');
        loadPlannerData();
    }
}

function closePlannerModal() {
    const modal = document.getElementById('weeklyPlannerModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function selectDay(day) {
    currentDay = day;

    // Update active button
    document.querySelectorAll('.day-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    const activeBtn = document.querySelector(`.day-btn[data-day="${day}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }

    // Load products for selected day
    displayPlannerProducts();
}

function loadPlannerData() {
    // Load from localStorage if available
    const saved = localStorage.getItem('weeklyPlanner');
    if (saved) {
        try {
            weeklyPlanner = JSON.parse(saved);
        } catch (e) {
            console.error('Error loading planner data:', e);
        }
    }
    displayPlannerProducts();
    updatePlannerCount();
}

function displayPlannerProducts() {
    const productsList = document.getElementById('productsList');
    if (!productsList) return;

    const products = weeklyPlanner[currentDay] || [];

    if (products.length === 0) {
        productsList.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #9CA3AF;">
                No products added for ${currentDay.charAt(0).toUpperCase() + currentDay.slice(1)}
            </div>
        `;
        return;
    }

    productsList.innerHTML = products.map((product, index) => `
        <div class="planner-product-item">
            <span>${product.name}</span>
            <span>${product.quantity} ${product.unit}</span>
            <button onclick="removeProduct(${index})" title="Remove">×</button>
        </div>
    `).join('');
}

function addProduct() {
    const name = prompt('Product name:');
    if (!name) return;

    const quantity = prompt('Quantity:', '1');
    if (!quantity) return;

    const unit = prompt('Unit (kg, box, etc):', 'kg');

    if (!weeklyPlanner[currentDay]) {
        weeklyPlanner[currentDay] = [];
    }

    weeklyPlanner[currentDay].push({
        name: name,
        quantity: parseFloat(quantity) || 1,
        unit: unit || 'kg'
    });

    savePlannerData();
    displayPlannerProducts();
    updatePlannerCount();
}

function removeProduct(index) {
    if (weeklyPlanner[currentDay]) {
        weeklyPlanner[currentDay].splice(index, 1);
        savePlannerData();
        displayPlannerProducts();
        updatePlannerCount();
    }
}

function clearAllProducts() {
    if (confirm('Clear all products for ' + currentDay + '?')) {
        weeklyPlanner[currentDay] = [];
        savePlannerData();
        displayPlannerProducts();
        updatePlannerCount();
    }
}

function savePlannerData() {
    localStorage.setItem('weeklyPlanner', JSON.stringify(weeklyPlanner));
}

function updatePlannerCount() {
    let totalCount = 0;
    Object.values(weeklyPlanner).forEach(day => {
        totalCount += day.length;
    });

    const countBadge = document.getElementById('plannerItemCount');
    if (countBadge) {
        countBadge.textContent = totalCount;
        countBadge.style.display = totalCount > 0 ? 'inline-block' : 'none';
    }

    const sendBadge = document.getElementById('sendBadge');
    if (sendBadge) {
        sendBadge.textContent = totalCount;
        sendBadge.style.display = totalCount > 0 ? 'inline-block' : 'none';
    }
}

// Send to Vendors Function
async function quickSendToVendors() {
    const totalItems = Object.values(weeklyPlanner).reduce((sum, day) => sum + day.length, 0);

    if (totalItems === 0) {
        showToast('Add items to your weekly planner first', 'warning');
        openWeeklyPlanner();
        return;
    }

    if (!confirm(`Send ${totalItems} items to vendors for quotes?`)) {
        return;
    }

    try {
        // Prepare RFQ data
        const rfqData = {
            items: [],
            delivery_date: null,
            special_instructions: 'Weekly order from planner'
        };

        // Combine all items from all days
        Object.entries(weeklyPlanner).forEach(([day, products]) => {
            products.forEach(product => {
                rfqData.items.push({
                    product_name: product.name,
                    quantity: product.quantity,
                    unit: product.unit,
                    day: day
                });
            });
        });

        // Send RFQ
        const response = await fetch('/api/buyer/rfq/create', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(rfqData)
        });

        const data = await response.json();

        if (response.ok && data.success) {
            showToast(`RFQ sent to ${data.vendor_count || 'all'} vendors!`, 'success');
            // Clear planner after successful send
            weeklyPlanner = {
                monday: [], tuesday: [], wednesday: [], thursday: [],
                friday: [], saturday: [], sunday: []
            };
            savePlannerData();
            updatePlannerCount();
            closePlannerModal();
        } else {
            showToast(data.message || 'Failed to send RFQ', 'error');
        }
    } catch (error) {
        console.error('Error sending RFQ:', error);
        showToast('Error sending request to vendors', 'error');
    }
}

// Toast Notification System
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    toast.textContent = message;

    document.body.appendChild(toast);

    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Quote Timer Updates
function updateQuoteTimers() {
    document.querySelectorAll('.quote-timer').forEach(timer => {
        const expiresAt = parseInt(timer.dataset.expires);
        if (!expiresAt) return;

        const now = Date.now();
        const remaining = Math.max(0, expiresAt - now);

        if (remaining === 0) {
            timer.textContent = 'Expired';
            timer.style.color = '#6B7280';
            // Disable accept button
            const quoteItem = timer.closest('.quote-item');
            if (quoteItem) {
                const acceptBtn = quoteItem.querySelector('.accept-btn');
                if (acceptBtn) {
                    acceptBtn.disabled = true;
                    acceptBtn.style.opacity = '0.5';
                    acceptBtn.style.cursor = 'not-allowed';
                }
            }
        } else {
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            timer.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

            // Change color based on time remaining
            if (minutes < 5) {
                timer.style.color = '#EF4444'; // Red
            } else if (minutes < 10) {
                timer.style.color = '#F59E0B'; // Orange
            } else {
                timer.style.color = '#10B981'; // Green
            }
        }
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Buyer Dashboard initialized');

    // Load planner data
    updatePlannerCount();

    // Start timer updates
    setInterval(updateQuoteTimers, 1000);

    // Listen for Livewire events
    if (window.Livewire) {
        Livewire.on('play-notification-sound', () => {
            // Play sound if needed
            const audio = new Audio('/sounds/notification.mp3');
            audio.play().catch(e => console.log('Could not play sound:', e));
        });

        Livewire.on('show-toast', (event) => {
            showToast(event.message || event.detail?.message, event.type || event.detail?.type);
        });
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);