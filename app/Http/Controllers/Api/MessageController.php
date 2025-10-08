<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Reverb\Facades\Reverb;

class MessageController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'quote_id' => 'required|exists:quotes,id',
            'message' => 'required|string|max:1000',
            'recipient_id' => 'required|integer',
            'recipient_type' => 'required|in:buyer,vendor'
        ]);

        // Try to get buyer from session auth
        $user = Auth::guard('buyer')->user() ?? Auth::guard('vendor')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userType = $user instanceof \App\Models\Buyer ? 'buyer' : 'vendor';

        // Verify user is part of this quote conversation (security check)
        $quote = Quote::findOrFail($request->quote_id);

        $isAuthorized = ($quote->buyer_id === $user->id && $userType === 'buyer') ||
                       ($quote->vendor_id === $user->id && $userType === 'vendor');

        if (!$isAuthorized) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You are not part of this quote conversation'
            ], 403);
        }

        $message = Message::create([
            'quote_id' => $request->quote_id,
            'sender_id' => $user->id,
            'sender_type' => $userType,
            'recipient_id' => $request->recipient_id,
            'recipient_type' => $request->recipient_type,
            'message' => $request->message,
            'is_read' => false
        ]);

        // Broadcast message via WebSocket
        broadcast(new \App\Events\MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message->load('sender')
        ]);
    }

    public function getMessages(Request $request, $quoteId)
    {
        // Try to get buyer from session auth
        $user = Auth::guard('buyer')->user() ?? Auth::guard('vendor')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userType = $user instanceof \App\Models\Buyer ? 'buyer' : 'vendor';

        // Verify user is part of this quote conversation (security check)
        $quote = Quote::find($quoteId);
        if (!$quote) {
            return response()->json(['error' => 'Quote not found'], 404);
        }

        $isAuthorized = ($quote->buyer_id === $user->id && $userType === 'buyer') ||
                       ($quote->vendor_id === $user->id && $userType === 'vendor');

        if (!$isAuthorized) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You are not part of this quote conversation'
            ], 403);
        }

        // Use model scope for cleaner query and prevent N+1
        $messages = Message::where('quote_id', $quoteId)
            ->forUser($user->id, $userType)
            ->with(['sender:id,business_name', 'recipient:id,business_name'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read using model method
        Message::where('quote_id', $quoteId)
            ->where('recipient_id', $user->id)
            ->where('recipient_type', $userType)
            ->where('is_read', false)
            ->get()
            ->each->markAsRead();

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function markAsRead(Request $request, $messageId)
    {
        // Try to get buyer from session auth
        $user = Auth::guard('buyer')->user() ?? Auth::guard('vendor')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $userType = $user instanceof \App\Models\Buyer ? 'buyer' : 'vendor';

        // Use model method for consistent read marking (sets is_read AND read_at)
        $message = Message::where('id', $messageId)
            ->where('recipient_id', $user->id)
            ->where('recipient_type', $userType)
            ->first();

        if ($message) {
            $message->markAsRead();
        }

        return response()->json(['success' => true]);
    }
}
