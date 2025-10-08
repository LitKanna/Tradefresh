<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class BroadcastAuthController extends Controller
{
    /**
     * Authenticate the request for channel access with multiple guards
     */
    public function authenticate(Request $request)
    {
        // Try buyer guard first
        if (auth()->guard('buyer')->check()) {
            $request->setUserResolver(function () {
                return auth()->guard('buyer')->user();
            });
            \Log::info('Broadcast auth using buyer guard', [
                'buyer_id' => auth()->guard('buyer')->id(),
            ]);
        }
        // Try vendor guard
        elseif (auth()->guard('vendor')->check()) {
            $request->setUserResolver(function () {
                return auth()->guard('vendor')->user();
            });
            \Log::info('Broadcast auth using vendor guard', [
                'vendor_id' => auth()->guard('vendor')->id(),
            ]);
        }
        // Try admin guard
        elseif (auth()->guard('admin')->check()) {
            $request->setUserResolver(function () {
                return auth()->guard('admin')->user();
            });
            \Log::info('Broadcast auth using admin guard', [
                'admin_id' => auth()->guard('admin')->id(),
            ]);
        }

        return Broadcast::auth($request);
    }
}
