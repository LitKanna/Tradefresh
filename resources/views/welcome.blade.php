<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Sydney Markets B2B Marketplace - Connect with verified suppliers and buyers for fresh produce.">
    <title>Sydney Markets - B2B Marketplace</title>
    
    <!-- Premium Typography System - Professional B2B Excellence -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@400;600;700;800&family=Lato:ital,wght@1,700&display=swap" rel="stylesheet">
    <style>
        /* Font loading optimization */
        @font-face {
            font-family: 'Inter';
            font-display: swap;
        }
    </style>
    
    <style>
        /* ============================================
           STUNNING VISUAL DESIGN SYSTEM
           Premium B2B Marketplace Experience
           ============================================ */
        
        :root {
            /* Dashboard Neumorphic Color System */
            --neu-bg: #E0E5EC;
            --neu-surface: #E4E9F0;
            --neu-dark: #B8BEC7;
            --neu-light: #E8EDF4;
            --surface-glass: rgba(224, 229, 236, 0.92);
            --surface-frost: linear-gradient(135deg, rgba(224, 229, 236, 0.95), rgba(232, 237, 244, 0.88));

            /* Dashboard Primary Palette - Green */
            --primary: #5CB85C;
            --primary-rich: #4CA84C;
            --primary-vibrant: #6CC86C;
            --primary-hover: #4CA84C;
            --primary-light: rgba(92, 184, 92, 0.06);
            --primary-glow: rgba(92, 184, 92, 0.3);
            --primary-gradient: linear-gradient(135deg, #5CB85C 0%, #6CC86C 50%, #7CD87C 100%);
            --primary-radial: radial-gradient(circle at 30% 50%, #6CC86C, #5CB85C);

            /* Dashboard Secondary - Deeper Green */
            --secondary: #4CA84C;
            --secondary-rich: #3C983C;
            --secondary-hover: #3C983C;
            --secondary-light: rgba(76, 168, 76, 0.08);
            --secondary-gradient: linear-gradient(135deg, #4CA84C 0%, #5CB85C 50%, #6CC86C 100%);

            /* Dashboard Accent Colors */
            --accent-gold: #F0A830;
            --accent-emerald: #5CB85C;
            --accent-lime: #8BC34A;
            --accent-mint: #6CC86C;
            --accent-sage: #4CA84C;

            /* Dashboard Text Hierarchy */
            --text-primary: #2A2620;
            --text-secondary: #4A453B;
            --text-muted: #8B8478;
            --text-light: #A39B90;
            
            /* Dashboard Shadow System - Neumorphic */
            --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.10), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            --shadow-2xl: 0 35px 70px -15px rgba(0, 0, 0, 0.20);
            --shadow-inner: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
            --shadow-premium: 0 10px 40px rgba(0, 0, 0, 0.08), 0 2px 10px rgba(0, 0, 0, 0.06);
            --shadow-neu-light: 12px 12px 24px #B8BEC7, -12px -12px 24px #E8EDF4;
            --shadow-neu-dark: inset 6px 6px 12px #B8BEC7, inset -6px -6px 12px #E8EDF4;
            --shadow-glow: 0 0 20px rgba(92, 184, 92, 0.3);
            
            /* Dashboard Glassmorphism */
            --glass-bg: rgba(224, 229, 236, 0.75);
            --glass-border: rgba(224, 229, 236, 0.18);
            --glass-shadow: 0 8px 32px 0 rgba(184, 190, 199, 0.15);
            --glass-blur: blur(12px);
            --glass-heavy: blur(20px);
            
            /* Enhanced Smooth Animation Curves - 60fps Optimized */
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
            --ease-elastic: cubic-bezier(0.68, -0.6, 0.32, 1.6);
            --ease-out-expo: cubic-bezier(0.19, 1, 0.22, 1);
            --ease-in-out-expo: cubic-bezier(0.87, 0, 0.13, 1);
            --ease-spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --duration-instant: 100ms;
            --duration-fast: 200ms;
            --duration-normal: 300ms;
            --duration-slow: 500ms;
            --duration-super-slow: 800ms;
            
            /* Performance Optimization */
            --transform-gpu: translateZ(0);
            
            /* Dashboard Mesh Gradients - Green Only */
            --mesh-gradient-1: radial-gradient(at 40% 20%, rgba(92, 184, 92, 0.15) 0px, transparent 50%),
                               radial-gradient(at 80% 0%, rgba(108, 200, 108, 0.12) 0px, transparent 50%),
                               radial-gradient(at 0% 50%, rgba(92, 184, 92, 0.08) 0px, transparent 50%),
                               radial-gradient(at 80% 50%, rgba(124, 216, 124, 0.06) 0px, transparent 50%),
                               radial-gradient(at 0% 100%, rgba(76, 168, 76, 0.1) 0px, transparent 50%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* CRITICAL NO-SCROLL ENFORCEMENT - ENHANCED LOCK */
        html, body {
            height: 100vh !important;
            max-height: 100vh !important;
            width: 100vw !important;
            max-width: 100vw !important;
            overflow: hidden !important;
            overflow-x: hidden !important;
            overflow-y: hidden !important;
            position: fixed !important; /* STRONGER LOCK */
            margin: 0 !important;
            padding: 0 !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
        }
        
        /* Hide ALL scrollbars permanently */
        html::-webkit-scrollbar,
        body::-webkit-scrollbar,
        *::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        
        html, body {
            -ms-overflow-style: none !important;  /* IE and Edge */
            scrollbar-width: none !important;  /* Firefox */
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #E0E5EC 0%, #E4E9F0 25%, #E8EDF4 50%, #E4E9F0 75%, #E0E5EC 100%);
            -webkit-font-smoothing: subpixel-antialiased;
            -moz-osx-font-smoothing: auto;
            text-rendering: geometricPrecision;
            color: var(--text-primary);
            touch-action: none !important; /* COMPLETE TOUCH LOCK */
            letter-spacing: -0.015em; /* Premium letter spacing */
            font-weight: 400; /* Regular weight for better readability */
            line-height: 1.6;
            font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1;
            will-change: auto;
            /* ADDITIONAL SCROLL LOCKS */
            -webkit-overflow-scrolling: auto !important;
            overscroll-behavior: none !important;
            scroll-behavior: auto !important;
        }
        
        /* Dashboard Focus Styles for Accessibility */
        *:focus {
            outline: 2px solid #8B8478;
            outline-offset: 3px;
            transition: outline-color var(--duration-fast) var(--ease-smooth);
        }

        *:focus:not(:focus-visible) {
            outline: none;
        }

        *:focus-visible {
            outline: 2px solid #8B8478;
            outline-offset: 3px;
            box-shadow: 0 0 0 4px rgba(139, 132, 120, 0.1);
        }
        
        /* Premium Link Transitions */
        a {
            transition: all var(--duration-fast) var(--ease-smooth);
            text-decoration: none;
            position: relative;
        }
        
        a:not(.option-card):not(.cta-button):hover {
            filter: brightness(1.1);
        }
        
        /* Main Container - Neuromorphic Background */
        .main-container {
            height: 100vh !important;
            max-height: 100vh !important;
            background: linear-gradient(135deg, #E0E5EC 0%, #E4E9F0 25%, #E8EDF4 50%, #E4E9F0 75%, #E0E5EC 100%);
            animation: fadeIn var(--duration-slow) var(--ease-smooth);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden !important;
        }


        /* Subtle Mesh Gradient Overlay */
        .main-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                conic-gradient(
                    from 180deg at 50% 50%,
                    rgba(92, 184, 92, 0.01) 0deg,
                    transparent 60deg,
                    rgba(92, 184, 92, 0.01) 120deg,
                    transparent 180deg,
                    rgba(76, 168, 76, 0.01) 240deg,
                    transparent 300deg,
                    rgba(92, 184, 92, 0.01) 360deg
                );
            animation: meshRotate 60s linear infinite;
            pointer-events: none;
            opacity: 0.3;
        }

        @keyframes meshRotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Subtle Noise Texture */
        .main-container::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.015;
            background-image: 
                repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(0, 0, 0, .01) 35px, rgba(0, 0, 0, .01) 70px);
            pointer-events: none;
        }
        
        /* Floating Orbs - Enhanced Ambient Animation */
        .floating-orbs {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }
        
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            animation: floatOrb 20s infinite ease-in-out;
            mix-blend-mode: normal;
        }
        
        .orb1 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle at 30% 30%, 
                rgba(92, 184, 92, 0.15), 
                rgba(92, 184, 92, 0.08) 40%, 
                transparent 70%);
            top: -300px;
            left: -300px;
            animation-duration: 28s;
            filter: blur(100px);
        }
        
        .orb2 {
            width: 700px;
            height: 700px;
            background: radial-gradient(circle at 70% 70%, 
                rgba(108, 200, 108, 0.12), 
                rgba(76, 168, 76, 0.08) 40%, 
                transparent 70%);
            bottom: -350px;
            right: -350px;
            animation-duration: 32s;
            animation-delay: -5s;
            filter: blur(120px);
        }
        
        .orb3 {
            width: 450px;
            height: 450px;
            background: radial-gradient(circle at 50% 50%, 
                rgba(92, 184, 92, 0.1); 
                rgba(108, 200, 108, 0.06) 40%, 
                transparent 70%);
            top: 35%;
            left: 45%;
            animation-duration: 38s;
            animation-delay: -10s;
            filter: blur(90px);
        }
        
        .orb4 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle at 40% 60%, 
                rgba(92, 184, 92, 0.08), 
                rgba(92, 184, 92, 0.06) 40%, 
                transparent 70%);
            top: 10%;
            right: 15%;
            animation-duration: 42s;
            animation-delay: -15s;
            filter: blur(100px);
        }
        
        .orb5 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle at 60% 40%, 
                rgba(76, 168, 76, 0.12), 
                rgba(108, 200, 108, 0.06) 40%, 
                transparent 70%);
            bottom: 20%;
            left: 10%;
            animation-duration: 35s;
            animation-delay: -20s;
            filter: blur(90px);
        }
        
        @keyframes floatOrb {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            20% {
                transform: translate(40px, -60px) rotate(90deg);
            }
            40% {
                transform: translate(-30px, 40px) rotate(180deg);
            }
            60% {
                transform: translate(60px, 20px) rotate(270deg);
            }
            80% {
                transform: translate(-40px, -30px) rotate(360deg);
            }
        }

        /* Particle System - Premium Floating Elements */
        .particle-system {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(92, 184, 92, 0.4);
            border-radius: 50%;
            animation: particleFloat 15s infinite linear;
        }

        .particle:nth-child(1) {
            left: 10%;
            animation-delay: 0s;
            animation-duration: 18s;
        }

        .particle:nth-child(2) {
            left: 20%;
            animation-delay: 2s;
            animation-duration: 20s;
            background: rgba(92, 184, 92, 0.3);
        }

        .particle:nth-child(3) {
            left: 30%;
            animation-delay: 4s;
            animation-duration: 22s;
            background: rgba(92, 184, 92, 0.35);
        }

        .particle:nth-child(4) {
            left: 40%;
            animation-delay: 6s;
            animation-duration: 19s;
            background: rgba(92, 184, 92, 0.3);
        }

        .particle:nth-child(5) {
            left: 50%;
            animation-delay: 8s;
            animation-duration: 21s;
            background: rgba(92, 184, 92, 0.25);
        }

        .particle:nth-child(6) {
            left: 60%;
            animation-delay: 10s;
            animation-duration: 17s;
        }

        .particle:nth-child(7) {
            left: 70%;
            animation-delay: 12s;
            animation-duration: 23s;
            background: rgba(76, 168, 76, 0.3);
        }

        .particle:nth-child(8) {
            left: 80%;
            animation-delay: 14s;
            animation-duration: 20s;
            background: rgba(92, 184, 92, 0.3);
        }

        .particle:nth-child(9) {
            left: 90%;
            animation-delay: 16s;
            animation-duration: 18s;
            background: rgba(92, 184, 92, 0.25);
        }

        .particle:nth-child(10) {
            left: 95%;
            animation-delay: 18s;
            animation-duration: 24s;
            background: rgba(76, 168, 76, 0.3);
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
                opacity: 0;
            }
            10% {
                opacity: 1;
                transform: translateY(90vh) translateX(10px);
            }
            20% {
                transform: translateY(80vh) translateX(-10px);
            }
            30% {
                transform: translateY(70vh) translateX(15px);
            }
            40% {
                transform: translateY(60vh) translateX(-5px);
            }
            50% {
                transform: translateY(50vh) translateX(10px);
            }
            60% {
                transform: translateY(40vh) translateX(-15px);
            }
            70% {
                transform: translateY(30vh) translateX(5px);
            }
            80% {
                transform: translateY(20vh) translateX(-10px);
            }
            90% {
                transform: translateY(10vh) translateX(10px);
                opacity: 1;
            }
            100% {
                transform: translateY(-10vh) translateX(0);
                opacity: 0;
                opacity: 0;
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        /* Content Area - Compressed for No Scroll */
        .content-area {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 15px;
            text-align: center;
            animation: slideUp 0.6s var(--ease-smooth);
            overflow: hidden !important;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Premium Typography System - Luxury Headlines */
        .tagline {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: clamp(48px, 5.8vw, 72px);
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 16px;
            line-height: 1.05;
            letter-spacing: -0.035em;
            animation: fadeInScale 0.8s var(--ease-smooth) 0.1s both;
            position: relative;
            text-align: center;
            text-shadow: 0 4px 16px rgba(42, 38, 32, 0.08);
        }
        
        .tagline::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 2px;
            background: var(--primary-gradient);
            border-radius: 2px;
            opacity: 0;
            animation: slideIn 0.6s var(--ease-smooth) 0.4s forwards;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                width: 0;
            }
            to {
                opacity: 1;
                width: 60px;
            }
        }
        
        .highlight-text {
            position: relative;
            display: inline-block;
            /* Premium Lato Italic - Professional Weight for Elegance */
            font-family: 'Lato', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 700;  /* Slightly more bulky as requested */
            font-style: italic;  /* Elegant italic style as requested */
            font-size: 1em;  /* Same size as Trade Fresh for balance */
            /* Enhanced Multi-Tone Gradient with Dashboard Greens */
            background: linear-gradient(135deg,
                #5CB85C 0%,    /* Dashboard primary green */
                #6CC86C 25%,   /* Dashboard green light */
                #7CD87C 35%,   /* Dashboard green lighter */
                #6CC86C 65%,   /* Dashboard green light */
                #4CA84C 100%); /* Dashboard green dark */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% 200%;
            animation: subtleShimmer 20s ease-in-out infinite;  /* Much slower, subtle */
            /* Refined Visual Effects - Less intense */
            filter: brightness(1.1) contrast(1.08) saturate(1.1);
            letter-spacing: -0.015em;  /* Slightly tighter for elegance */
            /* Enhanced Dark Green Text Shadow */
            text-shadow:
                0 1px 2px rgba(92, 184, 92, 0.25),     /* Dashboard green shadow */
                0 2px 6px rgba(92, 184, 92, 0.18),     /* Dashboard green shadow */
                0 3px 12px rgba(76, 168, 76, 0.12),    /* Dashboard green depth */
                0 0 20px rgba(108, 200, 108, 0.15);    /* Dashboard green glow */
            /* Premium text enhancement */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
            font-feature-settings: 'kern' 1, 'liga' 1, 'calt' 1, 'salt' 1;
            /* Very subtle text stroke for depth */
            -webkit-text-stroke: 0.3px rgba(92, 184, 92, 0.05);
            /* Smooth transition effect */
            transition: all var(--duration-normal) var(--ease-smooth);
            cursor: default;
            /* Premium Positioning */
            margin-left: 0.12em;  /* Perfect spacing from "Trade Fresh." */
            padding: 0 0.04em;    /* Subtle padding for gradient visibility */
            /* Elegant Transform - Less dramatic */
            transform: skewX(-1.5deg) translateZ(0);
            transform-origin: center;
            will-change: filter;
        }
        
        /* Subtle Shimmer Animation for Trade Direct - Very Gentle */
        @keyframes subtleShimmer {
            0% {
                background-position: 0% 50%;
                filter: brightness(1.1) contrast(1.08) saturate(1.1);
                transform: skewX(-1.5deg) translateY(0);
            }
            25% {
                background-position: 50% 50%;
                filter: brightness(1.12) contrast(1.09) saturate(1.12);
                transform: skewX(-1.5deg) translateY(0);
            }
            50% {
                background-position: 100% 50%;
                filter: brightness(1.15) contrast(1.1) saturate(1.15);
                transform: skewX(-1.5deg) translateY(-0.5px);
            }
            75% {
                background-position: 50% 50%;
                filter: brightness(1.12) contrast(1.09) saturate(1.12);
                transform: skewX(-1.5deg) translateY(0);
            }
            100% {
                background-position: 0% 50%;
                filter: brightness(1.1) contrast(1.08) saturate(1.1);
                transform: skewX(-1.5deg) translateY(0);
            }
        }
        
        /* Enhanced Glow Effect on Load */
        .highlight-text::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120%;
            height: 120%;
            transform: translate(-50%, -50%);
            background: radial-gradient(ellipse at center,
                rgba(124, 216, 124, 0.3) 0%,
                rgba(108, 200, 108, 0.2) 30%,
                rgba(92, 184, 92, 0.1) 60%,
                transparent 100%);
            filter: blur(20px);
            opacity: 0;
            animation: glowPulse 3s ease-in-out infinite;
            pointer-events: none;
            z-index: -1;
        }
        
        @keyframes glowPulse {
            0%, 100% {
                opacity: 0;
                transform: translate(-50%, -50%);
            }
            50% {
                opacity: 0.6;
                transform: translate(-50%, -50%);
            }
        }
        
        /* Removed weird shimmer line effect completely */
        
        /* Removed shimmerSlide animation keyframes */
        
        /* Premium hover effect for the highlighted text */
        .tagline:hover .highlight-text {
            filter: brightness(1.18) contrast(1.12) saturate(1.2) drop-shadow(0 4px 16px rgba(108, 200, 108, 0.25));
            transform: skewX(-1.5deg) translateY(-2px);
            -webkit-text-stroke: 0.4px rgba(92, 184, 92, 0.08);
        }
        
        /* Responsive adjustments for premium text */
        @media (max-width: 768px) {
            .highlight-text {
                font-size: 1em;
                font-weight: 600;  /* Maintain Lato semi-bold weight */
                transform: skewX(-1.2deg) translateZ(0);
                -webkit-text-stroke: 0.25px rgba(92, 184, 92, 0.04);
            }
        }
        
        @media (max-width: 480px) {
            .highlight-text {
                font-size: 1em;
                font-weight: 600;  /* Consistent Lato weight across devices */
                transform: skewX(-1deg) translateZ(0);
                -webkit-text-stroke: 0.2px rgba(92, 184, 92, 0.03);
                letter-spacing: -0.01em;
                animation: subtleShimmer 25s ease-in-out infinite;  /* Even slower on mobile */
            }
            
            /* Shimmer effect removed - no longer needed */
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: translateY(2px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .sub-heading {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-secondary);
            font-size: clamp(19px, 2.3vw, 24px);
            font-weight: 300;
            margin-bottom: 32px;
            line-height: 1.5;
            max-width: 680px;
            margin-left: auto;
            margin-right: auto;
            letter-spacing: 0.02em;
            animation: fadeInUp 0.8s var(--ease-smooth) 0.2s both;
            opacity: 0.92;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .choice-label {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-vibrant) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            animation: fadeInUp 0.8s var(--ease-smooth) 0.3s both;
            position: relative;
            display: inline-block;
        }
        
        .choice-label::before,
        .choice-label::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--text-light));
            transform: translateY(-50%);
        }
        
        .choice-label::before {
            left: -50px;
            background: linear-gradient(90deg, transparent, var(--text-light));
        }
        
        .choice-label::after {
            right: -50px;
            background: linear-gradient(270deg, transparent, var(--text-light));
        }
        
        /* Option Cards - Stunning Neuromorphic Design */
        .options-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 20px;
            max-width: 600px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            perspective: 1000px;
        }
        
        .option-card {
            background: #E0E5EC;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            border: none;
            border-radius: 24px;
            padding: 28px 24px;
            box-shadow:
                inset 4px 4px 8px #B8BEC7,
                inset -4px -4px 8px #E8EDF4;
            transition: transform var(--duration-normal) var(--ease-out-expo),
                       box-shadow var(--duration-normal) var(--ease-smooth),
                       background var(--duration-fast) var(--ease-smooth);
            cursor: pointer;
            text-decoration: none;
            display: block;
            position: relative;
            overflow: hidden;
            transform: translateZ(0) translateY(0);
            transform-style: preserve-3d;
            backface-visibility: hidden;
            will-change: transform, box-shadow;
            animation: cardEntrance 0.8s var(--ease-elastic) 0.4s both;
            outline: none !important;
        }
        
        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(40px) rotateX(-10deg);
            }
            to {
                opacity: 1;
                transform: translateY(0) rotateX(0deg);
            }
        }
        
        .option-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, 
                rgba(224, 229, 236, 0) 0%,
                rgba(224, 229, 236, 0.5) 50%,
                rgba(224, 229, 236, 0) 100%);
            opacity: 0;
            transform: translateX(-100%);
            transition: all 0.6s var(--ease-smooth);
            pointer-events: none;
        }
        
        /* Removed ugly green glow effect */
        
        /* Vendor glow effect also removed */

        /* Removed glow effect layer completely */

        /* Vendor glow effect also completely removed */
        
        .option-card:hover {
            transform: translateY(0) translateZ(0);
            box-shadow:
                inset 5px 5px 10px #B8BEC7,
                inset -5px -5px 10px #E8EDF4;
            background: linear-gradient(135deg, #E0E5EC 0%, #E8EDF4 100%);
        }

        .option-card.vendor:hover {
            box-shadow:
                inset 5px 5px 10px #B8BEC7,
                inset -5px -5px 10px #E8EDF4;
        }

        /* Both buyer and vendor cards get deeper inset when clicked */
        .option-card.buyer:active {
            transform: translateZ(0) translateY(0) !important;
            box-shadow:
                inset 6px 6px 12px #B8BEC7,
                inset -6px -6px 12px #E8EDF4 !important;
            transition-duration: var(--duration-instant);
            outline: none !important;
        }

        .option-card.vendor:active {
            transform: translateZ(0) translateY(0) !important;
            box-shadow:
                inset 6px 6px 12px #B8BEC7,
                inset -6px -6px 12px #E8EDF4 !important;
            transition-duration: var(--duration-instant);
            outline: none !important;
        }
        
        .option-card:hover::before {
            opacity: 1;
            transform: translateX(100%);
            transition: all 0.6s var(--ease-smooth);
        }
        
        /* Removed ::after green background glow on hover */

        /* Removed option-card-glow hover effect */
        
        /* General active state removed - using specific buyer/vendor states above */
        
        /* Remove all focus outlines from option cards */
        .option-card:focus,
        .option-card:focus-visible,
        .option-card:focus-within,
        .option-card.vendor:focus,
        .option-card.vendor:focus-visible,
        .option-card.vendor:focus-within {
            outline: none !important;
            box-shadow:
                9px 9px 16px #B8BEC7,
                -9px -9px 16px #E8EDF4;
        }

        /* Touch Device Optimizations */
        @media (hover: none) {
            .option-card.buyer:active,
            .option-card.vendor:active {
                opacity: 0.98;
                box-shadow:
                    inset 5px 5px 10px #B8BEC7,
                    inset -5px -5px 10px #E8EDF4 !important;
            }
        }
        
        .option-title {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
            transition: color var(--duration-fast) var(--ease-smooth);
            letter-spacing: -0.02em;
            color: var(--text-primary);
            -webkit-font-smoothing: subpixel-antialiased;
            -moz-osx-font-smoothing: auto;
            text-rendering: geometricPrecision;
            font-feature-settings: 'kern' 1, 'liga' 0;
        }
        
        .option-card:hover .option-title {
            color: var(--text-primary);
            /* Removed jittery scale transform */
        }
        
        /* Premium Neuromorphic Icon System - Matching Login/Register Style */
        .option-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 18px;
            background: #E0E5EC; /* Dashboard neumorphic background */
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            /* MATCHING DASHBOARD NEUROMORPHIC STYLE - Deep inset effect */
            box-shadow: 
                inset 4px 4px 8px rgba(184, 190, 199, 0.4),
                inset -4px -4px 8px #E8EDF4;
            transition: all var(--duration-normal) var(--ease-spring);
            transform: translateZ(0);
            will-change: transform, box-shadow;
        }
        
        /* Inner Circle Element - Like Dashboard Design */
        .option-icon::before {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, var(--neu-surface) 0%, var(--neu-light) 100%);
            border-radius: 50%;
            box-shadow: 
                inset 2px 2px 4px rgba(184, 190, 199, 0.25),
                inset -2px -2px 4px #E8EDF4;
            opacity: 0.5;
            z-index: 0;
        }
        
        /* Remove glossy overlay for cleaner neuromorphic look */
        .option-icon::after {
            display: none;
        }
        
        /* Removed hover effects for cleaner design */
        
        /* SVG Icon Styling - Theme colors only */
        .option-icon svg {
            transition: all var(--duration-normal) var(--ease-smooth);
            width: 24px;
            height: 24px;
            z-index: 1;
            position: relative;
            stroke-width: 2;
        }
        
        /* Buy Produce Card Icon - Inset Style */
        .option-card.buyer .option-icon {
            background: #E0E5EC;
            /* Inset neuromorphic shadows */
            box-shadow:
                inset 3px 3px 6px #B8BEC7,
                inset -3px -3px 6px #E8EDF4;
        }
        
        .option-card.buyer .option-icon svg {
            stroke: #2A2620; /* Dashboard dark text */
            fill: none;
        }
        
        .option-card.buyer .option-icon svg path {
            fill: none !important;
        }
        
        .option-card.buyer .option-icon svg circle {
            fill: none;
            stroke: #2A2620;
        }
        
        /* Sell Produce Card Icon - Same inset style as Buy Produce */
        .option-card.vendor .option-icon {
            background: #E0E5EC;
            /* Inset neuromorphic shadows - same as buyer */
            box-shadow:
                inset 3px 3px 6px #B8BEC7,
                inset -3px -3px 6px #E8EDF4;
        }
        
        .option-card.vendor .option-icon svg {
            stroke: #2A2620; /* Dashboard dark text */
            fill: none;
        }
        
        .option-card.vendor .option-icon svg path {
            fill: none !important;
        }
        
        .option-card.vendor .option-icon svg rect {
            fill: none;
            stroke: #2A2620;
        }
        
        /* Hover State - Enhanced Neuromorphic Effect */
        .option-card:hover .option-icon {
            transform: translateY(-2px);
            /* Deeper neuromorphic effect on hover */
            box-shadow:
                inset 6px 6px 12px #B8BEC7,
                inset -6px -6px 12px #E8EDF4;
        }
        
        /* Buyer hover - deeper inset on hover */
        .option-card.buyer:hover .option-icon {
            /* Deeper inset effect on hover */
            box-shadow:
                inset 4px 4px 8px #B8BEC7,
                inset -4px -4px 8px #E8EDF4;
        }

        .option-card.buyer:hover .option-icon svg {
            transform: translateY(-2px);
            stroke-width: 2;
            stroke: #4A453B; /* Slightly darker on hover */
        }
        
        /* Keep consistent style on hover */
        .option-card.buyer:hover .option-icon svg circle {
            fill: none;
            stroke: #2A2620;
        }
        
        /* Vendor hover - deeper inset on hover */
        .option-card.vendor:hover .option-icon {
            /* Deeper inset effect on hover */
            box-shadow:
                inset 4px 4px 8px #B8BEC7,
                inset -4px -4px 8px #E8EDF4;
        }
        
        .option-card.vendor:hover .option-icon svg {
            transform: translateY(-2px);
            stroke-width: 2;
            stroke: #4A453B; /* Slightly darker on hover */
        }
        
        /* Keep consistent style on hover */
        .option-card.vendor:hover .option-icon svg rect {
            fill: none;
            stroke: #2A2620;
        }
        
        /* Both cards' icons get deepest inset when pressed */
        .option-card.buyer:active .option-icon,
        .option-card.vendor:active .option-icon {
            transform: translateY(1px);
            /* Deepest pressed inset effect for both */
            box-shadow:
                inset 5px 5px 10px #B8BEC7,
                inset -5px -5px 10px #E8EDF4;
        }

        .option-card:active .option-icon svg {
            transform: translateY(2px);
            opacity: 0.9;
            stroke: #2A2620 !important; /* Keep dark on active */
        }
        
        .option-description {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.55;
            font-weight: 400;
            letter-spacing: 0.005em;
            transition: color var(--duration-fast) var(--ease-smooth);
            -webkit-font-smoothing: subpixel-antialiased;
            -moz-osx-font-smoothing: auto;
            text-rendering: geometricPrecision;
        }
        
        .option-card:hover .option-description {
            color: var(--text-secondary);
        }
        
        /* Clean Divider - Compressed */
        .divider {
            font-family: 'Montserrat', sans-serif;
            color: var(--text-muted);
            font-size: 11px;
            margin: 12px auto;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 600;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 200px;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            height: 1px;
            width: 50px;
            background: linear-gradient(90deg, transparent, rgba(184, 190, 199, 0.3), transparent);
            top: 50%;
        }
        
        .divider::before {
            left: -60px;
        }
        
        .divider::after {
            right: -60px;
        }
        
        /* Premium CTA Button - Luxury Design */
        .cta-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 16px 40px;
            background: linear-gradient(135deg, #5CB85C 0%, #6CC86C 50%, #7CD87C 100%);
            background-size: 200% 200%;
            background-position: 0% 50%;
            color: white;
            font-family: 'Montserrat', sans-serif;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.02em;
            border: none;
            border-radius: 16px;
            box-shadow: 
                0 4px 20px rgba(92, 184, 92, 0.25),
                0 12px 48px rgba(92, 184, 92, 0.15),
                0 2px 8px rgba(76, 168, 76, 0.1),
                inset 0 2px 4px rgba(224, 229, 236, 0.2);
            cursor: pointer;
            transition: transform var(--duration-normal) var(--ease-out-expo),
                       box-shadow var(--duration-normal) var(--ease-smooth),
                       background-position var(--duration-slow) var(--ease-smooth),
                       filter var(--duration-fast) var(--ease-smooth);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            transform: translateZ(0) translateY(0);
            will-change: transform, box-shadow;
            animation: ctaEntrance 0.8s var(--ease-elastic) 0.6s both;
        }
        
        @keyframes ctaEntrance {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Shimmer effect */
        .cta-button::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -100%;
            width: 100%;
            height: 104%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(224, 229, 236, 0.3),
                rgba(224, 229, 236, 0.5),
                rgba(224, 229, 236, 0.3), 
                transparent);
            transition: left 0.7s var(--ease-smooth);
            transform: skewX(-20deg);
        }
        
        /* Glow effect */
        .cta-button-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(92, 184, 92, 0.4), transparent 70%);
            opacity: 0;
            transition: opacity var(--duration-slow) var(--ease-smooth);
            pointer-events: none;
        }
        
        .cta-button:hover::before {
            left: 100%;
        }
        
        .cta-button:hover .cta-button-glow {
            opacity: 1;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: translateY(0);
                opacity: 1;
            }
            50% {
                transform: translateY(-1px);
                opacity: 0.7;
            }
        }
        
        .cta-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent, rgba(224, 229, 236, 0.1));
            opacity: 0;
            transition: opacity var(--duration-normal) var(--ease-smooth);
        }
        
        .cta-button:hover {
            transform: translateZ(0) translateY(-3px);
            box-shadow: 
                0 8px 30px rgba(92, 184, 92, 0.35),
                0 20px 60px rgba(92, 184, 92, 0.2),
                0 4px 12px rgba(76, 168, 76, 0.15),
                inset 0 2px 6px rgba(224, 229, 236, 0.3);
            background-position: 100% 50%;
            filter: brightness(1.08) saturate(1.1);
        }
        
        .cta-button:hover::after {
            opacity: 1;
        }
        
        .cta-arrow {
            display: inline-block;
            transition: transform var(--duration-fast) var(--ease-spring);
            font-size: 20px;
            will-change: transform;
        }
        
        .cta-button:hover .cta-arrow {
            transform: translateX(4px);
        }
        
        .cta-button:active {
            transform: translateZ(0) translateY(-2px);
            box-shadow: 
                0 6px 20px rgba(92, 184, 92, 0.3),
                0 15px 40px rgba(92, 184, 92, 0.15),
                inset 0 1px 3px rgba(0, 0, 0, 0.08);
            transition-duration: var(--duration-instant);
        }
        
        .cta-button span {
            position: relative;
            z-index: 2;
        }
        
        /* Enhanced Responsive - Maintain No Scroll */
        @media (max-width: 768px) {
            .content-area {
                padding: 10px;
            }
            
            .tagline {
                font-size: 36px;
                margin-bottom: 12px;
            }
            
            .tagline::after {
                bottom: -4px;
                width: 40px;
            }
            
            .sub-heading {
                font-size: 17px;
                margin-bottom: 24px;
            }
            
            .choice-label {
                font-size: 14px;
                margin-bottom: 24px;
            }
            
            .choice-label::before,
            .choice-label::after {
                width: 30px;
            }
            
            .choice-label::before {
                left: -40px;
            }
            
            .choice-label::after {
                right: -40px;
            }
            
            .live-indicator {
                padding: 8px 16px;
                margin-bottom: 20px;
            }
            
            .options-container {
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                max-width: 100%;
            }
            
            .option-card {
                padding: 20px 15px;
            }
            
            .option-icon {
                width: 40px;
                height: 40px;
                margin-bottom: 12px;
            }
            
            .option-title {
                font-size: 17px;
            }
            
            .option-description {
                font-size: 13px;
            }
            
            .option-card:hover {
                transform: translateY(-2px) translateZ(0);
            }
            
            .divider {
                margin: 15px auto;
            }
            
            .cta-button {
                padding: 12px 36px;
                font-size: 15px;
            }
            
            .orb1, .orb2, .orb3 {
                opacity: 0.15;
            }
        }
        
        @media (max-width: 480px) {
            .content-area {
                padding: 8px;
            }
            
            .tagline {
                font-size: 28px;
                margin-bottom: 10px;
            }
            
            .tagline::after {
                width: 30px;
                height: 2px;
                bottom: -3px;
            }
            
            .sub-heading {
                font-size: 15px;
                margin-bottom: 20px;
                line-height: 1.4;
            }
            
            .choice-label {
                font-size: 12px;
                margin-bottom: 20px;
                letter-spacing: 0.06em;
            }
            
            .choice-label::before,
            .choice-label::after {
                display: none;
            }
            
            .live-indicator {
                padding: 6px 12px;
                margin-bottom: 15px;
            }
            
            .live-text {
                font-size: 11px;
            }
            
            .options-container {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .option-card {
                padding: 16px 12px;
            }
            
            .option-icon {
                width: 36px;
                height: 36px;
                margin-bottom: 8px;
            }
            
            .option-title {
                font-size: 15px;
                margin-bottom: 6px;
            }
            
            .option-description {
                font-size: 11px;
            }
            
            .divider {
                margin: 12px auto;
            }
            
            .cta-button {
                padding: 10px 28px;
                font-size: 14px;
            }
        }
        
        /* Large Screen Optimizations - Keep Compact */
        @media (min-width: 1200px) {
            .content-area {
                padding: 30px;
            }
            
            .options-container {
                gap: 30px;
                max-width: 650px;
            }
        }
        
        /* Ultra Wide Screens */
        @media (min-width: 1920px) {
            .tagline {
                font-size: 72px;
                margin-bottom: 24px;
            }
            
            .sub-heading {
                font-size: 26px;
                margin-bottom: 48px;
            }
            
            .choice-label {
                font-size: 18px;
            }
            
            .options-container {
                max-width: 720px;
                gap: 36px;
            }
            
            .option-card {
                padding: 42px 32px;
            }
            
            .option-icon {
                width: 64px;
                height: 64px;
            }
            
            .option-title {
                font-size: 24px;
            }
            
            .option-description {
                font-size: 16px;
            }
            
            .cta-button {
                padding: 18px 40px;
                font-size: 18px;
            }
        }
        
        /* Reduced Motion for Accessibility */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        
        /* High Contrast Mode Support */
        @media (prefers-contrast: high) {
            .option-card {
                border: 2px solid #2A2620;
            }
            
            .cta-button {
                border: 2px solid var(--primary);
            }
        }
        
        /* Loading State Animation */
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        .loading {
            animation: pulse 2s infinite;
        }
        
        /* Premium Live Indicator - Inset Style */
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #E0E5EC;
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            border: none;
            padding: 8px 20px;
            border-radius: 100px;
            box-shadow:
                inset 4px 4px 8px #B8BEC7,
                inset -4px -4px 8px #E8EDF4;
            margin-bottom: 20px;
            animation: slideDown 0.6s var(--ease-smooth) 0.1s both;
            position: relative;
            transition: all var(--duration-normal) var(--ease-smooth);
        }

        .live-indicator:hover {
            transform: translateY(0);
            box-shadow:
                inset 5px 5px 10px #B8BEC7,
                inset -5px -5px 10px #E8EDF4;
            background: linear-gradient(135deg,
                #D8DDE4 0%,
                #C8CDD4 100%);
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .pulse-dot {
            width: 8px;
            height: 8px;
            background: linear-gradient(45deg, #5CB85C, #6CC86C);
            border-radius: 50%;
            position: relative;
            animation: livePulse 2s infinite;
            box-shadow: 0 0 0 2px rgba(92, 184, 92, 0.2);
        }
        
        .pulse-dot::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #5CB85C, #6CC86C);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: pulseRing 2s infinite;
        }
        
        @keyframes livePulse {
            0%, 100% {
                background: linear-gradient(45deg, #5CB85C, #6CC86C);
                transform: translateY(0);
            }
            50% {
                background: linear-gradient(45deg, #6CC86C, #F0A830);
                transform: translateY(-1px);
            }
        }
        
        @keyframes pulseRing {
            0% {
                transform: translate(-50%, -50%);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%);
                opacity: 0;
            }
        }
        
        .live-text {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
            letter-spacing: 0.01em;
        }
        
        .live-text strong {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(90deg, #5CB85C 0%, #6CC86C 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 14px;
            margin: 0 4px;
            transition: all var(--duration-fast) var(--ease-smooth);
            font-variant-numeric: tabular-nums;
            letter-spacing: -0.01em;
        }
        
        /* ============================================
           MODAL STYLES - CREAM-GREEN NEUROMORPHIC THEME
           ============================================ */
        
        /* Modal Backdrop */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(184, 190, 199, 0.95);
            backdrop-filter: blur(8px) saturate(180%);
            -webkit-backdrop-filter: blur(8px) saturate(180%);
            animation: modalFadeIn 0.3s var(--ease-smooth);
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        /* Modal Content Container */
        .modal-content {
            position: relative;
            background: #DDE2E9;
            margin: 50vh auto;
            transform: translateY(-50%);
            padding: 0;
            border-radius: 24px;
            width: 90%;
            max-width: 420px;
            max-height: 84vh;
            overflow: hidden;
            box-shadow:
                0 25px 50px rgba(42, 38, 32, 0.15),
                0 15px 35px rgba(184, 190, 199, 0.08),
                0 5px 15px rgba(184, 190, 199, 0.05),
                inset 0 1px 2px rgba(184, 190, 199, 0.9);
            animation: modalSlideIn 0.35s var(--ease-out-expo);
            /* Prevent layout shifts during animation */
            contain: layout style paint;
            transform-origin: center center;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50%);
            }
            to {
                opacity: 1;
                transform: translateY(-50%);
            }
        }
        
        /* Modal Header */
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 28px 14px;
            border-bottom: 1px solid rgba(184, 190, 199, 0.12);
            background: #DDE2E9;
        }
        
        .modal-title {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 22px;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--text-primary);
            margin: 0;
            letter-spacing: -0.01em;
        }
        
        /* Modal Close Button */
        .modal-close {
            background: #E0E5EC;
            border: none;
            border-radius: 12px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            font-weight: 300;
            color: var(--text-muted);
            transition: all var(--duration-normal) var(--ease-smooth);
            box-shadow: 
                inset 3px 3px 6px rgba(184, 190, 199, 0.3),
                inset -3px -3px 6px #E8EDF4;
        }
        
        .modal-close:hover {
            color: var(--text-primary);
            box-shadow: 
                inset 4px 4px 8px rgba(184, 190, 199, 0.4),
                inset -4px -4px 8px #E8EDF4;
            transform: translateY(-2px);
        }
        
        /* Modal Body */
        .modal-body {
            padding: 20px 28px 24px;
            line-height: 1.5;
        }
        
        /* Modal Footer */
        .modal-footer {
            padding: 16px 28px 28px;
            background: #DDE2E9;
            border-top: 1px solid rgba(184, 190, 199, 0.12);
        }
        
        /* Form Elements in Modal - Cream-Green Theme */
        .modal .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .modal .input-field {
            width: 100%;
            height: 52px;
            padding: 0 20px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 15px;
            font-weight: 400;
            letter-spacing: -0.01em;
            font-size: 15px;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-weight: 400;
            border: none;
            border-radius: 14px;
            background: #E0E5EC !important;
            box-shadow:
                inset 2px 2px 5px rgba(184, 190, 199, 0.25),
                inset -2px -2px 5px #E8EDF4 !important;
            transition: all 0.2s var(--ease-smooth);
            outline: none;
            color: var(--text-primary) !important;
            letter-spacing: -0.005em;
        }
        
        /* Force override browser autofill styles */
        .modal .input-field:-webkit-autofill {
            -webkit-box-shadow: 
                inset 2px 2px 5px rgba(184, 190, 199, 0.25),
                inset -2px -2px 5px #E8EDF4,
                inset 0 0 0 1000px #E0E5EC !important;
            -webkit-text-fill-color: var(--text-primary) !important;
            background-color: #E0E5EC !important;
        }
        
        .modal .input-field:-webkit-autofill:hover {
            -webkit-box-shadow: 
                inset 4px 4px 8px rgba(184, 190, 199, 0.35),
                inset -4px -4px 8px #E8EDF4,
                inset 0 0 0 1000px #DDE2E9 !important;
        }
        
        .modal .input-field:-webkit-autofill:focus {
            -webkit-box-shadow: 
                inset 4px 4px 8px rgba(184, 190, 199, 0.4),
                inset -4px -4px 8px #E8EDF4,
                0 0 0 3px rgba(92, 184, 92, 0.1),
                inset 0 0 0 1000px #D8DDE4 !important;
        }
        
        .modal .input-field::placeholder {
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 400;
            letter-spacing: -0.005em;
        }
        
        .modal .input-field:hover {
            box-shadow: 
                inset 4px 4px 8px rgba(184, 190, 199, 0.35),
                inset -4px -4px 8px #E8EDF4;
        }
        
        .modal .input-field:focus {
            box-shadow: 
                inset 4px 4px 8px rgba(184, 190, 199, 0.4),
                inset -4px -4px 8px #E8EDF4,
                0 0 0 3px rgba(92, 184, 92, 0.1);
            background: #D8DDE4;
        }
        
        /* Password Toggle in Modal */
        .modal .password-wrapper {
            position: relative;
        }
        
        .modal .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 6px;
            cursor: pointer;
            color: rgba(139, 132, 120, 0.6);
            transition: all 0.2s var(--ease-smooth);
            border-radius: 8px;
        }
        
        .modal .password-toggle:hover {
            color: rgba(139, 132, 120, 0.85);
            background: rgba(224, 229, 236, 0.5);
        }
        
        .modal .password-toggle svg {
            width: 16px;
            height: 16px;
            stroke-width: 1.5;
        }
        
        /* Form Options in Modal */
        .modal .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
            margin-bottom: 24px;
        }
        
        /* Checkbox in Modal */
        .modal .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal .checkbox-wrapper {
            position: relative;
            width: 18px;
            height: 18px;
        }
        
        .modal .checkbox-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .modal .checkbox-custom {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            background: #E0E5EC;
            box-shadow:
                inset 2px 2px 4px rgba(184, 190, 199, 0.4),
                inset -2px -2px 4px #E8EDF4;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s var(--ease-smooth);
            cursor: pointer;
        }
        
        .modal .checkbox-input:checked ~ .checkbox-custom {
            background: var(--primary);
            box-shadow: 
                0 2px 4px rgba(92, 184, 92, 0.3),
                inset 0 1px 2px rgba(224, 229, 236, 0.2);
        }
        
        .modal .checkbox-custom svg {
            width: 12px;
            height: 12px;
            color: white;
            opacity: 0;
            transition: opacity 0.2s var(--ease-smooth);
        }
        
        .modal .checkbox-input:checked ~ .checkbox-custom svg {
            opacity: 1;
        }
        
        .modal .checkbox-label {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--text-secondary);
            user-select: none;
            cursor: pointer;
        }
        
        .modal .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            transition: color var(--duration-fast) var(--ease-smooth);
        }
        
        .modal .forgot-link:hover {
            color: var(--primary-dark);
        }
        
        /* PREMIUM NEUROMORPHIC SUBMIT BUTTON - B2B EXCELLENCE */
        .modal .btn {
            width: 100%;
            height: 54px;
            padding: 0 28px;
            border-radius: 18px;
            font-size: 16px;
            font-weight: 600;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            letter-spacing: -0.01em;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .modal .btn-primary {
            /* Premium gradient with depth */
            background: linear-gradient(145deg,
                #5CB85C 0%,
                #6CC86C 25%,
                #7CD87C 50%,
                #6CC86C 75%,
                #4CA84C 100%);
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
            
            /* TRUE NEUROMORPHIC - RAISED EFFECT */
            box-shadow: 
                /* Primary elevation shadows */
                0 8px 20px rgba(92, 184, 92, 0.25),
                0 4px 12px rgba(108, 200, 108, 0.15),
                0 2px 6px rgba(124, 216, 124, 0.1),
                
                /* Inner light reflection */
                inset 0 2px 4px rgba(224, 229, 236, 0.2),
                inset 0 -1px 2px rgba(0, 0, 0, 0.1),
                
                /* Subtle border definition */
                0 0 0 0.5px rgba(224, 229, 236, 0.1);
        }
        
        /* Premium Shimmer Effect */
        .modal .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent 0%, 
                rgba(224, 229, 236, 0.2) 45%,
                rgba(224, 229, 236, 0.4) 50%,
                rgba(224, 229, 236, 0.2) 55%,
                transparent 100%);
            transition: left 0.5s ease;
            pointer-events: none;
        }
        
        .modal .btn-primary:hover::before {
            left: 100%;
        }
        
        .modal .btn-primary:hover {
            /* Enhanced elevation on hover */
            background: linear-gradient(145deg, 
                #0E8A72 0%, 
                #12B394 25%,
                #16C6A4 50%, 
                #0F9B82 75%,
                #0B7461 100%);
            
            box-shadow: 
                /* Deeper shadows for more elevation */
                0 12px 32px rgba(92, 184, 92, 0.35),
                0 6px 16px rgba(92, 184, 92, 0.25),
                0 3px 8px rgba(20, 179, 148, 0.15),
                
                /* Enhanced inner glow */
                inset 0 3px 6px rgba(224, 229, 236, 0.25),
                inset 0 -1px 3px rgba(0, 0, 0, 0.12),
                
                /* Glowing border */
                0 0 0 1px rgba(224, 229, 236, 0.15),
                0 0 20px rgba(92, 184, 92, 0.15);
                
            transform: translateY(-3px);
        }
        
        .modal .btn-primary:active {
            /* Pressed state - subtle inset */
            background: linear-gradient(145deg, 
                #4CA84C 0%, 
                #5CB85C 25%,
                #5CB85C 50%, 
                #4CA84C 75%,
                #3C983C 100%);
                
            box-shadow: 
                /* Reduced elevation */
                0 4px 12px rgba(92, 184, 92, 0.3),
                0 2px 6px rgba(92, 184, 92, 0.2),
                
                /* Subtle inset for pressed feel */
                inset 0 1px 3px rgba(0, 0, 0, 0.2),
                inset 0 -1px 2px rgba(224, 229, 236, 0.1);
                
            transform: translateY(-2px);
        }
        
        /* Icon styling in button */
        .modal .btn-primary svg {
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
            transition: transform 0.2s ease;
        }
        
        .modal .btn-primary:hover svg {
            transform: translateY(-2px);
        }
        
        .modal .btn svg {
            width: 16px;
            height: 16px;
        }
        
        /* Register Text in Modal */
        .modal .register-text {
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            color: var(--text-secondary);
            text-align: center;
            margin: 0;
        }
        
        .modal .register-link {
            color: var(--primary);
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            font-size: 13px;
            transition: color var(--duration-fast) var(--ease-smooth);
        }
        
        .modal .register-link:hover {
            color: var(--primary-dark);
        }
        
        /* Mobile Responsive Modal */
        @media (max-width: 768px) {
            .modal-content {
                margin: 50vh auto;
                transform: translateY(-50%);
                width: 95%;
                max-width: none;
                border-radius: 20px;
            }
            
            .modal-header {
                padding: 20px 24px 12px;
            }
            
            .modal-title {
                font-size: 20px;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                letter-spacing: -0.015em;
            }
            
            .modal-body {
                padding: 16px 24px;
            }
            
            .modal-footer {
                padding: 12px 24px 24px;
            }
            
            .modal .input-field {
                height: 48px;
                font-size: 16px; /* Prevents zoom on iOS */
                padding: 0 18px;
            }
            
            .modal .btn {
                height: 52px;
                font-size: 16px;
            }
        }
        
        /* Modal Animation on Close */
        .modal.closing {
            animation: modalFadeOut 0.3s var(--ease-smooth) forwards;
        }
        
        .modal.closing .modal-content {
            animation: modalSlideOut 0.3s var(--ease-smooth) forwards;
        }
        
        @keyframes modalFadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        @keyframes modalSlideOut {
            from {
                opacity: 1;
                transform: translateY(-50%);
            }
            to {
                opacity: 0;
                transform: translateY(-50%);
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Enhanced Floating Orbs with Depth -->
        <div class="floating-orbs">
            <div class="orb orb1"></div>
            <div class="orb orb2"></div>
            <div class="orb orb3"></div>
            <div class="orb orb4"></div>
            <div class="orb orb5"></div>
        </div>
        
        <!-- Premium Particle System -->
        <div class="particle-system">
            <span class="particle"></span>
            <span class="particle"></span>
            <span class="particle"></span>
            <span class="particle"></span>
            <span class="particle"></span>
            <span class="particle"></span>
            <span class="particle"></span>
            <span class="particle"></span>
            <span class="particle"></span>
            <span class="particle"></span>
        </div>
        
        <!-- Logo removed - cleaner design -->
        
        <!-- Content Area with Enhanced Visual Hierarchy -->
        <div class="content-area">
            <!-- Live Traders Count -->
            <div class="live-indicator">
                <span class="pulse-dot"></span>
                <span class="live-text">Live Now: <strong id="tradersCount">2,847</strong> Active Traders</span>
            </div>
            
            <!-- Main Heading - Clean Modern Typography -->
            <h1 class="tagline">Trade Fresh. <span class="highlight-text">Trade Direct.</span></h1>
            <p class="sub-heading">Sydney's #1 B2B Fresh Produce Wholesale Marketplace</p>
            
            <!-- Choice Section -->
            <div class="choice-label">I want to...</div>
            
            <!-- Options with Enhanced Neuromorphic Design -->
            <div class="options-container">
                <a href="#" class="option-card buyer" onclick="openLoginModal('buyer'); return false;">
                    <div class="option-icon">
                        <!-- Neuromorphic Shopping Cart - Matching Login Style -->
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Main cart body with clean lines -->
                            <path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M17 13v8m0-8L15 21m2-8l2 8" stroke="#2A2620" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0"/>
                            
                            <!-- Simplified clean cart -->
                            <path d="M3 3h2l2 10h10l3-7H8" stroke="#2A2620" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            
                            <!-- Wheels -->
                            <circle cx="9" cy="19" r="1" stroke="#2A2620" stroke-width="2"/>
                            <circle cx="17" cy="19" r="1" stroke="#2A2620" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="option-title">Buy Produce</div>
                    <div class="option-description">Source directly from verified wholesale suppliers</div>
                </a>
                
                <a href="#" class="option-card vendor" onclick="openLoginModal('vendor'); return false;">
                    <div class="option-icon">
                        <!-- Neuromorphic Store - Matching Login Style -->
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Clean store outline -->
                            <path d="M3 21h18M3 10h18M3 7l9-4 9 4M6 10v11M18 10v11" stroke="#2A2620" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            
                            <!-- Door -->
                            <rect x="10" y="14" width="4" height="7" stroke="#2A2620" stroke-width="2" rx="0.5"/>
                        </svg>
                    </div>
                    <div class="option-title">Sell Produce</div>
                    <div class="option-description">Reach qualified buyers across Sydney Markets</div>
                </a>
            </div>
            
            <!-- Premium CTA Section with Enhanced Effects -->
            <div class="divider">or</div>
            <a href="#" id="getStartedBtn" class="cta-button">
                <span>Get Started Free</span>
                <span class="cta-arrow">→</span>
            </a>
        </div>
    </div>
    
    <!-- Login Modals -->
    <!-- Buyer Login Modal -->
    <div id="buyerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Buyer Sign In</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="{{ route('buyer.login.post') }}" id="buyerLoginForm">
                @csrf
                <div class="modal-body">
                    <!-- Email Field -->
                    <div class="input-group">
                        <input 
                            type="email" 
                            name="email" 
                            class="input-field" 
                            placeholder="Email"
                            value="{{ old('email') }}"
                            required 
                            autofocus>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="input-group">
                        <div class="password-wrapper">
                            <input 
                                type="password" 
                                id="buyerPassword"
                                name="password" 
                                class="input-field" 
                                placeholder="Password"
                                required>
                            <button type="button" class="password-toggle" onclick="togglePasswordModal('buyer')">
                                <svg class="eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed" style="display: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember & Forgot -->
                    <div class="form-options">
                        <div class="checkbox-group">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="buyerRemember" name="remember" class="checkbox-input" checked>
                                <div class="checkbox-custom">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <label for="buyerRemember" class="checkbox-label">
                                Remember me
                            </label>
                        </div>
                        
                        <a href="#" class="forgot-link" onclick="showForgotPasswordModal(); return false;">
                            Forgot password?
                        </a>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">
                        Sign In
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                    
                    <!-- Register Link -->
                    <div class="register-text">
                        New to Sydney Markets?
                        <a href="{{ route('buyer.register') }}" class="register-link">Create Account</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Vendor Login Modal -->
    <div id="vendorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Vendor Sign In</h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="{{ route('vendor.login.post') }}" id="vendorLoginForm">
                @csrf
                <div class="modal-body">
                    <!-- Email Field -->
                    <div class="input-group">
                        <input 
                            type="email" 
                            name="email" 
                            class="input-field" 
                            placeholder="Email"
                            value="{{ old('email') }}"
                            required 
                            autofocus>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="input-group">
                        <div class="password-wrapper">
                            <input 
                                type="password" 
                                id="vendorPassword"
                                name="password" 
                                class="input-field" 
                                placeholder="Password"
                                required>
                            <button type="button" class="password-toggle" onclick="togglePasswordModal('vendor')">
                                <svg class="eye-open" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="eye-closed" style="display: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Remember & Forgot -->
                    <div class="form-options">
                        <div class="checkbox-group">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="vendorRemember" name="remember" class="checkbox-input" checked>
                                <div class="checkbox-custom">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <label for="vendorRemember" class="checkbox-label">
                                Remember me
                            </label>
                        </div>
                        
                        <a href="#" class="forgot-link" onclick="showVendorForgotPasswordModal(); return false;">
                            Forgot password?
                        </a>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">
                        Sign In
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                    
                    <!-- Register Link -->
                    <div class="register-text">
                        New vendor?
                        <a href="{{ route('vendor.register') }}" class="register-link">Create Account</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <!-- Form State -->
            <div id="forgotFormState" class="modal-state">
                <div class="modal-header">
                    <h2 class="modal-title">Reset Password</h2>
                    <button class="modal-close" onclick="closeForgotPasswordModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="text-align: center; margin-bottom: 32px;">
                        <p style="font-size: 15px; color: var(--text-secondary); line-height: 1.5; margin: 0; font-family: 'Inter', sans-serif; letter-spacing: -0.01em;">
                            We'll send a reset link to your email
                        </p>
                    </div>
                    
                    <form id="forgotPasswordForm">
                        @csrf
                        <div class="input-group">
                            <input 
                                type="email" 
                                id="resetEmail" 
                                name="email" 
                                class="input-field" 
                                placeholder="Enter your email address" 
                                required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" form="forgotPasswordForm" class="btn btn-primary" id="sendResetBtn">
                        Send Link
                    </button>
                    
                    <div class="register-text">
                        <a href="#" onclick="closeForgotPasswordModal(); return false;" class="register-link">Back to Sign In</a>
                    </div>
                </div>
            </div>
            
            <!-- Loading State -->
            <div id="forgotLoadingState" class="modal-state" style="display: none;">
                <div class="modal-header">
                    <h2 class="modal-title">Sending Email...</h2>
                    <button class="modal-close" onclick="closeForgotPasswordModal()">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center; padding: 40px 24px;">
                    <div style="display: inline-block; position: relative;">
                        <div class="spinner-ring"></div>
                        <div class="spinner-ring" style="animation-delay: 0.15s;"></div>
                        <div class="spinner-ring" style="animation-delay: 0.3s;"></div>
                    </div>
                    <p style="margin-top: 24px; color: var(--text-secondary); font-size: 14px;">
                        Please wait while we send your reset link...
                    </p>
                </div>
            </div>
            
            <!-- Success State -->
            <div id="forgotSuccessState" class="modal-state" style="display: none;">
                <div class="modal-header">
                    <h2 class="modal-title">Email Sent!</h2>
                    <button class="modal-close" onclick="closeForgotPasswordModal()">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center; padding: 32px 24px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(145deg, var(--primary), var(--primary-vibrant)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; animation: successPulse 2s infinite;">
                        <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                            <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                        </svg>
                    </div>
                    <p style="font-size: 16px; color: var(--text-primary); margin-bottom: 16px; font-weight: 500;">
                        Password reset link sent!
                    </p>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 24px;">
                        We've sent a password reset link to your email address. Check your inbox and follow the instructions to reset your password.
                    </p>
                    <div style="background: rgba(92, 184, 92, 0.05); border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                        <p style="font-size: 13px; color: var(--text-secondary); margin: 0; line-height: 1.4;">
                            <strong>Note:</strong> The reset link will expire in 2 hours. If you don't see the email, check your spam folder.
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="closeForgotPasswordModal()">
                        Continue
                    </button>
                </div>
            </div>
            
            <!-- Error State -->
            <div id="forgotErrorState" class="modal-state" style="display: none;">
                <div class="modal-header">
                    <h2 class="modal-title">Unable to Send Email</h2>
                    <button class="modal-close" onclick="closeForgotPasswordModal()">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center; padding: 32px 24px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(145deg, #8B4444, #A85555); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; animation: errorShake 0.5s ease-in-out;">
                        <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <p style="font-size: 16px; color: var(--text-primary); margin-bottom: 16px; font-weight: 500;" id="errorTitle">
                        Something went wrong
                    </p>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 24px;" id="errorMessage">
                        We couldn't send the reset email. Please try again or contact support if the problem persists.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="resetForgotPasswordModal()">
                        Try Again
                    </button>
                    <div class="register-text">
                        <a href="#" onclick="closeForgotPasswordModal(); return false;" class="register-link">Back to Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal Styles -->
    <style>
        /* Modal states management */
        .modal-state {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Loading spinner */
        .spinner-ring {
            position: absolute;
            width: 60px;
            height: 60px;
            border: 3px solid transparent;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .spinner-ring:nth-child(2) {
            width: 50px;
            height: 50px;
            top: 5px;
            left: 5px;
            border-top-color: var(--primary-vibrant);
            animation-duration: 1.2s;
        }
        
        .spinner-ring:nth-child(3) {
            width: 40px;
            height: 40px;
            top: 10px;
            left: 10px;
            border-top-color: var(--accent-emerald);
            animation-duration: 1.5s;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes successPulse {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-2px); }
        }
        
        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
    </style>
    
    <!-- Vendor Forgot Password Modal -->
    <div id="vendorForgotPasswordModal" class="modal">
        <div class="modal-content">
            <!-- Form State -->
            <div id="vendorForgotFormState" class="modal-state">
                <div class="modal-header">
                    <h2 class="modal-title">Reset Password</h2>
                    <button class="modal-close" onclick="closeVendorForgotPasswordModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div style="text-align: center; margin-bottom: 32px;">
                        <p style="font-size: 15px; color: var(--text-secondary); line-height: 1.5; margin: 0; font-family: 'Inter', sans-serif; letter-spacing: -0.01em;">
                            We'll send a reset link to your business email
                        </p>
                    </div>
                    
                    <form id="vendorForgotPasswordForm">
                        @csrf
                        <div class="input-group">
                            <input 
                                type="email" 
                                id="vendorResetEmail" 
                                name="email" 
                                class="input-field" 
                                placeholder="Enter your business email" 
                                required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="submit" form="vendorForgotPasswordForm" class="btn btn-primary" id="vendorSendResetBtn">
                        Send Link
                    </button>
                    
                    <div class="register-text">
                        <a href="#" onclick="closeVendorForgotPasswordModal(); return false;" class="register-link">Back to Sign In</a>
                    </div>
                </div>
            </div>
            
            <!-- Loading State -->
            <div id="vendorForgotLoadingState" class="modal-state" style="display: none;">
                <div class="modal-header">
                    <h2 class="modal-title">Sending Email...</h2>
                    <button class="modal-close" onclick="closeVendorForgotPasswordModal()">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center; padding: 40px 24px;">
                    <div style="display: inline-block; position: relative;">
                        <div class="spinner-ring"></div>
                        <div class="spinner-ring" style="animation-delay: 0.15s;"></div>
                        <div class="spinner-ring" style="animation-delay: 0.3s;"></div>
                    </div>
                    <p style="margin-top: 24px; color: var(--text-secondary); font-size: 14px;">
                        Please wait while we send your reset link...
                    </p>
                </div>
            </div>
            
            <!-- Success State -->
            <div id="vendorForgotSuccessState" class="modal-state" style="display: none;">
                <div class="modal-header">
                    <h2 class="modal-title">Email Sent!</h2>
                    <button class="modal-close" onclick="closeVendorForgotPasswordModal()">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center; padding: 32px 24px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(145deg, var(--primary), var(--primary-vibrant)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; animation: successPulse 2s infinite;">
                        <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                            <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                        </svg>
                    </div>
                    <p style="font-size: 16px; color: var(--text-primary); margin-bottom: 16px; font-weight: 500;">
                        Password reset link sent!
                    </p>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 24px;">
                        Check your business email inbox for instructions to reset your vendor password. The link will expire in 2 hours for security.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="closeVendorForgotPasswordModal()">
                        Continue
                    </button>
                </div>
            </div>
            
            <!-- Error State -->
            <div id="vendorForgotErrorState" class="modal-state" style="display: none;">
                <div class="modal-header">
                    <h2 class="modal-title">Unable to Send Email</h2>
                    <button class="modal-close" onclick="closeVendorForgotPasswordModal()">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center; padding: 32px 24px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(145deg, #8B4444, #A85555); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; animation: errorShake 0.5s ease-in-out;">
                        <svg width="40" height="40" fill="white" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                    </div>
                    <p style="font-size: 16px; color: var(--text-primary); margin-bottom: 16px; font-weight: 500;" id="vendorErrorTitle">
                        Something went wrong
                    </p>
                    <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 24px;" id="vendorErrorMessage">
                        We couldn't send the reset email. Please try again or contact support if the problem persists.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="resetVendorForgotPasswordModal()">
                        Try Again
                    </button>
                    <div class="register-text">
                        <a href="#" onclick="closeVendorForgotPasswordModal(); return false;" class="register-link">Back to Sign In</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enhanced Smart Navigation JavaScript with 60fps Optimizations -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Performance optimization - use requestAnimationFrame for smooth animations
            let rafId = null;
            
            // Animate the live traders count with smooth 60fps animation
            const tradersCount = document.getElementById('tradersCount');
            const targetCount = 2847;
            const startCount = 2750;
            let currentCount = startCount;
            const animationDuration = 2000; // 2 seconds
            const startTime = performance.now();
            
            // Smooth count-up animation using requestAnimationFrame
            function animateCount(timestamp) {
                const elapsed = timestamp - startTime;
                const progress = Math.min(elapsed / animationDuration, 1);
                
                // Easing function for smooth animation
                const easeOutExpo = 1 - Math.pow(2, -10 * progress);
                currentCount = startCount + (targetCount - startCount) * easeOutExpo;
                
                tradersCount.textContent = Math.floor(currentCount).toLocaleString();
                
                if (progress < 1) {
                    rafId = requestAnimationFrame(animateCount);
                } else {
                    currentCount = targetCount;
                    tradersCount.textContent = targetCount.toLocaleString();
                    startPeriodicUpdates();
                }
            }
            
            rafId = requestAnimationFrame(animateCount);
            
            // Simulate live updates - small random changes
            function startPeriodicUpdates() {
                setInterval(() => {
                    const variation = Math.floor(Math.random() * 21) - 10; // Random between -10 and +10
                    const newCount = targetCount + variation;
                    animateCountChange(parseInt(tradersCount.textContent.replace(/,/g, '')), newCount);
                }, 8000); // Update every 8 seconds
            }
            
            // Smooth animation for count changes
            function animateCountChange(from, to) {
                const duration = 500;
                const steps = 10;
                const stepDuration = duration / steps;
                const stepIncrement = (to - from) / steps;
                let current = from;
                let step = 0;
                
                const animate = setInterval(() => {
                    step++;
                    current += stepIncrement;
                    
                    if (step >= steps) {
                        current = to;
                        clearInterval(animate);
                    }
                    
                    tradersCount.textContent = Math.floor(current).toLocaleString();
                    
                    // Add a subtle pulse effect on update
                    tradersCount.style.transform = 'translateY(-1px)';
                    setTimeout(() => {
                        tradersCount.style.transform = 'translateY(0)';
                    }, 200);
                }, stepDuration);
            }
            
            // Add transition for the count changes
            tradersCount.style.transition = 'transform 0.2s ease-out';
            // Smart Get Started button routing
            const getStartedBtn = document.getElementById('getStartedBtn');
            const optionCards = document.querySelectorAll('.option-card');
            let selectedUserType = null;
            
            // Track user choice when hovering/clicking option cards
            optionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    selectedUserType = this.classList.contains('buyer') ? 'buyer' : 'vendor';
                });
                
                card.addEventListener('click', function(e) {
                    // Allow the default navigation to happen
                    selectedUserType = this.classList.contains('buyer') ? 'buyer' : 'vendor';
                });
            });
            
            // Smart routing for Get Started button
            getStartedBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Route based on user's last interaction or default to buyer
                if (selectedUserType === 'vendor') {
                    window.location.href = '{{ route("vendor.register") }}';
                } else {
                    // Default to buyer registration
                    window.location.href = '{{ route("buyer.register") }}';
                }
            });
            
            // Enhanced card interactions with smooth feedback
            optionCards.forEach(card => {
                // Add touch-optimized interactions
                let touchStartY = 0;
                let touchStartTime = 0;
                
                // Touch start handler
                card.addEventListener('touchstart', function(e) {
                    touchStartY = e.touches[0].clientY;
                    touchStartTime = Date.now();
                    this.style.transform = 'translateZ(0) translateY(-4px)';
                    this.style.transition = 'transform 100ms cubic-bezier(0.4, 0, 0.2, 1)';
                }, { passive: true });
                
                // Touch end handler
                card.addEventListener('touchend', function(e) {
                    const touchDuration = Date.now() - touchStartTime;
                    const touchEndY = e.changedTouches[0].clientY;
                    const touchDistance = Math.abs(touchEndY - touchStartY);
                    
                    // Only trigger click if it's a tap (not a swipe)
                    if (touchDuration < 500 && touchDistance < 10) {
                        this.style.transform = 'translateZ(0) translateY(0)';
                        this.style.transition = 'transform 200ms cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                    }
                }, { passive: true });
                
                // Mouse enter/leave for desktop
                card.addEventListener('mouseenter', function() {
                    this.style.willChange = 'transform, box-shadow';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.willChange = 'auto';
                });
                
                // Click handler with haptic feedback (if supported)
                card.addEventListener('click', function() {
                    // Add visual feedback
                    optionCards.forEach(c => {
                        c.style.opacity = '0.92';
                        c.style.filter = 'brightness(0.98)';
                    });
                    this.style.opacity = '1';
                    this.style.filter = 'brightness(1.02)';
                    
                    // Trigger haptic feedback if available
                    if (window.navigator && window.navigator.vibrate) {
                        window.navigator.vibrate(10);
                    }
                });
            });
            
            // Enhanced keyboard navigation with visual feedback
            optionCards.forEach((card, index) => {
                card.setAttribute('tabindex', '0');
                card.setAttribute('role', 'button');
                card.setAttribute('aria-label', card.querySelector('.option-title').textContent);
                
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.style.transform = 'translateZ(0) translateY(-6px)';
                    }
                });
                
                card.addEventListener('keyup', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.style.transform = 'translateZ(0) translateY(0)';
                        this.click();
                    }
                });
                
                // Add focus styles
                card.addEventListener('focus', function() {
                    this.style.outline = '3px solid #8B8478';
                    this.style.outlineOffset = '4px';
                });
                
                card.addEventListener('blur', function() {
                    this.style.outline = 'none';
                });
            });
            
            // Add smooth scroll for any anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href !== '#') {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });
            
            // Remove unnecessary font preloading that causes shifts
            
            // Enhanced CTA button interactions
            const ctaButton = document.querySelector('.cta-button');
            if (ctaButton) {
                // Add smooth hover animations
                ctaButton.addEventListener('mouseenter', function() {
                    this.style.willChange = 'transform, box-shadow, filter';
                    const arrow = this.querySelector('.cta-arrow');
                    if (arrow) {
                        arrow.style.willChange = 'transform';
                    }
                });
                
                ctaButton.addEventListener('mouseleave', function() {
                    this.style.willChange = 'auto';
                    const arrow = this.querySelector('.cta-arrow');
                    if (arrow) {
                        arrow.style.willChange = 'auto';
                    }
                });
                
                // Touch optimizations
                ctaButton.addEventListener('touchstart', function(e) {
                    this.style.transform = 'translateZ(0) translateY(-2px)';
                }, { passive: true });
                
                ctaButton.addEventListener('touchend', function(e) {
                    this.style.transform = 'translateZ(0) translateY(0)';
                }, { passive: true });
            }
            
            // Premium Performance monitoring for guaranteed 60fps
            let lastFrameTime = performance.now();
            let frameCount = 0;
            let fps = 60;
            
            // Enable GPU acceleration for all animated elements
            document.querySelectorAll('.option-card, .cta-button, .orb, .particle').forEach(el => {
                el.style.transform = 'translateZ(0)';
                el.style.backfaceVisibility = 'hidden';
                el.style.perspective = '1000px';
            });
            
            function measureFPS() {
                const now = performance.now();
                const delta = now - lastFrameTime;
                frameCount++;
                
                if (delta >= 1000) {
                    fps = Math.round((frameCount * 1000) / delta);
                    frameCount = 0;
                    lastFrameTime = now;
                    
                    // Log FPS if below threshold
                    if (fps < 55) {
                        console.warn(`FPS dropped to ${fps}`);
                    }
                }
                
                requestAnimationFrame(measureFPS);
            }
            
            // Start FPS monitoring in development mode
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                requestAnimationFrame(measureFPS);
            }
            
            // Optimize animations based on device capabilities
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (prefersReducedMotion) {
                document.documentElement.style.setProperty('--duration-fast', '50ms');
                document.documentElement.style.setProperty('--duration-normal', '100ms');
                document.documentElement.style.setProperty('--duration-slow', '150ms');
            }
            
            // Premium Analytics tracking with enhanced performance metrics
            optionCards.forEach(card => {
                card.addEventListener('click', function() {
                    const userType = this.classList.contains('buyer') ? 'buyer' : 'vendor';
                    const clickTime = performance.now();
                    
                    // Track user choice and performance
                    if (window.performance && window.performance.mark) {
                        performance.mark(`${userType}-card-clicked`);
                        performance.measure(`time-to-${userType}-selection`, 'navigationStart', `${userType}-card-clicked`);
                    }
                    
                    // Add premium click animation
                    const ripple = document.createElement('span');
                    ripple.style.cssText = `
                        position: absolute;
                        width: 20px;
                        height: 20px;
                        background: radial-gradient(circle, rgba(224, 229, 236, 0.8), transparent);
                        border-radius: 50%;
                        transform: translate(-50%, -50%); opacity: 0;
                        animation: rippleEffect 0.6s ease-out;
                        pointer-events: none;
                    `;
                    
                    const rect = this.getBoundingClientRect();
                    ripple.style.left = `${event.clientX - rect.left}px`;
                    ripple.style.top = `${event.clientY - rect.top}px`;
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                    
                    console.log('User selected:', userType, 'at', Math.round(clickTime) + 'ms');
                });
            });
            
            // Add ripple animation keyframe
            const style = document.createElement('style');
            style.textContent = `
                @keyframes rippleEffect {
                    to {
                        transform: translate(-50%, -50%); opacity: 0;
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
        
        // Prefetch auth pages for instant navigation
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => {
                const prefetchLinks = [
                    '{{ route("buyer.login") }}',
                    '{{ route("vendor.login") }}',
                    '{{ route("buyer.register") }}',
                    '{{ route("vendor.register") }}'
                ];
                
                prefetchLinks.forEach(url => {
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = url;
                    link.as = 'document';
                    document.head.appendChild(link);
                });
                
                // Preconnect to external resources for faster font loading
                const preconnectOrigins = ['https://fonts.googleapis.com', 'https://fonts.gstatic.com'];
                preconnectOrigins.forEach(origin => {
                    const link = document.createElement('link');
                    link.rel = 'preconnect';
                    link.href = origin;
                    link.crossOrigin = 'anonymous';
                    document.head.appendChild(link);
                });
            });
        }
        
        // Premium Intersection Observer for optimized animations
        const observerOptions = {
            root: null,
            rootMargin: '50px',
            threshold: [0, 0.1, 0.5, 1.0]
        };
        
        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                    animationObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observe animated elements with staggered delays for premium effect
        document.querySelectorAll('.option-card, .cta-button, .live-indicator').forEach((el, index) => {
            el.style.animationPlayState = 'paused';
            el.style.animationDelay = `${index * 100}ms`;
            animationObserver.observe(el);
        });
        
        // Add premium page load animation
        window.addEventListener('load', () => {
            document.body.style.opacity = '0';
            document.body.style.animation = 'fadeIn 0.5s ease-out forwards';
            
            // Trigger entrance animations after a slight delay
            setTimeout(() => {
                document.querySelectorAll('[style*="animation-play-state: paused"]').forEach(el => {
                    el.style.animationPlayState = 'running';
                });
            }, 100);
        });
        
        // FIXED: Complete scroll lock - no movement, no jitter
        document.addEventListener('wheel', (e) => {
            e.preventDefault();
            e.stopPropagation();
            // NO visual feedback - complete lock
        }, { passive: false });
        
        // FIXED: Complete touch scroll lock - no movement, no jitter
        document.addEventListener('touchstart', (e) => {
            // Just prevent - no elastic feedback
        }, { passive: true });
        
        document.addEventListener('touchmove', (e) => {
            // Complete scroll lock - no visual feedback
            e.preventDefault();
            e.stopPropagation();
        }, { passive: false });
        
        document.addEventListener('touchend', (e) => {
            // No visual feedback needed
        }, { passive: true });
        
        // ADDITIONAL COMPREHENSIVE SCROLL LOCKS
        // Catch any remaining scroll events
        document.addEventListener('scroll', (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.scrollTo(0, 0);
        }, { passive: false });
        
        window.addEventListener('scroll', (e) => {
            e.preventDefault();
            e.stopPropagation();
            window.scrollTo(0, 0);
        }, { passive: false });
        
        // Lock arrow keys that might cause scrolling
        document.addEventListener('keydown', (e) => {
            if ([32, 33, 34, 35, 36, 37, 38, 39, 40].includes(e.keyCode)) {
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
        }, { passive: false });
        
        // Force scroll position to remain at top
        setInterval(() => {
            if (window.scrollY !== 0 || window.scrollX !== 0) {
                window.scrollTo(0, 0);
            }
        }, 100);
        
        // ============================================
        // MODAL FUNCTIONALITY
        // ============================================
        
        // Open Login Modal
        function openLoginModal(type) {
            const modalId = type === 'buyer' ? 'buyerModal' : 'vendorModal';
            const modal = document.getElementById(modalId);
            
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent body scroll
                
                // Immediate focus without delay to prevent layout shift
                const firstInput = modal.querySelector('input[type="email"]');
                if (firstInput) {
                    requestAnimationFrame(() => firstInput.focus());
                }
                
                // Add click outside to close
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal();
                    }
                });
            }
        }
        
        // Close Modal
        function closeModal() {
            const buyerModal = document.getElementById('buyerModal');
            const vendorModal = document.getElementById('vendorModal');
            
            // Close whichever modal is open
            [buyerModal, vendorModal].forEach(modal => {
                if (modal && modal.style.display === 'block') {
                    modal.classList.add('closing');
                    
                    setTimeout(() => {
                        modal.style.display = 'none';
                        modal.classList.remove('closing');
                        document.body.style.overflow = ''; // Restore body scroll
                    }, 300); // Match CSS animation duration
                }
            });
        }
        
        // Password Toggle for Modal
        function togglePasswordModal(type) {
            const passwordId = type === 'buyer' ? 'buyerPassword' : 'vendorPassword';
            const passwordInput = document.getElementById(passwordId);
            const modal = document.getElementById(type + 'Modal');
            const eyeOpen = modal.querySelector('.eye-open');
            const eyeClosed = modal.querySelector('.eye-closed');
            
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
        
        // ============================================
        // FORGOT PASSWORD MODAL FUNCTIONALITY
        // ============================================
        
        // Show Forgot Password Modal
        function showForgotPasswordModal() {
            const modal = document.getElementById('forgotPasswordModal');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Pre-fill email from buyer login if available
                const buyerEmail = document.getElementById('buyerEmail');
                const resetEmail = document.getElementById('resetEmail');
                if (buyerEmail && resetEmail && buyerEmail.value) {
                    resetEmail.value = buyerEmail.value;
                }
                
                // Focus email input
                requestAnimationFrame(() => resetEmail.focus());
            }
        }
        
        // Close Forgot Password Modal
        function closeForgotPasswordModal() {
            const modal = document.getElementById('forgotPasswordModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Reset to form state
                setTimeout(() => {
                    resetForgotPasswordModal();
                }, 300);
            }
        }
        
        // Reset Modal to Initial State
        function resetForgotPasswordModal() {
            // Show form state, hide others
            document.getElementById('forgotFormState').style.display = 'block';
            document.getElementById('forgotLoadingState').style.display = 'none';
            document.getElementById('forgotSuccessState').style.display = 'none';
            document.getElementById('forgotErrorState').style.display = 'none';
            
            // Reset form
            const form = document.getElementById('forgotPasswordForm');
            if (form) {
                form.reset();
            }
            
            // Reset button
            const button = document.getElementById('sendResetBtn');
            if (button) {
                button.disabled = false;
                button.innerHTML = `Send Link`;
            }
        }
        
        // Show Different Modal States
        function showForgotState(stateName) {
            const states = ['forgotFormState', 'forgotLoadingState', 'forgotSuccessState', 'forgotErrorState'];
            
            states.forEach(state => {
                const element = document.getElementById(state);
                if (element) {
                    element.style.display = state === stateName ? 'block' : 'none';
                }
            });
        }
        
        // Handle Forgot Password Form Submission
        document.addEventListener('DOMContentLoaded', function() {
            const forgotForm = document.getElementById('forgotPasswordForm');
            if (forgotForm) {
                forgotForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    const email = document.getElementById('resetEmail').value;
                    const button = document.getElementById('sendResetBtn');
                    
                    if (!email) {
                        alert('Please enter your email address.');
                        return false;
                    }
                    
                    // Show loading state
                    showForgotState('forgotLoadingState');
                    button.disabled = true;
                    
                    try {
                        // Send password reset request to Laravel backend
                        const response = await fetch('/auth/buyer/password/email', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ email: email }),
                            credentials: 'same-origin'
                        });
                        
                        // Ensure we get JSON response, not HTML
                        const contentType = response.headers.get('content-type');
                        let data;
                        
                        if (contentType && contentType.includes('application/json')) {
                            data = await response.json();
                        } else {
                            // If we get HTML instead of JSON, it means Laravel redirected
                            throw new Error('Unexpected response format - possible redirect');
                        }
                        
                        if (response.ok) {
                            // Show success state
                            showForgotState('forgotSuccessState');
                        } else {
                            // Show error state
                            showForgotState('forgotErrorState');
                            
                            // Update error messages if provided
                            if (data.message) {
                                const errorMessage = document.getElementById('errorMessage');
                                if (errorMessage) {
                                    errorMessage.textContent = data.message;
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Forgot password error:', error);
                        
                        // Show error state
                        showForgotState('forgotErrorState');
                        
                        // Generic error message
                        const errorMessage = document.getElementById('errorMessage');
                        if (errorMessage) {
                            errorMessage.textContent = 'Network error. Please check your connection and try again.';
                        }
                    }
                });
            }
        });
        
        // VENDOR FORGOT PASSWORD MODAL FUNCTIONALITY
        // ============================================
        
        // Show Vendor Forgot Password Modal
        function showVendorForgotPasswordModal() {
            const modal = document.getElementById('vendorForgotPasswordModal');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Pre-fill email from vendor login if available
                const vendorEmail = document.getElementById('vendorEmail');
                const resetEmail = document.getElementById('vendorResetEmail');
                if (vendorEmail && resetEmail && vendorEmail.value) {
                    resetEmail.value = vendorEmail.value;
                }
            }
        }
        
        // Close Vendor Forgot Password Modal
        function closeVendorForgotPasswordModal() {
            const modal = document.getElementById('vendorForgotPasswordModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Reset to form state
                setTimeout(() => {
                    resetVendorForgotPasswordModal();
                }, 300);
            }
        }
        
        // Reset Vendor Modal to Initial State
        function resetVendorForgotPasswordModal() {
            // Show form state, hide others
            document.getElementById('vendorForgotFormState').style.display = 'block';
            document.getElementById('vendorForgotLoadingState').style.display = 'none';
            document.getElementById('vendorForgotSuccessState').style.display = 'none';
            document.getElementById('vendorForgotErrorState').style.display = 'none';
            
            // Reset form
            const form = document.getElementById('vendorForgotPasswordForm');
            if (form) {
                form.reset();
            }
            
            // Reset button
            const button = document.getElementById('vendorSendResetBtn');
            if (button) {
                button.disabled = false;
            }
        }
        
        // Show Different Vendor Modal States
        function showVendorForgotState(stateName) {
            const states = ['vendorForgotFormState', 'vendorForgotLoadingState', 'vendorForgotSuccessState', 'vendorForgotErrorState'];
            
            states.forEach(state => {
                const element = document.getElementById(state);
                if (element) {
                    element.style.display = state === stateName ? 'block' : 'none';
                }
            });
        }
        
        // Handle Vendor Forgot Password Form Submission
        document.addEventListener('DOMContentLoaded', function() {
            const vendorForgotForm = document.getElementById('vendorForgotPasswordForm');
            if (vendorForgotForm) {
                vendorForgotForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    const email = document.getElementById('vendorResetEmail').value;
                    const button = document.getElementById('vendorSendResetBtn');
                    
                    if (!email) {
                        alert('Please enter your business email address.');
                        return false;
                    }
                    
                    // Show loading state
                    showVendorForgotState('vendorForgotLoadingState');
                    button.disabled = true;
                    
                    try {
                        // Send password reset request to Laravel backend
                        const response = await fetch('/auth/vendor/password/email', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ email: email }),
                            credentials: 'same-origin'
                        });
                        
                        // Ensure we get JSON response, not HTML
                        const contentType = response.headers.get('content-type');
                        let data;
                        
                        if (contentType && contentType.includes('application/json')) {
                            data = await response.json();
                        } else {
                            // If we get HTML instead of JSON, it means Laravel redirected
                            throw new Error('Unexpected response format - possible redirect');
                        }
                        
                        if (response.ok) {
                            // Show success state
                            showVendorForgotState('vendorForgotSuccessState');
                        } else {
                            // Show error state
                            showVendorForgotState('vendorForgotErrorState');
                            
                            // Update error messages if provided
                            if (data.message) {
                                const errorMessage = document.getElementById('vendorErrorMessage');
                                if (errorMessage) {
                                    errorMessage.textContent = data.message;
                                }
                            }
                        }
                    } catch (error) {
                        console.error('Vendor forgot password error:', error);
                        
                        // Show error state
                        showVendorForgotState('vendorForgotErrorState');
                        
                        // Generic error message
                        const errorMessage = document.getElementById('vendorErrorMessage');
                        if (errorMessage) {
                            errorMessage.textContent = 'Network error. Please check your connection and try again.';
                        }
                    }
                });
            }
        });
        
        // Form Submission Handlers
        document.addEventListener('DOMContentLoaded', function() {
            // Buyer form submission
            const buyerForm = document.getElementById('buyerLoginForm');
            if (buyerForm) {
                buyerForm.addEventListener('submit', function(e) {
                    const button = this.querySelector('button[type="submit"]');
                    button.disabled = true;
                    button.innerHTML = 'Signing in...';
                });
            }
            
            // Vendor form submission
            const vendorForm = document.getElementById('vendorLoginForm');
            if (vendorForm) {
                vendorForm.addEventListener('submit', function(e) {
                    const button = this.querySelector('button[type="submit"]');
                    button.disabled = true;
                    button.innerHTML = 'Signing in...';
                });
            }
        });
        
        // Keyboard Navigation
        document.addEventListener('keydown', function(e) {
            // Close modal on Escape key
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>