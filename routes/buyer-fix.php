<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// TEMPORARY FIX: Dashboard without middleware for testing
Route::get('/buyer-test/dashboard', function () {
    $buyer = Auth::guard('buyer')->user();

    if (! $buyer) {
        // Try to auto-login test buyer for development
        $testBuyer = \App\Models\Buyer::where('email', 'buyer@test.com')->first();
        if ($testBuyer) {
            Auth::guard('buyer')->login($testBuyer);
            $buyer = $testBuyer;
        }
    }

    if ($buyer) {
        return view('buyer.dashboard');
    }

    return redirect('/auth/buyer/login')->with('error', 'Please login first');
})->name('buyer.test.dashboard');

// Alternative login that definitely works
Route::post('/buyer-test/login', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $buyer = \App\Models\Buyer::where('email', $request->email)->first();

    if ($buyer && \Illuminate\Support\Facades\Hash::check($request->password, $buyer->password)) {
        Auth::guard('buyer')->login($buyer);

        // Force session regeneration
        $request->session()->regenerate();

        return redirect('/buyer-test/dashboard')->with('success', 'Logged in successfully!');
    }

    return back()->withErrors(['email' => 'Invalid credentials']);
});

// Simple login form
Route::get('/buyer-test/login', function () {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Simple Buyer Login</title>
        <style>
            body { font-family: sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
            input { width: 100%; padding: 10px; margin: 10px 0; }
            button { background: #10B981; color: white; padding: 10px 20px; border: none; cursor: pointer; width: 100%; }
            .error { color: red; }
            .success { color: green; }
        </style>
    </head>
    <body>
        <h2>Simple Buyer Login Test</h2>

        '.(session('error') ? '<div class="error">'.session('error').'</div>' : '').'
        '.(session('success') ? '<div class="success">'.session('success').'</div>' : '').'

        <form method="POST" action="/buyer-test/login">
            '.csrf_field().'
            <input type="email" name="email" placeholder="Email" value="buyer@test.com" required>
            <input type="password" name="password" placeholder="Password" value="password" required>
            <button type="submit">Login</button>
        </form>

        <hr>
        <p>Test Account:</p>
        <ul>
            <li>Email: buyer@test.com</li>
            <li>Password: password</li>
        </ul>

        <hr>
        <p>Debug Links:</p>
        <ul>
            <li><a href="/debug-auth">Debug Auth Status</a></li>
            <li><a href="/test-buyer-auth">Test Buyer Auth</a></li>
            <li><a href="/test-quick-login">Quick Login (Auto)</a></li>
        </ul>
    </body>
    </html>
    ';
});
