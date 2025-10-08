<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications
     */
    public function index(Request $request)
    {
        // Get authenticated user from any guard
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            abort(401, 'Unauthorized');
        }
        
        $query = DB::table('notifications')
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc');
        
        // Filter by read status
        if ($request->has('unread')) {
            $query->whereNull('read_at');
        }
        
        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        
        $notifications = $query->paginate(20)->withQueryString();
        
        // Get unread count
        $unreadCount = DB::table('notifications')
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
        
        return view('notifications.index', compact('notifications', 'unreadCount'));
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $notification = DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->first();
        
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        
        DB::table('notifications')
            ->where('id', $id)
            ->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        DB::table('notifications')
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
    }
    
    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $deleted = DB::table('notifications')
            ->where('id', $id)
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->delete();
        
        if (!$deleted) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        
        return response()->json(['success' => true, 'message' => 'Notification deleted']);
    }
    
    /**
     * Clear all notifications
     */
    public function clearAll()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        DB::table('notifications')
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->delete();
        
        return response()->json(['success' => true, 'message' => 'All notifications cleared']);
    }
    
    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['count' => 0]);
        }
        
        $count = DB::table('notifications')
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();
        
        return response()->json(['count' => $count]);
    }
    
    /**
     * Get recent notifications (AJAX)
     */
    public function recent()
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['notifications' => []]);
        }
        
        $notifications = DB::table('notifications')
            ->where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                $data = json_decode($notification->data, true);
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'time_ago' => \Carbon\Carbon::parse($notification->created_at)->diffForHumans(),
                ];
            });
        
        return response()->json(['notifications' => $notifications]);
    }
    
    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'order_updates' => 'boolean',
            'rfq_updates' => 'boolean',
            'quote_updates' => 'boolean',
            'payment_updates' => 'boolean',
            'delivery_updates' => 'boolean',
            'promotional' => 'boolean',
        ]);
        
        // Store preferences in user's metadata or settings
        $preferences = $request->only([
            'email_notifications',
            'sms_notifications',
            'push_notifications',
            'order_updates',
            'rfq_updates',
            'quote_updates',
            'payment_updates',
            'delivery_updates',
            'promotional',
        ]);
        
        // Update user notification preferences
        $user->notification_preferences = json_encode($preferences);
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated',
            'preferences' => $preferences
        ]);
    }
    
    /**
     * Get authenticated user from any guard
     */
    protected function getAuthenticatedUser()
    {
        if (Auth::guard('buyer')->check()) {
            return Auth::guard('buyer')->user();
        } elseif (Auth::guard('vendor')->check()) {
            return Auth::guard('vendor')->user();
        } elseif (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->user();
        } elseif (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        }
        
        return null;
    }
}