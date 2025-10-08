<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Test authentication and redirect
Route::get('/test-buyer-auth', function () {
    $buyer = Auth::guard('buyer')->user();

    if ($buyer) {
        return '✅ You are logged in as: '.$buyer->email.'<br>'.
               'Name: '.$buyer->contact_name.'<br>'.
               "<a href='/buyer/dashboard'>Go to Dashboard</a>";
    }

    return "❌ Not logged in. <a href='/auth/buyer/login'>Login here</a>";
});

// Simple test login that bypasses validation
Route::get('/test-quick-login', function () {
    $buyer = \App\Models\Buyer::where('email', 'buyer@test.com')->first();

    if ($buyer) {
        Auth::guard('buyer')->login($buyer);

        return redirect('/test-buyer-auth');
    }

    return 'Test buyer not found';
});

// Debug session and auth
Route::get('/debug-auth', function () {
    return [
        'session_id' => session()->getId(),
        'session_driver' => config('session.driver'),
        'auth_guards' => array_keys(config('auth.guards')),
        'buyer_logged_in' => Auth::guard('buyer')->check(),
        'buyer_user' => Auth::guard('buyer')->user(),
        'web_logged_in' => Auth::guard('web')->check(),
        'session_data' => session()->all(),
    ];
});
