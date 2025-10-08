<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Administrator Portal - Sydney Markets B2B</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/global/enterprise-framework.css') }}">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        body {
            width: 100vw;
            height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .login-container {
            width: 100%;
            max-width: 480px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 10px 10px -5px rgb(0 0 0 / 0.04);
            padding: 3rem;
            border: 1px solid #e2e8f0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            width: 48px;
            height: 48px;
            background: #334155;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .security-badge {
            display: inline-flex;
            align-items: center;
            background: #dc2626;
            color: white;
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            color: #1f2937;
            background: #ffffff;
            transition: all 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
        }
        
        .login-btn {
            width: 100%;
            background: #334155;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .login-btn:hover {
            background: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.15);
        }
        
        .security-warning {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            display: flex;
            align-items: flex-start;
        }
        
        .warning-icon {
            flex-shrink: 0;
            width: 16px;
            height: 16px;
            color: #dc2626;
            margin-right: 0.75rem;
            margin-top: 2px;
        }
        
        .warning-text {
            font-size: 0.75rem;
            color: #991b1b;
            line-height: 1.4;
        }
        
        @media (max-width: 640px) {
            .login-container {
                margin: 1rem;
                padding: 2rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="header">
            <div class="logo">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <div class="security-badge">RESTRICTED ACCESS</div>
            <h1 class="title">Administrator Portal</h1>
            <p class="subtitle">Sydney Markets B2B Platform</p>
        </div>

        <form method="POST" action="/auth/admin/login" id="adminLoginForm">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="email">Administrator Email</label>
                <input type="email" name="email" id="email" class="form-input" placeholder="admin@sydneymarkets.com" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="two_factor">Two-Factor Authentication</label>
                <input type="text" name="two_factor_code" id="two_factor" class="form-input" placeholder="000000" maxlength="6" autocomplete="one-time-code">
            </div>
            
            <button type="submit" class="login-btn">
                Access Administrator Portal
            </button>
            
            <div class="security-warning">
                <svg class="warning-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="warning-text">
                    <strong>Security Notice:</strong> This is a restricted administrator interface. All access attempts are logged and monitored. Unauthorized access is prohibited.
                </div>
            </div>
        </form>
    </div>

    <script src="{{ asset('assets/js/global/enterprise-core.js') }}"></script>
    <script>
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/auth/admin/login', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || '/admin/dashboard';
                } else {
                    alert('Access Denied: ' + (data.message || 'Invalid credentials'));
                }
            })
            .catch(error => {
                console.error('Authentication error:', error);
                alert('Authentication system error. Contact support.');
            });
        });
    </script>
</body>
</html>