<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sydney Markets')</title>

    <!-- Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Compiled Assets -->
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    <link rel="stylesheet" href="{{ asset('build/assets/app-UbOWPfv8.css') }}">
    {{-- Temporarily disabled - using CDN Echo instead --}}
    {{-- <script src="{{ asset('build/assets/app-CNmq6n8Y.js') }}" defer></script> --}}

    <!-- Page Specific Styles -->
    @stack('styles')

    <!-- Livewire Styles -->
    @livewireStyles

    <style>
        :root {
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --surface: #fdfdf4;
            --border: rgba(163, 177, 198, 0.2);
        }
        
        .dashboard-container {
            height: 100vh;
            width: 100vw;
            position: relative;
            background: var(--surface);
        }
        
        .floating-user {
            position: absolute;
            top: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
            z-index: 1000;
        }
        
        .surface-icon {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .surface-icon:hover {
            background: #f4f4ec;
            border-color: #cbd5e1;
        }
        
        .surface-icon svg {
            width: 20px;
            height: 20px;
            stroke-width: 1.5;
        }
        
        .notification-badge,
        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: #fdfdf4;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
            line-height: 1.2;
        }
        
        .user-menu-container {
            position: relative;
        }
        
        .user-icon-button {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .user-icon-button:hover {
            background: #f4f4ec;
            border-color: #cbd5e1;
        }
        
        .user-icon-button svg {
            width: 20px;
            height: 20px;
        }
        
        .user-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 240px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            opacity: 1;
            transform: translateY(0);
            transition: all 0.15s ease-out;
        }
        
        .user-dropdown.hiding {
            opacity: 0;
            transform: translateY(-8px);
        }
        
        .dropdown-inner {
            padding: 8px 0;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--text-primary);
            text-decoration: none;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.1s ease;
        }
        
        .logout-btn:hover {
            background: #f4f4ec;
        }
        
        .logout-btn svg {
            width: 16px;
            height: 16px;
            stroke-width: 1.5;
            flex-shrink: 0;
        }
        
        .logout-btn span {
            font-weight: 500;
        }
        
        .content-wrapper {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <!-- Main Dashboard Container - Full Screen -->
    <div class="dashboard-container">
        
        <!-- Floating User Controls (Top Right) -->
        <div class="floating-user">
            <!-- Home Icon - Direct on Surface -->
            <a href="{{ url('/') }}" class="surface-icon home-icon" title="Home">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </a>
            
            <!-- Notifications Icon - Direct on Surface -->
            <button class="surface-icon notification-icon" title="Notifications" onclick="showNotifications()">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                @if(session('unread_notifications', 0) > 0)
                    <span class="notification-badge">{{ session('unread_notifications', 0) }}</span>
                @endif
            </button>
            
            <!-- Cart Icon - Direct on Surface -->
            <a href="/buyer/cart" class="surface-icon cart-icon" title="Shopping Cart">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                @if(session('cart_count', 0) > 0)
                    <span class="cart-badge">{{ session('cart_count', 0) }}</span>
                @endif
            </a>
            
            <!-- User Icon - Clean Surface Integration -->
            <div class="user-menu-container">
                <button class="user-icon-button" onclick="toggleUserMenu()" type="button">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </button>
                
                <!-- Simple Dropdown Menu -->
                <div id="userDropdown" class="user-dropdown" style="display: none;">
                    <div class="dropdown-inner">
                        @auth('buyer')
                        <div style="padding: 10px 14px; border-bottom: 1px solid rgba(163, 177, 198, 0.2);">
                            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 2px;">Logged in as</div>
                            <div style="font-size: 14px; font-weight: 600; color: var(--text-primary);">
                                {{ Auth::guard('buyer')->user()->contact_name ?? Auth::guard('buyer')->user()->first_name . ' ' . Auth::guard('buyer')->user()->last_name ?? 'Buyer' }}
                            </div>
                            <div style="font-size: 11px; color: var(--text-secondary);">{{ Auth::guard('buyer')->user()->email }}</div>
                        </div>
                        @else
                        <div style="padding: 10px 14px; border-bottom: 1px solid rgba(163, 177, 198, 0.2);">
                            <div style="font-size: 14px; font-weight: 600; color: var(--text-primary);">Not logged in</div>
                        </div>
                        @endauth
                        @auth('buyer')
                        <a href="{{ route('buyer.profile') }}" class="logout-btn" style="border-bottom: 1px solid rgba(163, 177, 198, 0.1);">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>Profile</span>
                        </a>
                        <a href="{{ route('buyer.settings.index') }}" class="logout-btn" style="border-bottom: 1px solid rgba(163, 177, 198, 0.1);">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Settings</span>
                        </a>
                        <form id="logout-form" action="{{ route('buyer.logout') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="logout-btn">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span>Logout</span>
                            </button>
                        </form>
                        @else
                        <a href="{{ route('buyer.login') }}" class="logout-btn" style="border-bottom: 1px solid rgba(163, 177, 198, 0.1);">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span>Login</span>
                        </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation removed for cleaner interface -->
        
        <!-- Main Content Area - Full Screen -->
        <div class="content-wrapper">
            @yield('content')
        </div>
        
    </div>
    
    <!-- Scripts -->
    <script>
        // Placeholder notification function
        function showNotifications() {
            alert('Notifications feature coming soon');
        }
    </script>
    
    <!-- Enhanced User Menu and Session Management -->
    <script>
        let userMenuOpen = false;
        
        // Check authentication status on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user is authenticated
            @auth('buyer')
                console.log('User authenticated:', '{{ Auth::guard("buyer")->user()->email }}');
                
                // Set up session management
                setupSessionManagement();
                
                // Update last activity timestamp periodically
                setInterval(updateLastActivity, 5 * 60 * 1000); // Every 5 minutes
            @else
                console.log('User not authenticated');
            @endauth
        });
        
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            
            if (!userMenuOpen) {
                // Open dropdown
                dropdown.style.display = 'block';
                dropdown.classList.remove('hiding');
                userMenuOpen = true;
                
                // Add click outside listener
                setTimeout(() => {
                    document.addEventListener('click', closeUserMenu);
                }, 100);
            } else {
                // Close dropdown
                closeUserDropdown();
            }
        }
        
        function closeUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.add('hiding');
            
            setTimeout(() => {
                dropdown.style.display = 'none';
                userMenuOpen = false;
            }, 150);
            
            document.removeEventListener('click', closeUserMenu);
        }
        
        function closeUserMenu(e) {
            const container = document.querySelector('.user-menu-container');
            if (!container.contains(e.target)) {
                closeUserDropdown();
            }
        }
        
        // Enhanced logout functionality
        function handleLogout(event) {
            event.preventDefault();
            
            if (!confirm('Are you sure you want to logout?')) {
                return false;
            }
            
            // Show loading state
            const logoutButton = event.target;
            const originalText = logoutButton.innerHTML;
            logoutButton.innerHTML = '<span>Logging out...</span>';
            logoutButton.disabled = true;
            
            // Submit the form
            const form = document.getElementById('logout-form');
            
            // Use fetch for better error handling
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new FormData(form)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear local storage
                    localStorage.clear();
                    sessionStorage.clear();
                    
                    // Show success message
                    alert('Successfully logged out');
                    
                    // Redirect to login page
                    window.location.href = data.redirect || '{{ route("buyer.login") }}';
                } else {
                    throw new Error(data.message || 'Logout failed');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                
                // Fallback: submit form normally
                form.submit();
            })
            .finally(() => {
                // Restore button state
                logoutButton.innerHTML = originalText;
                logoutButton.disabled = false;
            });
        }
        
        // Session management functions
        function setupSessionManagement() {
            // Handle session expiration warnings
            const sessionTimeout = {{ config('session.lifetime', 120) }} * 60 * 1000; // Convert to milliseconds
            const warningTime = sessionTimeout - (10 * 60 * 1000); // Warn 10 minutes before expiration
            
            setTimeout(() => {
                if (confirm('Your session is about to expire. Do you want to stay logged in?')) {
                    // Refresh session by making a simple request
                    fetch('{{ route("buyer.dashboard") }}', {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                }
            }, warningTime);
        }
        
        function updateLastActivity() {
            // Update last activity timestamp
            if (navigator.onLine) {
                fetch('/buyer/update-activity', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).catch(error => {
                    console.log('Activity update failed:', error);
                });
            }
        }
        
        // Bind logout handler to form
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForm = document.getElementById('logout-form');
            if (logoutForm) {
                logoutForm.addEventListener('submit', handleLogout);
            }
        });
    </script>

    <!-- Pusher must be loaded before Laravel Echo -->
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

    <script>
        // Initialize Laravel Echo for real-time features BEFORE Livewire
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ config("broadcasting.connections.reverb.key") }}',
            wsHost: '{{ config("broadcasting.connections.reverb.options.host") }}',
            wsPort: {{ config("broadcasting.connections.reverb.options.port") }},
            wssPort: {{ config("broadcasting.connections.reverb.options.port") }},
            forceTLS: {{ config("broadcasting.connections.reverb.options.useTLS") ? 'true' : 'false' }},
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
        });

        console.log('🔊 Laravel Echo initialized for buyer dashboard');

        // Connection event handlers for debugging
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('✅ WebSocket connected successfully!');
        });

        window.Echo.connector.pusher.connection.bind('error', (err) => {
            console.error('❌ WebSocket error:', err);
        });

        window.Echo.connector.pusher.connection.bind('unavailable', () => {
            console.warn('⚠️  WebSocket unavailable - will auto-retry');
        });

        window.Echo.connector.pusher.connection.bind('failed', () => {
            console.error('❌ WebSocket connection failed');
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.warn('⚠️  WebSocket disconnected');
        });
    </script>

    <!-- Livewire Scripts - MUST come AFTER Echo initialization -->
    @livewireScripts

    <!-- Page Specific Scripts -->
    @stack('scripts')
</body>
</html>