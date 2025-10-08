<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sydney Markets B2B - Fresh Wholesale Marketplace')</title>

    <!-- Premium Typography: Montserrat & Lato Font Families -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

    <!-- Smart Screen Resolution Adaptation System -->
    <link rel="stylesheet" href="{{ asset('assets/css/global/screen-resolution-adapter.css') }}">
    
    <!-- Professional Global Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/global/global-professional.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/global/modern-design-system.css') }}">
    
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
                }
            }
        }
    </script>
    
    <style>
        /* Premium Typography System */
        * {
            font-family: 'Lato', system-ui, -apple-system, sans-serif;
            font-weight: 400;
        }
        
        h1, h2, h3, h4, h5, h6,
        .font-heading,
        .font-montserrat {
            font-family: 'Montserrat', system-ui, -apple-system, sans-serif !important;
            font-weight: 600;
            letter-spacing: -0.02em;
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased">
    <!-- Navigation Header -->
    <nav class="bg-[#fdfdf4] shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <!-- Logo and Brand -->
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Sydney Markets</h1>
                            <p class="text-xs text-gray-500">B2B Wholesale Marketplace</p>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ url('/') }}" class="text-gray-700 hover:text-green-600 font-medium transition-colors">Home</a>
                    <a href="{{ route('marketplace') }}" class="text-gray-700 hover:text-green-600 font-medium transition-colors">Marketplace</a>
                    <a href="{{ route('about') }}" class="text-gray-700 hover:text-green-600 font-medium transition-colors">About</a>
                    <a href="{{ route('contact') }}" class="text-gray-700 hover:text-green-600 font-medium transition-colors">Contact</a>
                </div>

                <!-- Auth Links -->
                <div class="flex items-center space-x-4">
                    @guest
                        <div class="hidden md:flex items-center space-x-4">
                            <div class="relative group">
                                <button class="px-4 py-2 text-gray-700 hover:text-green-600 font-medium transition-colors flex items-center">
                                    Sign In
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div class="absolute right-0 mt-2 w-48 bg-[#fdfdf4] rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                    <a href="{{ route('buyer.login') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600">
                                        <div class="font-medium">Buyer Login</div>
                                        <div class="text-xs text-gray-500">Purchase wholesale goods</div>
                                    </a>
                                    <a href="{{ route('vendor.login') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600">
                                        <div class="font-medium">Vendor Login</div>
                                        <div class="text-xs text-gray-500">Sell your products</div>
                                    </a>
                                    <a href="{{ route('admin.login') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-purple-50 hover:text-purple-600">
                                        <div class="font-medium">Admin Login</div>
                                        <div class="text-xs text-gray-500">System administration</div>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="relative group">
                                <button class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-medium rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-200 flex items-center">
                                    Register
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div class="absolute right-0 mt-2 w-48 bg-[#fdfdf4] rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                    <a href="{{ route('buyer.register') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600">
                                        <div class="font-medium">Register as Buyer</div>
                                        <div class="text-xs text-gray-500">Start purchasing</div>
                                    </a>
                                    <a href="{{ route('vendor.register') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-green-50 hover:text-green-600">
                                        <div class="font-medium">Register as Vendor</div>
                                        <div class="text-xs text-gray-500">Start selling</div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">Welcome, {{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-gray-700 hover:text-red-600 font-medium">
                                    Logout
                                </button>
                            </form>
                        </div>
                    @endguest

                    <!-- Mobile menu button -->
                    <button class="md:hidden p-2 rounded-lg text-gray-700 hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold">Sydney Markets</h3>
                            <p class="text-xs text-gray-400">B2B Wholesale Platform</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-400">
                        Connecting wholesale buyers and vendors across Sydney with fresh produce and quality goods.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('marketplace') }}" class="text-gray-400 hover:text-white transition-colors">Browse Products</a></li>
                        <li><a href="{{ route('vendor.register') }}" class="text-gray-400 hover:text-white transition-colors">Become a Vendor</a></li>
                        <li><a href="{{ route('buyer.register') }}" class="text-gray-400 hover:text-white transition-colors">Register as Buyer</a></li>
                        <li><a href="{{ route('about') }}" class="text-gray-400 hover:text-white transition-colors">About Us</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="{{ route('contact') }}" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 class="font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            support@sydneymarkets.com.au
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            1300 MARKETS
                        </li>
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Sydney Markets<br>
                            Flemington, NSW 2140
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} Sydney Markets B2B. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Smart Resolution Detection System -->
    <script src="{{ asset('assets/js/global/smart-resolution.js') }}"></script>
    
    @stack('scripts')
</body>
</html>