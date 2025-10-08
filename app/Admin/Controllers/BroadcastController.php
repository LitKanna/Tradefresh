<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BroadcastMessage;
use App\Models\Vendor;
use App\Models\Buyer;
use App\Services\EnhancedNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BroadcastController extends Controller
{
    protected $notificationService;

    public function __construct(EnhancedNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth:admin');
    }

    /**
     * Display broadcast messages
     */
    public function index(Request $request)
    {
        $query = BroadcastMessage::with('admin')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $broadcasts = $query->paginate(20);

        $stats = [
            'total' => BroadcastMessage::count(),
            'draft' => BroadcastMessage::where('status', 'draft')->count(),
            'scheduled' => BroadcastMessage::where('status', 'scheduled')->count(),
            'sent' => BroadcastMessage::where('status', 'sent')->count(),
            'failed' => BroadcastMessage::where('status', 'failed')->count(),
        ];

        return view('admin.broadcasts.index', compact('broadcasts', 'stats'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $vendorCount = Vendor::where('status', 'active')->count();
        $buyerCount = Buyer::where('status', 'active')->count();
        $totalUsers = $vendorCount + $buyerCount;

        return view('admin.broadcasts.create', compact(
            'vendorCount',
            'buyerCount',
            'totalUsers'
        ));
    }

    /**
     * Store new broadcast
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_audience' => 'required|in:all,vendors,buyers,specific_segment',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,sms,push,database',
            'priority' => 'required|in:low,normal,high,urgent',
            'send_now' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'audience_criteria' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $broadcast = BroadcastMessage::create([
                'admin_id' => Auth::id(),
                'title' => $request->title,
                'message' => $request->message,
                'target_audience' => $request->target_audience,
                'audience_criteria' => $request->audience_criteria,
                'channels' => $request->channels,
                'priority' => $request->priority,
                'status' => $request->send_now ? 'sending' : ($request->scheduled_at ? 'scheduled' : 'draft'),
                'scheduled_at' => $request->scheduled_at,
            ]);

            // Calculate recipients count
            $recipients = $broadcast->getRecipients();
            $broadcast->update(['total_recipients' => $recipients->count()]);

            // Send immediately if requested
            if ($request->send_now) {
                $this->notificationService->sendBroadcast($broadcast);
                $message = 'Broadcast message is being sent to ' . $recipients->count() . ' recipients.';
            } elseif ($request->scheduled_at) {
                // TODO: Schedule job for later
                $message = 'Broadcast message scheduled for ' . $request->scheduled_at . '.';
            } else {
                $message = 'Broadcast message saved as draft.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'broadcast_id' => $broadcast->id,
                'redirect' => route('admin.broadcasts.show', $broadcast)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show broadcast details
     */
    public function show(BroadcastMessage $broadcast)
    {
        $broadcast->load('admin');
        
        // Get recipient preview
        $recipients = $broadcast->getRecipients()->take(10);
        $totalRecipients = $broadcast->getRecipients()->count();

        return view('admin.broadcasts.show', compact(
            'broadcast',
            'recipients',
            'totalRecipients'
        ));
    }

    /**
     * Edit broadcast (only drafts)
     */
    public function edit(BroadcastMessage $broadcast)
    {
        if ($broadcast->status !== 'draft') {
            return redirect()->route('admin.broadcasts.show', $broadcast)
                ->with('error', 'Only draft messages can be edited.');
        }

        $vendorCount = Vendor::where('status', 'active')->count();
        $buyerCount = Buyer::where('status', 'active')->count();
        $totalUsers = $vendorCount + $buyerCount;

        return view('admin.broadcasts.edit', compact(
            'broadcast',
            'vendorCount',
            'buyerCount',
            'totalUsers'
        ));
    }

    /**
     * Update broadcast
     */
    public function update(Request $request, BroadcastMessage $broadcast)
    {
        if ($broadcast->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft messages can be updated.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target_audience' => 'required|in:all,vendors,buyers,specific_segment',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,sms,push,database',
            'priority' => 'required|in:low,normal,high,urgent',
            'audience_criteria' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $broadcast->update($request->only([
                'title',
                'message',
                'target_audience',
                'audience_criteria',
                'channels',
                'priority'
            ]));

            // Recalculate recipients count
            $recipients = $broadcast->getRecipients();
            $broadcast->update(['total_recipients' => $recipients->count()]);

            return response()->json([
                'success' => true,
                'message' => 'Broadcast updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send broadcast immediately
     */
    public function send(BroadcastMessage $broadcast)
    {
        if (!$broadcast->canBeSent()) {
            return response()->json([
                'success' => false,
                'message' => 'This broadcast cannot be sent.'
            ], 422);
        }

        try {
            $this->notificationService->sendBroadcast($broadcast);

            return response()->json([
                'success' => true,
                'message' => 'Broadcast is being sent to ' . $broadcast->total_recipients . ' recipients.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel scheduled broadcast
     */
    public function cancel(BroadcastMessage $broadcast)
    {
        if ($broadcast->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Only scheduled broadcasts can be cancelled.'
            ], 422);
        }

        try {
            $broadcast->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Broadcast cancelled successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete broadcast (only drafts)
     */
    public function destroy(BroadcastMessage $broadcast)
    {
        if ($broadcast->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft messages can be deleted.'
            ], 422);
        }

        try {
            $broadcast->delete();

            return response()->json([
                'success' => true,
                'message' => 'Broadcast deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete broadcast: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview recipients
     */
    public function previewRecipients(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_audience' => 'required|in:all,vendors,buyers,specific_segment',
            'audience_criteria' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create temporary broadcast to get recipients
        $tempBroadcast = new BroadcastMessage([
            'target_audience' => $request->target_audience,
            'audience_criteria' => $request->audience_criteria,
        ]);

        $recipients = $tempBroadcast->getRecipients();
        $preview = $recipients->take(20)->map(function ($recipient) {
            return [
                'id' => $recipient->id,
                'name' => $recipient->company_name ?? $recipient->name,
                'email' => $recipient->email,
                'type' => class_basename(get_class($recipient))
            ];
        });

        return response()->json([
            'success' => true,
            'total' => $recipients->count(),
            'preview' => $preview
        ]);
    }

    /**
     * Get broadcast statistics
     */
    public function statistics(BroadcastMessage $broadcast)
    {
        $broadcast->updateStatistics();
        $broadcast->refresh();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_recipients' => $broadcast->total_recipients,
                'sent_count' => $broadcast->sent_count,
                'failed_count' => $broadcast->failed_count,
                'read_count' => $broadcast->read_count,
                'delivery_rate' => $broadcast->statistics['delivery_rate'] ?? 0,
                'read_rate' => $broadcast->statistics['read_rate'] ?? 0,
                'status' => $broadcast->status,
                'sent_at' => $broadcast->sent_at?->toISOString()
            ]
        ]);
    }
}