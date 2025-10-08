<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson()) {
            // Determine the guard and redirect accordingly
            if ($request->is('admin/*')) {
                return route('admin.login');
            } elseif ($request->is('vendor/*')) {
                return route('vendor.login');
            } elseif ($request->is('buyer/*')) {
                return route('buyer.login');
            }
            
            // Default redirect
            return route('login');
        }
        
        return null;
    }
    
    /**
     * Handle unauthenticated user
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        // Custom redirect based on the path
        if ($request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        return redirect()->guest($this->redirectTo($request));
    }
}