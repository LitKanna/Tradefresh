<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Vendor Portal') - Sydney Markets B2B</title>

    <!-- Buyer Dashboard CSS Files for Consistent Theme (PROVEN WORKING) -->
    <link href="{{ asset('assets/css/buyer/dashboard/colors.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/buyer/dashboard/layout.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/buyer/dashboard/components.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/buyer/dashboard/typography.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/buyer/dashboard/spacing.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/buyer/dashboard/user-dropdown.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/buyer/dashboard/quotes-system.css') }}" rel="stylesheet">

    <!-- Neumorphic Enhancement for RFQ Panel (Non-breaking additions only) -->
    <link href="{{ asset('vendor-dashboard/css/neumorphic-rfq-panel.css') }}" rel="stylesheet">

    <!-- Fluid Responsive Layout - Auto-adapts to ANY screen -->
    <link href="{{ asset('vendor-dashboard/css/vendor-fluid-layout.css') }}" rel="stylesheet">

    <!-- Premium Typography: Montserrat & Lato Font Families -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Montserrat for headings - strong and professional -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Lato for body text - clean and readable -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Lato', 'system-ui', '-apple-system', 'sans-serif'],
                        'heading': ['Montserrat', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        green: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'gradient': 'gradient 15s ease infinite',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'fade-in': 'fadeIn 0.5s ease-out',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        gradient: {
                            '0%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' },
                            '100%': { backgroundPosition: '0% 50%' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(10px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js included with Livewire 3 - no need to load separately -->

    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    /* Critical: No-scroll viewport constraint */
    html, body {
        height: 100vh;
        overflow: hidden !important;
        margin: 0;
        padding: 0;
    }
    
    /* Premium Typography System */
    * {
        font-family: 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 400;
    }
    
    h1, h2, h3, h4, h5, h6,
    .font-heading,
    .font-montserrat {
        font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
        font-weight: 600;
        letter-spacing: -0.02em;
    }
    
    #app {
        height: 100vh;
        overflow: hidden;
    }
    </style>
    
    @livewireStyles
    @stack('styles')
</head>
<body class="font-sans antialiased" style="background-color: var(--bg-primary);">
    <div id="app">
        @yield('content')
    </div>

    @livewireScripts

    <!-- Pusher must be loaded after Livewire -->
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>

    <script>
        // Initialize Laravel Echo after Livewire is loaded
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'pusher', // Reverb uses Pusher protocol
            key: '{{ config("broadcasting.connections.reverb.key") }}',
            cluster: '', // Required by Pusher, empty for Reverb
            wsHost: '{{ config("broadcasting.connections.reverb.options.host") }}',
            wsPort: {{ config("broadcasting.connections.reverb.options.port") }},
            wssPort: {{ config("broadcasting.connections.reverb.options.port") }},
            forceTLS: {{ config("broadcasting.connections.reverb.options.useTLS") ? 'true' : 'false' }},
            disableStats: true,
            enabledTransports: ['ws', 'wss'],
        });

        console.log('🔊 Laravel Echo initialized for vendor dashboard');

        // Connection event handlers
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

    <!-- Smart Resolution Adapter - Auto-scales to any screen -->
    <script src="{{ asset('assets/js/global/smart-resolution.js') }}"></script>

    @stack('scripts')
</body>
</html>