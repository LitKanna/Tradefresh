<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Buyer Registration - Sydney Markets B2B</title>
    
    <!-- Premium Typography: Montserrat + Lato Font Families -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <style>
        /* ============================================
           NEUMORPHIC DESIGN SYSTEM - BUYER VERSION
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
            
            /* Layout */
            --sidebar-width: 340px;
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
        .registration-wrapper {
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
            max-width: 1280px;
            height: calc(100vh - 40px);
            max-height: 720px;
            display: flex;
            overflow: hidden;
            animation: slideUp 0.6s var(--ease-out);
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Left Brand Panel */
        .brand-panel {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 40px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
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
        
        .brand-content {
            position: relative;
            z-index: 1;
        }
        
        .logo-container {
            margin-bottom: 40px;
            animation: fadeInLeft 0.8s var(--ease-out) 0.2s backwards;
        }
        
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        
        /* Neuromorphic Cut Design */
        .logo-cut {
            width: 48px;
            height: 48px;
            background: var(--neu-surface);
            border-radius: 14px;
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
            width: 24px;
            height: 24px;
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
            font-size: 22px;
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
            font-size: 14px;
            line-height: 1.6;
            font-weight: 400;
            letter-spacing: 0.01em;
            animation: fadeInLeft 0.8s var(--ease-out) 0.3s backwards;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
            padding-left: 2px;
        }
        
        /* Feature List */
        .feature-list {
            list-style: none;
            animation: fadeInLeft 0.8s var(--ease-out) 0.4s backwards;
            margin-top: 32px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 16px;
            opacity: 0;
            animation: fadeInLeft 0.5s var(--ease-out) forwards;
            transition: transform 0.3s var(--ease-spring);
        }
        
        .feature-item:hover {
            transform: translateX(4px);
        }
        
        .feature-item:nth-child(1) { animation-delay: 0.5s; }
        .feature-item:nth-child(2) { animation-delay: 0.6s; }
        .feature-item:nth-child(3) { animation-delay: 0.7s; }
        
        .feature-icon {
            width: 24px;
            height: 24px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.25), rgba(255, 255, 255, 0.15));
            backdrop-filter: blur(10px);
            border-radius: 8px;
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
            width: 14px;
            height: 14px;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
        }
        
        /* Trust Badges */
        .trust-badges {
            display: flex;
            gap: 12px;
            animation: fadeInLeft 0.8s var(--ease-out) 0.8s backwards;
        }
        
        .trust-badge {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 8px 12px;
            color: white;
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
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
        
        /* Right Form Panel - OPTIMIZED LAYOUT */
        .form-panel {
            flex: 1;
            padding: 32px;
            display: flex;
            flex-direction: column;
            background: var(--neu-surface);
        }
        
        /* Form Header - COMPACT */
        .form-header {
            margin-bottom: 16px;
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
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-secondary);
            font-size: 13px;
        }
        
        /* Progress Steps - COMPACT */
        .progress-steps {
            display: flex;
            gap: 6px;
            margin-bottom: 20px;
            animation: fadeIn 0.8s var(--ease-out) 0.3s backwards;
        }
        
        .step {
            flex: 1;
            height: 4px;
            background: var(--neu-surface);
            border-radius: 2px;
            box-shadow: var(--neu-shadow-inset-sm);
            position: relative;
            overflow: hidden;
            transition: all 0.3s var(--ease-out);
        }
        
        .step.active {
            background: var(--primary);
            box-shadow: none;
        }
        
        .step.active::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Form Grid - OPTIMIZED NO SCROLL */
        .form-content {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            animation: fadeIn 0.8s var(--ease-out) 0.4s backwards;
        }
        
        /* Grid Sections */
        .grid-row {
            display: contents;
        }
        
        .grid-full {
            grid-column: span 2;
        }
        
        .grid-half {
            grid-column: span 1;
        }
        
        /* Neumorphic Input Fields */
        .input-group {
            position: relative;
        }
        
        .input-field {
            width: 100%;
            height: 38px;
            padding: 0 12px;
            font-size: 13px;
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, sans-serif;
            font-weight: 400;
            border: none;
            border-radius: 8px;
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
        
        .input-field.verified {
            padding-right: 32px;
        }
        
        /* Readonly Field Styling */
        .input-field.readonly-field {
            background: rgba(196, 205, 213, 0.15);
            color: var(--text-secondary);
            cursor: not-allowed;
            box-shadow: inset 2px 2px 4px rgba(196, 205, 213, 0.2);
        }
        
        /* Input Wrapper - cleaned up */
        .input-wrapper {
            position: relative;
            width: 100%;
        }
        
        /* Verified Field State */
        .input-field.field-verified {
            background: rgba(16, 185, 129, 0.05);
            border-color: rgba(16, 185, 129, 0.3);
            box-shadow: inset 2px 2px 4px rgba(16, 185, 129, 0.1);
        }
        
        .input-field.field-verified:focus {
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1), inset 2px 2px 4px rgba(16, 185, 129, 0.1);
        }
        
        .input-field.readonly-field:hover {
            box-shadow: inset 2px 2px 4px rgba(196, 205, 213, 0.2);
        }
        
        .input-field.readonly-field:focus {
            box-shadow: inset 2px 2px 4px rgba(196, 205, 213, 0.2);
            background: rgba(196, 205, 213, 0.15);
        }
        
        .input-field.readonly-field::placeholder {
            color: var(--text-muted);
            font-style: italic;
            font-size: 11px;
        }
        
        /* ABN Field with Auto-Verification */
        .abn-wrapper {
            position: relative;
        }
        
        .abn-status {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .abn-status.checking {
            display: flex;
        }
        
        .abn-status.verified {
            display: flex;
            color: var(--primary);
        }
        
        .abn-status .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid var(--primary-soft);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Select Fields */
        .select-wrapper {
            position: relative;
        }
        
        .select-field {
            appearance: none;
            cursor: pointer;
            padding-right: 30px;
        }
        
        .select-arrow {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 14px;
            height: 14px;
            color: var(--text-muted);
            pointer-events: none;
        }
        
        /* Password Strength Indicator */
        .password-wrapper {
            position: relative;
        }
        
        .password-strength {
            position: absolute;
            bottom: -3px;
            left: 0;
            right: 0;
            display: flex;
            gap: 2px;
            height: 2px;
        }
        
        .strength-bar {
            flex: 1;
            background: var(--neu-dark);
            opacity: 0.2;
            transition: all 0.3s var(--ease-out);
        }
        
        .strength-bar.active {
            background: var(--primary);
            opacity: 1;
        }
        
        /* Checkbox - Compact */
        .checkbox-row {
            grid-column: span 2;
            display: flex;
            gap: 16px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }
        
        .checkbox-wrapper {
            position: relative;
            width: 16px;
            height: 16px;
        }
        
        .checkbox-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .checkbox-custom {
            width: 16px;
            height: 16px;
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
            width: 10px;
            height: 10px;
            color: white;
            opacity: 0;
            transition: opacity 0.2s var(--ease-out);
        }
        
        .checkbox-input:checked ~ .checkbox-custom svg {
            opacity: 1;
        }
        
        .checkbox-label {
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 12px;
            color: var(--text-secondary);
            user-select: none;
            cursor: pointer;
        }
        
        .checkbox-label a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid rgba(196, 205, 213, 0.3);
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
            letter-spacing: -0.01em;
            border: none;
            cursor: pointer;
            transition: all 0.2s var(--ease-out);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-secondary {
            background: var(--neu-surface);
            color: var(--text-secondary);
            box-shadow: var(--neu-shadow-sm);
        }
        
        .btn-secondary:hover {
            box-shadow: var(--neu-shadow-md);
            transform: translateY(-1px);
        }
        
        .btn-primary {
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
            width: 16px;
            height: 16px;
        }
        
        /* Success Toast */
        .toast {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 10px;
            padding: 14px 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s var(--ease-spring);
            z-index: 1000;
        }
        
        .toast.show {
            display: flex;
            opacity: 1;
            transform: translateX(0);
        }
        
        .toast-icon {
            width: 28px;
            height: 28px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .toast-icon svg {
            width: 14px;
            height: 14px;
        }
        
        .toast-message {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .neu-card {
                flex-direction: column;
                max-height: none;
            }
            
            .brand-panel {
                width: 100%;
                padding: 24px;
                max-height: 200px;
            }
            
            .form-content {
                grid-template-columns: 1fr;
            }
            
            .grid-half {
                grid-column: span 1;
            }
            
            .checkbox-row {
                flex-direction: column;
                gap: 8px;
            }
        }
        
        @media (max-width: 640px) {
            .registration-wrapper {
                padding: 10px;
            }
            
            .neu-card {
                border-radius: 16px;
            }
            
            .form-panel {
                padding: 20px;
            }
            
            .form-title {
                font-size: 20px;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
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
    
    <!-- Neumorphic Background -->
    <div class="neu-background">
        <div class="neu-pattern"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>
    
    <!-- Main Registration Container -->
    <div class="registration-wrapper">
        <div class="neu-card">
            <!-- Left Brand Panel -->
            <div class="brand-panel">
                <div class="brand-content">
                    <div class="logo-container">
                        <div class="logo">
                            <!-- Beautiful Neuromorphic Cut Design -->
                            <div class="logo-cut"></div>
                            <div class="logo-text">Sydney Markets</div>
                        </div>
                        <p class="brand-tagline">Join 10,000+ buyers saving 30% on fresh produce from Sydney's trusted vendors</p>
                    </div>
                    
                    <ul class="feature-list">
                        <li class="feature-item">
                            <div class="feature-icon">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span>Instant ABN verification</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span>Access to 700+ verified vendors</span>
                        </li>
                        <li class="feature-item">
                            <div class="feature-icon">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span>Real-time quote comparisons</span>
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
                        Trusted B2B
                    </div>
                </div>
            </div>
            
            <!-- Right Form Panel -->
            <div class="form-panel">
                <form id="buyerForm" method="POST" action="{{ route('buyer.register.post') }}">
                    @csrf
                    
                    <!-- Form Header -->
                    <div class="form-header">
                        <h1 class="form-title">Scale Your Business</h1>
                        <p class="form-subtitle">Create your free buyer account in 60 seconds</p>
                    </div>
                    
                    <!-- Progress Steps -->
                    <div class="progress-steps">
                        <div class="step active" data-step="1"></div>
                        <div class="step" data-step="2"></div>
                        <div class="step" data-step="3"></div>
                        <div class="step" data-step="4"></div>
                    </div>
                    
                    <!-- Optimized Form Grid -->
                    <div class="form-content">
                        <!-- Row 1: ABN & Business Name -->
                        <div class="input-group grid-half">
                            <div class="abn-wrapper">
                                <input type="text" 
                                       id="abn"
                                       name="abn" 
                                       class="input-field"
                                       placeholder="Australian Business Number"
                                       maxlength="14"
                                       required>
                                <div class="abn-status" id="abnStatus">
                                    <div class="spinner"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="input-group grid-half">
                            <div class="input-wrapper">
                                <input type="text" 
                                       id="businessName"
                                       name="company_name" 
                                       class="input-field readonly-field verified-field"
                                       placeholder="Business Name"
                                       readonly="readonly"
                                       disabled="disabled"
                                       tabindex="-1"
                                       required>
                            </div>
                        </div>
                        
                        <!-- Row 2: Business Type & Primary Purchase Category -->
                        <div class="input-group grid-half">
                            <div class="select-wrapper">
                                <select name="business_type" class="input-field select-field" required>
                                    <option value="">Business Type</option>
                                    <option value="restaurant">Restaurant</option>
                                    <option value="hotel">Hotel</option>
                                    <option value="cafe">Cafe</option>
                                    <option value="catering">Catering Service</option>
                                    <option value="retail">Retail Store</option>
                                    <option value="wholesale">Wholesale Distributor</option>
                                    <option value="other">Other</option>
                                </select>
                                <svg class="select-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        
                        <div class="input-group grid-half">
                            <div class="select-wrapper">
                                <select name="purchase_category" class="input-field select-field" required>
                                    <option value="">Primary Purchase Category</option>
                                    <option value="fruits_vegetables">Fruits & Vegetables</option>
                                    <option value="dairy_eggs">Dairy & Eggs</option>
                                    <option value="meat_seafood">Meat & Seafood</option>
                                    <option value="flowers">Flowers</option>
                                    <option value="herbs_spices">Herbs & Spices</option>
                                    <option value="mixed">Mixed Categories</option>
                                </select>
                                <svg class="select-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Row 3: Contact Person & Phone -->
                        <div class="input-group grid-half">
                            <input type="text" 
                                   name="contact_name" 
                                   class="input-field"
                                   placeholder="Contact Person Name"
                                   required>
                        </div>
                        
                        <div class="input-group grid-half">
                            <input type="tel" 
                                   name="phone" 
                                   class="input-field"
                                   placeholder="Phone Number"
                                   pattern="[0-9]{10}"
                                   maxlength="10"
                                   required>
                        </div>
                        
                        <!-- Row 4: Email -->
                        <div class="input-group grid-full">
                            <input type="email" 
                                   name="email" 
                                   class="input-field"
                                   placeholder="Business Email Address"
                                   required>
                        </div>
                        
                        <!-- Row 5: Delivery Address -->
                        <div class="input-group grid-full">
                            <input type="text" 
                                   id="address"
                                   name="delivery_address" 
                                   class="input-field"
                                   placeholder="Delivery Address"
                                   required>
                        </div>
                        
                        <!-- Row 6: Suburb & State -->
                        <div class="input-group grid-half">
                            <input type="text" 
                                   id="suburb"
                                   name="suburb" 
                                   class="input-field"
                                   placeholder="Suburb"
                                   required>
                        </div>
                        
                        <div class="input-group grid-half">
                            <div class="select-wrapper">
                                <select id="state" name="state" class="input-field select-field" required>
                                    <option value="">State</option>
                                    <option value="NSW" selected>NSW</option>
                                    <option value="VIC">VIC</option>
                                    <option value="QLD">QLD</option>
                                    <option value="SA">SA</option>
                                    <option value="WA">WA</option>
                                    <option value="TAS">TAS</option>
                                    <option value="NT">NT</option>
                                    <option value="ACT">ACT</option>
                                </select>
                                <svg class="select-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Row 7: Postcode & Volume -->
                        <div class="input-group grid-half">
                            <input type="text" 
                                   id="postcode"
                                   name="postcode" 
                                   class="input-field"
                                   placeholder="Postcode"
                                   pattern="[0-9]{4}"
                                   maxlength="4"
                                   required>
                        </div>
                        
                        <div class="input-group grid-half">
                            <div class="select-wrapper">
                                <select name="buyer_type" class="input-field select-field" required>
                                    <option value="">Buyer Type</option>
                                    <option value="owner">Owner</option>
                                    <option value="co_owner">Co-Owner</option>
                                    <option value="manager">Manager</option>
                                    <option value="buyer">Buyer</option>
                                    <option value="salesman">Salesman</option>
                                    <option value="accounts_member">Accounts Member</option>
                                    <option value="authorized_rep">Authorized Rep</option>
                                </select>
                                <svg class="select-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Row 8: Password & Confirm -->
                        <div class="input-group grid-half">
                            <div class="password-wrapper">
                                <input type="password" 
                                       id="password"
                                       name="password" 
                                       class="input-field"
                                       placeholder="Create Password"
                                       minlength="8"
                                       required>
                                <div class="password-strength" id="strengthIndicator">
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                    <div class="strength-bar"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="input-group grid-half">
                            <input type="password" 
                                   name="password_confirmation" 
                                   class="input-field"
                                   placeholder="Confirm Password"
                                   required>
                        </div>
                        
                        <!-- Row 9: Checkboxes -->
                        <div class="checkbox-row">
                            <div class="checkbox-group">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="terms" name="terms" class="checkbox-input" required>
                                    <div class="checkbox-custom">
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <label for="terms" class="checkbox-label">
                                    I agree to the <a href="#">Terms</a> and <a href="#">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <div class="checkbox-group">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" id="marketing" name="marketing" class="checkbox-input">
                                    <div class="checkbox-custom">
                                        <svg fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <label for="marketing" class="checkbox-label">
                                    Send me deals and vendor updates
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='{{ route('buyer.login') }}'">
                            Already have an account?
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Create Account
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Success Toast -->
    <div class="toast" id="toast">
        <div class="toast-icon">
            <svg fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
        </div>
        <span class="toast-message" id="toastMessage">Success!</span>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Progress tracking
            const steps = document.querySelectorAll('.step');
            const inputs = document.querySelectorAll('.input-field');
            
            function updateProgress() {
                const totalInputs = document.querySelectorAll('.input-field[required]').length;
                const filledInputs = Array.from(document.querySelectorAll('.input-field[required]')).filter(input => {
                    return input.value.trim() !== '';
                }).length;
                const progress = Math.ceil((filledInputs / totalInputs) * 4);
                
                steps.forEach((step, index) => {
                    if (index < progress) {
                        step.classList.add('active');
                    } else {
                        step.classList.remove('active');
                    }
                });
            }
            
            inputs.forEach(input => {
                input.addEventListener('input', updateProgress);
                input.addEventListener('change', updateProgress);
            });
            
            // ABN Auto-Verification
            const abnInput = document.getElementById('abn');
            const abnStatus = document.getElementById('abnStatus');
            const businessNameInput = document.getElementById('businessName');
            let verificationTimeout;
            
            // Initialize business name field state
            businessNameInput.placeholder = 'Business Name';
            
            // Format ABN as user types
            abnInput.addEventListener('input', function(e) {
                let value = this.value.replace(/\s/g, '');
                
                // Reset business name field when ABN is being edited
                if (businessNameInput.value) {
                    businessNameInput.value = '';
                    businessNameInput.setAttribute('disabled', true);
                    businessNameInput.setAttribute('readonly', true);
                    businessNameInput.classList.add('readonly-field');
                    businessNameInput.classList.remove('field-verified');
                    businessNameInput.placeholder = 'Business Name';
                }
                
                // Format with spaces
                if (value.length > 2) {
                    value = value.substring(0, 2) + ' ' + value.substring(2);
                }
                if (value.length > 6) {
                    value = value.substring(0, 6) + ' ' + value.substring(6);
                }
                if (value.length > 10) {
                    value = value.substring(0, 10) + ' ' + value.substring(10);
                }
                
                this.value = value.substring(0, 14); // Max length with spaces
                
                // Clear previous timeout
                clearTimeout(verificationTimeout);
                abnStatus.className = 'abn-status';
                abnInput.classList.remove('verified');
                
                // Auto-verify when 11 digits are entered
                const cleanValue = this.value.replace(/\s/g, '');
                if (cleanValue.length === 11) {
                    // Show checking status
                    abnStatus.classList.add('checking');
                    
                    // Delay verification slightly for better UX
                    verificationTimeout = setTimeout(() => {
                        verifyABN(cleanValue);
                    }, 500);
                }
            });
            
            async function verifyABN(abn) {
                try {
                    const response = await fetch('/api/vendor/abn/lookup', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ abn: abn })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.businessName) {
                        // Success - show verified status
                        abnStatus.className = 'abn-status verified';
                        abnStatus.innerHTML = `
                            <svg fill="currentColor" viewBox="0 0 20 20" style="width: 16px; height: 16px;">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        `;
                        
                        // Auto-fill business name but keep it readonly
                        businessNameInput.value = data.businessName;
                        // KEEP READONLY - Never allow user to edit verified business name
                        businessNameInput.setAttribute('readonly', true);
                        businessNameInput.removeAttribute('disabled'); // Remove disabled to allow form submission
                        businessNameInput.classList.add('readonly-field'); // Keep readonly styling
                        businessNameInput.classList.add('field-verified'); // Add verified state
                        abnInput.classList.add('verified');
                        
                        // Auto-fill address if available
                        if (data.address) {
                            if (data.address.street) document.getElementById('address').value = data.address.street;
                            if (data.address.suburb) document.getElementById('suburb').value = data.address.suburb;
                            if (data.address.state) document.getElementById('state').value = data.address.state;
                            if (data.address.postcode) document.getElementById('postcode').value = data.address.postcode;
                        }
                        
                        updateProgress();
                    } else {
                        // Not found - keep field disabled
                        abnStatus.className = 'abn-status';
                        businessNameInput.setAttribute('disabled', true);
                        businessNameInput.setAttribute('readonly', true);
                        businessNameInput.classList.add('readonly-field');
                        businessNameInput.classList.remove('field-verified');
                        businessNameInput.placeholder = 'Business Name';
                        businessNameInput.value = ''; // Clear any previous value
                        
                        showToast('ABN not found. Please verify your ABN number.', false);
                    }
                } catch (error) {
                    // Verification failed - keep field disabled
                    abnStatus.className = 'abn-status';
                    businessNameInput.setAttribute('disabled', true);
                    businessNameInput.setAttribute('readonly', true);
                    businessNameInput.classList.add('readonly-field');
                    businessNameInput.classList.remove('field-verified');
                    businessNameInput.placeholder = 'Business Name';
                    businessNameInput.value = ''; // Clear any previous value
                    
                    showToast('ABN verification failed. Please try again.', false);
                }
            }
            
            // Password Strength Indicator
            const passwordInput = document.getElementById('password');
            const strengthBars = document.querySelectorAll('.strength-bar');
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                if (password.match(/[0-9]/)) strength++;
                if (password.match(/[^a-zA-Z0-9]/)) strength++;
                
                strengthBars.forEach((bar, index) => {
                    if (index < strength) {
                        bar.classList.add('active');
                    } else {
                        bar.classList.remove('active');
                    }
                });
            });
            
            // Toast notification
            function showToast(message, success = true) {
                const toast = document.getElementById('toast');
                const toastMessage = document.getElementById('toastMessage');
                const toastIcon = toast.querySelector('.toast-icon');
                
                toastMessage.textContent = message;
                
                if (!success) {
                    toastIcon.style.background = '#FF6B6B';
                } else {
                    toastIcon.style.background = 'var(--primary)';
                }
                
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }
            
            // Form validation and submission
            document.getElementById('buyerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Map form fields to what controller expects
                // The controller's simpleRegister method expects these field names
                const formData = new FormData(this);
                
                // Ensure business name field is enabled for submission
                if (businessNameInput.value) {
                    businessNameInput.removeAttribute('disabled');
                    // Also ensure it's in the form data with the right name
                    formData.set('business_name', businessNameInput.value);
                }
                
                // Add missing fields that controller expects
                formData.set('first_name', formData.get('contact_name') || '');
                formData.set('last_name', ''); // Will be extracted from contact_name in controller
                
                // Map buyer_type field correctly - controller expects this
                const businessType = formData.get('business_type');
                if (businessType && businessType !== 'other') {
                    formData.set('buyer_type', 'business');
                } else {
                    formData.set('buyer_type', 'individual');
                }
                
                // Ensure password confirmation field name matches
                if (!formData.get('password_confirmation')) {
                    const passwordConfirm = document.querySelector('input[name="password_confirmation"]');
                    if (passwordConfirm) {
                        formData.set('password_confirmation', passwordConfirm.value);
                    }
                }
                
                // Add delivery address fields with correct names
                formData.set('delivery_address', formData.get('delivery_address') || formData.get('address') || '');
                formData.set('delivery_suburb', formData.get('suburb') || '');
                formData.set('delivery_state', formData.get('state') || '');
                formData.set('delivery_postcode', formData.get('postcode') || '');
                
                // Add role field (required by businessRegister method)
                const buyerTypeField = formData.get('buyer_type');
                if (buyerTypeField === 'owner' || buyerTypeField === 'manager') {
                    formData.set('role', buyerTypeField);
                } else {
                    formData.set('role', 'buyer');
                }
                
                // Validate required fields
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    // Skip disabled fields in validation
                    if (field.disabled) return;
                    
                    if (!field.value || (field.type === 'checkbox' && !field.checked)) {
                        isValid = false;
                        field.style.boxShadow = 'var(--neu-shadow-inset), 0 0 0 2px rgba(255, 107, 107, 0.3)';
                        
                        setTimeout(() => {
                            field.style.boxShadow = '';
                        }, 2000);
                    }
                });
                
                // Special check for business name field
                if (!businessNameInput.value) {
                    isValid = false;
                    abnInput.style.boxShadow = 'var(--neu-shadow-inset), 0 0 0 2px rgba(255, 107, 107, 0.3)';
                    showToast('Please verify your ABN first', false);
                    setTimeout(() => {
                        abnInput.style.boxShadow = '';
                    }, 2000);
                    return;
                }
                
                if (isValid) {
                    showToast('Creating your account...', true);
                    
                    // Submit form using fetch for better error handling
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Registration successful! Redirecting...', true);
                            setTimeout(() => {
                                window.location.href = data.redirect || '/buyer/dashboard';
                            }, 1500);
                        } else {
                            showToast(data.message || 'Registration failed. Please try again.', false);
                            console.error('Registration error:', data);
                        }
                    })
                    .catch(error => {
                        console.error('Form submission error:', error);
                        showToast('An error occurred. Please try again.', false);
                        // Fallback to normal form submission
                        setTimeout(() => {
                            this.submit();
                        }, 1000);
                    });
                } else {
                    showToast('Please fill all required fields', false);
                }
            });
            
            // Initial progress check
            updateProgress();
        });
    </script>
</body>
</html>