<div>
<div class="dashboard-container">
    <!-- Floating Icons - Above Everything -->
    <div class="floating-icons">
        <button class="neuro-icon-raised" title="Home">
            <svg viewBox="0 0 24 24">
                <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
        </button>
        <button class="neuro-icon-raised" title="Cart">
            <svg viewBox="0 0 24 24">
                <circle cx="8" cy="21" r="1"/>
                <circle cx="19" cy="21" r="1"/>
                <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57L23 6H7"/>
            </svg>
        </button>
        <button class="neuro-icon-raised" title="Theme">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="5"/>
                <path d="M12 1v2m0 18v2m9-11h2M1 12h2m16.24-6.36l1.42-1.42M4.22 19.78l1.42-1.42m0-11.72l-1.42-1.42m13.14 13.14l1.42 1.42"/>
            </svg>
        </button>
        <div class="user-icon-container">
            <button class="neuro-icon-raised" onclick="toggleUserMenu()" title="User Account" aria-label="User menu">
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </button>

            <div class="user-dropdown" id="userDropdown">
                <div class="user-dropdown-header">
                    <div class="user-dropdown-name">
                        @auth('buyer')
                            {{ auth('buyer')->user()->alias ?: auth('buyer')->user()->first_name }}
                        @else
                            Guest User
                        @endauth
                    </div>
                    <div class="user-dropdown-email">
                        @auth('buyer')
                            {{ ucfirst(auth('buyer')->user()->buyer_type ?: 'Buyer') }}
                        @else
                            Not logged in
                        @endauth
                    </div>
                </div>

                <div class="user-dropdown-body">
                    @auth('buyer')
                        <a href="{{ route('buyer.profile') }}" class="user-dropdown-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                            Profile
                        </a>

                        <a href="{{ route('buyer.settings.index') }}" class="user-dropdown-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87 C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.8,11.69,4.8,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54 c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/>
                            </svg>
                            Settings
                        </a>

                        <a href="{{ route('buyer.discovered-leads') }}" class="user-dropdown-item" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white;">
                            <svg viewBox="0 0 24 24" fill="white">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                            <strong>📧 Discovered Leads (92 Emails)</strong>
                        </a>

                        <div class="user-dropdown-divider"></div>

                        <button onclick="handleLogout()" class="user-dropdown-item logout-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                            </svg>
                            Logout
                        </button>
                    @else
                        <a href="{{ route('buyer.login') }}" class="user-dropdown-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M11,7L9.6,8.4l2.6,2.6H2v2h10.2l-2.6,2.6L11,17l5-5L11,7z M20,19h-8v2h8c1.1,0,2-0.9,2-2V5c0-1.1-0.9-2-2-2h-8v2h8V19z"/>
                            </svg>
                            Login
                        </a>

                        <a href="{{ route('buyer.register') }}" class="user-dropdown-item">
                            <svg viewBox="0 0 24 24">
                                <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V8c0-.55-.45-1-1-1s-1 .45-1 1v2H2c-.55 0-1 .45-1 1s.45 1 1 1h2v2c0 .55.45 1 1 1s1-.45 1-1v-2h2c.55 0 1-.45 1-1s-.45-1-1-1H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                            Register
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Stats Section -->
        <div class="stats-section">
            <!-- Revenue Widget -->
            <div class="stat-widget" data-stat="totalValue">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24">
                        <line x1="12" y1="2" x2="12" y2="22"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Revenue</div>
                    <div class="stat-value" data-weight="900">$0</div>
                    <div class="stat-change positive">Accepted</div>
                </div>
            </div>

            <!-- Active Vendors Widget -->
            <div class="stat-widget">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Active Vendors</div>
                    <div class="stat-value" data-weight="300">142</div>
                    <div class="stat-change positive">+5.2%</div>
                </div>
            </div>

            <!-- Savings Widget -->
            <div class="stat-widget">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24">
                        <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Savings</div>
                    <div class="stat-value" data-weight="600">$8,450</div>
                    <div class="stat-change positive">+18.2%</div>
                </div>
            </div>

            <!-- Quotes Widget -->
            <div class="stat-widget" data-stat="quotesReceived">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <path d="M8 9h8"/>
                        <path d="M8 13h6"/>
                    </svg>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Quotes</div>
                    <div class="stat-value" id="statsQuotesValue" data-weight="200">0</div>
                    <div class="stat-change positive">Active</div>
                </div>
            </div>
        </div>

        <!-- Market Data Section -->
        <div class="market-section">
            <div class="section-header">
                <div class="header-left">
                    <div class="market-status-indicators">
                        <div class="market-indicator" id="produce-indicator">
                            <div class="status-dot closed" id="produce-dot"></div>
                            <span class="market-label">Produce</span>
                            <span class="market-status-text" id="produce-status">CLOSED</span>
                        </div>
                        <div class="market-indicator" id="flowers-indicator">
                            <div class="status-dot closed" id="flowers-dot"></div>
                            <span class="market-label">Flowers</span>
                            <span class="market-status-text" id="flowers-status">CLOSED</span>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <div class="category-dropdown-container">
                        <div class="custom-dropdown" id="customDropdown">
                            <div class="dropdown-selected" onclick="toggleDropdown()">
                                <span id="selectedCategory">Fruits</span>
                                <div class="dropdown-arrow"></div>
                            </div>
                            <div class="dropdown-options" id="dropdownOptions">
                                <div class="dropdown-option" data-value="fruits" onclick="selectCategory('fruits', 'Fruits')">Fruits</div>
                                <div class="dropdown-option" data-value="vegetables" onclick="selectCategory('vegetables', 'Vegetables')">Vegetables</div>
                                <div class="dropdown-option" data-value="herbs" onclick="selectCategory('herbs', 'Herbs')">Herbs</div>
                                <div class="dropdown-option" data-value="flowers" onclick="selectCategory('flowers', 'Flowers')">Flowers</div>
                                <div class="dropdown-option" data-value="dairy" onclick="selectCategory('dairy', 'Dairy & Eggs')">Dairy & Eggs</div>
                            </div>
                        </div>
                    </div>
                    <!-- Search Feature -->
                    <div class="search-container">
                        <div class="search-toggle" id="searchToggle" onclick="toggleSearch()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </div>
                        <!-- Search Overlay -->
                        <div class="search-overlay" id="searchOverlay">
                            <div class="search-input-container">
                                <input type="text" id="searchInput" placeholder="Search products..." onkeyup="handleSearch()" />
                            </div>
                        </div>
                    </div>
                    <!-- Pagination Arrows -->
                    <div class="pagination-container" id="paginationContainer">
                        <button class="pagination-arrow" id="prevPage" onclick="navigatePrevious()">
                            <svg viewBox="0 0 24 24" fill="none">
                                <polyline points="15,18 9,12 15,6"></polyline>
                            </svg>
                        </button>
                        <button class="pagination-arrow" id="nextPage" onclick="navigateNext()">
                            <svg viewBox="0 0 24 24" fill="none">
                                <polyline points="9,18 15,12 9,6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="market-grid" id="marketGrid">
                @forelse($products as $product)
                    <div class="market-product-card" data-product-id="{{ $product['id'] }}">
                        <div class="product-name">{{ $product['name'] }}</div>
                        <div class="product-price" data-weight="{{ $product['price'] * 100 }}">
                            ${{ number_format($product['price'], 2) }}/{{ $product['unit'] }}
                        </div>
                        <div class="product-change positive">+{{ $product['price_change'] }}%</div>
                    </div>
                @empty
                    <!-- Fallback products if database is empty -->
                    <div class="market-product-card">
                        <div class="product-name">Royal Gala Apples</div>
                        <div class="product-price" data-weight="320">$4.20/kg</div>
                        <div class="product-change positive">+3.2%</div>
                    </div>
                    <div class="market-product-card">
                        <div class="product-name">Fuji Apples</div>
                        <div class="product-price" data-weight="480">$5.80/kg</div>
                        <div class="product-change positive">+2.5%</div>
                    </div>
                    <div class="market-product-card">
                        <div class="product-name">Valencia Oranges</div>
                        <div class="product-price" data-weight="250">$3.50/kg</div>
                        <div class="product-change positive">+1.8%</div>
                    </div>
                    <div class="market-product-card">
                        <div class="product-name">Navel Oranges</div>
                        <div class="product-price" data-weight="320">$4.20/kg</div>
                        <div class="product-change positive">+2.1%</div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Communication Hub - Unified Interface (AI + Quotes + Messaging) -->
        @livewire('buyer.hub.communication-hub')
    </div>


    <!-- Messenger Icon - Floating (Outside Order Card) -->
    <button wire:click="$set('showMessenger', true)" class="messaging-icon-btn-floating" title="Messages" style="position: fixed; top: 16px; right: 60px; z-index: 100;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 20px; height: 20px;">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-width="2"/>
        </svg>
        @if(($unreadMessagesCount ?? 0) > 0)
            <span class="message-badge">{{ $unreadMessagesCount }}</span>
        @endif
    </button>


    <!-- OLD QUOTE CARDS - REMOVED (Now in QuotePanel component) -->
    @php
        // This section is now handled by QuotePanel component
        // Lines 264-359 extracted to app/Livewire/Buyer/Quotes/QuotePanel.php
    @endphp
    @if(false)
            <div class="order-card-content" id="quotesContainer">
                @forelse([] as $index => $quote)
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
                @endforelse
            </div>
    @endif
    <!-- END OLD CODE -->


    <!-- Weekly Planner Modal - Neumorphic Inset Style -->
