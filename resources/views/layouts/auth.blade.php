<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="@yield('meta_description', 'Secure authentication portal for buyers. Login or register to access exclusive features and manage your account.')">
    <meta name="keywords" content="@yield('meta_keywords', 'buyer login, buyer registration, secure authentication, account access')">
    <meta name="author" content="{{ config('app.name', 'Laravel') }}">
    <meta property="og:title" content="@yield('page_title', 'Authentication') - {{ config('app.name', 'Laravel') }}">
    <meta property="og:description" content="@yield('meta_description', 'Secure authentication portal for buyers')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    
    <title>@yield('page_title', 'Authentication') - {{ config('app.name', 'Laravel') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Premium Typography: Montserrat & Lato Font Families -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Animate CSS Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <!-- Smart Screen Resolution Adaptation System -->
    <link rel="stylesheet" href="{{ asset('assets/css/global/screen-resolution-adapter.css') }}">
    
    <!-- Custom Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#7C3AED',
                        accent: '#EC4899',
                    },
                    fontFamily: {
                        sans: ['Lato', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
                        heading: ['Montserrat', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    animation: {
                        'gradient': 'gradient 8s ease infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        gradient: {
                            '0%, 100%': {
                                'background-size': '200% 200%',
                                'background-position': 'left center'
                            },
                            '50%': {
                                'background-size': '200% 200%',
                                'background-position': 'right center'
                            },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        }
                    },
                }
            }
        }
    </script>
    
    <!-- Custom CSS -->
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
        
        /* Animated gradient background */
        .gradient-bg {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c, #4facfe, #00f2fe);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Glass morphism effect */
        .glass-morphism {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Custom form input styles */
        .form-input {
            transition: all 0.3s ease;
            border: 2px solid transparent;
            background: rgba(243, 244, 246, 0.5);
        }
        
        .form-input:focus {
            background: white;
            border-color: #4F46E5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            transform: translateY(-2px);
        }
        
        .form-input:hover:not(:focus) {
            border-color: #E5E7EB;
            background: rgba(243, 244, 246, 0.8);
        }
        
        /* Custom button styles */
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-primary:hover::before {
            left: 100%;
        }
        
        /* Floating animation for decorative elements */
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(2deg); }
            66% { transform: translateY(10px) rotate(-1deg); }
        }
        
        /* Custom checkbox and radio styles */
        input[type="checkbox"]:checked,
        input[type="radio"]:checked {
            background-color: #4F46E5;
            border-color: #4F46E5;
        }
        
        /* Loading spinner */
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 3px solid white;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Smooth scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a67d8 0%, #6b3aa3 100%);
        }
    </style>
    
    @stack('styles')
