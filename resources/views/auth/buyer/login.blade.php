<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buyer Login - Sydney Markets B2B</title>
    
    <!-- Premium Typography: Montserrat & Lato Font Families -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================
           PREMIUM ANIMATED NEUROMORPHIC DESIGN SYSTEM
           ============================================ */
        
        :root {
            /* Cream-Green Neuromorphic Base Colors */
            --neu-bg: #f9faf7;
            --neu-surface: #f9faf7;
            --neu-dark: #e8ede5;
            --neu-light: #ffffff;
            
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
            
            /* Cream-Green Neumorphic Shadows */
            --neu-shadow-xs: 2px 2px 4px rgba(232, 237, 229, 0.6), -2px -2px 4px rgba(255, 255, 255, 0.9);
            --neu-shadow-sm: 3px 3px 6px rgba(232, 237, 229, 0.7), -3px -3px 6px rgba(255, 255, 255, 1);
            --neu-shadow-md: 5px 5px 10px rgba(232, 237, 229, 0.8), -5px -5px 10px rgba(255, 255, 255, 1);
            --neu-shadow-lg: 8px 8px 16px rgba(232, 237, 229, 0.8), -8px -8px 16px rgba(255, 255, 255, 1);
            --neu-shadow-inset: inset 3px 3px 6px rgba(232, 237, 229, 0.7), inset -3px -3px 6px rgba(255, 255, 255, 1);
            --neu-shadow-inset-sm: inset 2px 2px 4px rgba(232, 237, 229, 0.6), inset -2px -2px 4px rgba(255, 255, 255, 0.9);
            
            /* Animations */
            --ease-out: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-spring: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --duration-fast: 150ms;
            --duration-base: 250ms;
            
            /* Layout */
            --sidebar-width: 340px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Prefers Reduced Motion Support */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
        
        /* Premium Page Load Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes rotateIn {
            from {
                opacity: 0;
                transform: rotate(-10deg) scale(0.9);
            }
            to {
                opacity: 1;
                transform: rotate(0) scale(1);
            }
        }
        
        /* Floating Animation for Decorative Elements */
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            25% {
                transform: translateY(-20px) rotate(2deg);
            }
            50% {
                transform: translateY(10px) rotate(-1deg);
            }
            75% {
                transform: translateY(-10px) rotate(1deg);
            }
        }
        
        /* Shimmer Effect */
        @keyframes shimmer {
            0% {
                background-position: -1000px 0;
            }
            100% {
                background-position: 1000px 0;
            }
        }
        
        /* Pulse Animation */
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
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
        
        /* Floating Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            background: var(--neu-surface);
            box-shadow: var(--neu-shadow-lg);
            opacity: 0.3;
        }
        
        .orb-1 {
            width: 400px;
            height: 400px;
            top: -200px;
            left: -200px;
            animation: float 20s infinite ease-in-out;
        }
        
        .orb-2 {
            width: 300px;
            height: 300px;
            bottom: -150px;
            right: -150px;
            animation: float 15s infinite ease-in-out reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(20px, -20px) scale(1.05); }
        }
        
        /* Main Container */
        .login-wrapper {
            position: fixed;
            inset: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        }
        
        /* Neumorphic Card */
        .neu-card {
            background: var(--neu-surface);
            border-radius: 24px;
            box-shadow: var(--neu-shadow-lg);
            width: 100%;
            max-width: 1100px;
            height: auto;
            max-height: calc(100vh - 20px);
            display: flex;
            overflow: hidden;
            animation: slideUp 0.6s var(--ease-out);
            /* Remove any padding to let gradient fill completely */
            padding: 0;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Left Brand Panel - Structured Layout */
        .brand-panel {
            width: var(--sidebar-width);
            /* Force full height with no gaps */
            height: 100%;
            min-height: 100%;
            /* Gradient fills entire panel */
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            /* Keep content padding but not affecting background */
            padding: 36px 28px 32px 28px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
            /* Ensure gradient extends to card edges */
            margin: 0;
            border-radius: 24px 0 0 24px;
        }
        
        /* Brand Content */
        .brand-content {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        
        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0,128,255,0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(0,213,132,0.1) 0%, transparent 40%),
                repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.015) 35px, rgba(255,255,255,.015) 70px);
            pointer-events: none;
        }
        
        .brand-panel::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            animation: floatGradient 20s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes floatGradient {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(-20%, 10%) rotate(120deg); }
            66% { transform: translate(10%, -20%) rotate(240deg); }
        }
        
        /* Logo Container */
        .logo-container {
            margin-bottom: 32px;
            animation: fadeInLeft 0.8s var(--ease-out) 0.2s backwards;
        }
        
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        /* Neuromorphic Cut Design */
        .logo-cut {
            width: 42px;
            height: 42px;
            background: var(--neu-surface);
            border-radius: 12px;
            position: relative;
            box-shadow: 
                inset 4px 4px 8px rgba(196, 205, 213, 0.5),
                inset -4px -4px 8px rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s var(--ease-out);
        }
        
        /* Inner Neuromorphic Element */
        .logo-cut::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            background: linear-gradient(135deg, var(--neu-surface) 0%, var(--neu-light) 100%);
            border-radius: 50%;
            box-shadow: 
                inset 2px 2px 4px rgba(196, 205, 213, 0.3),
                inset -2px -2px 4px rgba(255, 255, 255, 0.7);
        }
        
        /* Subtle Animation on Hover */
        .logo-cut:hover {
            transform: translateY(-2px);
            box-shadow: 
                inset 5px 5px 10px rgba(196, 205, 213, 0.6),
                inset -5px -5px 10px rgba(255, 255, 255, 1);
        }
        
        .logo-cut:hover::before {
            box-shadow: 
                inset 3px 3px 6px rgba(196, 205, 213, 0.4),
                inset -3px -3px 6px rgba(255, 255, 255, 0.8);
        }
        
        .logo-text {
            color: white;
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1;
            text-shadow: 
                0 2px 8px rgba(0, 0, 0, 0.2),
                0 1px 2px rgba(0, 0, 0, 0.25);
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        
        .brand-tagline {
            color: rgba(255, 255, 255, 0.95);
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            font-weight: 400;
            letter-spacing: 0.01em;
            animation: fadeInLeft 0.8s var(--ease-out) 0.3s backwards;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
            padding-left: 2px;
        }
        
        /* Portal Badge */
        .portal-badge {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15));
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 6px 14px;
            color: white;
            font-family: 'Lato', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 20px;
            box-shadow: 
                inset 1px 1px 2px rgba(255, 255, 255, 0.3),
                0 2px 6px rgba(0, 0, 0, 0.15);
            animation: fadeInLeft 0.8s var(--ease-out) 0.35s backwards;
        }
        
        .portal-badge svg {
            width: 14px;
            height: 14px;
        }
        
        /* Feature List */
        .feature-list {
            list-style: none;
            animation: fadeInLeft 0.8s var(--ease-out) 0.4s backwards;
            margin-top: 28px;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-family: 'Lato', sans-serif;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 14px;
            opacity: 0;
            animation: fadeInLeft 0.5s var(--ease-out) forwards;
            transition: transform 0.3s var(--ease-spring);
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-item:hover {
            transform: translateX(4px);
        }
        
        .feature-item:nth-child(1) { animation-delay: 0.5s; }
        .feature-item:nth-child(2) { animation-delay: 0.6s; }
        .feature-item:nth-child(3) { animation-delay: 0.7s; }
        
        .feature-icon {
            width: 20px;
            height: 20px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15));
            backdrop-filter: blur(10px);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 
                inset 1px 1px 2px rgba(255, 255, 255, 0.3),
                0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s var(--ease-spring);
        }
        
        .feature-item:hover .feature-icon {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0.25));
            transform: scale(1.1) rotate(-5deg);
        }
        
        .feature-icon svg {
            width: 12px;
            height: 12px;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        }
        
        /* Trust Badges */
        .trust-badges {
            display: flex;
            gap: 12px;
            animation: fadeInLeft 0.8s var(--ease-out) 0.8s backwards;
            margin-top: auto;
            padding-top: 24px;
        }
        
        .trust-badge {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 8px 12px;
            color: white;
            font-family: 'Lato', sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.03em;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 
                inset 1px 1px 2px rgba(255, 255, 255, 0.2),
                0 2px 6px rgba(0, 0, 0, 0.15);
            transition: all 0.3s var(--ease-spring);
            text-transform: uppercase;
        }
        
        .trust-badge:hover {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15));
            transform: translateY(-2px);
            box-shadow: 
                inset 1px 1px 3px rgba(255, 255, 255, 0.3),
                0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .trust-badge svg {
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.15));
        }
        
        /* Right Form Panel */
        .form-panel {
            flex: 1;
            padding: 24px 32px;
            display: flex;
            flex-direction: column;
            background: var(--neu-surface);
            justify-content: center;
            /* Ensure proper right edge rounding */
            border-radius: 0 24px 24px 0;
            margin: 0;
        }
        
        /* Form Header - COMPACT */
        .form-header {
            margin-bottom: 20px;
            animation: fadeIn 0.8s var(--ease-out) 0.2s backwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .form-title {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2px;
            letter-spacing: -0.02em;
        }
        
        .form-subtitle {
            color: var(--text-secondary);
            font-family: 'Lato', sans-serif;
            font-size: 13px;
        }
        
        /* Form Content */
        .form-content {
            display: flex;
            flex-direction: column;
            gap: 16px;
            animation: fadeIn 0.8s var(--ease-out) 0.4s backwards;
        }
        
        /* Input Groups */
        .input-group {
            position: relative;
        }
        
        .input-field {
            width: 100%;
            height: 40px;
            padding: 0 14px;
            font-size: 13px;
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, sans-serif;
            font-weight: 400;
            border: none;
            border-radius: 10px;
            background: var(--neu-surface);
            box-shadow: var(--neu-shadow-inset-sm);
            transition: all 0.2s var(--ease-out);
            outline: none;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }
        
        .input-field::placeholder {
            color: var(--text-muted);
            font-size: 12px;
        }
        
        .input-field:hover {
            box-shadow: var(--neu-shadow-inset);
        }
        
        .input-field:focus {
            box-shadow: var(--neu-shadow-inset), 0 0 0 2px var(--primary-glow);
            background: #F5F7F9;
        }
        
        /* Password Toggle */
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 4px;
            cursor: pointer;
            color: var(--text-muted);
            transition: color var(--duration-fast) var(--ease-out);
        }
        
        .password-toggle:hover {
            color: var(--text-secondary);
        }
        
        .password-toggle svg {
            width: 18px;
            height: 18px;
        }
        
        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-wrapper {
            position: relative;
            width: 18px;
            height: 18px;
        }
        
        .checkbox-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .checkbox-custom {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            background: var(--neu-surface);
            box-shadow: var(--neu-shadow-inset-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s var(--ease-out);
            cursor: pointer;
        }
        
        .checkbox-input:checked ~ .checkbox-custom {
            background: var(--primary);
            box-shadow: var(--neu-shadow-xs);
        }
        
        .checkbox-custom svg {
            width: 12px;
            height: 12px;
            color: white;
            opacity: 0;
            transition: opacity 0.2s var(--ease-out);
        }
        
        .checkbox-input:checked ~ .checkbox-custom svg {
            opacity: 1;
        }
        
        .checkbox-label {
            font-family: 'Lato', sans-serif;
            font-size: 12px;
            color: var(--text-secondary);
            user-select: none;
            cursor: pointer;
        }
        
        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-family: 'Lato', sans-serif;
            font-size: 12px;
            font-weight: 600;
        }
        
        .forgot-link:hover {
            color: var(--primary-dark);
        }
        
        /* Submit Button - COMPACT */
        .btn {
            height: 40px;
            padding: 0 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
            letter-spacing: -0.01em;
            border: none;
            cursor: pointer;
            transition: all 0.2s var(--ease-out);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: var(--neu-shadow-md);
        }
        
        .btn-primary:hover {
            box-shadow: var(--neu-shadow-lg);
            transform: translateY(-1px);
        }
        
        .btn-primary:active {
            box-shadow: var(--neu-shadow-sm);
            transform: translateY(0);
        }
        
        .btn svg {
            width: 14px;
            height: 14px;
        }
        
        /* Form Actions */
        .form-actions {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid rgba(196, 205, 213, 0.3);
            text-align: center;
        }
        
        .register-text {
            font-family: 'Lato', sans-serif;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .register-link {
            color: var(--primary);
            text-decoration: none;
            font-family: 'Lato', sans-serif;
            font-weight: 600;
            font-size: 12px;
        }
        
        .register-link:hover {
            color: var(--primary-dark);
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
            margin: 0 auto;
        }
        
        .neu-button.loading .button-text {
            display: none;
        }
        
        .neu-button.loading .button-loader {
            display: block;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Error Messages - Premium Style */
        .error-message {
            background: var(--error-light);
            border: 1px solid rgba(220, 38, 38, 0.2);
            color: var(--error);
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown var(--duration-base) var(--ease-out);
            box-shadow: var(--shadow-sm);
        }
        
        .error-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Success State - Premium Style */
        .success-message {
            background: var(--success-light);
            border: 1px solid rgba(5, 150, 105, 0.2);
            color: var(--success);
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown var(--duration-base) var(--ease-out);
            box-shadow: var(--shadow-sm);
        }
        
        /* ====================================
           MOBILE-FIRST RESPONSIVE DESIGN
           ==================================== */
        
        /* Base Mobile Styles - 320px to 640px */
        @media (max-width: 640px) {
            /* Maintain overflow hidden on mobile */
            html, body {
                height: 100vh;
                overflow: hidden !important;
            }
            
            .login-container {
                flex-direction: column;
                height: 100vh;
                overflow: hidden !important;
            }
            
            /* Collapse brand panel on mobile */
            .brand-panel {
                width: 100%;
                height: auto;
                padding: var(--space-lg) var(--space-md);
                min-height: auto;
                box-shadow: none;
                flex-shrink: 0;
            }
            
            /* Hide decorative elements on mobile */
            .orb, .brand-pattern {
                display: none;
            }
            
            /* Simplify logo on mobile */
            .logo-section {
                margin-bottom: var(--space-lg);
            }
            
            .logo {
                gap: 12px;
            }
            
            .logo-icon {
                width: 40px;
                height: 40px;
            }
            
            /* Compact logo on mobile */
            .logo-text-primary {
                font-size: clamp(2rem, 8vw, 2.5rem);
            }
            
            .logo-tagline {
                font-size: clamp(0.75rem, 2vw, 0.875rem);
            }
            
            /* Hide benefits on very small screens */
            .minimal-benefits {
                display: none;
            }
            
            /* Login panel adjustments */
            .login-panel {
                flex: 1;
                padding: var(--space-lg) var(--space-md);
                overflow-y: auto;
                overflow-x: hidden;
                height: 100%;
            }
            
            .form-container {
                width: 100%;
                max-width: 100%;
            }
            
            .form-header {
                margin-bottom: var(--space-xl);
            }
            
            /* Card padding reduction */
            .neu-card {
                padding: 0;
                border-radius: 16px;
            }
            
            /* Form adjustments for touch */
            .form-group {
                margin-bottom: var(--space-lg);
            }
            
            /* Ensure minimum touch target sizes */
            .neu-input {
                height: 52px;
                font-size: 16px; /* Prevents zoom on iOS */
                padding: 0 16px;
            }
            
            .neu-button {
                height: 52px;
                font-size: 16px;
            }
            
            .password-toggle {
                padding: 8px;
                right: 8px;
            }
            
            /* Trust badges as single row */
            .trust-badges {
                position: static;
                margin-top: var(--space-lg);
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .trust-badge {
                flex: 1;
                min-width: 0;
                padding: 8px;
                font-size: 11px;
            }
            
            .trust-badge svg {
                width: 14px;
                height: 14px;
            }
            
            /* Form options stacked */
            .form-options {
                flex-direction: column;
                gap: var(--space-md);
                align-items: flex-start;
            }
            
            .form-divider {
                margin: var(--space-lg) 0;
            }
            
            /* Error message adjustments */
            .error-message, .success-message {
                padding: 12px;
                font-size: 14px;
            }
        }
        
        /* Small Tablets - 641px to 768px */
        @media (min-width: 641px) and (max-width: 768px) {
            html, body {
                height: 100vh;
                overflow: hidden !important;
            }
            
            .login-container {
                flex-direction: column;
                height: 100vh;
                overflow: hidden !important;
            }
            
            .brand-panel {
                width: 100%;
                padding: var(--space-2xl) var(--space-xl);
            }
            
            .minimal-benefits {
                display: flex;
                gap: 30px;
                padding: 40px 0;
            }
            
            .benefit-number {
                font-size: 1.75rem;
            }
            
            .login-panel {
                padding: var(--space-2xl);
            }
            
            .form-container {
                max-width: 500px;
            }
            
            .neu-card {
                padding: 0;
            }
        }
        
        /* Tablets - 769px to 1024px */
        @media (min-width: 769px) and (max-width: 1024px) {
            .brand-panel {
                width: 380px;
            }
            
            .login-panel {
                padding: var(--space-2xl);
            }
            
            .form-container {
                max-width: 440px;
            }
        }
        
        /* Desktop - 1025px to 1440px */
        @media (min-width: 1025px) and (max-width: 1440px) {
            /* Default styles apply */
        }
        
        /* Large Desktop - 1441px to 1920px */
        @media (min-width: 1441px) and (max-width: 1920px) {
            .brand-panel {
                width: 480px;
                padding: 80px 60px;
            }
            
            .login-panel {
                padding: 60px;
            }
            
            .form-container {
                max-width: 480px;
            }
            
            .neu-card {
                padding: 0;
            }
        }
        
        /* 4K and Ultra-wide - 1921px+ */
        @media (min-width: 1921px) {
            :root {
                /* Scale up typography for 4K */
                --font-base: 20px;
                --font-lg: 24px;
                --font-xl: 28px;
                --font-2xl: 36px;
                --font-3xl: 48px;
                --font-4xl: 60px;
            }
            
            .login-container {
                max-width: 2400px;
                margin: 0 auto;
            }
            
            .brand-panel {
                width: 600px;
                padding: 120px 100px;
            }
            
            .login-panel {
                padding: 100px;
            }
            
            .form-container {
                max-width: 600px;
            }
            
            .neu-card {
                padding: 0;
                border-radius: 32px;
            }
            
            .logo-icon {
                width: 64px;
                height: 64px;
            }
            
            .neu-input {
                height: 64px;
                border-radius: 16px;
            }
            
            .neu-button {
                height: 64px;
                border-radius: 16px;
            }
        }
        
        /* Special handling for landscape phones */
        @media (max-width: 896px) and (max-height: 414px) and (orientation: landscape) {
            .login-container {
                flex-direction: row;
            }
            
            .brand-panel {
                width: 40%;
                padding: var(--space-lg);
            }
            
            .welcome-title {
                font-size: 24px;
            }
            
            .benefits-list {
                display: none;
            }
            
            .trust-badges {
                display: none;
            }
            
            .login-panel {
                width: 60%;
                padding: var(--space-md);
            }
            
            .form-header {
                margin-bottom: var(--space-md);
            }
            
            .neu-card {
                padding: 0;
            }
            
            .form-group {
                margin-bottom: var(--space-md);
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            /* Increase all touch targets */
            .neu-input {
                min-height: 48px;
            }
            
            .neu-button {
                min-height: 48px;
            }
            
            .forgot-link {
                padding: 12px 16px;
                margin: -12px -16px;
            }
            
            .neu-checkbox-group {
                padding: 8px 0;
            }
            
            .password-toggle {
                padding: 12px;
                margin: -12px;
            }
            
            /* Disable hover effects on touch */
            .neu-button:hover:not(:disabled) {
                transform: none;
            }
            
            /* Add active states instead */
            .neu-button:active:not(:disabled) {
                transform: scale(0.98);
            }
        }
        
        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            /* Sharper shadows and borders */
            .neu-card {
                border-width: 0.5px;
            }
            
            .neu-input {
                border-width: 1.5px;
            }
        }
        
        /* Print styles */
        @media print {
            .brand-panel {
                display: none;
            }
            
            .orb, .neu-background {
                display: none;
            }
            
            .login-panel {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Neumorphic Background -->
    <div class="neu-background">
        <div class="neu-pattern"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    
    <!-- Sydney Markets Logo - Top Left -->
    <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        @include('components.sydney-markets-logo', ['size' => 'default'])
    </div>
    
    <!-- Home Navigation Button -->
    <div style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <a href="{{ url('/') }}" class="home-button" title="Back to Home">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px; height: 20px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
        </a>
    </div>
    
    <style>
        /* Neuromorphic Home Button */
        .home-button {
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
            position: relative;
            overflow: hidden;
        }
        
        .home-button::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg, rgba(13, 124, 102, 0) 0%, rgba(13, 124, 102, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s var(--ease-out);
        }
        
        .home-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--neu-shadow-lg);
            color: var(--primary-dark);
        }
        
        .home-button:hover::before {
            opacity: 1;
        }
        
        .home-button:active {
            transform: translateY(0);
            box-shadow: var(--neu-shadow-inset-sm);
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .home-button {
                width: 42px;
                height: 42px;
            }
        }
    </style>
    
    <!-- Main Login Container -->
    <div class="login-wrapper">
        <div class="neu-card">
            <!-- Left Brand Panel -->
            <div class="brand-panel">
                <div class="brand-content">
                    <div class="logo-container">
                        <div class="logo">
                            <!-- Beautiful Neuromorphic Cut Design -->
                            <div class="logo-cut"></div>
                            <div class="logo-text">Buyer Portal</div>
                        </div>
                        <p class="brand-tagline">Access Australia's freshest produce direct from Sydney Markets suppliers</p>
                        
                        <!-- Portal Badge -->
                        <div class="portal-badge">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z"/>
                            </svg>
                            Premium Buyer Access
                        </div>
                    </div>
                    
                    <ul class="feature-list">
                        <li class="feature-item">
                            <div class="feature-icon">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span>Direct supplier connections</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span>Best wholesale pricing</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span>Next-day delivery options</span>
                        </li>
                    </ul>
                </div>
                
                <div class="trust-badges">
                    <div class="trust-badge">
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        SSL Secured
                    </div>
                    <div class="trust-badge">
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Verified
                    </div>
                </div>
            </div>
            
            <!-- Right Form Panel -->
            <div class="form-panel">
                <form method="POST" action="{{ route('buyer.login.post') }}" id="buyerLoginForm">
                    @csrf
                    
                    <!-- Form Header -->
                    <div class="form-header">
                        <h1 class="form-title">Access Your Buying Dashboard</h1>
                        <p class="form-subtitle">Sign in to browse products and manage orders</p>
                    </div>
                    
                    <!-- Form Content -->
                    <div class="form-content">
                        <!-- Email Field -->
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
                        
                        <!-- Password Field -->
                        <div class="input-group">
                            <div class="password-wrapper">
                                <input 
                                    type="password" 
                                    id="password"
                                    name="password" 
                                    class="input-field" 
                                    placeholder="Password"
                                    required>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <svg class="eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg class="eye-closed" style="display: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Remember & Forgot -->
                        <div class="form-options">
                            <div class="checkbox-group">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="remember" name="remember" class="checkbox-input" checked>
                                    <div class="checkbox-custom">
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <label for="remember" class="checkbox-label">
                                    Remember me
                                </label>
                            </div>
                            
                            <a href="#" class="forgot-link" onclick="openForgotPasswordModal(); return false;" id="forgotPasswordLink">
                                Forgot password?
                            </a>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary">
                            Sign In to Buy
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Register Link -->
                    <div class="form-actions">
                        <p class="register-text">
                            New buyer?
                            <a href="{{ route('buyer.register') }}" class="register-link">Register Your Business →</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Forgot Password Modal - Premium Cream-Green Design -->
    <div id="forgotPasswordModal" class="forgot-modal-overlay" style="display: none;">
        <div class="forgot-modal-container">
            <!-- Modal Background with Gradient -->
            <div class="forgot-modal-bg">
                <div class="gradient-orb orb-1"></div>
                <div class="gradient-orb orb-2"></div>
                <div class="gradient-orb orb-3"></div>
            </div>
            
            <!-- Main Modal Card -->
            <div class="forgot-modal-card" id="forgotModalCard">
                <!-- Close Button -->
                <button type="button" class="forgot-modal-close" onclick="closeForgotPasswordModal()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- Modal States Container -->
                <div class="modal-states-container">
                    
                    <!-- State 1: Initial Form -->
                    <div class="modal-state" id="forgotInitialState">
                        <div class="forgot-header">
                            <div class="forgot-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <h3 class="forgot-title">Reset Your Password</h3>
                            <p class="forgot-subtitle">Enter your email address and we'll send you a secure link to reset your password</p>
                        </div>
                        
                        <form id="forgotPasswordForm" class="forgot-form">
                            <div class="forgot-input-group">
                                <label class="forgot-label">Email Address</label>
                                <div class="forgot-input-wrapper">
                                    <input 
                                        type="email" 
                                        name="email" 
                                        id="resetEmail"
                                        class="forgot-input" 
                                        placeholder="your@company.com"
                                        required>
                                    <div class="forgot-input-icon">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="forgot-actions">
                                <button type="button" class="forgot-btn forgot-btn-secondary" onclick="closeForgotPasswordModal()">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                                    </svg>
                                    Back to Login
                                </button>
                                <button type="submit" class="forgot-btn forgot-btn-primary" id="sendResetBtn">
                                    <span class="btn-text">Send Reset Link</span>
                                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    <div class="forgot-loader"></div>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- State 2: Loading -->
                    <div class="modal-state" id="forgotLoadingState" style="display: none;">
                        <div class="forgot-loading">
                            <div class="loading-spinner">
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                                <div class="spinner-ring"></div>
                            </div>
                            <h4 class="loading-title">Sending Reset Link</h4>
                            <p class="loading-subtitle">Please wait while we process your request...</p>
                        </div>
                    </div>

                    <!-- State 3: Success -->
                    <div class="modal-state" id="forgotSuccessState" style="display: none;">
                        <div class="forgot-success">
                            <div class="success-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <h4 class="success-title">Email Sent Successfully!</h4>
                            <p class="success-subtitle">We've sent a password reset link to your email address. Please check your inbox and follow the instructions.</p>
                            <div class="success-info">
                                <div class="info-item">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Link expires in 24 hours</span>
                                </div>
                                <div class="info-item">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-9 0H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2h-2"/>
                                    </svg>
                                    <span>Check spam folder if needed</span>
                                </div>
                            </div>
                            <button type="button" class="forgot-btn forgot-btn-primary" onclick="closeForgotPasswordModal()">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Continue to Login
                            </button>
                        </div>
                    </div>

                    <!-- State 4: Error -->
                    <div class="modal-state" id="forgotErrorState" style="display: none;">
                        <div class="forgot-error">
                            <div class="error-icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                </svg>
                            </div>
                            <h4 class="error-title">Unable to Send Reset Link</h4>
                            <p class="error-subtitle" id="errorMessage">We encountered an issue while processing your request. Please try again.</p>
                            <div class="forgot-actions">
                                <button type="button" class="forgot-btn forgot-btn-secondary" onclick="showForgotState('initial')">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Try Again
                                </button>
                                <button type="button" class="forgot-btn forgot-btn-primary" onclick="closeForgotPasswordModal()">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <style>
        /* ====================================
           FORGOT PASSWORD MODAL - PREMIUM CREAM-GREEN DESIGN
           ==================================== */

        /* Modal Overlay with Premium Backdrop */
        .forgot-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: linear-gradient(135deg, 
                rgba(13, 124, 102, 0.15) 0%, 
                rgba(0, 128, 255, 0.08) 50%, 
                rgba(249, 250, 247, 0.95) 100%);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: overlayFadeIn 0.4s var(--ease-out);
        }

        @keyframes overlayFadeIn {
            from {
                opacity: 0;
                backdrop-filter: blur(0px);
            }
            to {
                opacity: 1;
                backdrop-filter: blur(12px);
            }
        }

        @keyframes overlayFadeOut {
            from {
                opacity: 1;
                backdrop-filter: blur(12px);
            }
            to {
                opacity: 0;
                backdrop-filter: blur(0px);
            }
        }

        /* Modal Container */
        .forgot-modal-container {
            position: relative;
            width: 100%;
            max-width: 480px;
            animation: modalSlideUp 0.5s var(--ease-spring);
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Background Gradient Orbs */
        .forgot-modal-bg {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            border-radius: 18px;
        }

        .gradient-orb {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, 
                rgba(13, 124, 102, 0.1) 0%, 
                rgba(0, 128, 255, 0.05) 100%);
            filter: blur(40px);
            animation: floatOrb 8s ease-in-out infinite;
        }

        .gradient-orb.orb-1 {
            width: 200px;
            height: 200px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .gradient-orb.orb-2 {
            width: 160px;
            height: 160px;
            bottom: -80px;
            right: -80px;
            animation-delay: 3s;
        }

        .gradient-orb.orb-3 {
            width: 120px;
            height: 120px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 6s;
        }

        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-10px, -15px) scale(1.05); }
            66% { transform: translate(15px, -10px) scale(0.95); }
        }

        /* Main Modal Card */
        .forgot-modal-card {
            position: relative;
            background: var(--neu-surface);
            border-radius: 18px;
            box-shadow: 
                var(--neu-shadow-lg),
                0 25px 60px rgba(232, 237, 229, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Close Button */
        .forgot-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 10;
            width: 32px;
            height: 32px;
            background: var(--neu-surface);
            border: none;
            border-radius: 8px;
            color: var(--text-muted);
            cursor: pointer;
            box-shadow: var(--neu-shadow-xs);
            transition: all 0.3s var(--ease-out);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .forgot-modal-close:hover {
            color: var(--text-secondary);
            box-shadow: var(--neu-shadow-sm);
            transform: translateY(-1px) rotate(90deg);
        }

        .forgot-modal-close svg {
            width: 16px;
            height: 16px;
        }

        /* Modal States Container */
        .modal-states-container {
            position: relative;
            min-height: 400px;
        }

        .modal-state {
            position: absolute;
            inset: 0;
            padding: 36px 32px 32px 32px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            opacity: 1;
            transform: translateX(0);
            transition: all 0.4s var(--ease-out);
        }

        .modal-state.slide-out-left {
            opacity: 0;
            transform: translateX(-30px);
        }

        .modal-state.slide-out-right {
            opacity: 0;
            transform: translateX(30px);
        }

        .modal-state.slide-in-right {
            opacity: 0;
            transform: translateX(30px);
        }

        .modal-state.slide-in-left {
            opacity: 0;
            transform: translateX(-30px);
        }

        /* =================================
           STATE 1: INITIAL FORM
           ================================= */

        .forgot-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .forgot-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, 
                rgba(13, 124, 102, 0.1) 0%, 
                rgba(0, 128, 255, 0.05) 100%);
            border-radius: 16px;
            box-shadow: var(--neu-shadow-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            color: var(--primary);
            animation: iconFloat 3s ease-in-out infinite;
        }

        .forgot-icon svg {
            width: 28px;
            height: 28px;
        }

        @keyframes iconFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-4px) rotate(2deg); }
        }

        .forgot-title {
            font-family: 'Plus Jakarta Sans', 'Montserrat', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 8px 0;
            letter-spacing: -0.02em;
        }

        .forgot-subtitle {
            font-family: 'Plus Jakarta Sans', 'Lato', sans-serif;
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin: 0;
        }

        /* Form Styling */
        .forgot-form {
            width: 100%;
        }

        .forgot-input-group {
            margin-bottom: 24px;
        }

        .forgot-label {
            display: block;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
            letter-spacing: -0.01em;
        }

        .forgot-input-wrapper {
            position: relative;
        }

        .forgot-input {
            width: 100%;
            height: 48px;
            padding: 0 16px 0 44px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            border: none;
            border-radius: 18px;
            background: var(--neu-surface);
            box-shadow: var(--neu-shadow-inset);
            transition: all 0.3s var(--ease-out);
            outline: none;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .forgot-input::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }

        .forgot-input:hover {
            box-shadow: 
                var(--neu-shadow-inset),
                0 0 0 1px rgba(13, 124, 102, 0.1);
        }

        .forgot-input:focus {
            box-shadow: 
                var(--neu-shadow-inset),
                0 0 0 2px rgba(13, 124, 102, 0.2);
            background: #fdfdfc;
        }

        .forgot-input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: color 0.3s var(--ease-out);
        }

        .forgot-input-icon svg {
            width: 18px;
            height: 18px;
        }

        .forgot-input:focus ~ .forgot-input-icon {
            color: var(--primary);
        }

        .forgot-input-wrapper:focus-within .forgot-input-icon {
            color: var(--primary);
        }

        /* Action Buttons */
        .forgot-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .forgot-btn {
            height: 54px;
            padding: 0 20px;
            border: none;
            border-radius: 18px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: -0.01em;
            cursor: pointer;
            transition: all 0.3s var(--ease-out);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .forgot-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, 
                transparent 30%, 
                rgba(255, 255, 255, 0.1) 50%, 
                transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .forgot-btn:hover::before {
            transform: translateX(100%);
        }

        .forgot-btn-secondary {
            flex: 1;
            background: var(--neu-surface);
            color: var(--text-secondary);
            box-shadow: var(--neu-shadow-sm);
        }

        .forgot-btn-secondary:hover {
            color: var(--text-primary);
            box-shadow: var(--neu-shadow-md);
            transform: translateY(-2px);
        }

        .forgot-btn-secondary svg {
            width: 16px;
            height: 16px;
        }

        .forgot-btn-primary {
            flex: 2;
            background: linear-gradient(135deg, 
                var(--primary) 0%, 
                var(--secondary) 100%);
            color: white;
            box-shadow: 
                var(--neu-shadow-md),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .forgot-btn-primary:hover {
            box-shadow: 
                var(--neu-shadow-lg),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .forgot-btn-primary:active {
            transform: translateY(0);
            box-shadow: var(--neu-shadow-sm);
        }

        .btn-icon {
            width: 16px;
            height: 16px;
            transition: transform 0.3s var(--ease-out);
        }

        .forgot-btn-primary:hover .btn-icon {
            transform: translateX(2px);
        }

        /* Loading Spinner */
        .forgot-loader {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .forgot-btn.loading .btn-text,
        .forgot-btn.loading .btn-icon {
            display: none;
        }

        .forgot-btn.loading .forgot-loader {
            display: block;
        }

        /* =================================
           STATE 2: LOADING STATE
           ================================= */

        .forgot-loading {
            text-align: center;
            padding: 20px;
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-bottom: 24px;
        }

        .spinner-ring {
            width: 12px;
            height: 12px;
            border: 2px solid rgba(13, 124, 102, 0.2);
            border-top: 2px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .spinner-ring:nth-child(2) {
            animation-delay: 0.1s;
        }

        .spinner-ring:nth-child(3) {
            animation-delay: 0.2s;
        }

        .loading-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 8px 0;
        }

        .loading-subtitle {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            color: var(--text-secondary);
            margin: 0;
        }

        /* =================================
           STATE 3: SUCCESS STATE
           ================================= */

        .forgot-success {
            text-align: center;
        }

        .success-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, 
                rgba(15, 118, 110, 0.1) 0%, 
                rgba(5, 150, 105, 0.05) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            color: var(--success);
            animation: successPulse 2s ease-in-out infinite;
        }

        .success-icon svg {
            width: 32px;
            height: 32px;
        }

        @keyframes successPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(15, 118, 110, 0.2); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(15, 118, 110, 0); }
        }

        .success-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 12px 0;
        }

        .success-subtitle {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin: 0 0 24px 0;
        }

        .success-info {
            background: rgba(15, 118, 110, 0.05);
            border: 1px solid rgba(15, 118, 110, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--success);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        /* =================================
           STATE 4: ERROR STATE
           ================================= */

        .forgot-error {
            text-align: center;
        }

        .error-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, 
                rgba(220, 38, 38, 0.1) 0%, 
                rgba(239, 68, 68, 0.05) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            color: var(--error);
            animation: errorShake 0.5s ease-in-out;
        }

        .error-icon svg {
            width: 32px;
            height: 32px;
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px); }
            75% { transform: translateX(2px); }
        }

        .error-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 12px 0;
        }

        .error-subtitle {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin: 0 0 24px 0;
        }

        /* =================================
           RESPONSIVE DESIGN
           ================================= */

        @media (max-width: 640px) {
            .forgot-modal-overlay {
                padding: 10px;
            }

            .forgot-modal-container {
                max-width: 100%;
            }

            .modal-state {
                padding: 24px 20px 20px 20px;
            }

            .forgot-title {
                font-size: 20px;
            }

            .forgot-actions {
                flex-direction: column;
                gap: 12px;
            }

            .forgot-btn-secondary,
            .forgot-btn-primary {
                flex: none;
                width: 100%;
            }

            .forgot-input {
                height: 52px;
                font-size: 16px; /* Prevents zoom on iOS */
            }

            .forgot-btn {
                height: 52px;
            }
        }

        @media (max-height: 600px) {
            .modal-states-container {
                min-height: 350px;
            }

            .modal-state {
                padding: 20px 32px;
            }

            .forgot-header {
                margin-bottom: 20px;
            }

            .forgot-icon {
                width: 48px;
                height: 48px;
                margin-bottom: 16px;
            }

            .forgot-icon svg {
                width: 24px;
                height: 24px;
            }
        }

        /* Dark mode support (if needed) */
        @media (prefers-color-scheme: dark) {
            .forgot-modal-overlay {
                background: linear-gradient(135deg, 
                    rgba(13, 124, 102, 0.2) 0%, 
                    rgba(0, 128, 255, 0.1) 50%, 
                    rgba(15, 23, 42, 0.95) 100%);
            }
        }
    </style>
    
    <script>
        // Password Toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.querySelector('.eye-open');
            const eyeClosed = document.querySelector('.eye-closed');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.style.display = 'none';
                eyeClosed.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                eyeOpen.style.display = 'block';
                eyeClosed.style.display = 'none';
            }
        }
        
        // ====================================
        // FORGOT PASSWORD MODAL FUNCTIONS - PREMIUM STATE MANAGEMENT
        // ====================================

        function openForgotPasswordModal() {
            const modal = document.getElementById('forgotPasswordModal');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Reset to initial state
            showForgotState('initial');
            
            // Pre-fill email if available
            const loginEmail = document.querySelector('input[name="email"]').value;
            if (loginEmail) {
                document.getElementById('resetEmail').value = loginEmail;
            }
            
            // Focus email input after animation
            setTimeout(() => {
                const emailInput = document.getElementById('resetEmail');
                if (emailInput && emailInput.offsetParent !== null) {
                    emailInput.focus();
                }
            }, 300);
        }
        
        function closeForgotPasswordModal() {
            const modal = document.getElementById('forgotPasswordModal');
            
            // Animate out
            modal.style.animation = 'overlayFadeOut 0.3s ease-out forwards';
            
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.animation = '';
                document.body.style.overflow = '';
                
                // Reset form
                resetForgotForm();
            }, 300);
        }

        function showForgotState(stateName) {
            const states = {
                'initial': document.getElementById('forgotInitialState'),
                'loading': document.getElementById('forgotLoadingState'),
                'success': document.getElementById('forgotSuccessState'),
                'error': document.getElementById('forgotErrorState')
            };

            // Hide all states
            Object.values(states).forEach(state => {
                if (state) state.style.display = 'none';
            });

            // Show target state with animation
            if (states[stateName]) {
                states[stateName].style.display = 'flex';
                states[stateName].style.opacity = '0';
                states[stateName].style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    states[stateName].style.transition = 'all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                    states[stateName].style.opacity = '1';
                    states[stateName].style.transform = 'translateY(0)';
                }, 50);
            }
        }

        function resetForgotForm() {
            // Clear form
            document.getElementById('resetEmail').value = '';
            
            // Reset button state
            const btn = document.getElementById('sendResetBtn');
            btn.classList.remove('loading');
            btn.disabled = false;
            
            // Reset to initial state
            showForgotState('initial');
        }

        function showErrorMessage(message) {
            document.getElementById('errorMessage').textContent = message;
            showForgotState('error');
        }
        
        // Handle forgot password form submission
        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('resetEmail').value.trim();
            
            // Validate email
            if (!email) {
                showErrorMessage('Please enter your email address.');
                return;
            }
            
            if (!isValidEmail(email)) {
                showErrorMessage('Please enter a valid email address.');
                return;
            }
            
            // Show loading state
            showForgotState('loading');
            
            // Submit form via AJAX
            fetch('{{ route("buyer.password.email") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.message || data.status) {
                    // Success - show success state
                    showForgotState('success');
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                let errorMessage = 'We encountered an issue while processing your request. Please try again.';
                
                if (error.message) {
                    errorMessage = error.message;
                } else if (error.errors && error.errors.email) {
                    errorMessage = error.errors.email[0];
                } else if (error.status === 429) {
                    errorMessage = 'Too many attempts. Please wait a few minutes before trying again.';
                } else if (error.status === 422) {
                    errorMessage = 'Please check your email address and try again.';
                }
                
                showErrorMessage(errorMessage);
            });
        });

        // Email validation helper
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Close modal on outside click
        document.getElementById('forgotPasswordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeForgotPasswordModal();
            }
        });
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('forgotPasswordModal').style.display === 'flex') {
                closeForgotPasswordModal();
            }
        });

        // Form Submission
        document.getElementById('buyerLoginForm').addEventListener('submit', function(e) {
            // Add loading state to button if needed
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.innerHTML = 'Signing in...';
        });
    </script>
</body>
</html>