<div id="weeklyPlannerModal" class="planner-modal">
    <div class="planner-container" onclick="event.stopPropagation()">
        <!-- Close Button - Top Right Corner -->
        <button class="planner-close-btn" onclick="closePlannerModal()" title="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M18 6L6 18M6 6l12 12" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>

        <!-- Modal Header with Day Selector Only -->
        <div class="planner-header">
            <!-- Day Selector -->
            <div class="day-selector">
                <button class="day-btn active" data-day="monday" onclick="selectDay('monday')">MON</button>
                <button class="day-btn" data-day="tuesday" onclick="selectDay('tuesday')">TUE</button>
                <button class="day-btn" data-day="wednesday" onclick="selectDay('wednesday')">WED</button>
                <button class="day-btn" data-day="thursday" onclick="selectDay('thursday')">THU</button>
                <button class="day-btn" data-day="friday" onclick="selectDay('friday')">FRI</button>
                <button class="day-btn" data-day="saturday" onclick="selectDay('saturday')">SAT</button>
                <button class="day-btn" data-day="sunday" onclick="selectDay('sunday')">SUN</button>
            </div>
        </div>

        <!-- Products List Area -->
        <div class="planner-content">
            <div id="productsList">
                <!-- Products will be added here -->
            </div>
        </div>

        <!-- Simple Action Footer - Two Icons Only -->
        <div class="planner-footer">
            <button class="planner-action-btn add-btn" onclick="addProduct()" title="Add Product">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M12 5v14M5 12h14" stroke-width="1.5"/>
                </svg>
            </button>
            <button class="planner-action-btn delete-all-btn" onclick="clearAllProducts()" title="Delete All">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2m3 0v14a1 1 0 01-1 1H6a1 1 0 01-1-1V6" stroke-width="1.5"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Echo already initialized in layouts/buyer.blade.php - no duplicate init needed -->

    <script>
        // Sydney Markets Product Data
    const sydneyMarketsProducts = {
        fruits: [
            { name: 'Royal Gala Apples', price: '4.20', change: '+3.2%' },
            { name: 'Fuji Apples', price: '5.80', change: '+2.5%' },
            { name: 'Valencia Oranges', price: '3.50', change: '+1.8%' },
            { name: 'Navel Oranges', price: '4.20', change: '+2.1%' },
            { name: 'Imperial Mandarins', price: '4.80', change: '+1.5%' },
            { name: 'Cavendish Bananas', price: '3.20', change: '+0.9%' },
            { name: 'Ladyfinger Bananas', price: '4.80', change: '+1.2%' },
            { name: 'Strawberries', price: '8.50', change: '+5.1%' },
            { name: 'Blueberries', price: '12.90', change: '+4.2%' },
            { name: 'Raspberries', price: '14.50', change: '+3.8%' },
            { name: 'Kiwifruit', price: '7.50', change: '+2.7%' },
            { name: 'Crimson Grapes', price: '6.50', change: '+3.5%' },
            { name: 'Thompson Grapes', price: '5.40', change: '+2.8%' },
            { name: 'Watermelon', price: '2.90', change: '+1.1%' },
            { name: 'Rockmelon', price: '3.80', change: '+1.6%' },
            { name: 'Honeydew Melon', price: '4.20', change: '+2.2%' },
            // Page 2
            { name: 'Pink Lady Apples', price: '5.20', change: '+2.8%' },
            { name: 'Granny Smith Apples', price: '3.80', change: '+1.5%' },
            { name: 'Blood Oranges', price: '6.20', change: '+3.1%' },
            { name: 'Tangelos', price: '3.90', change: '+1.9%' },
            { name: 'Ruby Grapefruit', price: '4.50', change: '+2.3%' },
            { name: 'White Grapefruit', price: '3.80', change: '+1.7%' },
            { name: 'Lemons', price: '2.80', change: '+0.8%' },
            { name: 'Limes', price: '3.20', change: '+1.2%' },
            { name: 'Passionfruit', price: '12.50', change: '+4.5%' },
            { name: 'Mangoes', price: '8.90', change: '+3.6%' },
            { name: 'Papaya', price: '6.50', change: '+2.9%' },
            { name: 'Pineapple', price: '4.90', change: '+2.1%' },
            { name: 'Dragon Fruit', price: '18.90', change: '+6.2%' },
            { name: 'Lychees', price: '15.50', change: '+5.3%' },
            { name: 'Cherries', price: '22.80', change: '+7.5%' },
            { name: 'Peaches', price: '6.80', change: '+3.1%' }
        ],
        vegetables: [
            { name: 'Truss Tomatoes', price: '5.20', change: '+3.0%' },
            { name: 'Cherry Tomatoes', price: '8.90', change: '+4.5%' },
            { name: 'Red Capsicum', price: '8.50', change: '+3.6%' },
            { name: 'Green Capsicum', price: '6.20', change: '+2.4%' },
            { name: 'Iceberg Lettuce', price: '2.20', change: '+1.8%' },
            { name: 'Cos Lettuce', price: '2.80', change: '+1.5%' },
            { name: 'Baby Spinach', price: '12.80', change: '+3.9%' },
            { name: 'Broccoli', price: '3.70', change: '+2.1%' },
            { name: 'Cauliflower', price: '4.50', change: '+2.5%' },
            { name: 'Carrots', price: '1.90', change: '-0.5%' },
            { name: 'Potatoes', price: '1.60', change: '-1.2%' },
            { name: 'Sweet Potatoes', price: '3.40', change: '+1.8%' },
            { name: 'Brown Onions', price: '1.40', change: '+0.3%' },
            { name: 'Red Onions', price: '2.20', change: '+0.8%' },
            { name: 'Mushrooms', price: '6.40', change: '+3.8%' },
            { name: 'Corn', price: '2.50', change: '+0.6%' },
            // Page 2
            { name: 'Yellow Capsicum', price: '7.80', change: '+3.2%' },
            { name: 'Zucchini', price: '3.50', change: '+1.6%' },
            { name: 'Eggplant', price: '4.20', change: '+2.1%' },
            { name: 'Pumpkin', price: '2.80', change: '+1.3%' },
            { name: 'Brussels Sprouts', price: '6.90', change: '+2.8%' },
            { name: 'Asparagus', price: '8.50', change: '+3.5%' },
            { name: 'Green Beans', price: '5.20', change: '+2.4%' },
            { name: 'Snow Peas', price: '7.80', change: '+3.1%' },
            { name: 'Celery', price: '3.20', change: '+1.5%' },
            { name: 'Leeks', price: '4.50', change: '+2.2%' },
            { name: 'Cabbage', price: '2.80', change: '+1.2%' },
            { name: 'Bok Choy', price: '3.60', change: '+1.7%' },
            { name: 'Kale', price: '5.80', change: '+2.6%' },
            { name: 'Silverbeet', price: '3.20', change: '+1.4%' }
        ],
        herbs: [
            { name: 'Fresh Basil', price: '12.50', change: '+2.8%' },
            { name: 'Fresh Parsley', price: '3.20', change: '+1.5%' },
            { name: 'Fresh Mint', price: '4.80', change: '+2.0%' },
            { name: 'Fresh Coriander', price: '3.60', change: '+1.0%' },
            { name: 'Fresh Dill', price: '5.20', change: '+2.5%' },
            { name: 'Fresh Thyme', price: '8.90', change: '+3.2%' },
            { name: 'Fresh Rosemary', price: '6.50', change: '+2.6%' },
            { name: 'Fresh Oregano', price: '7.20', change: '+2.9%' },
            { name: 'Fresh Sage', price: '9.80', change: '+3.5%' },
            { name: 'Fresh Chives', price: '4.20', change: '+1.8%' },
            { name: 'Bay Leaves', price: '15.60', change: '+4.1%' },
            { name: 'Lemongrass', price: '6.80', change: '+2.3%' },
            { name: 'Kaffir Lime', price: '18.90', change: '+5.2%' },
            { name: 'Fresh Tarragon', price: '14.50', change: '+4.8%' },
            { name: 'Fresh Marjoram', price: '11.20', change: '+3.6%' },
            { name: 'Fresh Fennel', price: '8.40', change: '+3.0%' }
        ],
        flowers: [
            { name: 'Red Roses', price: '24.50', change: '+5.2%' },
            { name: 'White Roses', price: '22.80', change: '+4.8%' },
            { name: 'Pink Roses', price: '23.60', change: '+4.9%' },
            { name: 'Lisianthus', price: '25.90', change: '+5.5%' },
            { name: 'Dahlias', price: '28.60', change: '+6.2%' },
            { name: 'Carnations', price: '12.20', change: '+2.5%' },
            { name: 'Oriental Lilies', price: '32.50', change: '+6.8%' },
            { name: 'Gerberas', price: '18.90', change: '+3.9%' },
            { name: 'Chrysanthemums', price: '15.60', change: '+3.2%' },
            { name: 'Tulips', price: '19.80', change: '+4.2%' },
            { name: 'Sunflowers', price: '16.50', change: '+3.5%' },
            { name: 'Peonies', price: '42.90', change: '+8.5%' },
            { name: 'Hydrangeas', price: '35.60', change: '+7.2%' },
            { name: 'Orchids', price: '28.90', change: '+6.1%' },
            { name: 'Proteas', price: '31.20', change: '+6.5%' },
            { name: 'Native Mix', price: '26.80', change: '+5.6%' }
        ],
        dairy: [
            { name: 'Full Cream Milk', price: '4.50', change: '+1.2%' },
            { name: 'Skim Milk', price: '4.20', change: '+1.0%' },
            { name: 'Greek Yogurt', price: '8.90', change: '+2.5%' },
            { name: 'Natural Yogurt', price: '6.50', change: '+1.8%' },
            { name: 'Cheddar Cheese', price: '12.80', change: '+3.2%' },
            { name: 'Mozzarella', price: '11.50', change: '+2.8%' },
            { name: 'Feta Cheese', price: '14.90', change: '+3.5%' },
            { name: 'Farm Eggs (Doz)', price: '7.80', change: '+2.2%' },
            { name: 'Free Range Eggs', price: '9.50', change: '+2.6%' },
            { name: 'Organic Eggs', price: '12.90', change: '+3.4%' },
            { name: 'Butter', price: '6.50', change: '+1.5%' },
            { name: 'Cream', price: '5.80', change: '+1.3%' },
            { name: 'Sour Cream', price: '4.90', change: '+1.1%' },
            { name: 'Cottage Cheese', price: '8.20', change: '+2.1%' },
            { name: 'Ricotta', price: '9.60', change: '+2.4%' },
            { name: 'Buttermilk', price: '3.80', change: '+0.8%' }
        ]
    };

    // Dropdown functionality
    // CRITICAL: Attach to window object for onclick handlers to work
    window.toggleDropdown = function() {
        const dropdown = document.getElementById('customDropdown');
        const options = document.getElementById('dropdownOptions');

        dropdown.classList.toggle('active');
        options.classList.toggle('show');

        // Close dropdown when clicking outside (but not inside planner modal)
        document.addEventListener('click', function closeDropdown(e) {
            // Don't close if clicking inside planner modal
            const plannerModal = document.getElementById('weeklyPlannerModal');
            const isInsidePlanner = plannerModal && plannerModal.contains(e.target);

            if (!dropdown.contains(e.target) && !isInsidePlanner) {
                dropdown.classList.remove('active');
                options.classList.remove('show');
                document.removeEventListener('click', closeDropdown);
            }
        });
    }

    // CRITICAL: Attach to window object for onclick handlers to work
    window.selectCategory = function(value, displayText) {
        document.getElementById('selectedCategory').textContent = displayText;
        document.getElementById('customDropdown').classList.remove('active');
        document.getElementById('dropdownOptions').classList.remove('show');

        // Update current category
        currentCategory = value;

        // Clear search when changing category
        document.getElementById('searchInput').value = '';
        document.getElementById('searchOverlay').classList.remove('active');

        // Filter products by category
        filterProductsByCategory(value);
    }

    function filterProductsByCategory(category) {
        const products = sydneyMarketsProducts[category] || sydneyMarketsProducts.fruits;

        // Store products and update pagination
        originalProducts = products;
        currentProducts = products;
        currentCategory = category;
        currentPage = 1;
        totalPages = Math.ceil(products.length / productsPerPage);

        // Update display
        updateProductGrid();
        updatePaginationControls();
    }

    // Track current category and original products
    let currentCategory = 'fruits';
    let originalProducts = [];

    // Pagination variables
    let currentPage = 1;
    let totalPages = 1;
    let currentProducts = [];
    const productsPerPage = 16; // 4x4 grid

    function updateProductGrid() {
        const productGrid = document.getElementById('marketGrid');
        if (!productGrid) return;

        // Clear existing products
        productGrid.innerHTML = '';

        // Get current page products
        const startIndex = (currentPage - 1) * productsPerPage;
        const endIndex = startIndex + productsPerPage;
        const productsToShow = currentProducts.slice(startIndex, endIndex);

        // console.log(`Showing products ${startIndex} to ${endIndex} of ${currentProducts.length}`); // Debug - disabled

        // Add products to grid
        productsToShow.forEach(product => {
            // Calculate weight based on price
            const price = parseFloat(product.price);
            const weight = 200 + Math.min(700, Math.floor((price - 1) * 100));

            const changeClass = product.change.startsWith('+') ? 'positive' : '';

            const productCard = document.createElement('div');
            productCard.className = 'market-product-card';
            productCard.innerHTML = `
                <div class="product-name">${product.name}</div>
                <div class="product-price" data-weight="${weight}">$${product.price}/kg</div>
                <div class="product-change ${changeClass}">${product.change}</div>
            `;
            productGrid.appendChild(productCard);
        });
    }

    // Products are now pre-rendered in Blade PHP to avoid JavaScript issues
    // Initialize the JavaScript arrays with the fruits data so search/pagination work
    (function() {
        // Set up the initial state to match pre-rendered fruits
        if (sydneyMarketsProducts && sydneyMarketsProducts.fruits) {
            originalProducts = sydneyMarketsProducts.fruits;
            currentProducts = sydneyMarketsProducts.fruits;
            currentCategory = 'fruits';
            totalPages = Math.ceil(currentProducts.length / productsPerPage);
            console.log('Products pre-rendered, JavaScript state synchronized with fruits category');
        }
    })();

    // Sydney Markets Trading Hours Checker
    function updateMarketStatus() {
        // Get current Sydney time (UTC+10 or UTC+11 during daylight saving)
        const now = new Date();
        const sydneyTime = new Date(now.toLocaleString("en-US", {timeZone: "Australia/Sydney"}));

        const hours = sydneyTime.getHours();
        const minutes = sydneyTime.getMinutes();
        const day = sydneyTime.getDay(); // 0 = Sunday, 6 = Saturday
        const currentTime = hours + minutes / 60; // Convert to decimal hours

        // Market trading hours
        // Produce Market: Monday-Saturday 3:00 AM - 9:00 AM
        // Flowers Market: Monday-Saturday 5:00 AM - 10:00 AM
        // Closed on Sundays

        const isWeekday = day >= 1 && day <= 6; // Monday to Saturday

        // Produce Market Status
        const produceOpen = isWeekday && currentTime >= 3 && currentTime < 9;
        const produceDot = document.getElementById('produce-dot');
        const produceStatus = document.getElementById('produce-status');
        const produceIndicator = document.getElementById('produce-indicator');

        if (produceOpen) {
            produceDot.classList.remove('closed');
            produceDot.classList.add('open');
            produceStatus.textContent = 'OPEN';
            produceIndicator.classList.add('open');
        } else {
            produceDot.classList.remove('open');
            produceDot.classList.add('closed');
            produceStatus.textContent = 'CLOSED';
            produceIndicator.classList.remove('open');
        }

        // Flowers Market Status
        const flowersOpen = isWeekday && currentTime >= 5 && currentTime < 10;
        const flowersDot = document.getElementById('flowers-dot');
        const flowersStatus = document.getElementById('flowers-status');
        const flowersIndicator = document.getElementById('flowers-indicator');

        if (flowersOpen) {
            flowersDot.classList.remove('closed');
            flowersDot.classList.add('open');
            flowersStatus.textContent = 'OPEN';
            flowersIndicator.classList.add('open');
        } else {
            flowersDot.classList.remove('open');
            flowersDot.classList.add('closed');
            flowersStatus.textContent = 'CLOSED';
            flowersIndicator.classList.remove('open');
        }

        // Add next opening time info
        if (!isWeekday && day === 0) { // Sunday
            // Next opening is Monday
            const hoursUntilProduce = ((24 - hours) + 3) + (24 - minutes/60);
            const hoursUntilFlowers = ((24 - hours) + 5) + (24 - minutes/60);
            console.log(`Markets closed. Produce opens in ${Math.floor(hoursUntilProduce)} hours, Flowers in ${Math.floor(hoursUntilFlowers)} hours`);
        } else if (!produceOpen && isWeekday) {
            if (currentTime < 3) {
                // Opens later today
                const hoursUntil = 3 - currentTime;
                console.log(`Produce opens in ${Math.floor(hoursUntil)} hours ${Math.floor((hoursUntil % 1) * 60)} minutes`);
            } else if (currentTime >= 9) {
                // Opens tomorrow
                const hoursUntil = (24 - currentTime) + 3;
                console.log(`Produce opens tomorrow at 3:00 AM (${Math.floor(hoursUntil)} hours)`);
            }
        }
    }

    // Update immediately on load
    updateMarketStatus();

    // Update every minute
    setInterval(updateMarketStatus, 60000);

    // Also update when page becomes visible (user returns to tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateMarketStatus();
        }
    });

    // Search functionality
    // CRITICAL: Attach to window object for onclick handlers to work
    window.toggleSearch = function() {
        const overlay = document.getElementById('searchOverlay');
        const input = document.getElementById('searchInput');

        overlay.classList.toggle('active');

        if (overlay.classList.contains('active')) {
            input.focus();

            // Close search when clicking outside (but not inside planner modal)
            setTimeout(() => {
                document.addEventListener('click', function closeSearch(e) {
                    const searchContainer = document.querySelector('.search-container');
                    const paginationContainer = document.getElementById('paginationContainer');
                    const plannerModal = document.getElementById('weeklyPlannerModal');
                    const isInsidePlanner = plannerModal && plannerModal.contains(e.target);

                    // Don't close if clicking on pagination arrows, search container, or inside planner modal
                    if (!searchContainer.contains(e.target) && !paginationContainer.contains(e.target) && !isInsidePlanner) {
                        overlay.classList.remove('active');
                        input.value = '';
                        // Restore original products
                        currentProducts = originalProducts;
                        currentPage = 1;
                        totalPages = Math.ceil(currentProducts.length / productsPerPage);
                        updateProductGrid();
                        updatePaginationControls();
                        document.removeEventListener('click', closeSearch);
                    }
                });
            }, 100);
        }
    }

    function handleSearch() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();

        if (searchTerm.length === 0) {
            // If search is empty, show current category products
            currentProducts = originalProducts;
        } else {
            // Search across all products
            const allProducts = [];
            Object.values(sydneyMarketsProducts).forEach(categoryProducts => {
                allProducts.push(...categoryProducts);
            });

            // Filter products that match the search term
            currentProducts = allProducts.filter(product =>
                product.name.toLowerCase().includes(searchTerm)
            );

            // Sort the filtered results alphabetically by name
            currentProducts.sort((a, b) => a.name.localeCompare(b.name));
        }

        // Reset to first page and update pagination
        currentPage = 1;
        totalPages = Math.ceil(currentProducts.length / productsPerPage);

        console.log(`Search found ${currentProducts.length} products (sorted A-Z), ${totalPages} pages`); // Debug

        // Update display
        updateProductGrid();
        updatePaginationControls();
    }

    // Navigation functions - make them global
    window.navigatePrevious = function() {
        console.log(`Previous: Page ${currentPage} of ${totalPages}`); // Debug
        if (currentPage > 1) {
            currentPage--;
            updateProductGrid();
            updatePaginationControls();
        }
    }

    window.navigateNext = function() {
        console.log(`Next: Page ${currentPage} of ${totalPages}, Products: ${currentProducts.length}`); // Debug
        if (currentPage < totalPages) {
            currentPage++;
            updateProductGrid();
            updatePaginationControls();
        }
    }

    function updatePaginationControls() {
        const prevButton = document.getElementById('prevPage');
        const nextButton = document.getElementById('nextPage');
        const paginationContainer = document.getElementById('paginationContainer');

        // Always show pagination arrows
        paginationContainer.style.display = 'flex';

        // Update arrow states - both disabled if only 1 page
        if (totalPages <= 1) {
            prevButton.classList.add('disabled');
            nextButton.classList.add('disabled');
        } else {
            prevButton.classList.toggle('disabled', currentPage === 1);
            nextButton.classList.toggle('disabled', currentPage === totalPages);
        }
    }

    // Store timer intervals globally to clear them on Livewire updates
    window.quoteTimerIntervals = window.quoteTimerIntervals || {};
    let quoteInterval = null;
    let quoteTimers = {}; // Store individual timers for each quote
    let quotesReceiving = false;

    // activeQuotes array now handled by QuotePanel component
    // See: resources/views/livewire/quotes/buyer-quote-panel.blade.php
    window.activeQuotes = window.activeQuotes || [];

    // DELETED: initializeQuoteTimers() - Now in public/assets/js/buyer/quotes/quote-timers.js

    // DELETED: updateQuoteCounts() - Now in public/assets/js/buyer/quotes/quote-timers.js

    // DELETED: syncActiveQuotes() - Now in public/assets/js/buyer/quotes/quote-timers.js

    // Initialize timers on page load
    document.addEventListener('DOMContentLoaded', () => {
        syncActiveQuotes(); // Ensure activeQuotes is synced on initial load
        initializeQuoteTimers();
    });

    // Re-initialize timers on Livewire updates
    document.addEventListener('livewire:init', function() {
        console.log('🚀 LIVEWIRE INITIALIZED - Setting up event listeners');

        document.addEventListener('livewire:initialized', () => {
        Livewire.on('refreshQuotes', () => {
            console.error('🔥🔥🔥 REFRESH QUOTES EVENT RECEIVED 🔥🔥🔥');
            console.log('🔄 Quote count in DOM before sync:', document.querySelectorAll('.quote-item[data-quote-id]').length);

            // Wait for Livewire DOM updates to complete
            setTimeout(() => {
                console.log('📊 Quote count in DOM after sync:', document.querySelectorAll('.quote-item[data-quote-id]').length);
                syncActiveQuotes();
                initializeQuoteTimers();
                updateQuoteStats(); // FIX: Update stats widget after sync
                console.log('✅ Frontend re-render complete after refreshQuotes');
            }, 300); // Increased timeout to ensure DOM updates
        });

        Livewire.on('quoteReceived', () => {
            console.error('🔥🔥🔥 QUOTE RECEIVED EVENT 🔥🔥🔥');
            console.log('📨 LIVEWIRE EVENT: quoteReceived - Triggering quote-specific updates');

            setTimeout(() => {
                console.log('📊 Processing new quote in DOM');
                syncActiveQuotes();
                initializeQuoteTimers();
                updateQuoteStats(); // Update stats widget
                console.log('✅ Quote-specific updates complete');
            }, 300);
        });
    });

    // CRITICAL: Also listen for Livewire component updates
    document.addEventListener('livewire:updated', function() {
        console.error('⚡ LIVEWIRE COMPONENT UPDATED - DOM has been re-rendered');
        console.log('📊 Current quote count in DOM:', document.querySelectorAll('.quote-item[data-quote-id]').length);

        // IMPORTANT: Re-sync and re-initialize after Livewire updates the DOM
        setTimeout(() => {
            console.log('🔄 Auto-syncing after Livewire update');
            syncActiveQuotes();
            initializeQuoteTimers();
            updateQuoteStats(); // Update stats widget
        }, 100);
    });

    // Listen for quote data updates from backend
    document.addEventListener('livewire:init', function() {
        Livewire.on('quote-data-updated', (event) => {
            console.error('📊 QUOTE DATA UPDATED EVENT:', event);
            console.log('Backend reports:', event.quotes_count, 'total quotes,', event.active_count, 'active');

            // NUCLEAR OPTION: Manually call Livewire's refreshQuotes method to force component re-render
            console.log('🔥 Manually calling Livewire.find() to refresh component');

            // Force Livewire to refresh by calling the refreshQuotes method
            const component = Livewire.find('{{ $_instance->getId() }}');
            if (component) {
                console.log('✅ Found Livewire component, calling refreshQuotes()');
                component.call('refreshQuotes').then(() => {
                    console.log('✅ Livewire refreshQuotes() completed');
                    setTimeout(() => {
                        syncActiveQuotes();
                        initializeQuoteTimers();
                        updateQuoteStats(); // Update stats widget
                    }, 200);
                });
            } else {
                console.error('❌ Could not find Livewire component');
                // Fallback: just sync from current DOM
                setTimeout(() => {
                    syncActiveQuotes();
                    initializeQuoteTimers();
                    updateQuoteStats(); // Update stats widget
                }, 200);
            }
        });
    });

    // Also initialize when page becomes visible
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            initializeQuoteTimers();
        }
    });

    // QUICK SEND - Direct from footer button (MUCH BETTER UX!)
    // CRITICAL: Attach to window object for onclick handlers to work
    window.quickSendToVendors = async function() {
        // Load saved orders
        loadWeeklyOrders();

        // Count total items across all days
        let totalItems = 0;
        Object.values(weeklyOrders).forEach(dayItems => {
            totalItems += dayItems.filter(item => item.name).length;
        });

        if (totalItems === 0) {
            // Quick visual feedback - pulse the planner button
            const plannerBtn = document.querySelector('.planner-button');
            if (plannerBtn) {
                plannerBtn.style.animation = 'shake 0.5s ease';
                setTimeout(() => {
                    plannerBtn.style.animation = '';
                }, 500);
            }

            // Show helpful tooltip
            showQuickTooltip('Add items to planner first!', 'warning');
            return;
        }

        // Collect and send all products
        const allProducts = [];
        Object.keys(weeklyOrders).forEach(day => {
            weeklyOrders[day].forEach(product => {
                if (product.name) {
                    allProducts.push({
                        name: product.name,
                        quantity: product.quantity,
                        unit: product.unit,
                        day: day
                    });
                }
            });
        });

        // Visual feedback on button
        const sendBtn = document.getElementById('mainSendButton');
        if (sendBtn) {
            sendBtn.classList.add('sending');
            const svgElement = sendBtn.querySelector('svg');
            if (svgElement) {
                svgElement.style.animation = 'spinning 1s linear infinite';
            }
        }

        // DON'T clear existing quotes - they should remain visible
        // Users can have multiple RFQs and quotes active at the same time
        // clearAllQuotes(); // REMOVED - This was hiding existing quotes!

        // Group products by name for the API
        const groupedProducts = Object.values(allProducts.reduce((acc, product) => {
            if (!acc[product.name]) {
                acc[product.name] = {
                    name: product.name,
                    totalQuantity: 0,
                    unit: product.unit,
                    days: []
                };
            }
            acc[product.name].totalQuantity += parseFloat(product.quantity) || 0;
            acc[product.name].days.push(product.day);
            return acc;
        }, {}));

        try {
            // Send RFQ to backend API
            const response = await fetch('/buyer/rfq/create-from-planner', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    items: groupedProducts,
                    delivery_date: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    delivery_time: 'Morning',
                    special_instructions: 'Quick order from planner'
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Store the sent RFQ data globally for viewing/editing
                window.currentRFQ = {
                    id: data.rfq_id,
                    reference_number: data.reference_number || 'RFQ-' + Date.now(),
                    items: groupedProducts,
                    delivery_date: data.delivery_date,
                    quotes_received: []
                };

                // Just show success message - no DOM manipulation
                showQuickTooltip('Request sent! Waiting for quotes...', 'success');

                // Trigger Livewire refresh to update any existing quotes
                @this.call('loadDashboardData');

                // Clear the planner after successful send
                clearWeeklyOrders();
                updatePlannerBadge();

                console.log('✅ RFQ sent successfully via quick send!', data);
            } else {
                throw new Error(data.message || 'Failed to send RFQ');
            }

        } catch (error) {
            console.error('❌ Failed to send RFQ:', error);

            // Show error message
            // Show error in toast without touching quotes display
            showQuickTooltip('Failed to send: ' + (error.message || 'Please try again'), 'error');
        } finally {
            // Reset button state
            if (sendBtn) {
                sendBtn.classList.remove('sending');
                const svgElement = sendBtn.querySelector('svg');
                if (svgElement) {
                    svgElement.style.animation = '';
                }
            }
        }
    }

    // Helper to group products by name
    function groupProductsByName(products) {
        const grouped = products.reduce((acc, product) => {
            if (!acc[product.name]) {
                acc[product.name] = {
                    name: product.name,
                    totalQuantity: 0,
                    unit: product.unit,
                    days: []
                };
            }
            acc[product.name].totalQuantity += parseFloat(product.quantity) || 0;
            acc[product.name].days.push(product.day);
            return acc;
        }, {});
        return Object.values(grouped);
    }

    // Save planner and close modal
    function savePlannerAndClose() {
        saveWeeklyOrders();
        updatePlannerBadge();
        closePlannerModal();
        showQuickTooltip('Planner saved!', 'success');
    }

    // Show quick tooltip feedback - DISABLED for silent operation
    function showQuickTooltip(message, type = 'info') {
        // Silent operation - no notifications displayed
        return;
    }

    // View RFQ Modal - Shows current RFQ details with edit capabilities
    function viewRFQModal() {
        if (!window.currentRFQ) {
            console.error('No RFQ data available');
            return;
        }

        const rfq = window.currentRFQ;
        const hasQuotes = rfq.quotes_received && rfq.quotes_received.length > 0;

        // Create modal overlay
        const modalOverlay = document.createElement('div');
        modalOverlay.id = 'rfqViewModal';
        modalOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(8px);
        `;

        // Build items list HTML
        const itemsListHTML = Object.values(rfq.items).map((item, index) => `
            <div class="rfq-item" data-index="${index}" style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px;
                background: #F9FAFB;
                border-radius: 12px;
                margin-bottom: 8px;
                transition: all 0.2s;
                box-shadow: 2px 2px 4px rgba(163, 177, 198, 0.3), -2px -2px 4px rgba(255, 255, 255, 0.7);
            ">
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #1F2937; font-size: 14px;">
                        ${item.product_name}
                    </div>
                    <div style="color: #6B7280; font-size: 12px; margin-top: 2px;">
                        Category: ${item.category || 'General'}
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    ${!hasQuotes ? `
                        <input type="number"
                               id="rfq-qty-${index}"
                               value="${item.totalQuantity || item.quantity}"
                               min="1"
                               style="
                                   width: 80px;
                                   padding: 8px 12px;
                                   border: none;
                                   border-radius: 8px;
                                   background: #E8EBF0;
                                   text-align: center;
                                   font-weight: 600;
                                   color: #1F2937;
                                   box-shadow: inset 2px 2px 4px rgba(163, 177, 198, 0.5), inset -2px -2px 4px rgba(255, 255, 255, 0.7);
                               "
                               onchange="updateRFQItem(${index}, this.value)">
                        <span style="color: #6B7280; font-weight: 500;">${item.unit}</span>
                        <button onclick="removeRFQItem(${index})" style="
                            padding: 6px;
                            background: #EF4444;
                            border: none;
                            border-radius: 8px;
                            color: white;
                            cursor: pointer;
                            transition: all 0.2s;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        " title="Remove item">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    ` : `
                        <span style="color: #10B981; font-weight: 600;">
                            ${item.totalQuantity || item.quantity} ${item.unit}
                        </span>
                    `}
                </div>
            </div>
        `).join('');

        // Build quotes section if available
        const quotesHTML = hasQuotes ? `
            <div style="margin-top: 24px;">
                <div style="font-size: 16px; font-weight: 600; color: #1F2937; margin-bottom: 12px;">
                    Quotes Received (${rfq.quotes_received.length})
                </div>
                <div style="display: grid; gap: 12px;">
                    ${rfq.quotes_received.map((quote, idx) => `
                        <div onclick="editQuoteForVendor(${quote.id}, '${quote.vendor_id}')" style="
                            padding: 16px;
                            background: white;
                            border-radius: 12px;
                            cursor: pointer;
                            transition: all 0.2s;
                            box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.4), -4px -4px 8px rgba(255, 255, 255, 0.8);
                        " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600; color: #1F2937;">${quote.vendor_name}</div>
                                    <div style="color: #6B7280; font-size: 12px; margin-top: 4px;">
                                        Quote #${quote.id} • ${quote.items_count} items
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 20px; font-weight: 700; color: #10B981;">
                                        $${parseFloat(quote.total_amount).toFixed(2)}
                                    </div>
                                    <div style="color: #6B7280; font-size: 11px; margin-top: 2px;">
                                        Click to modify
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        ` : '';

        // Build add item form if no quotes
        const addItemFormHTML = !hasQuotes ? `
            <div style="
                margin-top: 20px;
                padding: 16px;
                background: #F3F4F6;
                border-radius: 12px;
                border: 2px dashed #D1D5DB;
            ">
                <div style="font-size: 14px; font-weight: 600; color: #6B7280; margin-bottom: 12px;">
                    Add New Item
                </div>
                <div style="display: flex; gap: 8px; align-items: flex-end;">
                    <div style="flex: 2;">
                        <input type="text" id="newItemName" placeholder="Product name" style="
                            width: 100%;
                            padding: 10px 14px;
                            border: none;
                            border-radius: 8px;
                            background: white;
                            font-size: 14px;
                            box-shadow: inset 2px 2px 4px rgba(163, 177, 198, 0.3), inset -2px -2px 4px rgba(255, 255, 255, 0.8);
                        ">
                    </div>
                    <div style="flex: 1;">
                        <input type="number" id="newItemQty" placeholder="Qty" min="1" style="
                            width: 100%;
                            padding: 10px 14px;
                            border: none;
                            border-radius: 8px;
                            background: white;
                            font-size: 14px;
                            box-shadow: inset 2px 2px 4px rgba(163, 177, 198, 0.3), inset -2px -2px 4px rgba(255, 255, 255, 0.8);
                        ">
                    </div>
                    <div style="flex: 1;">
                        <select id="newItemUnit" style="
                            width: 100%;
                            padding: 10px 14px;
                            border: none;
                            border-radius: 8px;
                            background: white;
                            font-size: 14px;
                            box-shadow: inset 2px 2px 4px rgba(163, 177, 198, 0.3), inset -2px -2px 4px rgba(255, 255, 255, 0.8);
                        ">
                            <option value="KG">KG</option>
                            <option value="BOX">BOX</option>
                            <option value="BUNCH">BUNCH</option>
                            <option value="PIECE">PIECE</option>
                            <option value="PACKET">PACKET</option>
                        </select>
                    </div>
                    <button onclick="addNewRFQItem()" style="
                        padding: 10px 20px;
                        background: linear-gradient(135deg, #10B981, #059669);
                        color: white;
                        border: none;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                        box-shadow: 3px 3px 6px rgba(163, 177, 198, 0.4), -3px -3px 6px rgba(255, 255, 255, 0.7);
                    " onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                        Add
                    </button>
                </div>
            </div>
        ` : '';

        // Create modal content
        modalOverlay.innerHTML = `
            <div style="
                background: #E8EBF0;
                border-radius: 24px;
                width: ${hasQuotes ? '600px' : '550px'};
                max-width: 90%;
                max-height: 85vh;
                overflow: hidden;
                box-shadow: 20px 20px 60px rgba(163, 177, 198, 0.5), -20px -20px 60px rgba(255, 255, 255, 0.7);
                display: flex;
                flex-direction: column;
            ">
                <!-- Header -->
                <div style="
                    padding: 24px 28px;
                    background: linear-gradient(135deg, #E8EBF0, #DDE1E7);
                    border-bottom: 1px solid rgba(163, 177, 198, 0.2);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <div>
                        <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #1F2937;">
                            RFQ Details
                        </h2>
                        <div style="color: #6B7280; font-size: 13px; margin-top: 4px;">
                            Reference: ${rfq.reference_number}
                        </div>
                    </div>
                    <button onclick="closeRFQModal()" style="
                        width: 36px;
                        height: 36px;
                        border-radius: 12px;
                        border: none;
                        background: #E8EBF0;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.2s;
                        box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                    " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <svg width="20" height="20" fill="#6B7280" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div style="
                    padding: 24px 28px;
                    overflow-y: auto;
                    flex: 1;
                ">
                    <div style="
                        margin-bottom: 20px;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                    ">
                        <div style="font-size: 16px; font-weight: 600; color: #1F2937;">
                            Requested Items
                        </div>
                        ${!hasQuotes ? `
                            <div style="
                                background: linear-gradient(135deg, #FEF3C7, #FDE68A);
                                padding: 6px 12px;
                                border-radius: 20px;
                                font-size: 12px;
                                font-weight: 600;
                                color: #92400E;
                                display: flex;
                                align-items: center;
                                gap: 6px;
                            ">
                                <span style="font-size: 14px;">✏️</span>
                                Editable
                            </div>
                        ` : ''}
                    </div>

                    <div id="rfqItemsList">
                        ${itemsListHTML}
                    </div>

                    ${addItemFormHTML}
                    ${quotesHTML}
                </div>

                <!-- Footer -->
                <div style="
                    padding: 20px 28px;
                    background: linear-gradient(135deg, #E8EBF0, #DDE1E7);
                    border-top: 1px solid rgba(163, 177, 198, 0.2);
                    display: flex;
                    justify-content: ${!hasQuotes ? 'space-between' : 'flex-end'};
                    align-items: center;
                ">
                    ${!hasQuotes ? `
                        <div style="color: #6B7280; font-size: 13px;">
                            <span style="font-size: 16px;">📡</span>
                            Changes will broadcast to all vendors
                        </div>
                        <button onclick="updateAndBroadcastRFQ()" style="
                            padding: 12px 24px;
                            background: linear-gradient(135deg, #10B981, #059669);
                            color: white;
                            border: none;
                            border-radius: 12px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.2s;
                            box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                        " onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                            Update RFQ
                        </button>
                    ` : `
                        <button onclick="closeRFQModal()" style="
                            padding: 12px 24px;
                            background: #6B7280;
                            color: white;
                            border: none;
                            border-radius: 12px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.2s;
                            box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                        ">
                            Close
                        </button>
                    `}
                </div>
            </div>
        `;

        document.body.appendChild(modalOverlay);

        // Add animation
        setTimeout(() => {
            modalOverlay.style.opacity = '1';
        }, 10);
    }

    // Close RFQ Modal
    // CRITICAL: Attach to window object for onclick handlers to work
    window.closeRFQModal = function() {
        const modal = document.getElementById('rfqViewModal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
            }, 300);
        }
    }

    // Update RFQ item quantity
    function updateRFQItem(index, newQuantity) {
        if (!window.currentRFQ) return;

        const items = Object.values(window.currentRFQ.items);
        if (items[index]) {
            items[index].totalQuantity = parseFloat(newQuantity) || 1;
            items[index].quantity = parseFloat(newQuantity) || 1;
            console.log(`Updated item ${items[index].product_name} to ${newQuantity} ${items[index].unit}`);
        }
    }

    // Remove RFQ item
    // CRITICAL: Attach to window object for onclick handlers to work
    window.removeRFQItem = function(index) {
        if (!window.currentRFQ) return;

        const items = Object.values(window.currentRFQ.items);
        if (items[index]) {
            const removedItem = items.splice(index, 1)[0];
            // Convert back to object format
            window.currentRFQ.items = {};
            items.forEach((item, idx) => {
                window.currentRFQ.items[idx] = item;
            });
            console.log(`Removed item: ${removedItem.product_name}`);

            // Refresh the modal
            closeRFQModal();
            setTimeout(() => viewRFQModal(), 100);
        }
    }

    // Add new RFQ item
    // CRITICAL: Attach to window object for onclick handlers to work
    window.addNewRFQItem = function() {
        const nameInput = document.getElementById('newItemName');
        const qtyInput = document.getElementById('newItemQty');
        const unitSelect = document.getElementById('newItemUnit');

        if (!nameInput || !qtyInput || !unitSelect) return;

        const name = nameInput.value.trim();
        const qty = parseFloat(qtyInput.value) || 0;
        const unit = unitSelect.value;

        if (!name || qty <= 0) {
            alert('Please enter a valid product name and quantity');
            return;
        }

        // Add to current RFQ
        const itemsArray = Object.values(window.currentRFQ.items);
        const newItem = {
            product_name: name,
            totalQuantity: qty,
            quantity: qty,
            unit: unit,
            category: 'General',
            product_id: null
        };

        itemsArray.push(newItem);

        // Convert back to object format
        window.currentRFQ.items = {};
        itemsArray.forEach((item, idx) => {
            window.currentRFQ.items[idx] = item;
        });

        console.log(`Added new item: ${name} - ${qty} ${unit}`);

        // Clear inputs
        nameInput.value = '';
        qtyInput.value = '';
        unitSelect.value = 'KG';

        // Refresh the modal
        closeRFQModal();
        setTimeout(() => viewRFQModal(), 100);
    }

    // Update and broadcast RFQ to all vendors
    // CRITICAL: Attach to window object for onclick handlers to work
    window.updateAndBroadcastRFQ = async function() {
        if (!window.currentRFQ) return;

        try {
            const response = await fetch('/api/rfq/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    rfq_id: window.currentRFQ.id,
                    items: Object.values(window.currentRFQ.items),
                    broadcast_to_all: true
                })
            });

            const data = await response.json();

            if (data.success) {
                console.log('✅ RFQ updated and broadcasted to all vendors');
                closeRFQModal();

                // Show success feedback
                const noQuotesMsg = document.getElementById('noQuotesMessage');
                if (noQuotesMsg) {
                    noQuotesMsg.innerHTML += `
                        <div style="
                            margin-top: 12px;
                            padding: 10px;
                            background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
                            border-radius: 8px;
                            color: #065F46;
                            font-size: 13px;
                            font-weight: 600;
                            display: flex;
                            align-items: center;
                            gap: 8px;
                        ">
                            <span style="font-size: 16px;">✅</span>
                            RFQ updated and sent to all vendors
                        </div>
                    `;

                    // Remove success message after 3 seconds
                    setTimeout(() => {
                        const successMsg = noQuotesMsg.querySelector('div[style*="D1FAE5"]');
                        if (successMsg) successMsg.remove();
                    }, 3000);
                }
            } else {
                console.error('Failed to update RFQ:', data.message);
                alert('Failed to update RFQ. Please try again.');
            }
        } catch (error) {
            console.error('Error updating RFQ:', error);
            alert('Error updating RFQ. Please check your connection.');
        }
    }

    // Edit quote for specific vendor
    // CRITICAL: Attach to window object for onclick handlers to work
    window.editQuoteForVendor = function(quoteId, vendorId) {
        console.log(`Opening edit modal for quote ${quoteId} from vendor ${vendorId}`);

        // Close current modal
        closeRFQModal();

        // Store vendor context for targeted update
        window.currentQuoteEdit = {
            quoteId: quoteId,
            vendorId: vendorId,
            originalRFQ: JSON.parse(JSON.stringify(window.currentRFQ)) // Deep copy
        };

        // Open a modified version of the RFQ modal for vendor-specific editing
        setTimeout(() => {
            viewVendorQuoteEditModal(quoteId, vendorId);
        }, 300);
    }

    // View vendor-specific quote edit modal
    function viewVendorQuoteEditModal(quoteId, vendorId) {
        // Find the specific quote
        const quote = window.currentRFQ.quotes_received.find(q => q.id === quoteId);
        if (!quote) {
            console.error('Quote not found');
            return;
        }

        // Similar modal structure but with vendor-specific update button
        const modalOverlay = document.createElement('div');
        modalOverlay.id = 'vendorQuoteEditModal';
        modalOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(8px);
        `;

        // This modal will be similar to viewRFQModal but with vendor-specific context
        modalOverlay.innerHTML = `
            <div style="
                background: #E8EBF0;
                border-radius: 24px;
                width: 550px;
                max-width: 90%;
                max-height: 85vh;
                overflow: hidden;
                box-shadow: 20px 20px 60px rgba(163, 177, 198, 0.5), -20px -20px 60px rgba(255, 255, 255, 0.7);
                display: flex;
                flex-direction: column;
            ">
                <!-- Header -->
                <div style="
                    padding: 24px 28px;
                    background: linear-gradient(135deg, #E8EBF0, #DDE1E7);
                    border-bottom: 1px solid rgba(163, 177, 198, 0.2);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <div>
                        <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #1F2937;">
                            Edit Quote Request
                        </h2>
                        <div style="color: #6B7280; font-size: 13px; margin-top: 4px;">
                            For: ${quote.vendor_name}
                        </div>
                    </div>
                    <button onclick="closeVendorEditModal()" style="
                        width: 36px;
                        height: 36px;
                        border-radius: 12px;
                        border: none;
                        background: #E8EBF0;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.2s;
                        box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                    ">
                        <svg width="20" height="20" fill="#6B7280" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div style="
                    padding: 24px 28px;
                    overflow-y: auto;
                    flex: 1;
                ">
                    <div style="
                        background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
                        padding: 12px 16px;
                        border-radius: 12px;
                        margin-bottom: 20px;
                        color: #1E40AF;
                        font-size: 13px;
                    ">
                        💡 Add or modify items to request an updated quote from ${quote.vendor_name}
                    </div>

                    <div style="font-size: 16px; font-weight: 600; color: #1F2937; margin-bottom: 12px;">
                        Current Quote Items
                    </div>

                    <!-- Show current quote items here -->
                    <div>
                        ${Object.values(window.currentRFQ.items).map((item, idx) => `
                            <div style="
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                padding: 12px 16px;
                                background: #F9FAFB;
                                border-radius: 12px;
                                margin-bottom: 8px;
                            ">
                                <div>
                                    <div style="font-weight: 600; color: #1F2937;">
                                        ${item.product_name}
                                    </div>
                                </div>
                                <div style="color: #10B981; font-weight: 600;">
                                    ${item.totalQuantity || item.quantity} ${item.unit}
                                </div>
                            </div>
                        `).join('')}
                    </div>

                    <!-- Add new item form -->
                    <div style="
                        margin-top: 20px;
                        padding: 16px;
                        background: #F3F4F6;
                        border-radius: 12px;
                        border: 2px dashed #D1D5DB;
                    ">
                        <div style="font-size: 14px; font-weight: 600; color: #6B7280; margin-bottom: 12px;">
                            Request Additional Item
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="vendorNewItemName" placeholder="Product name" style="
                                flex: 2;
                                padding: 10px 14px;
                                border: none;
                                border-radius: 8px;
                                background: white;
                                font-size: 14px;
                            ">
                            <input type="number" id="vendorNewItemQty" placeholder="Qty" min="1" style="
                                width: 80px;
                                padding: 10px 14px;
                                border: none;
                                border-radius: 8px;
                                background: white;
                                font-size: 14px;
                            ">
                            <select id="vendorNewItemUnit" style="
                                width: 100px;
                                padding: 10px 14px;
                                border: none;
                                border-radius: 8px;
                                background: white;
                                font-size: 14px;
                            ">
                                <option value="KG">KG</option>
                                <option value="BOX">BOX</option>
                                <option value="BUNCH">BUNCH</option>
                                <option value="PIECE">PIECE</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div style="
                    padding: 20px 28px;
                    background: linear-gradient(135deg, #E8EBF0, #DDE1E7);
                    border-top: 1px solid rgba(163, 177, 198, 0.2);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                ">
                    <div style="color: #6B7280; font-size: 13px;">
                        <span style="font-size: 16px;">📨</span>
                        Update will be sent only to ${quote.vendor_name}
                    </div>
                    <button onclick="sendUpdateToVendor('${vendorId}')" style="
                        padding: 12px 24px;
                        background: linear-gradient(135deg, #3B82F6, #2563EB);
                        color: white;
                        border: none;
                        border-radius: 12px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.2s;
                        box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                    ">
                        Send Update to Vendor
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modalOverlay);
        setTimeout(() => modalOverlay.style.opacity = '1', 10);
    }

    // Close vendor edit modal
    // CRITICAL: Attach to window object for onclick handlers to work
    window.closeVendorEditModal = function() {
        const modal = document.getElementById('vendorQuoteEditModal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => modal.remove(), 300);
        }
    }

    // Send update to specific vendor
    // CRITICAL: Attach to window object for onclick handlers to work
    window.sendUpdateToVendor = async function(vendorId) {
        const nameInput = document.getElementById('vendorNewItemName');
        const qtyInput = document.getElementById('vendorNewItemQty');
        const unitSelect = document.getElementById('vendorNewItemUnit');

        // Prepare update data
        const updateData = {
            rfq_id: window.currentRFQ.id,
            vendor_id: vendorId,
            items: Object.values(window.currentRFQ.items)
        };

        // Add new item if provided
        if (nameInput && nameInput.value.trim()) {
            updateData.items.push({
                product_name: nameInput.value.trim(),
                quantity: parseFloat(qtyInput.value) || 1,
                unit: unitSelect.value,
                category: 'General'
            });
        }

        try {
            const response = await fetch('/api/rfq/update-vendor', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(updateData)
            });

            const data = await response.json();

            if (data.success) {
                console.log(`✅ Update sent to vendor ${vendorId}`);
                closeVendorEditModal();

                // Show success feedback
                const noQuotesMsg = document.getElementById('noQuotesMessage');
                if (noQuotesMsg) {
                    noQuotesMsg.innerHTML += `
                        <div style="
                            margin-top: 12px;
                            padding: 10px;
                            background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
                            border-radius: 8px;
                            color: #1E40AF;
                            font-size: 13px;
                            font-weight: 600;
                        ">
                            ✅ Update sent to vendor
                        </div>
                    `;

                    setTimeout(() => {
                        const msg = noQuotesMsg.querySelector('div[style*="DBEAFE"]');
                        if (msg) msg.remove();
                    }, 3000);
                }
            } else {
                alert('Failed to send update to vendor');
            }
        } catch (error) {
            console.error('Error sending update:', error);
            alert('Error sending update. Please try again.');
        }
    }

    // Update planner badge to show item count
    function updatePlannerBadge() {
        let totalItems = 0;
        Object.values(weeklyOrders).forEach(dayItems => {
            totalItems += dayItems.filter(item => item.name).length;
        });

        const badge = document.getElementById('plannerItemCount');
        const sendBadge = document.getElementById('sendBadge');

        if (totalItems > 0) {
            badge.textContent = totalItems;
            badge.style.display = 'inline-block';
            sendBadge.textContent = totalItems;
            sendBadge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
            sendBadge.style.display = 'none';
        }
    }

    // Send Weekly Planner to Vendors - Automatic Backend Process
    async function sendWeeklyPlannerToVendors() {
        const allProducts = [];
        const days = Object.keys(weeklyOrders);

        // Collect all products from weekly planner
        days.forEach(day => {
            weeklyOrders[day].forEach(product => {
                if (product.name) {
                    allProducts.push({
                        name: product.name,
                        quantity: product.quantity,
                        unit: product.unit,
                        day: day
                    });
                }
            });
        });

        if (allProducts.length === 0) {
            // Subtle feedback without blocking
            flashPlannerButton('No products to send', 'warning');
            return;
        }

        // Group products by name
        const groupedProducts = Object.values(allProducts.reduce((acc, product) => {
            if (!acc[product.name]) {
                acc[product.name] = {
                    name: product.name,
                    totalQuantity: 0,
                    unit: product.unit,
                    days: []
                };
            }
            acc[product.name].totalQuantity += parseFloat(product.quantity) || 0;
            acc[product.name].days.push(product.day);
            return acc;
        }, {}));

        // Show loading state
        const sendButton = document.querySelector('.send-planner-btn');
        const originalButtonText = sendButton ? sendButton.innerHTML : '';
        if (sendButton) {
            sendButton.disabled = true;
            sendButton.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Sending...';
        }

        try {
            // Send RFQ to backend API
            const response = await fetch('/buyer/rfq/create-from-planner', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    items: groupedProducts,
                    delivery_date: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 2 days from now
                    delivery_time: 'Morning',
                    special_instructions: 'Please provide best prices for weekly order'
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Success! DON'T clear existing quotes - keep them visible
                // clearAllQuotes(); // REMOVED - Multiple RFQs can be active

                // Store the sent RFQ data globally for viewing/editing
                window.currentRFQ = {
                    id: data.rfq_id,
                    reference_number: data.reference_number || 'RFQ-' + Date.now(),
                    items: groupedProducts,
                    delivery_date: data.delivery_date,
                    quotes_received: []
                };

                // Just trigger a Livewire refresh to load any existing quotes
                @this.call('loadDashboardData');
                // No RFQ status display - just wait for real-time quotes

                console.log('✅ RFQ sent successfully!', data);

                // Clear the planner after successful send
                clearWeeklyOrders();
                updatePlannerBadge();
                closePlannerModal();

            } else {
                // Error from backend
                throw new Error(data.message || 'Failed to send RFQ');
            }

        } catch (error) {
            console.error('❌ Failed to send RFQ:', error);

            // Show error message
            // Show error in toast without touching quotes display
            showQuickTooltip('Failed to send RFQ: ' + (error.message || 'Please check your connection'), 'error');

            // Flash error on button
            flashPlannerButton('Failed to send', 'error');
        } finally {
            // Restore button state
            if (sendButton) {
                sendButton.disabled = false;
                sendButton.innerHTML = originalButtonText;
            }
        }

        // NO MOCK QUOTES - Wait for real vendor quotes via WebSocket
        console.log('Waiting for real vendor quotes via WebSocket...');
    }

    // Start receiving quotes from vendors
    // REMOVED: Mock quote generation - Now using real WebSocket quotes only
    // Quotes will be received from real vendors via WebSocket events
    // See the WebSocket listener at line ~2080 for real-time quote reception

    // DELETED: addQuoteToUI() - Quote rendering now handled by BuyerQuotePanel component

    // startQuoteTimer function removed - using initializeQuoteTimers() pattern from vendor dashboard


    // Remove quote from UI and array
    function removeQuote(quoteId) {
        // Clear the timer for this quote
        if (window.quoteTimerIntervals && window.quoteTimerIntervals[quoteId]) {
            clearInterval(window.quoteTimerIntervals[quoteId]);
            delete window.quoteTimerIntervals[quoteId];
        }

        // Remove from array
        activeQuotes = activeQuotes.filter(q => q.id !== quoteId);

        // Remove from UI with animation
        const element = document.getElementById(`quote-${quoteId}`);
        if (element) {
            element.classList.add('quote-item-removing');
            setTimeout(() => {
                element.remove();
                updateQuoteStats();

                // Show no quotes message if empty
                if (activeQuotes.length === 0) {
                    document.getElementById('noQuotesMessage').style.display = 'block';
                }
            }, 300);
        }
    }

    // Initialize ultra-minimal modal - no wasted space
    function initializeQuoteModal() {
        // Remove any existing modal first
        const existingModal = document.getElementById('quoteDetailsModal');
        if (existingModal) {
            existingModal.remove();
        }

        // Revolutionary ultra-minimal floating card modal - NO header, NO scroll
        const modalHTML = `
            <div id="quoteDetailsModal" class="quote-modal-overlay" style="
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(232, 235, 240, 0.95);
                backdrop-filter: blur(10px);
                z-index: 999999;
                opacity: 0;
                transition: opacity 0.15s ease;
            ">
                <!-- Floating close button -->
                <button onclick="closeQuoteModal()" style="
                    position: absolute;
                    top: 20px;
                    right: 20px;
                    width: 36px;
                    height: 36px;
                    background: #E8EBF0;
                    border: none;
                    border-radius: 50%;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow:
                        4px 4px 8px rgba(163, 177, 198, 0.5),
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

                <!-- Ultra-compact floating card -->
                <div class="quote-modal-container" style="
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    width: 420px;
                    max-height: 80vh;
                    background: #E8EBF0;
                    border-radius: 24px;
                    box-shadow:
                        20px 20px 40px rgba(163, 177, 198, 0.5),
                        -20px -20px 40px rgba(255, 255, 255, 0.7);
                    display: flex;
                    flex-direction: column;
                ">

                    <!-- Compact content area - NO SCROLL -->
                    <div class="quote-modal-body" id="quoteDetailsContent" style="
                        padding: 24px;
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        gap: 16px;
                    ">
                        <!-- Content will be injected here -->
                    </div>

                    <!-- Floating action buttons -->
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
                            box-shadow:
                                4px 4px 8px rgba(16, 185, 129, 0.3),
                                -4px -4px 8px rgba(255, 255, 255, 0.7);
                            transition: all 0.15s ease;
                        "
                        onmouseover="this.style.background='linear-gradient(135deg, #059669, #047857)'; this.style.boxShadow='6px 6px 12px rgba(16, 185, 129, 0.4), -6px -6px 12px rgba(255, 255, 255, 0.8)'"
                        onmouseout="this.style.background='linear-gradient(135deg, #10B981, #059669)'; this.style.boxShadow='4px 4px 8px rgba(16, 185, 129, 0.3), -4px -4px 8px rgba(255, 255, 255, 0.7)'">
                            Accept Quote
                        </button>
                        <button onclick="startChatWithVendor()" style="
                            flex: 1;
                            padding: 10px;
                            background: #E8EBF0;
                            border: none;
                            border-radius: 12px;
                            font-size: 13px;
                            font-weight: 600;
                            color: #374151;
                            cursor: pointer;
                            box-shadow:
                                inset 2px 2px 5px rgba(163, 177, 198, 0.5),
                                inset -2px -2px 5px rgba(255, 255, 255, 0.7);
                            transition: all 0.15s ease;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 6px;
                        "
                        onmouseover="this.style.boxShadow='inset 3px 3px 6px rgba(163, 177, 198, 0.6), inset -3px -3px 6px rgba(255, 255, 255, 0.8)'"
                        onmouseout="this.style.boxShadow='inset 2px 2px 5px rgba(163, 177, 198, 0.5), inset -2px -2px 5px rgba(255, 255, 255, 0.7)'">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            Message Vendor
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Add overlay click handler
        const modal = document.getElementById('quoteDetailsModal');
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeQuoteModal();
            }
        });

        // Timer will be started when modal content is actually created
        // Don't start it here as the modal content doesn't exist yet
    }

    // View quote details in modal
    window.viewQuoteDetails = function(quoteId, event) {
        console.error('=== VIEW QUOTE CLICKED ===');
        console.error('🎯 Quote ID:', quoteId);
        console.error('📊 ActiveQuotes array:', window.activeQuotes);
        console.error('📊 ActiveQuotes length:', window.activeQuotes ? window.activeQuotes.length : 'NULL');
        console.error('📊 ActiveQuotes IDs:', window.activeQuotes ? window.activeQuotes.map(q => q.id) : 'NULL');

        // Prevent event bubbling
        if (event) {
            event.stopPropagation();
        }

        // Get the actual quote from activeQuotes array - this has all the correct data
        let quote = window.activeQuotes ? window.activeQuotes.find(q => q.id == quoteId) : null;

        console.error('✅ Found quote in activeQuotes:', quote);

        if (!quote) {
            console.error('❌ Quote not found in activeQuotes, trying DOM data-quote-json...');

            // PRIORITY 1: Try to get from data-quote-json attribute
            const quoteElement = document.querySelector(`[data-quote-id="${quoteId}"]`);
            console.error('🔍 Found quote element:', quoteElement);

            if (quoteElement) {
                const quoteJson = quoteElement.getAttribute('data-quote-json');
                console.error('📄 data-quote-json attribute:', quoteJson);

                if (quoteJson) {
                    try {
                        quote = JSON.parse(quoteJson);
                        console.error('✅ Successfully parsed quote from data-quote-json:', quote);
                    } catch (e) {
                        console.error('❌ Failed to parse data-quote-json:', e);
                    }
                }
            }

            if (!quote) {
                console.error('❌ No data-quote-json, trying legacy DOM fallback...');
                // Fallback to DOM element extraction (legacy)
                const quoteElement = document.getElementById(`quote-${quoteId}`);
                if (!quoteElement) {
                    console.error('❌ Quote element not found in DOM');
                    return;
                }

                const vendor = quoteElement.querySelector('.quote-vendor')?.textContent || 'Unknown Vendor';
                const priceText = quoteElement.querySelector('.quote-price')?.textContent || '$0';
                const timer = quoteElement.querySelector('.quote-timer')?.textContent || '00:00';
                const timerData = quoteElement.querySelector('.quote-timer');
                const expiresAt = timerData && timerData.dataset.expires ?
                    parseInt(timerData.dataset.expires) :
                    Date.now() + 30*60*1000;

                quote = {
                    id: quoteId,
                    vendor: vendor,
                    vendor_id: 1,  // Default fallback vendor_id
                    vendorId: 1,   // Also add vendorId for consistency
                    price: priceText.replace('$', ''), // Remove $ sign for calculations
                    timer: timer,
                    expiresAt: expiresAt,
                    items: window.latestRFQItems || [{description: 'Strawberries', quantity: 5, unit: 'BOX', unit_price: 45}],
                    notes: 'Premium quality, fresh picked today. Free same-day delivery within Sydney Markets.',
                    delivery_date: 'September 27, 2025'
                };
            }
        }

        console.log('Quote for modal:', quote);
        console.log('Quote expiresAt:', quote.expiresAt, new Date(quote.expiresAt).toLocaleTimeString());

        // Show modal with details
        showQuoteModal(quote);
    }

    // Calculate remaining time from expiry timestamp
    function calculateRemainingTime(expiresAt) {
        if (!expiresAt) return '30:00';

        const now = Date.now();
        const remaining = Math.max(0, expiresAt - now);

        if (remaining === 0) return '0:00';

        const minutes = Math.floor(remaining / 60000);
        const seconds = Math.floor((remaining % 60000) / 1000);

        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    // Show quote modal with details
    function showQuoteModal(quote) {
        console.log('showQuoteModal called with quote:', quote);

        // Get or initialize modal
        let modal = document.getElementById('quoteDetailsModal');
        if (!modal) {
            console.log('Modal not found, initializing...');
            initializeQuoteModal();
            modal = document.getElementById('quoteDetailsModal');
        }

        if (!modal) {
            console.error('Failed to create modal!');
            alert('Error opening quote details. Please refresh the page.');
            return;
        }

        // Update modal content with Sydney Markets themed design
        const content = document.getElementById('quoteDetailsContent');
        if (!content) {
            console.error('Modal content element not found!');
            alert('Error loading quote details. Please refresh the page.');
            return;
        }

        // Use the quote's actual price, not calculated from items
        const totalAmount = parseFloat(quote.price) || 0;

        console.log('Quote object in modal:', quote);
        console.log('Quote.items:', quote.items);
        console.log('Quote.items type:', typeof quote.items);
        console.log('Quote.items is array?', Array.isArray(quote.items));
        console.log('Quote.items length:', quote.items ? quote.items.length : 'null/undefined');

        content.innerHTML = `
            <!-- Ultra-compact header -->
            <div style="
                background: #E8EBF0;
                border-radius: 16px;
                padding: 16px;
                box-shadow:
                    inset 2px 2px 5px rgba(163, 177, 198, 0.5),
                    inset -2px -2px 5px rgba(255, 255, 255, 0.7);
            ">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="
                            width: 40px;
                            height: 40px;
                            background: linear-gradient(135deg, #10B981, #059669);
                            border-radius: 12px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 18px;
                            color: white;
                            font-weight: bold;
                        ">${quote.vendor ? quote.vendor.charAt(0) : 'V'}</div>
                        <div>
                            <div style="font-size: 16px; font-weight: 600; color: #1F2937;">${quote.vendor}</div>
                            <div style="font-size: 12px; color: #6B7280;">Quote #${String(quote.id).padStart(4, '0')}</div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 20px; font-weight: 700; color: #10B981;">$${totalAmount.toFixed(2)}</div>
                        <div style="
                            font-size: 11px;
                            color: #F59E0B;
                            display: flex;
                            align-items: center;
                            gap: 4px;
                            justify-content: flex-end;
                        ">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            <span id="modalQuoteTimer" data-quote-id="${quote.id}" data-expires="${quote.expiresAt || Date.now() + 30*60*1000}">${calculateRemainingTime(quote.expiresAt)}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact items list (max 3 visible) -->
            <div style="
                background: #E8EBF0;
                border-radius: 16px;
                padding: 12px;
                box-shadow:
                    3px 3px 6px rgba(163, 177, 198, 0.5),
                    -3px -3px 6px rgba(255, 255, 255, 0.7);
            ">
                <div style="font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 8px;">ITEMS</div>
                ${quote.items && quote.items.length > 0 ? quote.items.slice(0, 3).map((item) => `
                    <div style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 8px;
                        background: #F3F4F6;
                        border-radius: 8px;
                        margin-bottom: 6px;
                    ">
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
                ${quote.items && quote.items.length > 3 ? `
                    <div style="
                        text-align: center;
                        color: #6B7280;
                        font-size: 11px;
                        padding: 4px;
                        font-style: italic;
                    ">+${quote.items.length - 3} more items</div>
                ` : ''}
            </div>

            <!-- Minimal delivery info -->
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
                <div style="flex: 1;">
                    <div style="font-size: 11px; font-weight: 600; color: #059669;">DELIVERY</div>
                    <div style="font-size: 12px; color: #047857;">${quote.delivery_date || 'Within 24 hours'}</div>
                </div>
            </div>
        `;

        // Store quote ID for accept button
        modal.dataset.modalQuoteId = quote.id;

        console.log('Showing modal...');
        console.log('Modal display before:', modal.style.display);
        console.log('Modal opacity before:', modal.style.opacity);

        // Show modal with fade animation
        modal.style.display = 'block';
        modal.style.zIndex = '999999';
        setTimeout(() => {
            modal.style.opacity = '1';
            console.log('Modal should now be visible!');
            console.log('Modal display after:', modal.style.display);
            console.log('Modal opacity after:', modal.style.opacity);
        }, 10);
    }

    // Close quote modal with animation
    window.closeQuoteModal = function() {
        const modal = document.getElementById('quoteDetailsModal');
        if (modal) {
            // Note: Timer cleanup no longer needed - managed by master timer coordinator
            // The coordinator automatically handles modal timer updates
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    }

    // Start dynamic timer for modal
    // DEPRECATED: Modal timer now managed by master timer coordinator (initializeQuoteTimers)
    // The modal timer is automatically updated when it matches a quote ID in the coordinator
    // This ensures perfect synchronization between quote card and modal timers
    window.startModalTimer = function() {
        console.log('⚠️ startModalTimer called but is deprecated - timer managed by master coordinator');
    }

    // Start chat with vendor - slides chat panel within modal
    window.startChatWithVendor = function() {
        console.log('startChatWithVendor called');

        // Try different selectors to find the modal content
        let modalContent = document.querySelector('#quoteDetailsModal .modal-content');
        if (!modalContent) {
            modalContent = document.querySelector('#quoteDetailsModal > div');
            if (!modalContent) {
                modalContent = document.querySelector('[id*="quoteDetailsModal"]');
                console.log('Modal search result:', modalContent);
            }
        }

        if (!modalContent) {
            console.error('Modal content not found');
            return;
        }
        console.log('Modal content found:', modalContent);

        // Get quote data from the modal - try different ways
        const modal = document.getElementById('quoteDetailsModal');
        const quoteId = modal ? modal.dataset.modalQuoteId : null;
        console.log('Quote ID from modal:', quoteId);

        // Get the current quote from activeQuotes
        const quote = activeQuotes.find(q => q.id == quoteId);
        if (!quote) {
            console.error('Quote not found for chat. QuoteId:', quoteId, 'ActiveQuotes:', activeQuotes);
            // Try to create a minimal quote object
            const vendorName = modalContent.querySelector('h3')?.textContent || 'Unknown Vendor';
            console.log('Creating minimal quote with vendor:', vendorName);
            // Use minimal quote data
            const minimalQuote = {
                id: quoteId,
                vendor: vendorName,
                vendorId: 1, // default
                vendor_id: 1
            };
            // Continue with minimal quote
            startChatWithQuote(modalContent, minimalQuote, quoteId);
            return;
        }

        startChatWithQuote(modalContent, quote, quoteId);
    }

    function startChatWithQuote(modalContent, quote, quoteId) {
        console.log('Starting chat with quote:', quote);

        // Check if chat panel already exists
        let chatPanel = document.getElementById('modalChatPanel');

        if (!chatPanel) {
            console.log('Creating new chat panel');

            // Store original modal state if not stored
            if (!modalContent.dataset.originalWidth) {
                modalContent.dataset.originalWidth = modalContent.offsetWidth;
                modalContent.dataset.originalHeight = modalContent.offsetHeight;
                const computedStyle = window.getComputedStyle(modalContent);
                modalContent.dataset.originalPadding = computedStyle.padding;
            }

            // Create a wrapper for the existing content if it doesn't exist
            let contentWrapper = document.getElementById('modalContentWrapper');
            if (!contentWrapper) {
                contentWrapper = document.createElement('div');
                contentWrapper.id = 'modalContentWrapper';
                contentWrapper.style.cssText = `
                    display: flex;
                    gap: 24px;
                    width: 100%;
                    height: 100%;
                    align-items: stretch;
                `;

                // Create quote container to preserve original layout
                const quoteContainer = document.createElement('div');
                quoteContainer.id = 'quoteContentContainer';
                quoteContainer.style.cssText = `
                    width: ${modalContent.dataset.originalWidth}px;
                    min-width: ${modalContent.dataset.originalWidth}px;
                    max-width: ${modalContent.dataset.originalWidth}px;
                    flex-shrink: 0;
                    display: flex;
                    flex-direction: column;
                `;

                // Move all existing modal children to quote container
                while (modalContent.firstChild) {
                    quoteContainer.appendChild(modalContent.firstChild);
                }

                contentWrapper.appendChild(quoteContainer);
                modalContent.appendChild(contentWrapper);
            }

            // Calculate new modal width
            const chatPanelWidth = 420;
            const gap = 24;
            const totalWidth = parseInt(modalContent.dataset.originalWidth) + gap + chatPanelWidth;

            // Expand modal to accommodate both panels while keeping it centered
            modalContent.style.cssText = `
                position: absolute !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                width: ${totalWidth}px !important;
                max-width: 95vw !important;
                height: 650px !important;
                max-height: 90vh !important;
                padding: ${modalContent.dataset.originalPadding} !important;
                transition: all 0.3s ease !important;
                overflow: visible !important;
                display: block !important;
                background: #E8EBF0 !important;
                border-radius: 24px !important;
                box-shadow: 20px 20px 40px rgba(163, 177, 198, 0.5), -20px -20px 40px rgba(255, 255, 255, 0.7) !important;
            `;

            // Create chat panel as a flex sibling, not absolute positioned
            const chatPanelHTML = `
                <div id="modalChatPanel" style="
                    width: 0;
                    background: #E8EBF0;
                    border-radius: 16px;
                    margin-left: 24px;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                    transition: width 0.3s ease;
                    flex-shrink: 0;
                    box-shadow: inset 3px 3px 6px rgba(163, 177, 198, 0.5), inset -3px -3px 6px rgba(255, 255, 255, 0.7);
                ">
                    <!-- Chat Header with Neumorphic Design -->
                    <div style="
                        padding: 16px;
                        background: #E8EBF0;
                        border-radius: 16px 16px 0 0;
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        min-height: 60px;
                        box-shadow: inset 2px 2px 5px rgba(163, 177, 198, 0.5), inset -2px -2px 5px rgba(255, 255, 255, 0.7);
                    ">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="
                                width: 40px;
                                height: 40px;
                                background: linear-gradient(135deg, #10B981, #059669);
                                border-radius: 12px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-weight: 700;
                                font-size: 18px;
                                color: white;
                                box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                            ">${quote.vendor ? quote.vendor.charAt(0).toUpperCase() : 'V'}</div>
                            <div>
                                <div style="font-size: 16px; font-weight: 600; color: #1F2937;">${quote.vendor}</div>
                                <div style="font-size: 12px; color: #6B7280;">Chat with vendor</div>
                            </div>
                        </div>
                        <button onclick="closeChatPanel()" style="
                            background: #E8EBF0;
                            border: none;
                            border-radius: 50%;
                            width: 32px;
                            height: 32px;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transition: all 0.2s;
                            box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                        " onmouseover="this.style.boxShadow='inset 2px 2px 5px rgba(163, 177, 198, 0.5), inset -2px -2px 5px rgba(255, 255, 255, 0.7)'"
                           onmouseout="this.style.boxShadow='4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7)'">
                            <svg width="14" height="14" fill="none" stroke="#6B7280" stroke-width="2.5" stroke-linecap="round">
                                <path d="M1 1l12 12M1 13L13 1"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Chat Messages Container -->
                    <div id="chatMessages" style="
                        flex: 1;
                        padding: 20px 16px;
                        overflow-y: auto;
                        background: #E8EBF0;
                        display: flex;
                        flex-direction: column;
                    ">
                        <div style="
                            text-align: center;
                            padding: 40px 20px;
                            margin: auto;
                            background: #E8EBF0;
                            border-radius: 16px;
                            box-shadow: inset 2px 2px 5px rgba(163, 177, 198, 0.3), inset -2px -2px 5px rgba(255, 255, 255, 0.5);
                        ">
                            <div style="
                                width: 56px;
                                height: 56px;
                                margin: 0 auto 16px;
                                background: #E8EBF0;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                            ">
                                <svg width="28" height="28" fill="none" stroke="#10B981" stroke-width="2">
                                    <path d="M8 10h8m-8 4h5" stroke-linecap="round"/>
                                    <rect x="3" y="5" width="18" height="14" rx="2" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div style="font-size: 14px; font-weight: 500; color: #1F2937; margin-bottom: 6px;">
                                Start a conversation
                            </div>
                            <div style="font-size: 13px; color: #6B7280;">
                                Send a message to ${quote.vendor}
                            </div>
                        </div>
                    </div>

                    <!-- Chat Input Area with Neumorphic Design -->
                    <div style="
                        padding: 16px;
                        background: #E8EBF0;
                        border-radius: 0 0 16px 16px;
                        box-shadow: inset 0 2px 4px rgba(163, 177, 198, 0.3);
                    ">
                        <div style="
                            display: flex;
                            gap: 10px;
                            align-items: center;
                        ">
                            <input type="text" id="chatInput" placeholder="Type your message..." style="
                                flex: 1;
                                padding: 12px 16px;
                                border: none;
                                border-radius: 12px;
                                font-size: 14px;
                                outline: none;
                                background: #E8EBF0;
                                transition: all 0.2s;
                                color: #1F2937;
                                box-shadow: inset 3px 3px 6px rgba(163, 177, 198, 0.5), inset -3px -3px 6px rgba(255, 255, 255, 0.7);
                            " onfocus="this.style.boxShadow='inset 4px 4px 8px rgba(163, 177, 198, 0.6), inset -4px -4px 8px rgba(255, 255, 255, 0.8)'"
                               onblur="this.style.boxShadow='inset 3px 3px 6px rgba(163, 177, 198, 0.5), inset -3px -3px 6px rgba(255, 255, 255, 0.7)'"
                               onkeypress="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage(); }">
                            <button onclick="sendMessage()" style="
                                padding: 12px 24px;
                                background: linear-gradient(135deg, #10B981, #059669);
                                border: none;
                                border-radius: 12px;
                                color: white;
                                font-size: 14px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: all 0.15s ease;
                                display: flex;
                                align-items: center;
                                gap: 6px;
                                box-shadow: 4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7);
                                min-width: 90px;
                                justify-content: center;
                            " onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='6px 6px 12px rgba(163, 177, 198, 0.6), -6px -6px 12px rgba(255, 255, 255, 0.8)'"
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='4px 4px 8px rgba(163, 177, 198, 0.5), -4px -4px 8px rgba(255, 255, 255, 0.7)'">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                                </svg>
                                Send
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Add chat panel to the content wrapper
            const wrapper = document.getElementById('modalContentWrapper');
            if (wrapper) {
                wrapper.insertAdjacentHTML('beforeend', chatPanelHTML);
                chatPanel = document.getElementById('modalChatPanel');
            } else {
                console.error('Content wrapper not found');
                return;
            }

            // Store chat context
            window.currentModalChat = {
                vendorId: quote.vendorId || quote.vendor_id,
                vendorName: quote.vendor,
                quoteId: quoteId,
                buyerId: {{ auth('buyer')->user()->id ?? 'null' }}
            };

            // Animate panel sliding in
            setTimeout(() => {
                chatPanel.style.width = `420px`;
                chatPanel.style.flex = `0 0 420px`;
                const chatInput = document.getElementById('chatInput');
                if (chatInput) chatInput.focus();
                // Load existing messages
                loadChatMessages(quoteId);
                // Subscribe to WebSocket channel for this quote
                if (typeof window.subscribeToQuoteMessages === 'function') {
                    window.subscribeToQuoteMessages(quoteId);
                }
            }, 50);
        } else {
            // Toggle chat panel
            if (chatPanel.style.width === '0px' || chatPanel.style.width === '') {
                const existingContent = document.getElementById('quoteDetailsContent');
                const originalWidth = existingContent ? existingContent.offsetWidth : 450;
                const chatPanelWidth = 420;
                const gap = 24;
                const totalWidth = originalWidth + gap + chatPanelWidth;

                chatPanel.style.width = `${chatPanelWidth}px`;
                chatPanel.style.flex = `0 0 ${chatPanelWidth}px`;
                modalContent.style.width = `${totalWidth}px`;
                setTimeout(() => {
                    document.getElementById('chatInput').focus();
                    // Load existing messages
                    loadChatMessages(window.currentModalChat.quoteId);
                }, 300);
            } else {
                closeChatPanel();
            }
        }
    }

    // Close chat panel function
    window.closeChatPanel = function() {
        console.log('closeChatPanel called');
        const chatPanel = document.getElementById('modalChatPanel');
        const modalContent = document.querySelector('.quote-modal-container');

        if (chatPanel) {
            console.log('Closing chat panel...');
            // Animate panel sliding out
            chatPanel.style.width = '0px';
            chatPanel.style.opacity = '0';
            chatPanel.style.transition = 'all 0.3s ease';

            // Restore modal to original size
            if (modalContent && modalContent.dataset.originalWidth) {
                setTimeout(() => {
                    modalContent.style.width = `${modalContent.dataset.originalWidth}px`;
                    modalContent.style.height = `${modalContent.dataset.originalHeight}px`;
                    modalContent.style.transition = 'all 0.3s ease';
                }, 100);
            }

            // Remove the chat panel after animation
            setTimeout(() => {
                if (chatPanel && chatPanel.parentNode) {
                    chatPanel.remove();
                }
            }, 400);
        } else {
            console.log('Chat panel not found');
        }
    }

    // Send message function - integrated with real-time messaging
    window.sendMessage = function() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();

        if (!message || !window.currentModalChat) return;

        const messagesContainer = document.getElementById('chatMessages');
        if (!messagesContainer) return;

        // Add sent message to chat
        const messageHTML = `
            <div style="margin-bottom: 12px; display: flex; justify-content: flex-end;">
                <div style="
                    background: linear-gradient(135deg, #10B981, #059669);
                    color: white;
                    padding: 10px 14px;
                    border-radius: 16px 16px 4px 16px;
                    max-width: 70%;
                    font-size: 14px;
                ">
                    ${message}
                    <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">
                        ${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                    </div>
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        input.value = '';
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Send message via API
        console.log('Sending message to vendor:', {
            vendorId: window.currentModalChat.vendorId,
            quoteId: window.currentModalChat.quoteId,
            message: message
        });

        fetch('/api/messages/send', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                quote_id: window.currentModalChat.quoteId,
                recipient_id: window.currentModalChat.vendorId,
                recipient_type: 'vendor',
                message: message
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'API error');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Message sent successfully:', data);
        })
        .catch(error => {
            console.error('❌ Error sending message:', error);
            alert('Failed to send message: ' + error.message);
        });
    }

    // Subscribe to WebSocket channel for real-time messages
    window.subscribeToQuoteMessages = function(quoteId) {
        if (!window.Echo) {
            console.error('Laravel Echo not initialized');
            return;
        }

        // Leave any existing channel
        if (window.currentMessageChannel) {
            window.Echo.leave(window.currentMessageChannel);
        }

        // Subscribe to the quote messages channel (PUBLIC for no auth)
        window.currentMessageChannel = `quote.${quoteId}.messages`;
        window.Echo.channel(window.currentMessageChannel)
            .listen('.message.sent', (e) => {
                console.log('New message received:', e);

                // Don't display if it's our own message (already shown)
                if (e.sender_type === 'vendor') {
                    const messagesContainer = document.getElementById('chatMessages');
                    if (!messagesContainer) return;

                    const messageHTML = `
                        <div style="margin-bottom: 12px; display: flex; justify-content: flex-start;">
                            <div style="
                                background: white;
                                color: #374151;
                                padding: 10px 14px;
                                border-radius: 16px 16px 16px 16px;
                                max-width: 70%;
                                font-size: 14px;
                                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                            ">
                                ${e.message}
                                <div style="font-size: 11px; color: #6B7280; margin-top: 4px;">
                                    ${e.sender_name || 'Vendor'} • ${new Date(e.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                                </div>
                            </div>
                        </div>
                    `;

                    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
    }

    // Load chat messages when opening chat
    window.loadChatMessages = function(quoteId) {
        console.log('📥 Loading chat messages for quote:', quoteId);

        fetch(`/api/messages/quote/${quoteId}`, {
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('📡 API Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📦 Messages data received:', data);

            const messagesContainer = document.getElementById('chatMessages');
            if (!messagesContainer) {
                console.error('❌ Chat messages container not found!');
                return;
            }

            if (!data.messages) {
                console.warn('⚠️ No messages in response');
                return;
            }

            console.log(`✅ Loading ${data.messages.length} messages into chat`);
            messagesContainer.innerHTML = '';

            data.messages.forEach(msg => {
                const isSent = msg.sender_type === 'buyer';
                console.log(`  - ${msg.sender_type}: ${msg.message}`);

                const messageHTML = `
                    <div style="margin-bottom: 12px; display: flex; justify-content: ${isSent ? 'flex-end' : 'flex-start'};">
                        <div style="
                            background: ${isSent ? 'linear-gradient(135deg, #10B981, #059669)' : 'white'};
                            color: ${isSent ? 'white' : '#374151'};
                            padding: 10px 14px;
                            border-radius: 16px 16px ${isSent ? '4px' : '16px'} 16px;
                            max-width: 70%;
                            font-size: 14px;
                            ${!isSent ? 'box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);' : ''}
                        ">
                            ${msg.message}
                            <div style="font-size: 11px; ${isSent ? 'opacity: 0.8' : 'color: #6B7280'}; margin-top: 4px;">
                                ${isSent ? '' : (msg.sender_name || 'Vendor') + ' • '}${new Date(msg.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                            </div>
                        </div>
                    </div>
                `;
                messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
            });
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            console.log('✅ Chat messages loaded successfully');
        })
        .catch(error => {
            console.error('❌ Error loading messages:', error);
            alert('Failed to load chat messages: ' + error.message);
        });
    }

    // Show chat interface
    window.showChatInterface = function(vendorName, vendorId, quoteId) {
        // Create and show chat modal
        const chatHTML = `
            <div id="chatModal" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                opacity: 1;
                transition: opacity 0.3s ease;
            ">
                <div style="
                    background: #F1F5F9;
                    border-radius: 24px;
                    width: 500px;
                    max-height: 600px;
                    display: flex;
                    flex-direction: column;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                ">
                    <!-- Chat Header -->
                    <div style="
                        padding: 20px;
                        border-bottom: 1px solid rgba(163, 177, 198, 0.3);
                        background: linear-gradient(135deg, #10B981, #059669);
                        border-radius: 24px 24px 0 0;
                        color: white;
                    ">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h3 style="margin: 0; font-size: 18px; font-weight: 700;">Chat with ${vendorName}</h3>
                                <p style="margin: 4px 0 0; font-size: 12px; opacity: 0.9;">About Quote #${quoteId}</p>
                            </div>
                            <button onclick="closeChatModal()" style="
                                background: rgba(255, 255, 255, 0.2);
                                border: none;
                                border-radius: 8px;
                                width: 32px;
                                height: 32px;
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                transition: background 0.2s;
                            " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'"
                               onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Chat Messages -->
                    <div id="chatMessages" style="
                        flex: 1;
                        padding: 20px;
                        overflow-y: auto;
                        max-height: 400px;
                    ">
                        <div style="text-align: center; color: #6B7280; font-size: 13px; margin: 20px 0;">
                            Start a conversation with ${vendorName}
                        </div>
                    </div>

                    <!-- Chat Input -->
                    <div style="
                        padding: 20px;
                        border-top: 1px solid rgba(163, 177, 198, 0.3);
                        display: flex;
                        gap: 12px;
                    ">
                        <input type="text" id="chatInput" placeholder="Type your message..." style="
                            flex: 1;
                            padding: 10px 16px;
                            border: 1px solid rgba(163, 177, 198, 0.3);
                            border-radius: 12px;
                            font-size: 14px;
                            background: white;
                            outline: none;
                        " onkeypress="if(event.key === 'Enter') sendChatMessage()">
                        <button onclick="sendChatMessage()" style="
                            padding: 10px 20px;
                            background: linear-gradient(135deg, #10B981, #059669);
                            border: none;
                            border-radius: 12px;
                            color: white;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.15s ease;
                        " onmouseover="this.style.background='linear-gradient(135deg, #059669, #047857)'"
                           onmouseout="this.style.background='linear-gradient(135deg, #10B981, #059669)'">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', chatHTML);

        // Store current chat context
        window.currentChat = {
            vendorId: vendorId,
            vendorName: vendorName,
            quoteId: quoteId
        };

        // Focus on input
        setTimeout(() => {
            document.getElementById('chatInput').focus();
        }, 100);
    }

    // Close chat modal
    window.closeChatModal = function() {
        const modal = document.getElementById('chatModal');
        if (modal) {
            modal.style.opacity = '0';
            setTimeout(() => {
                modal.remove();
                window.currentChat = null;
            }, 300);
        }
    }

    // Send chat message
    window.sendChatMessage = function() {
        const input = document.getElementById('chatInput');
        const message = input.value.trim();

        if (!message || !window.currentChat) return;

        // Add message to chat
        const messagesContainer = document.getElementById('chatMessages');
        const messageHTML = `
            <div style="margin-bottom: 16px; display: flex; justify-content: flex-end;">
                <div style="
                    background: linear-gradient(135deg, #10B981, #059669);
                    color: white;
                    padding: 10px 14px;
                    border-radius: 16px 16px 4px 16px;
                    max-width: 70%;
                    font-size: 14px;
                    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
                ">
                    ${message}
                    <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">
                        ${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                    </div>
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);

        // Clear input
        input.value = '';

        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Send to server via Livewire
        if (window.Livewire) {
            Livewire.dispatch('sendMessage', {
                vendorId: window.currentChat.vendorId,
                quoteId: window.currentChat.quoteId,
                message: message
            });
        }

        // Simulate vendor response after 2 seconds (for demo)
        setTimeout(() => {
            const responseHTML = `
                <div style="margin-bottom: 16px; display: flex; justify-content: flex-start;">
                    <div style="
                        background: white;
                        color: #374151;
                        padding: 10px 14px;
                        border-radius: 16px 16px 16px 4px;
                        max-width: 70%;
                        font-size: 14px;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    ">
                        Thanks for your message! I'll get back to you shortly about this quote.
                        <div style="font-size: 11px; color: #6B7280; margin-top: 4px;">
                            ${window.currentChat.vendorName} • ${new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                        </div>
                    </div>
                </div>
            `;

            messagesContainer.insertAdjacentHTML('beforeend', responseHTML);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 2000);
    }

    // Helper function to get product emoji
    function getProductEmoji(productName) {
        const emojis = {
            'strawberries': '🍓',
            'tomatoes': '🍅',
            'apples': '🍎',
            'bananas': '🍌',
            'carrots': '🥕',
            'lettuce': '🥬',
            'oranges': '🍊',
            'grapes': '🍇',
            'watermelon': '🍉',
            'default': '📦'
        };
        const key = productName ? productName.toLowerCase() : 'default';
        return emojis[key] || emojis['default'];
    }

    // Accept quote from modal
    window.acceptQuoteFromModal = function() {
        const modal = document.getElementById('quoteDetailsModal');
        const quoteId = modal ? modal.dataset.modalQuoteId : null;
        if (quoteId) {
            acceptQuote(quoteId);
            closeQuoteModal();
        }
    }

    // Accept a quote - TRACK REVENUE
    // CRITICAL: Attach to window object for onclick handlers to work
    window.acceptQuote = function(quoteId) {
        const quote = activeQuotes.find(q => q.id === quoteId);
        if (quote) {
            // Add to accepted quotes and revenue
            acceptedQuotes.push(quote);
            totalRevenue += parseFloat(quote.price);

            console.log(`✅ Quote accepted: ${quote.vendor} - $${quote.price}`);
            console.log(`💰 Total Revenue: $${totalRevenue.toFixed(2)}`);

            // Remove from active quotes
            removeQuote(quoteId);

            // Update revenue display with roulette animation
            updateRevenueDisplay();
        }
    }

    // Clear all quotes
    function clearAllQuotes() {
        // Clear all individual quote timers using new system
        if (window.quoteTimerIntervals) {
            Object.keys(window.quoteTimerIntervals).forEach(quoteId => {
                clearInterval(window.quoteTimerIntervals[quoteId]);
                delete window.quoteTimerIntervals[quoteId];
            });
        }

        // Remove all quote elements
        const quoteElements = document.querySelectorAll('.quote-item');
        quoteElements.forEach(element => element.remove());

        // Reset activeQuotes if it exists
        if (window.activeQuotes) {
            window.activeQuotes = [];
        }

        updateQuoteCounts();
        const noQuotesMsg = document.getElementById('noQuotesMessage');
        if (noQuotesMsg) noQuotesMsg.style.display = 'block';
    }

    // Debouncing state for stats updates
    let updateStatsTimeout = null;
    let isAnimating = false;
    const MAX_QUOTES_DISPLAY = 1000; // Cap at 1000 quotes max

    // Update quote statistics - MODERN ROULETTE ANIMATION (DEBOUNCED)
    function updateQuoteStats() {
        // Debounce: Cancel pending updates
        if (updateStatsTimeout) {
            clearTimeout(updateStatsTimeout);
        }

        // If currently animating, queue the next update
        if (isAnimating) {
            updateStatsTimeout = setTimeout(updateQuoteStats, 600);
            return;
        }

        // Mark as animating
        isAnimating = true;

        // Calculate current values with 1000 quote cap
        const rawQuoteCount = activeQuotes.length;
        const quoteCount = Math.min(rawQuoteCount, MAX_QUOTES_DISPLAY);

        console.log(`📊 STATS UPDATE: ${quoteCount} active quotes${rawQuoteCount > MAX_QUOTES_DISPLAY ? ' (capped at 1000)' : ''}`);

        // FORCE UPDATE BADGE
        const badge = document.getElementById('quoteBadge');
        if (badge) {
            badge.textContent = `${quoteCount}`;
            badge.style.background = quoteCount > 0 ? '#10B981' : '#6B7280';
        }

        // UPDATE QUOTE COUNT WITH ROULETTE ANIMATION
        const quoteWidget = document.querySelector('.stat-widget[data-stat="quotesReceived"]');
        if (quoteWidget) {
            const statValue = quoteWidget.querySelector('.stat-value');
            if (statValue) {
                animateRouletteNumber(statValue, quoteCount);
            }
        }

        // Clear animating flag after animation completes
        setTimeout(() => {
            isAnimating = false;
        }, 600); // 500ms animation + 100ms buffer
    }

    // Update revenue display with roulette animation
    function updateRevenueDisplay() {
        const valueWidget = document.querySelector('.stat-widget[data-stat="totalValue"]');
        if (valueWidget) {
            const valueElement = valueWidget.querySelector('.stat-value');
            const labelElement = valueWidget.querySelector('.stat-label');

            // Update label to show it's revenue
            if (labelElement && labelElement.textContent !== 'Revenue') {
                labelElement.textContent = 'Revenue';
            }

            if (valueElement) {
                const formatted = '$' + totalRevenue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                animateRouletteNumber(valueElement, formatted);
            }
        }
    }

    // VISIBLE SCROLLING ROULETTE ANIMATION
    function animateRouletteNumber(element, newValue) {
        const oldValue = element.textContent;
        const newStr = newValue.toString();

        // For simple number increments, use visible scroll
        if (!isNaN(newValue) && !isNaN(parseInt(oldValue))) {
            const oldNum = parseInt(oldValue) || 0;
            const newNum = parseInt(newValue);
            createVisibleScroll(element, oldNum, newNum);
            return;
        }

        // For currency values
        if (newStr.includes('$')) {
            createCurrencyScroll(element, newStr);
            return;
        }

        // Default to simple text update
        element.textContent = newStr;
    }

    // CREATE REACT-SLOT-COUNTER STYLE ANIMATION (Exact Copy)
    function createVisibleScroll(element, oldNum, newNum) {
        // Don't animate if same number
        if (oldNum === newNum) {
            element.textContent = newNum;
            return;
        }

        // REACT-SLOT-COUNTER SETTINGS - OPTIMIZED
        const DURATION = 500; // Faster for smoother feel
        const SPEED = 1.0; // Normal speed
        const DUMMY_COUNT = 4; // Fewer dummies for cleaner animation
        const DIRECTION = 'bottom-up'; // Animation direction

        // Calculate effective duration
        const effectiveDuration = DURATION; // 500ms

        // Set up container for slot machine effect
        // Save original dimensions to prevent layout shift
        const originalHeight = element.offsetHeight;
        const originalWidth = element.offsetWidth;

        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.style.height = originalHeight + 'px'; // Keep exact same height
        element.style.width = originalWidth + 'px'; // Keep exact same width
        element.style.display = 'inline-block';
        element.style.textAlign = 'left';
        // No background or box effects - keep it clean

        // Create the scrolling reel
        const reel = document.createElement('div');
        reel.style.position = 'absolute';
        reel.style.width = '100%';
        reel.style.transition = `transform ${effectiveDuration}ms cubic-bezier(0.4, 0, 0.2, 1)`; // Smoother easing
        reel.style.willChange = 'transform';

        // Clear element
        element.innerHTML = '';

        const numbers = [];
        const isIncreasing = newNum > oldNum;

        // Build the slot reel with dummy numbers
        if (DIRECTION === 'bottom-up') {
            // Numbers scroll up (like slot machine)
            // Add dummy numbers before target - random for slot effect
            for (let i = 0; i < DUMMY_COUNT; i++) {
                // Random numbers 0-9 like real slot machine
                const dummyNum = Math.floor(Math.random() * 10);
                numbers.push(dummyNum);
            }

            // Add the old number (starting point)
            numbers.push(oldNum);

            // Add intermediate numbers if gap is small
            if (Math.abs(newNum - oldNum) <= 3) {
                if (isIncreasing) {
                    for (let i = oldNum + 1; i < newNum; i++) {
                        numbers.push(i);
                    }
                } else {
                    for (let i = oldNum - 1; i > newNum; i--) {
                        numbers.push(i);
                    }
                }
            }

            // Add the target number
            numbers.push(newNum);

            // Add one more random for smooth exit
            numbers.push(Math.floor(Math.random() * 10));
        }

        // Create number elements with blur effect
        numbers.forEach((num, index) => {
            const numDiv = document.createElement('div');
            numDiv.textContent = num;
            numDiv.style.height = '1.4em';
            numDiv.style.lineHeight = '1.4em';
            numDiv.style.fontSize = 'inherit';
            numDiv.style.fontWeight = 'inherit';
            numDiv.style.position = 'relative';

            // Apply blur to dummy numbers for motion effect
            const isDummy = index < DUMMY_COUNT || index === numbers.length - 1;
            const isTarget = num === newNum && index === numbers.lastIndexOf(newNum);
            const isOldNum = num === oldNum && index === numbers.indexOf(oldNum);

            if (isDummy) {
                numDiv.style.opacity = '0.2';
                numDiv.style.filter = 'blur(1px)';
                // NO SCALE - causes jumpy effect
            } else if (isOldNum) {
                // Old number - slightly faded
                numDiv.style.opacity = '0.6';
            }
            // Target number stays normal - no color change

            reel.appendChild(numDiv);
        });

        // Add reel to element
        element.appendChild(reel);

        // Calculate positions (adjusted for new height)
        const targetIndex = numbers.lastIndexOf(newNum); // Use lastIndexOf to get the target
        const oldIndex = numbers.indexOf(oldNum);
        const startOffset = -oldIndex * 1.4; // Start showing old number
        const finalOffset = -targetIndex * 1.4; // End showing target number

        // Set initial position (show dummy numbers scrolling)
        reel.style.transform = `translateY(${startOffset}em)`;

        // Force layout calculation
        element.offsetHeight;

        // Animate to target number smoothly
        requestAnimationFrame(() => {
            // Simple smooth scroll to target - no bounce
            reel.style.transform = `translateY(${finalOffset}em)`;
        });

        // Clean up after animation completes
        setTimeout(() => {
            element.textContent = newNum;
            element.style.overflow = '';
            element.style.position = '';
            element.style.width = '';
            element.style.height = '';
        }, effectiveDuration + 300);
    }

    // CREATE SCROLLING EFFECT FOR CURRENCY
    function createCurrencyScroll(element, newValue) {
        // Parse the value
        const cleanValue = newValue.replace(/[$,]/g, '');
        const parts = cleanValue.split('.');
        const dollars = parseInt(parts[0]) || 0;
        const cents = parts[1] || '00';

        // Set up main container
        element.style.position = 'relative';
        element.style.display = 'inline-block';
        element.innerHTML = '';

        // Add dollar sign (static)
        const dollarSign = document.createElement('span');
        dollarSign.textContent = '$';
        element.appendChild(dollarSign);

        // Animate dollar amount
        const dollarContainer = document.createElement('span');
        dollarContainer.style.display = 'inline-block';
        dollarContainer.style.position = 'relative';
        dollarContainer.style.overflow = 'hidden';
        dollarContainer.style.height = '1.4em';
        dollarContainer.style.verticalAlign = 'top';

        // Get old dollar value
        const oldText = element.getAttribute('data-dollars') || '0';
        const oldDollars = parseInt(oldText) || 0;

        // Create scrolling dollars - REACT-SLOT-COUNTER TIMING
        const scrollDiv = document.createElement('div');
        scrollDiv.style.transition = 'transform 500ms cubic-bezier(0.23, 1, 0.32, 1)'; // 700ms / 1.4 speed

        // Add old value
        const oldDiv = document.createElement('div');
        oldDiv.textContent = oldDollars.toLocaleString();
        oldDiv.style.height = '1.4em';
        scrollDiv.appendChild(oldDiv);

        // Add new value
        const newDiv = document.createElement('div');
        newDiv.textContent = dollars.toLocaleString();
        newDiv.style.height = '1.4em';
        scrollDiv.appendChild(newDiv);

        dollarContainer.appendChild(scrollDiv);
        element.appendChild(dollarContainer);

        // Add decimal point and cents (static for now)
        const decimal = document.createElement('span');
        decimal.textContent = '.' + cents;
        element.appendChild(decimal);

        // Store new value
        element.setAttribute('data-dollars', dollars);

        // Trigger animation
        if (oldDollars !== dollars) {
            requestAnimationFrame(() => {
                scrollDiv.style.transform = 'translateY(-1.4em)';
            });
        }

        // Clean up
        setTimeout(() => {
            element.innerHTML = newValue;
        }, 550);
    }



    // Animate number with easing
    function animateValue(element, start, end, duration, suffix = '') {
        if (start === end) return;

        const range = end - start;
        const increment = end > start ? 1 : -1;
        const stepTime = Math.abs(Math.floor(duration / range));
        const startTime = Date.now();

        function updateNumber() {
            const now = Date.now();
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function (ease-out-cubic)
            const easeOutCubic = 1 - Math.pow(1 - progress, 3);

            const current = Math.floor(start + (range * easeOutCubic));
            element.textContent = current + suffix;

            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            } else {
                element.textContent = end + suffix;
            }
        }

        requestAnimationFrame(updateNumber);
    }

    // Animate currency values with proper formatting
    function animateCurrency(element, start, end, duration) {
        if (start === end) return;

        const startTime = Date.now();

        function updateCurrency() {
            const now = Date.now();
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Smooth easing function
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);

            const current = start + ((end - start) * easeOutQuart);

            // Format as currency with proper decimals
            element.textContent = '$' + current.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

            if (progress < 1) {
                requestAnimationFrame(updateCurrency);
            } else {
                element.textContent = '$' + end.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }
        }

        requestAnimationFrame(updateCurrency);
    }

    // Add morphing effect for individual digits
    function morphDigits(element, newValue) {
        const oldText = element.textContent;
        const newText = newValue.toString();

        element.style.position = 'relative';
        element.innerHTML = '';

        const maxLength = Math.max(oldText.length, newText.length);

        for (let i = 0; i < maxLength; i++) {
            const span = document.createElement('span');
            span.style.cssText = 'display: inline-block; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);';

            if (i < oldText.length && i < newText.length && oldText[i] !== newText[i]) {
                // Digit changed - add flip animation
                span.classList.add('digit-flip');
                span.textContent = newText[i];
            } else if (i < newText.length) {
                // New digit
                span.classList.add('digit-slide-in');
                span.textContent = newText[i];
            }

            element.appendChild(span);
        }
    }

    // Add odometer-style rolling animation for large numbers
    function createOdometerEffect(element, value) {
        const formattedValue = typeof value === 'number'
            ? value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : value.toString();

        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.style.display = 'inline-block';

        const digits = formattedValue.split('');
        element.innerHTML = '';

        digits.forEach((digit, index) => {
            const span = document.createElement('span');
            span.style.cssText = `
                display: inline-block;
                transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                transform-origin: center;
            `;

            if (digit === '.' || digit === ',' || digit === '$') {
                span.textContent = digit;
                span.style.transform = 'none';
            } else {
                const oldDigit = element.getAttribute(`data-digit-${index}`) || '0';
                if (oldDigit !== digit) {
                    span.style.animation = `rollDigit 0.6s ease-out`;
                }
                span.textContent = digit;
                element.setAttribute(`data-digit-${index}`, digit);
            }

            element.appendChild(span);
        });
    }

    // Initialize quote system animation styles
    function initializeQuoteAnimations() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes rollDigit {
                0% {
                    transform: translateY(-100%);
                    opacity: 0;
                }
                50% {
                    transform: translateY(-50%);
                    opacity: 0.5;
                }
                100% {
                    transform: translateY(0);
                    opacity: 1;
                }
            }

            .stat-value {
                font-feature-settings: "tnum";
                font-variant-numeric: tabular-nums lining-nums;
            }

            .quote-item-new {
                animation: quoteSlideIn 0.5s ease-out;
                /* CRITICAL: White background with green border glow - NO green background */
                background: var(--neuro-bg) !important;
                box-shadow: inset 3px 3px 6px #B8BEC7,
                            inset -3px -3px 6px #FFFFFF,
                            0 0 0 2px rgba(16, 185, 129, 0.3);
            }

            @keyframes quoteSlideIn {
                0% {
                    transform: translateX(100%);
                    opacity: 0;
                }
                100% {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            .pulse-animation {
                animation: subtlePulse 1s cubic-bezier(0.4, 0, 0.6, 1);
            }

            /* NO SCALE - User's strict rule */
            @keyframes subtlePulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }

    // Silent backend process - no notifications shown
    function processBackendUpdate(action, data) {
        // This would normally send data to backend via AJAX/fetch
        console.log(`Backend process: ${action}`, data);
        // All updates happen silently in the background
    }

    // Flash planner button with feedback
    function flashPlannerButton(message, type = 'warning') {
        const button = document.querySelector('.send-planner-btn');
        if (button) {
            const originalColor = button.style.background;
            const flashColor = type === 'warning' ? '#F0A830' : '#E8745B';

            button.style.background = flashColor;
            button.style.animation = 'shake 0.5s ease';

            setTimeout(() => {
                button.style.background = originalColor;
                button.style.animation = '';
            }, 500);
        }
    }

    // Initialize animations on page load
    initializeQuoteAnimations();

    // Master Timer System removed - now using simpler vendor dashboard pattern

    // Removed problematic DOMContentLoaded that was hiding quotes due to race condition
    // The quotes container visibility is properly managed by the Livewire initialization above

    // Setup user dropdown event listeners after DOM loads
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Dashboard fully loaded, timers ready');
        updateQuoteStats(); // Initialize stats on load

        // Setup user dropdown event listeners
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) {
            dropdown.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        }
    });

    // Window load handler removed - products are initialized immediately at line 535

    // TEST FUNCTION - Run this in console to verify timers work
    window.testQuoteSystem = function() {
        console.log('🧪 Testing Quote System...');
        console.log('Active Quotes:', activeQuotes);
        console.log('Running Timers:', Object.keys(quoteTimers));

        // Test adding a manual quote
        const testQuote = {
            id: 'test_' + Date.now() + '_' + Math.floor(Math.random() * 10000),
            vendor: 'Test Vendor',
            product: 'Test Product - 10kg',
            price: '99.99',
            timestamp: Date.now(),
            expiresAt: Date.now() + (30 * 60 * 1000) // 30 minutes
        };

        console.log('📦 Adding test quote...');
        activeQuotes.push(testQuote);
        addQuoteToUI(testQuote);

        // Check if timer started
        setTimeout(() => {
            if (quoteTimers[testQuote.id]) {
                console.log('✅ Timer is running for test quote');
            } else {
                console.error('❌ Timer failed to start for test quote');
            }

            // Check timer display
            const timerEl = document.querySelector(`#quote-${testQuote.id} .quote-timer`);
            if (timerEl) {
                console.log(`⏰ Timer display: ${timerEl.textContent}`);
            }
        }, 1000);
    };

    // WEEKLY PLANNER SYSTEM
    let weeklyOrders = {
        monday: [], tuesday: [], wednesday: [], thursday: [], friday: [], saturday: [], sunday: []
    };
    let currentDay = 'monday';

    // CRITICAL: Attach to window object for onclick handlers to work
    window.openWeeklyPlanner = function() {
        // Load saved data first
        loadWeeklyOrders();
        document.getElementById('weeklyPlannerModal').style.display = 'flex';

        // Smart day detection - automatically select today's day
        const today = new Date();
        const dayIndex = today.getDay(); // 0 = Sunday, 1 = Monday, etc.
        const dayMap = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const todayName = dayMap[dayIndex];

        // Select today's day
        selectDay(todayName);
    };

    window.closePlannerModal = function() {
        document.getElementById('weeklyPlannerModal').style.display = 'none';
    };

    // Close modal when clicking outside (only on direct overlay click)
    document.getElementById('weeklyPlannerModal').addEventListener('click', function(e) {
        // Only close if clicking directly on the modal overlay (not on bubbled events)
        // Also ensure it's not a click from inside the planner-container
        if (e.target === this && e.currentTarget === this && e.target.id === 'weeklyPlannerModal') {
            closePlannerModal();
        }
    });

    // CRITICAL: Attach to window object for onclick handlers to work
    window.selectDay = function(day) {
        currentDay = day;

        // Update active day button
        document.querySelectorAll('.day-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`[data-day="${day}"]`).classList.add('active');

        // Display products for this day
        displayProducts();
    };

    function displayProducts() {
        const productsList = document.getElementById('productsList');
        const dayProducts = weeklyOrders[currentDay];

        if (dayProducts.length === 0) {
            productsList.innerHTML = `
                <div class="empty-state">
                    <p>No products added for ${currentDay}</p>
                    <p style="font-size: 11px; opacity: 0.7;">Click + to add products</p>
                </div>
            `;
        } else {
            productsList.innerHTML = dayProducts.map((product, index) => `
                <div class="planner-product-item">
                    <input
                        type="text"
                        class="product-name-input"
                        placeholder="Product name..."
                        value="${product.name}"
                        onchange="updateProductName(${index}, this.value)"
                    />
                    <div class="quantity-controls">
                        <input
                            type="number"
                            class="quantity-input"
                            value="${product.quantity}"
                            min="1"
                            onchange="updateProductQuantity(${index}, this.value)"
                        />
                        <!-- Custom Unit Dropdown -->
                        <div class="unit-dropdown" data-index="${index}">
                            <div class="unit-dropdown-selected" onclick="toggleUnitDropdown(${index})">
                                <span class="unit-value">${(product.unit || 'BOX').toUpperCase()}</span>
                                <span class="unit-dropdown-arrow"></span>
                            </div>
                            <div class="unit-dropdown-options">
                                <div class="unit-dropdown-option ${product.unit === 'kg' ? 'selected' : ''}"
                                     data-unit="kg"
                                     onclick="selectUnit(${index}, 'kg', event)">KG</div>
                                <div class="unit-dropdown-option ${product.unit === 'box' ? 'selected' : ''}"
                                     data-unit="box"
                                     onclick="selectUnit(${index}, 'box', event)">BOX</div>
                                <div class="unit-dropdown-option ${product.unit === 'crate' ? 'selected' : ''}"
                                     data-unit="crate"
                                     onclick="selectUnit(${index}, 'crate', event)">CRATE</div>
                                <div class="unit-dropdown-option ${product.unit === 'pallet' ? 'selected' : ''}"
                                     data-unit="pallet"
                                     onclick="selectUnit(${index}, 'pallet', event)">PALLET</div>
                                <div class="unit-dropdown-option ${product.unit === 'pkt' ? 'selected' : ''}"
                                     data-unit="pkt"
                                     onclick="selectUnit(${index}, 'pkt', event)">PKT</div>
                                <div class="unit-dropdown-option ${product.unit === 'bag' ? 'selected' : ''}"
                                     data-unit="bag"
                                     onclick="selectUnit(${index}, 'bag', event)">BAG</div>
                                <div class="unit-dropdown-option ${product.unit === 'dozen' ? 'selected' : ''}"
                                     data-unit="dozen"
                                     onclick="selectUnit(${index}, 'dozen', event)">DOZEN</div>
                                <div class="unit-dropdown-option ${product.unit === 'bunch' ? 'selected' : ''}"
                                     data-unit="bunch"
                                     onclick="selectUnit(${index}, 'bunch', event)">BUNCH</div>
                                <div class="unit-dropdown-option ${product.unit === 'tray' ? 'selected' : ''}"
                                     data-unit="tray"
                                     onclick="selectUnit(${index}, 'tray', event)">TRAY</div>
                                <div class="unit-dropdown-option ${product.unit === 'punnet' ? 'selected' : ''}"
                                     data-unit="punnet"
                                     onclick="selectUnit(${index}, 'punnet', event)">PUNNET</div>
                                <div class="unit-dropdown-option ${product.unit === 'carton' ? 'selected' : ''}"
                                     data-unit="carton"
                                     onclick="selectUnit(${index}, 'carton', event)">CARTON</div>
                                <div class="unit-dropdown-option ${product.unit === 'unit' ? 'selected' : ''}"
                                     data-unit="unit"
                                     onclick="selectUnit(${index}, 'unit', event)">UNIT</div>
                                <div class="unit-dropdown-option ${product.unit === 'each' ? 'selected' : ''}"
                                     data-unit="each"
                                     onclick="selectUnit(${index}, 'each', event)">EACH</div>
                                <div class="unit-dropdown-option ${product.unit === 'ltr' ? 'selected' : ''}"
                                     data-unit="ltr"
                                     onclick="selectUnit(${index}, 'ltr', event)">LTR</div>
                                <div class="unit-dropdown-option ${product.unit === 'pieces' ? 'selected' : ''}"
                                     data-unit="pieces"
                                     onclick="selectUnit(${index}, 'pieces', event)">PIECES</div>
                                <div class="unit-dropdown-option ${product.unit === 'bin' ? 'selected' : ''}"
                                     data-unit="bin"
                                     onclick="selectUnit(${index}, 'bin', event)">BIN</div>
                            </div>
                        </div>
                    </div>
                    <button class="delete-product-btn" onclick="deleteProduct(${index})" title="Remove">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            `).join('');
        }
    }

    // CRITICAL: Attach to window object for onclick handlers to work
    window.addProduct = function() {
        weeklyOrders[currentDay].push({ name: '', quantity: 1, unit: 'BOX' });
        displayProducts();
        saveWeeklyOrders();

        // Focus on the new input
        setTimeout(() => {
            const inputs = document.querySelectorAll('.product-name-input');
            if (inputs.length > 0) {
                inputs[inputs.length - 1].focus();
            }
        }, 100);
    }

    // CRITICAL: Attach to window object for onclick handlers to work
    window.deleteProduct = function(index) {
        // Add removing animation
        const productItems = document.querySelectorAll('.planner-product-item');
        if (productItems[index]) {
            productItems[index].classList.add('removing');

            // Wait for animation to complete
            setTimeout(() => {
                weeklyOrders[currentDay].splice(index, 1);
                displayProducts();
                saveWeeklyOrders();
            }, 300);
        } else {
            // Fallback if element not found
            weeklyOrders[currentDay].splice(index, 1);
            displayProducts();
            saveWeeklyOrders();
        }
    }

    // CRITICAL: Attach to window object for onclick handlers to work
    window.clearAllProducts = function() {
        if (weeklyOrders[currentDay].length > 0) {
            if (confirm(`Clear all products for ${currentDay}?`)) {
                // Animate all products out
                const productItems = document.querySelectorAll('.planner-product-item');
                productItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.add('removing');
                    }, index * 50); // Stagger the animations
                });

                // Clear after animations complete
                setTimeout(() => {
                    weeklyOrders[currentDay] = [];
                    displayProducts();
                    saveWeeklyOrders();
                }, 300 + (productItems.length * 50));
            }
        }
    }

    function updateProductName(index, value) {
        weeklyOrders[currentDay][index].name = value;
        saveWeeklyOrders();
    }

    function updateProductQuantity(index, value) {
        weeklyOrders[currentDay][index].quantity = parseInt(value) || 1;
        saveWeeklyOrders();
    }

    function updateProductUnit(index, value) {
        weeklyOrders[currentDay][index].unit = value;
        saveWeeklyOrders();
    }

    // Custom Unit Dropdown Functions
    // CRITICAL: Attach to window object for onclick handlers to work
    window.toggleUnitDropdown = function(index) {
        const dropdown = document.querySelector(`.unit-dropdown[data-index="${index}"]`);
        const allDropdowns = document.querySelectorAll('.unit-dropdown');

        // Close all other dropdowns
        allDropdowns.forEach(dd => {
            if (dd !== dropdown) {
                dd.classList.remove('active');
            }
        });

        // Toggle current dropdown
        dropdown.classList.toggle('active');

        // Position dropdown to avoid cutoff
        if (dropdown.classList.contains('active')) {
            const dropdownOptions = dropdown.querySelector('.unit-dropdown-options');
            const rect = dropdown.getBoundingClientRect();
            const modalRect = document.querySelector('.planner-container').getBoundingClientRect();

            // Check if dropdown would go beyond modal bottom
            if (rect.top + 200 > modalRect.bottom) {
                // Position dropdown above instead of below
                dropdownOptions.style.bottom = '100%';
                dropdownOptions.style.top = 'auto';
                dropdownOptions.style.marginBottom = '2px';
                dropdownOptions.style.marginTop = '0';
            } else {
                // Normal position below
                dropdownOptions.style.top = '100%';
                dropdownOptions.style.bottom = 'auto';
                dropdownOptions.style.marginTop = '2px';
                dropdownOptions.style.marginBottom = '0';
            }

            setTimeout(() => {
                document.addEventListener('click', closeDropdownsOnClickOutside);
            }, 10);
        }
    }

    // CRITICAL: Attach to window object for onclick handlers to work
    window.selectUnit = function(index, unit, event) {
        // Stop propagation to prevent modal from closing
        if (event) {
            event.stopPropagation();
        }

        // Update the data
        weeklyOrders[currentDay][index].unit = unit;
        saveWeeklyOrders();

        // Update the UI
        const dropdown = document.querySelector(`.unit-dropdown[data-index="${index}"]`);
        const valueSpan = dropdown.querySelector('.unit-value');
        valueSpan.textContent = unit.toUpperCase();

        // Update selected state
        dropdown.querySelectorAll('.unit-dropdown-option').forEach(option => {
            option.classList.remove('selected');
            if (option.dataset.unit === unit) {
                option.classList.add('selected');
            }
        });

        // Close dropdown with animation
        setTimeout(() => {
            dropdown.classList.remove('active');
        }, 150);
    }

    function closeDropdownsOnClickOutside(e) {
        if (!e.target.closest('.unit-dropdown')) {
            document.querySelectorAll('.unit-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
            document.removeEventListener('click', closeDropdownsOnClickOutside);
            // Don't stop propagation - let events flow naturally for input focus
        }
    }

    function saveWeeklyOrders() {
        localStorage.setItem('weeklyOrders', JSON.stringify(weeklyOrders));
    }

    function loadWeeklyOrders() {
        const saved = localStorage.getItem('weeklyOrders');
        if (saved) {
            try {
                weeklyOrders = JSON.parse(saved);
            } catch (e) {
                console.error('Error loading weekly orders:', e);
            }
        }
    }

    function clearWeeklyOrders() {
        // Clear all weekly orders
        weeklyOrders = {
            monday: [], tuesday: [], wednesday: [], thursday: [], friday: [], saturday: [], sunday: []
        };
        // Save the cleared state
        saveWeeklyOrders();
        // Update UI if planner is open
        if (document.getElementById('weeklyPlannerModal').style.display === 'block') {
            selectDay(currentDay);
        }
    }

    // USER DROPDOWN FUNCTIONS
    // CRITICAL: Attach to window object for onclick handlers to work
    window.toggleUserMenu = function() {
        const dropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('show');
    };

    // Close dropdown when clicking outside (but not inside planner modal)
    document.addEventListener('click', function(event) {
        const container = document.querySelector('.user-icon-container');
        const dropdown = document.getElementById('userDropdown');
        const plannerModal = document.getElementById('weeklyPlannerModal');
        const isInsidePlanner = plannerModal && plannerModal.contains(event.target);

        // Don't close if clicking inside planner modal
        if (container && !container.contains(event.target) && !isInsidePlanner) {
            dropdown.classList.remove('show');
        }
    });

    // Handle logout
    // CRITICAL: Attach to window object for onclick handlers to work
    window.handleLogout = function() {
        // Create a form and submit it for logout
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("buyer.logout") }}';

        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = '{{ csrf_token() }}';

        form.appendChild(token);
        document.body.appendChild(form);
        form.submit();
    }

    // ===== LOAD EXISTING QUOTES FROM DATABASE =====
    // DEPRECATED: This function is no longer used - Livewire handles quote loading
    function loadExistingQuotes() {
        const container = document.getElementById('quotesContainer');

        fetch('/api/buyer/quotes')
            .then(response => response.json())
            .then(data => {
                // Track valid quotes count
                let validQuotesCount = 0;

                if (data.quotes && data.quotes.length > 0) {
                    // Process each quote
                    data.quotes.forEach(quote => {
                        // Parse line items if string
                        let lineItems = quote.line_items;
                        if (typeof lineItems === 'string') {
                            try {
                                lineItems = JSON.parse(lineItems);
                            } catch(e) {
                                lineItems = [];
                            }
                        }

                        // Calculate remaining time (30 minutes from creation)
                        const createdAt = new Date(quote.created_at);
                        const expiryTime = new Date(createdAt.getTime() + 30 * 60 * 1000);
                        const now = new Date();
                        const remainingMinutes = Math.max(0, Math.floor((expiryTime - now) / 60000));

                        // CRITICAL: Only add non-expired quotes to UI
                        if (remainingMinutes > 0) {
                            validQuotesCount++;

                            const formattedQuote = {
                                id: quote.id,
                                vendor: quote.vendor?.business_name || 'Unknown Vendor',
                                vendor_id: quote.vendor_id || quote.vendor?.id || 1,  // Add vendor_id
                                vendorId: quote.vendor_id || quote.vendor?.id || 1,    // Also add vendorId for consistency
                                vendorBadge: quote.vendor?.verification_status === 'verified' ? 'Verified' : '',
                                vendorRating: quote.vendor?.rating || 4.5,
                                price: parseFloat(quote.total_amount),
                                delivery: quote.proposed_delivery_date || 'Next Day',
                                deliveryFee: parseFloat(quote.delivery_charge) || 0,
                                items: lineItems || [],
                                notes: quote.notes || '',
                                timestamp: createdAt,
                                validFor: remainingMinutes / 60, // Convert to hours for compatibility
                                status: 'pending', // Always pending since we filtered out expired
                                expiresAt: expiryTime.getTime() // Add the actual expiry time
                            };
                            addQuoteToUI(formattedQuote);
                        }
                    });

                    // Show/hide no quotes message based on valid quotes
                    const noQuotesMsg = document.getElementById('noQuotesMessage');
                    if (noQuotesMsg) {
                        noQuotesMsg.style.display = validQuotesCount > 0 ? 'none' : 'block';
                    }

                    // Update badge count with valid quotes only
                    const badge = document.getElementById('quoteBadge');
                    if (badge) {
                        badge.textContent = validQuotesCount > 0 ? `${validQuotesCount}` : '0';
                        badge.style.background = validQuotesCount > 0 ? '#10B981' : '#6B7280';
                    }

                    // Update stats
                    updateQuoteStats();
                } else {
                    // No quotes at all, show message
                    const noQuotesMsg = document.getElementById('noQuotesMessage');
                    if (noQuotesMsg) {
                        noQuotesMsg.style.display = 'block';
                    }
                }

                // Fade in the container after loading - make fully visible
                setTimeout(() => {
                    if (container) {
                        container.style.opacity = '1';
                        container.style.visibility = 'visible';
                    }
                }, 50); // Reduced delay for faster rendering
            })
            .catch(error => {
                console.error('Error loading existing quotes:', error);
                // Show container even on error with no quotes message
                if (container) {
                    container.style.opacity = '1';
                    container.style.visibility = 'visible';

                    const noQuotesMsg = document.getElementById('noQuotesMessage');
                    if (noQuotesMsg) {
                        noQuotesMsg.style.display = 'block';
                    }
                }
            });
    }

    // ===== WEBSOCKET REAL-TIME QUOTE INTEGRATION =====
    // Listen for real-time quotes from vendors
    document.addEventListener('DOMContentLoaded', function() {
        console.log('%c🏢 Sydney Markets B2B App Initialized', 'color: #10B981; font-size: 16px; font-weight: bold');
        console.log('Livewire initialized');

        @auth('buyer')
        const buyerId = {{ auth('buyer')->user()->id }};
        console.log('Buyer ID:', buyerId);

        // DISABLED: Duplicate loading - quotes are already loaded by Livewire
        // loadExistingQuotes(); // This was causing duplicate quotes on reload

        // Initialize quote modal system
        initializeQuoteModal();

        // REMOVED: Auto-refresh polling (unnecessary - WebSocket handles real-time updates)
        // Real-time quotes are delivered via Livewire WebSocket listeners:
        // - #[On('echo:buyers.all,QuoteReceived')] - Public channel for all buyers
        // - #[On('echo:quotes.buyer.{userId},QuoteReceived')] - Public channel for specific buyer
        // Auto-polling was causing quote panel re-renders and timer resets.

        // Function to initialize Echo when ready
        function initializeEcho() {
            if (typeof window.Echo !== 'undefined' && window.Echo) {
                console.log('✅ WebSocket connected for real-time vendor quotes');

                // Add comprehensive debugging
                window.wsDebug = function(message, data = {}) {
                    const timestamp = new Date().toLocaleTimeString();
                    console.log(`[${timestamp}] ${message}`, data);
                };

                // ========================================================
                // CRITICAL FIX: Explicit channel subscriptions for Livewire
                // ========================================================
                // Livewire's #[On('echo:...')] requires channels to be subscribed
                // explicitly in JavaScript for proper event routing
                // ========================================================

                console.log('📡 Subscribing to real-time quote channels...');

                // Subscribe to public broadcast channels
                // Channel 1: All buyers channel
                window.Echo.channel('buyers.all')
                    .subscribed(() => {
                        console.log('✅ Subscribed to buyers.all channel');
                    })
                    .listen('.QuoteReceived', (e) => {
                        console.error('🔥🔥🔥 ECHO: Quote received on buyers.all channel 🔥🔥🔥');
                        console.log('📦 Event data:', e);
                        console.log('🎯 Current buyer ID:', buyerId);
                        console.log('📊 Quote buyer ID:', e.quote?.buyer_id || e.buyerId);

                        // CRITICAL: The Livewire listener should handle this, but let's also
                        // manually trigger a refresh as a failsafe
                        if (e.quote?.buyer_id == buyerId || e.buyerId == buyerId) {
                            console.log('✅ Quote is for current buyer - triggering manual refresh');
                            setTimeout(() => {
                                const component = Livewire.find('{{ $_instance->getId() }}');
                                if (component) {
                                    console.log('🔥 Manually calling component.call(refreshQuotes)');
                                    component.call('refreshQuotes');
                                }
                            }, 500);
                        }
                    });

                // Channel 2: Specific buyer channel
                window.Echo.channel(`quotes.buyer.${buyerId}`)
                    .subscribed(() => {
                        console.log(`✅ Subscribed to quotes.buyer.${buyerId} channel`);
                    })
                    .listen('.QuoteReceived', (e) => {
                        console.error(`🔥🔥🔥 ECHO: Quote received on quotes.buyer.${buyerId} 🔥🔥🔥`);
                        console.log('📦 Event data:', e);

                        // CRITICAL: Manually trigger component refresh
                        console.log('🔥 Manually refreshing component from buyer-specific channel');
                        setTimeout(() => {
                            const component = Livewire.find('{{ $_instance->getId() }}');
                            if (component) {
                                console.log('✅ Calling component.call(refreshQuotes)');
                                component.call('refreshQuotes');
                            }
                        }, 500);
                    });

                console.log('✅ Channel subscriptions initialized - Livewire will handle events');

            /*
            // Commented out - Livewire handles this automatically
            const channel = Echo.private(`buyer.${buyerId}`);
            console.log('📡 Subscribing to PRIVATE channel: buyer.' + buyerId);

            channel
                .subscribed(() => {
                    console.log('✅ SUCCESSFULLY SUBSCRIBED to buyer.' + buyerId + ' channel');
                    wsDebug('Channel subscribed successfully');
                })
                .listen('.quote.received', (e) => {
                    console.log('🔔🔔🔔 QUOTE RECEIVED VIA WEBSOCKET!!! 🔔🔔🔔');
                    console.log('Full event data:', e);

                    // Log to debug panel if available
                    if (window.wsDebug) {
                        wsDebug('🔔 Quote received', {
                            quote_id: e.quote?.id,
                            vendor: e.vendor?.business_name,
                            rfq_id: e.quote?.rfq_id
                        });
                    }

                    try {
                        // Get the Livewire component instance using Livewire 3 syntax
                        const wireElement = document.querySelector('[wire\\:id]');
                        if (!wireElement) {
                            console.error('Could not find Livewire component element');
                            return;
                        }

                        const componentId = wireElement.getAttribute('wire:id');
                        const component = window.Livewire.find(componentId);

                        if (component) {
                            console.log('📡 Calling Livewire onQuoteReceived method...');

                            // Use Livewire 3's call method
                            component.call('onQuoteReceived', e).then(() => {
                                console.log('✅ Quote update processed by Livewire');

                                if (window.wsDebug) {
                                    wsDebug('✅ Livewire processed quote');
                                }

                                // Force a complete component refresh using multiple methods
                                // This ensures the UI updates immediately

                                // Method 1: Direct refresh
                                component.$refresh();

                                // Method 2: Emit refresh event
                                Livewire.emit('$refresh');

                                // Method 3: Call loadQuotes directly to ensure data is fresh
                                setTimeout(() => {
                                    component.call('loadQuotes');
                                }, 100);

                                // Also dispatch a browser event for any listeners
                                window.dispatchEvent(new CustomEvent('quote-received', {
                                    detail: e
                                }));

                                // Optional: Add visual feedback for new quote
                                setTimeout(() => {
                                    const quoteCards = document.querySelectorAll('.quote-item');
                                    if (quoteCards.length > 0) {
                                        // Find the newest quote by ID if possible
                                        let targetCard = quoteCards[0];
                                        if (e.quote && e.quote.id) {
                                            const cardById = document.querySelector(`[data-quote-id="${e.quote.id}"]`);
                                            if (cardById) {
                                                targetCard = cardById;
                                            }
                                        }

                                        // Apply highlight animation
                                        if (targetCard) {
                                            targetCard.classList.add('quote-card-highlight');
                                            setTimeout(() => {
                                                targetCard.classList.remove('quote-card-highlight');
                                            }, 2000);
                                        }
                                    }
                                }, 500); // Increased delay to ensure DOM is updated
                            }).catch(error => {
                                console.error('❌ Error calling Livewire method:', error);

                                if (window.wsDebug) {
                                    wsDebug('❌ Error processing quote', { error: error.message });
                                }

                                // Fallback: Force a refresh anyway
                                try {
                                    component.$refresh();
                                } catch (refreshError) {
                                    console.error('Could not refresh component:', refreshError);
                                    // Last resort: reload the page
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1000);
                                }
                            });
                        } else {
                            console.error('Could not find Livewire component with ID:', componentId);
                            // Fallback: reload the page
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                    } catch (error) {
                        console.error('❌ Error processing WebSocket event:', error);
                        if (window.wsDebug) {
                            wsDebug('❌ WebSocket error', { error: error.message });
                        }
                        // Last resort: reload page after delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                })
                .error((error) => {
                    console.error('❌ WebSocket channel subscription error:', error);

                    if (window.wsDebug) {
                        wsDebug('❌ Channel error', { error: error.message });
                    }

                    console.log('Attempting to reconnect in 5 seconds...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 5000);
                });

            // WebSocket connection established for real-time updates
            console.log('✅ Listening for real-time quotes on buyer.' + buyerId + ' channel');

            // Add manual test function for debugging
            window.testWebSocket = function() {
                console.log('=== WEBSOCKET TEST STARTED ===');
                console.log('1. Echo available:', typeof Echo !== 'undefined');
                console.log('2. Buyer ID:', buyerId);
                console.log('3. Channel name:', 'quotes.buyer.' + buyerId);

                // Try to manually listen to the PUBLIC channel
                const testChannel = Echo.channel('quotes.buyer.' + buyerId);
                console.log('4. Test channel created:', testChannel);

                // Auth check removed - using public channels
                console.log('5. Using public channels - no auth needed');

                // Check WebSocket connection
                if (Echo.connector && Echo.connector.pusher) {
                    console.log('6. Pusher state:', Echo.connector.pusher.connection.state);
                    console.log('7. Socket ID:', Echo.socketId());
                }

                console.log('=== TEST COMPLETE ===');
                console.log('Run this test when vendor sends a quote to see if events are received');
            };

            */

            // Listen for real-time chat messages
            // Subscribe to message channels for active quotes (PRIVATE - requires auth)
            window.subscribeToQuoteMessages = function(quoteId) {
                console.log('🔔 Subscribing to quote messages channel:', `quote.${quoteId}.messages`);

                Echo.private(`quote.${quoteId}.messages`)
                    .listen('.message.sent', (e) => {
                        console.log('📨 New message received via WebSocket:', e);

                        // Only show if chat is open for this quote
                        if (window.currentModalChat && window.currentModalChat.quoteId === e.quote_id) {
                            const messagesContainer = document.getElementById('chatMessages');
                            if (messagesContainer && e.sender_type !== 'buyer') {
                                // Add incoming message to chat
                                const messageHTML = `
                                    <div style="margin-bottom: 12px; display: flex; justify-content: flex-start;">
                                        <div style="
                                            background: white;
                                            color: #374151;
                                            padding: 10px 14px;
                                            border-radius: 16px 16px 16px 4px;
                                            max-width: 70%;
                                            font-size: 14px;
                                            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                                        ">
                                            ${e.message}
                                            <div style="font-size: 11px; color: #6B7280; margin-top: 4px;">
                                                ${e.sender_name || 'Vendor'} • ${new Date(e.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}
                                            </div>
                                        </div>
                                    </div>
                                `;
                                messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;

                                // Play notification sound
                                const audio = new Audio('/sounds/notification.mp3');
                                audio.play().catch(err => console.log('Could not play sound'));
                            }
                        }

                        // Show notification if chat is not open
                        if (!window.currentModalChat || window.currentModalChat.quoteId !== e.quote_id) {
                            showToast('info', `New message from ${e.sender_name || 'Vendor'}`);
                        }
                    });
            };

            console.log('✅ WebSocket connected for real-time quotes and messaging (buyer channel)');
            } else {
                console.warn('⚠️ Laravel Echo not initialized yet. Retrying...');
                return false; // Return false to indicate Echo is not ready
            }
            return true; // Return true to indicate Echo is ready
        }

        // Try to initialize Echo immediately
        if (!initializeEcho()) {
            // If Echo is not ready, retry every 500ms for up to 10 seconds
            let retryCount = 0;
            const maxRetries = 20;
            const retryInterval = setInterval(() => {
                retryCount++;
                if (initializeEcho() || retryCount >= maxRetries) {
                    clearInterval(retryInterval);
                    if (retryCount >= maxRetries) {
                        console.error('❌ Failed to initialize Echo after ' + maxRetries + ' attempts');
                    }
                }
            }, 500);
        }

        @else
        console.log('User not authenticated - WebSocket disabled');
        @endauth

        // All systems initialized
        console.log('DOM Ready');
    });

    // Toast notification helper with enhanced features
    function showToast(type, message, title = null, duration = 5000) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        // Determine background color based on type
        let bgColor = '#10b981'; // success/green
        if (type === 'error') bgColor = '#ef4444';
        else if (type === 'info') bgColor = '#3b82f6';
        else if (type === 'warning') bgColor = '#f59e0b';

        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${bgColor};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 9999;
            animation: slideIn 0.3s ease-out;
            font-size: 14px;
            font-weight: 500;
            max-width: 350px;
        `;

        // Build content with optional title
        let content = '';
        if (title) {
            content = `<div style="font-weight: 600; margin-bottom: 4px;">${title}</div>`;
        }
        content += `<div>${message}</div>`;
        toast.innerHTML = content;

        // Add to body
        document.body.appendChild(toast);

        // Remove after specified duration
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Listen for Livewire toast events
    window.addEventListener('show-toast', event => {
        const { type = 'success', message, title = null, duration = 5000 } = event.detail[0] || event.detail;
        showToast(type, message, title, duration);
    });

    // Listen for notification sound events
    window.addEventListener('play-notification-sound', event => {
        try {
            const audio = new Audio('/sounds/notification.mp3');
            audio.volume = 0.5; // Set to 50% volume
            audio.play().catch(err => {
                console.log('Could not play notification sound:', err);
            });
        } catch (e) {
            console.log('Error playing sound:', e);
        }
    });

    // Listen for quote data updates to ensure UI is refreshed
    window.addEventListener('quote-data-updated', event => {
        console.log('📊 Quote data updated:', event.detail);
        const { count, quote_id } = event.detail[0] || event.detail;

        // Update the active quotes count badge if it exists
        const countBadge = document.querySelector('.active-quotes-count');
        if (countBadge) {
            countBadge.textContent = count || '0';
            countBadge.classList.add('pulse-animation');
            setTimeout(() => {
                countBadge.classList.remove('pulse-animation');
            }, 1000);
        }

        // If a specific quote ID is provided, highlight it
        if (quote_id) {
            setTimeout(() => {
                const quoteCard = document.querySelector(`[data-quote-id="${quote_id}"]`);
                if (quoteCard) {
                    quoteCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    quoteCard.classList.add('quote-card-highlight');
                    setTimeout(() => {
                        quoteCard.classList.remove('quote-card-highlight');
                    }, 2000);
                }
            }, 300);
        }
    });

    // Add CSS animation for toast and badge pulse
    if (!document.querySelector('#websocket-styles')) {
        const style = document.createElement('style');
        style.id = 'websocket-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .pulse-animation {
                animation: subtlePulse 1s cubic-bezier(0.4, 0, 0.6, 1);
            }
            /* NO SCALE - User's strict rule */
            @keyframes subtlePulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            .quote-card-highlight {
                animation: quoteHighlight 0.5s ease-out;
            }
            /* CRITICAL: White background with green border glow - NO green background, NO scale */
            @keyframes quoteHighlight {
                0% {
                    background-color: var(--neuro-bg);
                    box-shadow: inset 3px 3px 6px #B8BEC7,
                                inset -3px -3px 6px #FFFFFF,
                                0 0 0 2px rgba(16, 185, 129, 0.4);
                }
                50% {
                    background-color: var(--neuro-bg);
                    box-shadow: inset 3px 3px 6px #B8BEC7,
                                inset -3px -3px 6px #FFFFFF,
                                0 0 0 3px rgba(16, 185, 129, 0.6);
                }
                100% {
                    background-color: var(--neuro-bg);
                    box-shadow: inset 3px 3px 6px #B8BEC7,
                                inset -3px -3px 6px #FFFFFF;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // WebSocket debug function stub (for compatibility)
    window.wsDebug = window.wsDebug || function() {};

    // Test function to simulate vendor quotes
    async function triggerTestVendorQuotes(rfqId) {
        if (!rfqId) {
            // Get the last RFQ ID from console or use a default
            rfqId = 2; // Use the RFQ that was successfully created
        }

        try {
            const response = await fetch(`/api/vendor/quote/test-submit/${rfqId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                console.log('✅ Test quote triggered successfully!', data);
                showToast(`Test quote from ${data.quote.vendor_name} for $${data.quote.total_amount}`, 'success');
            } else {
                console.error('❌ Failed to trigger test quote:', data.message);
                showToast('Failed to trigger test quote', 'error');
            }
        } catch (error) {
            console.error('❌ Error triggering test quote:', error);
            showToast('Error triggering test quote', 'error');
        }
    }

    // Make function globally accessible for testing
    window.triggerTestVendorQuotes = triggerTestVendorQuotes;
    console.log('💡 To test vendor quotes, run: triggerTestVendorQuotes(2)');
    </script>

<!-- Messaging System Styles - Matching Vendor Dashboard -->
<style>
    /* Messaging Icon Button */
    .messaging-icon-btn {
        position: relative;
        background: #E8EBF0;
        border: none;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 3px 3px 6px #c5c8cc, -3px -3px 6px #ffffff;
        transition: all 0.2s ease;
    }

    .messaging-icon-btn:hover {
        box-shadow: inset 2px 2px 4px #c5c8cc, inset -2px -2px 4px #ffffff;
    }

    .message-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: linear-gradient(135deg, #10B981, #059669);
        color: white;
        font-size: 10px;
        font-weight: 600;
        min-width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2px;
        box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
    }

    /* Messages Overlay - Neumorphic Integration */
    .messages-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #E8EBF0;
        z-index: 100;
        border-radius: 32px;
        display: flex;
        flex-direction: column;
        animation: fadeIn 0.2s ease-out;
        box-shadow: inset 3px 3px 8px #c5c8cc,
                    inset -3px -3px 8px #ffffff,
                    0 2px 4px rgba(197, 200, 204, 0.1);
        overflow: hidden;
    }

    .messages-container {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 16px;
    }

    .messages-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid rgba(197, 200, 204, 0.2);
        margin-bottom: 16px;
    }

    .messages-header h4 {
        font-size: 16px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    .close-messages-btn {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #E8EBF0;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 2px 2px 4px #c5c8cc, -2px -2px 4px #ffffff;
        transition: all 0.2s ease;
    }

    .close-messages-btn:hover {
        box-shadow: inset 2px 2px 4px #c5c8cc, inset -2px -2px 4px #ffffff;
    }

    .close-messages-btn svg {
        width: 14px;
        height: 14px;
        color: var(--text-tertiary);
    }

    .messages-list {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 4px;
    }

    .message-box {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: #E8EBF0;
        border-radius: 12px;
        box-shadow: 2px 2px 4px #c5c8cc, -2px -2px 4px #ffffff;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .message-box:hover {
        box-shadow: inset 1px 1px 2px #c5c8cc, inset -1px -1px 2px #ffffff;
        transform: translateY(-1px);
    }

    .message-box.unread {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.05));
    }

    .message-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #10B981, #059669);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 14px;
        flex-shrink: 0;
    }

    .message-content {
        flex: 1;
        min-width: 0;
    }

    .message-header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }

    .message-buyer-name {
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .message-time {
        font-size: 10px;
        color: var(--text-tertiary);
    }

    .message-preview {
        font-size: 12px;
        color: var(--text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .unread-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10B981, #059669);
        flex-shrink: 0;
    }

    .no-messages {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--text-tertiary);
    }

    .no-messages p {
        font-size: 13px;
        margin: 0;
    }

    /* Chat Messenger Modal */
    .chat-messenger-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(232, 235, 240, 0.95);
        backdrop-filter: blur(20px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        animation: fadeIn 0.2s ease-out;
    }

    .chat-messenger-container {
        width: 90%;
        max-width: 500px;
        height: 70vh;
        max-height: 600px;
        background: #E8EBF0;
        border-radius: 24px;
        box-shadow: 20px 20px 60px #c5c8cc, -20px -20px 60px #ffffff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: slideUp 0.3s ease-out;
    }

    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: linear-gradient(135deg, rgba(232, 235, 240, 0.95), rgba(232, 235, 240, 0.85));
        border-bottom: 1px solid rgba(255, 255, 255, 0.5);
    }

    .chat-buyer-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chat-avatar {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: linear-gradient(135deg, #10B981, #059669);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 16px;
    }

    .chat-buyer-details h4 {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0 0 2px 0;
    }

    .chat-buyer-status {
        font-size: 11px;
        color: var(--neuro-accent);
        font-weight: 500;
    }

    .chat-close-btn {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #E8EBF0;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 3px 3px 6px #c5c8cc, -3px -3px 6px #ffffff;
        transition: all 0.2s ease;
    }

    .chat-close-btn:hover {
        box-shadow: inset 2px 2px 4px #c5c8cc, inset -2px -2px 4px #ffffff;
    }

    .chat-close-btn svg {
        width: 16px;
        height: 16px;
        color: var(--text-tertiary);
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .chat-message {
        display: flex;
        animation: slideUp 0.2s ease-out;
    }

    .chat-message.sent {
        justify-content: flex-end;
    }

    .chat-message.received {
        justify-content: flex-start;
    }

    .chat-message-bubble {
        max-width: 70%;
        padding: 10px 14px;
        border-radius: 12px;
        background: #E8EBF0;
        box-shadow: 2px 2px 4px #c5c8cc, -2px -2px 4px #ffffff;
    }

    .chat-message.sent .chat-message-bubble {
        background: linear-gradient(135deg, #10B981, #059669);
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .chat-message-bubble p {
        margin: 0 0 4px 0;
        font-size: 13px;
        line-height: 1.4;
    }

    .chat-message-time {
        font-size: 10px;
        opacity: 0.7;
    }

    .chat-input-container {
        display: flex;
        gap: 10px;
        padding: 16px 20px;
        background: linear-gradient(135deg, rgba(232, 235, 240, 0.95), rgba(232, 235, 240, 0.85));
        border-top: 1px solid rgba(255, 255, 255, 0.5);
    }

    .chat-input {
        flex: 1;
        padding: 10px 14px;
        background: #E8EBF0;
        border: none;
        border-radius: 12px;
        font-size: 13px;
        color: var(--text-primary);
        box-shadow: inset 2px 2px 4px #c5c8cc, inset -2px -2px 4px #ffffff;
        outline: none;
    }

    .chat-input:focus {
        box-shadow: inset 3px 3px 6px #c5c8cc, inset -3px -3px 6px #ffffff;
    }

    .chat-send-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        background: linear-gradient(135deg, #10B981, #059669);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        transition: all 0.2s ease;
    }

    .chat-send-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    .chat-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .chat-send-btn svg {
        width: 18px;
        height: 18px;
    }
</style>

<!-- Lazy-loaded Messenger Component -->
@if($showMessenger)
    @livewire('messaging.buyer-messenger')
@endif

</div>
</div>
</div>