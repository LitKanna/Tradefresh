<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Portal') - Sydney Markets B2B</title>

    <!-- Premium Typography: Montserrat & Lato Font Families -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">

    <!-- Smart Resolution CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/global/smart-resolution.css') }}">
    
    <!-- Screen Adapter JS -->
    <script src="{{ asset('assets/js/global/screen-adapter.js') }}"></script>
    
    <style>
        * {
            font-family: 'Lato', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        h1, h2, h3, h4, h5, h6,
        .font-heading,
        .font-montserrat {
            font-family: 'Montserrat', system-ui, -apple-system, sans-serif !important;
            font-weight: 600;
            letter-spacing: -0.02em;
        }
        
        :root {
            --admin-primary: #6366f1;
            --admin-secondary: #8b5cf6;
            --admin-accent: #a855f7;
            --admin-dark: #4c1d95;
            --admin-light: #ede9fe;
            --admin-gradient: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-secondary) 50%, var(--admin-accent) 100%);
            --scale-factor: 1;
        }
        
        body {
            overflow: hidden;
            background: #f8fafc;
        }
        
        /* Admin Container */
        .admin-container {
            width: 100vw;
            height: 100vh;
            display: grid;
            grid-template-columns: calc(260px * var(--scale-factor)) 1fr;
            grid-template-rows: calc(64px * var(--scale-factor)) 1fr;
            overflow: hidden;
        }
        
        /* Admin Header */
        .admin-header {
            grid-column: 1 / -1;
            background: white;
            border-bottom: 2px solid var(--admin-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 calc(2rem * var(--scale-factor));
            z-index: 100;
            box-shadow: 0 1px 3px rgba(99, 102, 241, 0.1);
        }
        
        .admin-logo {
            display: flex;
            align-items: center;
            gap: calc(1rem * var(--scale-factor));
        }
        
        .admin-logo-icon {
            width: calc(40px * var(--scale-factor));
            height: calc(40px * var(--scale-factor));
            background: var(--admin-gradient);
            border-radius: calc(10px * var(--scale-factor));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: calc(1.2rem * var(--scale-factor));
        }
        
        .admin-logo-text {
            font-size: calc(1.25rem * var(--scale-factor));
            font-weight: 700;
            color: var(--admin-dark);
        }
        
        .admin-tag {
            font-size: calc(0.75rem * var(--scale-factor));
            background: var(--admin-gradient);
            color: white;
            padding: calc(0.125rem * var(--scale-factor)) calc(0.5rem * var(--scale-factor));
            border-radius: calc(4px * var(--scale-factor));
            font-weight: 600;
            margin-left: calc(0.5rem * var(--scale-factor));
        }
        
        /* Admin Navigation */
        .admin-sidebar {
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: calc(1.5rem * var(--scale-factor));
            overflow-y: auto;
        }
        
        .admin-nav-section {
            margin-bottom: calc(2rem * var(--scale-factor));
        }
        
        .admin-nav-title {
            font-size: calc(0.75rem * var(--scale-factor));
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: calc(0.75rem * var(--scale-factor));
        }
        
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: calc(0.75rem * var(--scale-factor));
            padding: calc(0.75rem * var(--scale-factor));
            border-radius: calc(8px * var(--scale-factor));
            color: #475569;
            text-decoration: none;
            font-size: calc(0.875rem * var(--scale-factor));
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: calc(0.25rem * var(--scale-factor));
        }
        
        .admin-nav-item:hover {
            background: var(--admin-light);
            color: var(--admin-primary);
        }
        
        .admin-nav-item.active {
            background: var(--admin-gradient);
            color: white;
        }
        
        .admin-nav-icon {
            width: calc(20px * var(--scale-factor));
            height: calc(20px * var(--scale-factor));
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Admin Content */
        .admin-content {
            overflow-y: auto;
            padding: calc(2rem * var(--scale-factor));
            background: #f8fafc;
        }
        
        /* Admin User Info */
        .admin-user-info {
            display: flex;
            align-items: center;
            gap: calc(1rem * var(--scale-factor));
        }
        
        .admin-user-avatar {
            width: calc(36px * var(--scale-factor));
            height: calc(36px * var(--scale-factor));
            border-radius: 50%;
            background: var(--admin-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: calc(0.875rem * var(--scale-factor));
        }
        
        .admin-user-details {
            display: flex;
            flex-direction: column;
        }
        
        .admin-user-name {
            font-size: calc(0.875rem * var(--scale-factor));
            font-weight: 600;
            color: #1e293b;
        }
        
        .admin-user-role {
            font-size: calc(0.75rem * var(--scale-factor));
            color: var(--admin-primary);
            font-weight: 500;
        }
        
        /* Notification Badge */
        .admin-notification {
            position: relative;
            margin-right: calc(1.5rem * var(--scale-factor));
        }
        
        .admin-notification-icon {
            width: calc(24px * var(--scale-factor));
            height: calc(24px * var(--scale-factor));
            color: #64748b;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .admin-notification-icon:hover {
            color: var(--admin-primary);
        }
        
        .admin-notification-badge {
            position: absolute;
            top: calc(-4px * var(--scale-factor));
            right: calc(-4px * var(--scale-factor));
            width: calc(8px * var(--scale-factor));
            height: calc(8px * var(--scale-factor));
            background: #ef4444;
            border-radius: 50%;
            border: 2px solid white;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="admin-logo">
                <div class="admin-logo-icon">SM</div>
                <div class="admin-logo-text">
                    Sydney Markets
                    <span class="admin-tag">ADMIN</span>
                </div>
            </div>
            
            <div style="display: flex; align-items: center;">
                <div class="admin-notification">
                    <svg class="admin-notification-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span class="admin-notification-badge"></span>
                </div>
                
                <div class="admin-user-info">
                    <div class="admin-user-avatar">SA</div>
                    <div class="admin-user-details">
                        <div class="admin-user-name">System Admin</div>
                        <div class="admin-user-role">Platform Administrator</div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="admin-nav-section">
                <div class="admin-nav-title">Overview</div>
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-item active">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Analytics
                </a>
            </div>
            
            <div class="admin-nav-section">
                <div class="admin-nav-title">User Management</div>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    All Users
                </a>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pending Approvals
                </a>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Vendors
                </a>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Buyers
                </a>
            </div>
            
            <div class="admin-nav-section">
                <div class="admin-nav-title">Marketplace</div>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Transactions
                </a>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Disputes
                </a>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Products
                </a>
            </div>
            
            <div class="admin-nav-section">
                <div class="admin-nav-title">System</div>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
                <a href="#" class="admin-nav-item">
                    <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Logs
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="admin-content">
            @yield('content')
        </main>
    </div>
    
    @stack('scripts')
</body>
</html>