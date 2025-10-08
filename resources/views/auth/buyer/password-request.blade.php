<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - Sydney Markets B2B</title>
    
    <!-- Premium Typography: Montserrat & Lato Font Families -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================
           PREMIUM NEUROMORPHIC DESIGN SYSTEM
           ============================================ */
        
        :root {
            /* Neumorphic Base Colors */
            --neu-bg: #E9EDF0;
            --neu-surface: #E9EDF0;
            --neu-dark: #C4CDD5;
            --neu-light: #FFFFFF;
            
            /* Sydney Markets Brand Colors - Premium Deep Green */
            --primary: #0D7C66;
            --primary-soft: rgba(13, 124, 102, 0.08);
            --primary-dark: #0A5D4E;
            --primary-light: #0F9B7D;
            --primary-glow: rgba(13, 124, 102, 0.2);
            
            --secondary: #0080FF;
            --secondary-soft: rgba(0, 128, 255, 0.08);
            --secondary-dark: #0066CC;
            
            --accent: #FFB800;
            
            /* Text Colors */
            --text-primary: #2C3E50;
            --text-secondary: #546E7A;
            --text-muted: #90A4AE;
            
            /* Status Colors */
            --success: #0F766E;
            --success-light: #F0FDFA;
            --error: #DC2626;
            --error-light: #FEF2F2;
            
            /* Neumorphic Shadows */
            --neu-shadow-xs: 2px 2px 4px rgba(196, 205, 213, 0.4), -2px -2px 4px rgba(255, 255, 255, 0.8);
            --neu-shadow-sm: 3px 3px 6px rgba(196, 205, 213, 0.5), -3px -3px 6px rgba(255, 255, 255, 0.9);
            --neu-shadow-md: 5px 5px 10px rgba(196, 205, 213, 0.6), -5px -5px 10px rgba(255, 255, 255, 1);
            --neu-shadow-lg: 8px 8px 16px rgba(196, 205, 213, 0.6), -8px -8px 16px rgba(255, 255, 255, 1);
            --neu-shadow-inset: inset 3px 3px 6px rgba(196, 205, 213, 0.5), inset -3px -3px 6px rgba(255, 255, 255, 0.9);
            --neu-shadow-inset-sm: inset 2px 2px 4px rgba(196, 205, 213, 0.4), inset -2px -2px 4px rgba(255, 255, 255, 0.8);
            
            /* Animations */
            --ease-out: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-spring: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --duration-fast: 150ms;
            --duration-base: 250ms;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100vh;
            overflow: hidden !important;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--neu-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Background Pattern */
        .neu-background {
            position: fixed;
            inset: 0;
            z-index: 0;
            background: var(--neu-bg);
        }
        
        .neu-pattern {
            position: absolute;
            inset: 0;
            opacity: 0.02;
            background-image: 
                linear-gradient(45deg, var(--neu-dark) 25%, transparent 25%),
                linear-gradient(-45deg, var(--neu-dark) 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, var(--neu-dark) 75%),
                linear-gradient(-45deg, transparent 75%, var(--neu-dark) 75%);
            background-size: 30px 30px;
            background-position: 0 0, 0 15px, 15px 30px, 15px 15px;
        }
        
        /* Main Container */
        .reset-wrapper {
            position: fixed;
            inset: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* Neumorphic Card */
        .neu-card {
            background: var(--neu-surface);
            border-radius: 24px;
            box-shadow: var(--neu-shadow-lg);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            animation: slideUp 0.6s var(--ease-out);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Header */
        .reset-header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .reset-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        
        .reset-subtitle {
            color: var(--text-secondary);
            font-family: 'Lato', sans-serif;
            font-size: 16px;
            line-height: 1.5;
        }
        
        /* Form */
        .reset-form {
            margin-bottom: 24px;
        }
        
        .input-group {
            margin-bottom: 24px;
        }
        
        .input-field {
            width: 100%;
            height: 48px;
            padding: 0 16px;
            font-size: 15px;
            font-family: 'Lato', sans-serif;
            font-weight: 400;
            border: none;
            border-radius: 12px;
            background: var(--neu-surface);
            box-shadow: var(--neu-shadow-inset-sm);
            transition: all 0.2s var(--ease-out);
            outline: none;
            color: var(--text-primary);
        }
        
        .input-field::placeholder {
            color: var(--text-muted);
        }
        
        .input-field:hover {
            box-shadow: var(--neu-shadow-inset);
        }
        
        .input-field:focus {
            box-shadow: var(--neu-shadow-inset), 0 0 0 2px var(--primary-glow);
            background: #F5F7F9;
        }
        
        /* Button */
        .btn {
            width: 100%;
            height: 48px;
            padding: 0 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: -0.01em;
            border: none;
            cursor: pointer;
            transition: all 0.2s var(--ease-out);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: var(--neu-shadow-md);
            margin-bottom: 16px;
        }
        
        .btn-primary:hover:not(:disabled) {
            box-shadow: var(--neu-shadow-lg);
            transform: translateY(-1px);
        }
        
        .btn-primary:active {
            box-shadow: var(--neu-shadow-sm);
            transform: translateY(0);
        }
        
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: var(--neu-surface);
            color: var(--text-secondary);
            box-shadow: var(--neu-shadow-xs);
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            box-shadow: var(--neu-shadow-sm);
            transform: translateY(-1px);
            color: var(--text-primary);
        }
        
        /* Messages */
        .message {
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s var(--ease-out);
        }
        
        .message.success {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid rgba(15, 118, 110, 0.2);
        }
        
        .message.error {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid rgba(220, 38, 38, 0.2);
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Loading State */
        .button-loader {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        .btn.loading .btn-text {
            display: none;
        }
        
        .btn.loading .button-loader {
            display: block;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Home Button */
        .home-button {
            position: fixed;
            top: 20px;
            right: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background: var(--neu-surface);
            border-radius: 14px;
            box-shadow: var(--neu-shadow-md);
            color: var(--primary);
            transition: all 0.3s var(--ease-out);
            text-decoration: none;
            z-index: 1000;
        }
        
        .home-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--neu-shadow-lg);
            color: var(--primary-dark);
        }
        
        .home-button:active {
            transform: translateY(0);
            box-shadow: var(--neu-shadow-inset-sm);
        }
        
        /* Mobile Responsive */
        @media (max-width: 640px) {
            .reset-wrapper {
                padding: 10px;
            }
            
            .neu-card {
                padding: 28px 20px;
            }
            
            .reset-title {
                font-size: 24px;
            }
            
            .reset-subtitle {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Background -->
    <div class="neu-background">
        <div class="neu-pattern"></div>
    </div>
    
    <!-- Home Button -->
    <a href="{{ url('/') }}" class="home-button" title="Back to Home">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
    </a>
    
    <div class="reset-wrapper">
        <div class="neu-card">
            <div class="reset-header">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary), var(--secondary)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: var(--neu-shadow-md);">
                    <svg width="28" height="28" fill="white" viewBox="0 0 24 24">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1L9 7V9C9 10.1 9.9 11 11 11V22H13V11C14.1 11 15 10.1 15 9H21Z"/>
                    </svg>
                </div>
                <h1 class="reset-title">Forgot Your Password?</h1>
                <p class="reset-subtitle">No worries! Enter your email and we'll send you secure instructions to reset your password.</p>
            </div>
            
            <!-- Success Message -->
            @if (session('status'))
                <div class="message success">
                    <div style="width: 32px; height: 32px; background: var(--success); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px;">Reset Link Sent!</div>
                        <div style="font-size: 13px; opacity: 0.8;">Check your email inbox and follow the instructions to reset your password. Link expires in 2 hours.</div>
                    </div>
                </div>
            @endif
            
            <!-- Error Messages -->
            @if ($errors->any())
                <div class="message error">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif
            
            <form method="POST" action="{{ route('buyer.password.email') }}" class="reset-form" id="resetForm">
                @csrf
                
                <div class="input-group">
                    <input 
                        type="email" 
                        name="email" 
                        class="input-field" 
                        placeholder="Your Email Address"
                        value="{{ old('email') }}"
                        required 
                        autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <svg width="20" height="20" fill="white" viewBox="0 0 24 24" style="margin-right: 8px;">
                        <path d="M2.01 21L23 12 2.01 3 2 10L17 12 2 14L2.01 21Z"/>
                    </svg>
                    <span class="btn-text">Send Reset Link</span>
                    <div class="button-loader"></div>
                </button>
            </form>
            
            <a href="{{ route('buyer.login') }}" class="btn btn-secondary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Login
            </a>
        </div>
    </div>
    
    <script>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>