</head>
<body class="font-sans antialiased">
    <!-- Main Container with Gradient Background -->
    <div class="min-h-screen gradient-bg flex flex-col relative overflow-hidden">
        
        <!-- Decorative Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-20 left-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 floating"></div>
            <div class="absolute top-40 right-10 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 floating" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 floating" style="animation-delay: 4s;"></div>
        </div>
        
        <!-- Header Navigation -->
        <header class="relative z-10 animate__animated animate__fadeInDown">
            <nav class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex justify-between items-center">
                    <!-- Logo -->
                    <div class="flex items-center space-x-3">
                        <a href="{{ url('/') }}" class="flex items-center group">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110">
                                <i class="fas fa-shopping-cart text-purple-600 text-xl"></i>
                            </div>
                            <span class="ml-3 text-white text-xl font-bold tracking-tight">{{ config('app.name', 'Laravel') }}</span>
                        </a>
                    </div>
                    
                    <!-- Navigation Links -->
                    <div class="flex items-center space-x-6">
                        <a href="{{ url('/') }}" class="text-white/80 hover:text-white transition-colors duration-200 flex items-center space-x-2 group">
                            <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform duration-200"></i>
                            <span class="hidden sm:inline-block">Back to Home</span>
                        </a>
                        <a href="{{ route('buyer.login') }}" class="text-white/80 hover:text-white transition-colors duration-200 {{ request()->routeIs('buyer.login') ? 'font-semibold text-white' : '' }}">
                            Login
                        </a>
                        <a href="{{ route('buyer.register') }}" class="text-white/80 hover:text-white transition-colors duration-200 {{ request()->routeIs('buyer.register') ? 'font-semibold text-white' : '' }}">
                            Register
                        </a>
                    </div>
                </div>
            </nav>
        </header>
        
        <!-- Main Content Area -->
        <main class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12 relative z-10">
            <div class="w-full max-w-md animate__animated animate__fadeInUp">
                <!-- Authentication Card -->
                <div class="glass-morphism rounded-2xl shadow-2xl p-8 space-y-8">
                    <!-- Card Header -->
                    <div class="text-center">
                        @hasSection('card_icon')
                            <div class="mx-auto w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center mb-4 shadow-lg animate__animated animate__pulse animate__infinite">
                                @yield('card_icon')
                            </div>
                        @endif
                        
                        <h2 class="text-3xl font-bold text-gray-900">
                            @yield('card_title', 'Welcome')
                        </h2>
                        <p class="mt-2 text-sm text-gray-600">
                            @yield('card_subtitle', 'Please enter your details')
                        </p>
                    </div>
                    
                    <!-- Form Content -->
                    <div class="space-y-6">
                        @yield('content')
                    </div>
                    
                    <!-- Additional Links -->
                    @hasSection('additional_links')
                        <div class="space-y-3">
                            @yield('additional_links')
                        </div>
                    @endif
                </div>
                
                <!-- Social Login Options (Optional) -->
                @hasSection('social_login')
                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300/50"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-transparent text-white">Or continue with</span>
                            </div>
                        </div>
                        
                        <div class="mt-6 grid grid-cols-3 gap-3">
                            @yield('social_login')
                        </div>
                    </div>
                @endif
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="relative z-10 animate__animated animate__fadeInUp">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="border-t border-white/20 pt-8">
                    <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                        <!-- Footer Links -->
                        <div class="flex flex-wrap justify-center sm:justify-start gap-4 text-sm">
                            <a href="{{ url('/about') }}" class="text-white/70 hover:text-white transition-colors duration-200">
                                About Us
                            </a>
                            <a href="{{ url('/contact') }}" class="text-white/70 hover:text-white transition-colors duration-200">
                                Contact
                            </a>
                            <a href="{{ url('/privacy') }}" class="text-white/70 hover:text-white transition-colors duration-200">
                                Privacy Policy
                            </a>
                            <a href="{{ url('/terms') }}" class="text-white/70 hover:text-white transition-colors duration-200">
                                Terms of Service
                            </a>
                            <a href="{{ url('/help') }}" class="text-white/70 hover:text-white transition-colors duration-200">
                                Help Center
                            </a>
                        </div>
                        
                        <!-- Copyright -->
                        <div class="text-white/70 text-sm text-center sm:text-right">
                            &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
                        </div>
                    </div>
                    
                    <!-- Social Media Icons -->
                    <div class="mt-6 flex justify-center space-x-6">
                        <a href="#" class="text-white/60 hover:text-white transition-colors duration-200">
                            <i class="fab fa-facebook-f text-lg"></i>
                        </a>
                        <a href="#" class="text-white/60 hover:text-white transition-colors duration-200">
                            <i class="fab fa-twitter text-lg"></i>
                        </a>
                        <a href="#" class="text-white/60 hover:text-white transition-colors duration-200">
                            <i class="fab fa-instagram text-lg"></i>
                        </a>
                        <a href="#" class="text-white/60 hover:text-white transition-colors duration-200">
                            <i class="fab fa-linkedin-in text-lg"></i>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Toast Notifications Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <!-- Custom Scripts -->
    <script>
        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `animate__animated animate__fadeInRight px-6 py-4 rounded-lg shadow-lg text-white flex items-center space-x-3`;
            
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            toast.classList.add(colors[type]);
            toast.innerHTML = `
                <i class="fas ${icons[type]}"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-4 text-white/70 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            document.getElementById('toast-container').appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('animate__fadeInRight');
                toast.classList.add('animate__fadeOutRight');
                setTimeout(() => toast.remove(), 1000);
            }, 5000);
        }
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return true;
            
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.classList.add('border-red-500');
                    isValid = false;
                } else {
                    input.classList.remove('border-red-500');
                }
            });
            
            return isValid;
        }
        
        // Password visibility toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('[data-toggle-password]');
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-toggle-password');
                    const input = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
        });
        
        // Display Laravel validation errors as toasts
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                showToast('{{ $error }}', 'error');
            @endforeach
        @endif
        
        // Display success message if present
        @if (session('success'))
            showToast('{{ session('success') }}', 'success');
        @endif
        
        // Display info message if present
        @if (session('info'))
            showToast('{{ session('info') }}', 'info');
        @endif
        
        // Display warning message if present
        @if (session('warning'))
            showToast('{{ session('warning') }}', 'warning');
        @endif
    </script>
    
    <!-- Smart Resolution Detection System -->
    <script src="{{ asset('assets/js/global/smart-resolution.js') }}"></script>
    
    @stack('scripts')
</body>
</html>