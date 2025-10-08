<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Your Password - TradeFresh B2B</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* PREMIUM NEUROMORPHIC DESIGN SYSTEM - MATCHING LOGIN MODAL */
        :root {
            /* Color System - Matching Welcome Page */
            --bg-primary: linear-gradient(135deg, #fefdfb 0%, #f8f9f5 25%, #f5f7f3 50%, #f2f5f0 75%, #f0f4ee 100%);
            --surface: #fefdfb;
            --surface-variant: #f8f9f5;
            --primary: #10B981;
            --primary-vibrant: #22C55E;
            --text-primary: #2d3748;
            --text-secondary: #4a5568;
            --text-muted: #6b7280;
            
            /* Enhanced shadows for premium feel */
            --shadow-inset: inset 2px 2px 5px rgba(155, 170, 145, 0.25), inset -2px -2px 5px rgba(255, 255, 252, 0.95);
            --shadow-raised: 8px 8px 16px rgba(155, 170, 145, 0.15), -8px -8px 16px rgba(255, 255, 252, 0.95);
            --shadow-pressed: inset 4px 4px 8px rgba(155, 170, 145, 0.25), inset -4px -4px 8px rgba(255, 255, 252, 0.8);
            
            /* Animation system */
            --duration-fast: 0.2s;
            --duration-normal: 0.3s;
            --duration-slow: 0.5s;
            --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
            --ease-elastic: cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-weight: 400;
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
            animation: pageLoad 0.8s var(--ease-smooth) both;
        }

        @keyframes pageLoad {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Premium Neuromorphic Container */
        .reset-container {
            width: 100%;
            max-width: 400px;
            background: var(--bg-primary);
            border-radius: 28px;
            box-shadow: var(--shadow-raised);
            border: 1px solid rgba(255, 255, 252, 0.3);
            padding: 32px 28px;
            position: relative;
            backdrop-filter: blur(20px);
            animation: containerEntrance 0.6s var(--ease-elastic) 0.2s both;
        }

        @keyframes containerEntrance {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Header */
        .reset-header {
            text-align: center;
            margin-bottom: 24px;
            animation: headerSlide 0.7s var(--ease-smooth) 0.4s both;
        }

        @keyframes headerSlide {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        .reset-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.2;
            letter-spacing: -0.025em;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .reset-subtitle {
            font-size: 15px;
            color: var(--text-secondary);
            font-weight: 400;
            line-height: 1.5;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            letter-spacing: -0.01em;
        }

        /* Form Styling */
        .reset-form {
            animation: formSlide 0.6s var(--ease-smooth) 0.5s both;
        }

        @keyframes formSlide {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 16px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: -0.005em;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            z-index: 2;
            pointer-events: none;
            transition: color var(--duration-normal) var(--ease-smooth);
        }

        .form-input {
            width: 100%;
            height: 56px;
            padding: 16px 16px;
            background: var(--bg-primary);
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 400;
            color: var(--text-primary);
            box-shadow: inset 8px 8px 16px rgba(155, 170, 145, 0.5), inset -8px -8px 16px rgba(255, 255, 252, 0.98);
            transition: all var(--duration-normal) var(--ease-smooth);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            letter-spacing: -0.01em;
            outline: none;
        }

        .form-input::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }

        .form-input:focus {
            box-shadow: 
                inset 10px 10px 20px rgba(155, 170, 145, 0.6), 
                inset -10px -10px 20px rgba(255, 255, 252, 0.99),
                0 0 0 3px rgba(16, 185, 129, 0.15);
        }

        .form-input:focus + .input-icon,
        .form-input:not(:placeholder-shown) + .input-icon {
            color: var(--primary);
        }

        /* Fix autofill styles */
        .form-input:-webkit-autofill,
        .form-input:-webkit-autofill:hover,
        .form-input:-webkit-autofill:focus {
            -webkit-box-shadow: inset 8px 8px 16px rgba(155, 170, 145, 0.5), inset -8px -8px 16px rgba(255, 255, 252, 0.98) !important;
            -webkit-text-fill-color: var(--text-primary) !important;
            background: transparent !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 16px;
            width: 20px;
            height: 20px;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: color var(--duration-normal) var(--ease-smooth);
            z-index: 2;
        }

        .password-toggle:hover {
            color: var(--primary);
        }



        /* Submit Button - Premium Neuromorphic Style */
        .btn {
            width: 100%;
            height: 56px;
            background: var(--bg-primary);
            color: var(--primary);
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 
                6px 6px 12px rgba(155, 170, 145, 0.25),
                -6px -6px 12px rgba(255, 255, 252, 0.95);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            letter-spacing: 0.01em;
            margin-top: 24px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 
                8px 8px 20px rgba(155, 170, 145, 0.35),
                -8px -8px 20px rgba(255, 255, 252, 0.98),
                inset 0 1px 3px rgba(16, 185, 129, 0.1);
            color: var(--primary-dark);
            background: linear-gradient(135deg, var(--bg-primary) 0%, rgba(255, 255, 252, 0.8) 100%);
        }

        .btn:active:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 
                inset 3px 3px 8px rgba(155, 170, 145, 0.3),
                inset -3px -3px 8px rgba(255, 255, 252, 0.9);
            background: var(--surface-variant);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            color: var(--text-muted);
            transform: none;
            box-shadow: 
                3px 3px 6px rgba(155, 170, 145, 0.15),
                -3px -3px 6px rgba(255, 255, 252, 0.8);
        }

        /* Premium Success Animation */
        .btn-success {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-vibrant) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 
                0 8px 25px rgba(16, 185, 129, 0.4),
                inset 0 1px 3px rgba(255, 255, 255, 0.3);
        }
        
        .form-fade-out {
            animation: fadeOut 0.8s var(--ease-smooth) forwards;
        }
        
        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
        }

        /* Validation Styling */
        .validation-message {
            font-size: 12px;
            font-weight: 500;
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            text-align: left;
            transition: all var(--duration-normal) var(--ease-smooth);
            display: none;
        }
        
        .validation-error {
            color: var(--text-secondary);
            background: var(--bg-primary);
            box-shadow: inset 2px 2px 4px rgba(155, 170, 145, 0.2), inset -2px -2px 4px rgba(255, 255, 252, 0.9);
        }
        
        .validation-success {
            color: var(--primary);
            background: var(--bg-primary);
            box-shadow: inset 2px 2px 4px rgba(16, 185, 129, 0.1), inset -2px -2px 4px rgba(255, 255, 252, 0.95);
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .error-container {
            background: var(--bg-primary);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            box-shadow: inset 3px 3px 6px rgba(239, 68, 68, 0.15), inset -3px -3px 6px rgba(255, 255, 252, 0.9);
            animation: errorSlide 0.4s var(--ease-smooth);
        }

        @keyframes errorSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-title {
            color: #dc2626;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .error-list {
            color: #dc2626;
            font-size: 13px;
            list-style: none;
        }

        .error-list li {
            margin-bottom: 4px;
            padding-left: 16px;
            position: relative;
        }

        .error-list li::before {
            content: '•';
            position: absolute;
            left: 0;
        }

        /* Loading State */
        .loading {
            display: none;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .reset-container {
                padding: 32px 24px;
                border-radius: 20px;
            }

            .reset-title {
                font-size: 24px;
            }

            .form-input {
                height: 52px;
                font-size: 15px;
            }

            .btn {
                height: 52px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <!-- Reset Form -->
        <div id="resetForm" class="reset-form">
            <div class="reset-header">
                <h1 class="reset-title">Create New Password</h1>
                <p class="reset-subtitle">Choose a secure password for your account</p>
            </div>

            @if ($errors->any())
                <div class="error-container">
                    <div class="error-title">Please fix the following errors:</div>
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('buyer.password.update') }}" id="passwordResetForm">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                
                <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                <!-- New Password Field -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="New password"
                            required
                            minlength="8"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <svg id="password-eye" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    
                </div>

                <!-- Confirm Password Field -->
                <div class="form-group">
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password_confirmation" 
                            name="password_confirmation" 
                            class="form-input" 
                            placeholder="Confirm password"
                            required
                            minlength="8"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation')">
                            <svg id="confirm-eye" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                    <div id="matchMessage" class="validation-message"></div>
                </div>


                <button type="submit" class="btn" id="submitBtn">
                    <span id="submitText">Update Password</span>
                    <div id="submitLoading" class="loading">
                        <div class="spinner"></div>
                        Updating Password...
                    </div>
                </button>
            </form>
        </div>

    </div>

    <script>
        let strengthScore = 0;
        let passwordsMatch = false;

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eyeIcon = document.getElementById(fieldId === 'password' ? 'password-eye' : 'confirm-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eyeIcon.innerHTML = '<path d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z"/><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>';
            } else {
                field.type = 'password';
                eyeIcon.innerHTML = '<path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>';
            }
        }


        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;
            const matchMessage = document.getElementById('matchMessage');

            // Only show validation if passwords don't match
            if (confirmation.length === 0) {
                matchMessage.style.display = 'none';
                passwordsMatch = false;
            } else if (password.length >= 8 && confirmation.length >= 8) {
                if (password === confirmation) {
                    matchMessage.style.display = 'none';
                    passwordsMatch = true;
                } else {
                    matchMessage.textContent = 'Passwords do not match';
                    matchMessage.className = 'validation-message validation-error';
                    matchMessage.style.display = 'block';
                    passwordsMatch = false;
                }
            } else {
                matchMessage.style.display = 'none';
                passwordsMatch = false;
            }

            updateSubmitButton();
        }

        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;
            const canSubmit = password.length >= 8 && password === confirmation;
            
            submitBtn.disabled = !canSubmit;
        }

        function showSuccess() {
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitLoading = document.getElementById('submitLoading');
            const resetForm = document.getElementById('resetForm');
            
            // Hide loading, show success button state
            submitLoading.style.display = 'none';
            submitText.textContent = 'Password Updated';
            submitText.style.display = 'inline';
            submitBtn.className = 'btn btn-success';
            
            // Wait a moment, then fade out form and redirect
            setTimeout(() => {
                resetForm.classList.add('form-fade-out');
                
                // Redirect after fade animation
                setTimeout(() => {
                    window.location.href = '{{ route("buyer.login") }}';
                }, 800);
            }, 600);
        }

        // Event listeners - only validate on blur for better UX
        document.getElementById('password_confirmation').addEventListener('blur', checkPasswordMatch);
        document.getElementById('password').addEventListener('blur', function() {
            const confirmation = document.getElementById('password_confirmation');
            if (confirmation.value.length > 0) {
                checkPasswordMatch();
            }
        });

        // Form submission
        document.getElementById('passwordResetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const submitText = document.getElementById('submitText');
            const submitLoading = document.getElementById('submitLoading');
            
            // Show loading state
            submitText.style.display = 'none';
            submitLoading.style.display = 'flex';
            submitBtn.disabled = true;
            
            // Create form data
            const formData = new FormData(this);
            
            // Submit form
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (response.ok) {
                    return response.text();
                } else {
                    throw new Error('Reset failed');
                }
            })
            .then(data => {
                // Check if response contains success indicators
                if (data.includes('success') || data.includes('password has been reset')) {
                    showSuccess();
                } else {
                    // If response looks like a redirect or success page, also show success
                    if (data.includes('login') || data.includes('redirect')) {
                        showSuccess();
                    } else {
                        throw new Error('Unexpected response');
                    }
                }
            })
            .catch(error => {
                console.error('Password reset error:', error);
                
                // Reset button state
                submitText.style.display = 'inline';
                submitLoading.style.display = 'none';
                submitBtn.disabled = false;
                
                // Show error message
                alert('Password reset failed. Please try again or contact support.');
            });
        });

        // Initialize
        document.getElementById('password').addEventListener('input', updateSubmitButton);
        document.getElementById('password_confirmation').addEventListener('input', updateSubmitButton);
        updateSubmitButton();
    </script>
</body>
</html>