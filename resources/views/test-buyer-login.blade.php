<!DOCTYPE html>
<html>
<head>
    <title>Quick Buyer Login Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
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
            font-size: 18px;
            cursor: pointer;
        }
        button:hover {
            background: #059669;
        }
        .info {
            background: #EFF6FF;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .success {
            background: #D1FAE5;
            color: #065F46;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Quick Buyer Login Test</h1>

        <div class="info">
            <strong>Test Credentials:</strong><br>
            Email: <code>test@buyer.com</code><br>
            Password: <code>password</code>
        </div>

        @if(session('error'))
            <div class="error">{{ session('error') }}</div>
        @endif

        @if(session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('buyer.login.post') }}">
            @csrf

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="test@buyer.com" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" value="password" required>
            </div>

            <button type="submit">Login Now</button>
        </form>

        <hr style="margin: 30px 0;">

        <h3>Alternative Actions:</h3>
        <ul>
            <li><a href="{{ route('buyer.login') }}">Go to Official Login Page</a></li>
            <li><a href="/test-auto-login">Auto-Login Test Buyer</a></li>
            <li><a href="{{ route('buyer.discovered-leads') }}">Try Direct Access to Discovered Leads</a></li>
        </ul>

        @auth('buyer')
            <div class="success">
                <strong>✅ You are logged in!</strong><br>
                <a href="{{ route('buyer.dashboard') }}">Go to Dashboard</a> |
                <a href="{{ route('buyer.discovered-leads') }}">View Discovered Leads</a>
            </div>
        @endauth
    </div>
</body>
</html>