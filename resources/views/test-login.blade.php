<!DOCTYPE html>
<html>
<head>
    <title>Test Login - Sydney Markets</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            width: 400px;
        }
        h2 {
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #10B981;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-bottom: 10px;
        }
        button:hover {
            background: #059669;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #10B981;
        }
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #EF4444;
        }
        .links {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .links a {
            display: block;
            padding: 10px;
            background: #f3f4f6;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 10px;
            text-align: center;
        }
        .links a:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🛒 Test Login System</h2>

        <div id="status" class="alert" style="display:none;"></div>

        <div class="form-group">
            <label>Login As:</label>
            <button onclick="loginAsBuyer()">Login as Test Buyer</button>
            <button onclick="loginAsVendor()">Login as Test Vendor</button>
        </div>

        <div class="links">
            <h3>Quick Links:</h3>
            <a href="/buyer/dashboard">Buyer Dashboard</a>
            <a href="/vendor/dashboard">Vendor Dashboard</a>
            <a href="/">Home Page</a>
        </div>

        <div class="links">
            <h3>Test Routes:</h3>
            <a href="/dashboard-now">Force Login Dashboard</a>
            <a href="/test-auto-login">Auto Login Test</a>
        </div>
    </div>

    <script>
        function showStatus(message, type) {
            const status = document.getElementById('status');
            status.className = 'alert alert-' + type;
            status.textContent = message;
            status.style.display = 'block';
        }

        async function loginAsBuyer() {
            showStatus('Logging in as buyer...', 'success');

            try {
                const response = await fetch('/auth/buyer/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        email: 'test@buyer.com',
                        password: 'password123'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showStatus('Login successful! Redirecting to buyer dashboard...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || '/buyer/dashboard';
                    }, 1000);
                } else {
                    showStatus('Login failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showStatus('Error: ' + error.message, 'error');
            }
        }

        async function loginAsVendor() {
            showStatus('Logging in as vendor...', 'success');

            try {
                const response = await fetch('/auth/vendor/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        email: 'vendor@example.com',
                        password: 'password'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showStatus('Login successful! Redirecting to vendor dashboard...', 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect || '/vendor/dashboard';
                    }, 1000);
                } else {
                    showStatus('Login failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showStatus('Error: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>