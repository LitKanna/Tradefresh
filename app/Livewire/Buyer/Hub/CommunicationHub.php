<?php

namespace App\Livewire\Buyer\Hub;

use App\Models\Quote;
use App\Services\MessageService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Communication Hub - Unified Interface
 *
 * ORCHESTRATES 3 FEATURES:
 * 1. AI Assistant (default) - Create RFQs conversationally
 * 2. Quote Inbox - Receive and review vendor quotes
 * 3. Messaging - Direct buyer-vendor communication
 *
 * ARCHITECTURE:
 * - Single panel (380px × full height)
 * - Icon navigation (top bar)
 * - Dynamic view switching
 * - Unified WebSocket listeners
 * - Cross-feature notifications
 */
class CommunicationHub extends Component
{
    // View state
    public string $activeView = 'ai-assistant'; // ai-assistant | quote-inbox | messaging

    // Badge counts (real-time)
    public int $unreadQuotes = 0;

    public int $unreadMessages = 0;

    // Feature availability
    public bool $aiEnabled = true;

    public bool $quotesEnabled = true;

    public bool $messagingEnabled = true;

    /**
     * Mount - Load initial counts
     */
    public function mount(): void
    {
        $this->refreshCounts();
    }

    /**
     * WebSocket listeners - UNIFIED (no duplication)
     */
    public function getListeners(): array
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            return ['refreshHub' => 'refreshCounts'];
        }

        return [
            // Quote received from vendor
            "echo:buyer.{$buyer->id},quote.received" => 'onQuoteReceived',

            // Message received from vendor
            "echo-private:messages.buyer.{$buyer->id},.message.sent" => 'onMessageReceived',

            // Manual refresh trigger
            'refreshHub' => 'refreshCounts',

            // View-specific events
            'switch-to-quotes' => 'switchToQuotes',
            'switch-to-messaging' => 'switchToMessaging',
            'switch-to-ai' => 'switchToAI',
        ];
    }

    /**
     * Handle incoming quote from vendor
     */
    public function onQuoteReceived($event): void
    {
        Log::info('=== HUB: Quote received ===', [
            'buyer_id' => auth('buyer')->id(),
            'event' => $event,
        ]);

        // Increment badge
        $this->unreadQuotes++;

        // If AI view is active, notify it
        if ($this->activeView === 'ai-assistant') {
            $this->dispatch('ai-quote-notification', quoteData: $event);
        }

        // Play notification sound
        $this->dispatch('play-notification-sound');

        // Show toast
        $this->dispatch('show-toast', [
            'type' => 'success',
            'title' => 'New Quote Received!',
            'message' => "Quote from {$event['vendor']['business_name']}",
            'duration' => 5000,
        ]);

        // Pulse the quotes badge
        $this->dispatch('pulse-badge', icon: 'quotes');
    }

    /**
     * Handle incoming message from vendor
     */
    public function onMessageReceived($event): void
    {
        Log::info('=== HUB: Message received ===', [
            'buyer_id' => auth('buyer')->id(),
            'event' => $event,
        ]);

        // Increment badge
        $this->unreadMessages++;

        // If messaging view is active, notify it
        if ($this->activeView === 'messaging') {
            $this->dispatch('messaging-new-message', messageData: $event);
        }

        // Play notification sound
        $this->dispatch('play-notification-sound');

        // Show toast (unless messaging view is open)
        if ($this->activeView !== 'messaging') {
            $this->dispatch('show-toast', [
                'type' => 'info',
                'title' => 'New Message',
                'message' => "Message from {$event['sender_name']}",
                'duration' => 5000,
            ]);
        }

        // Pulse the messages badge
        $this->dispatch('pulse-badge', icon: 'messages');
    }

    /**
     * Refresh badge counts from database
     */
    public function refreshCounts(): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            $this->unreadQuotes = 0;
            $this->unreadMessages = 0;

            return;
        }

        // Count pending quotes (SQLite compatible)
        $this->unreadQuotes = Quote::where('buyer_id', $buyer->id)
            ->where('status', 'submitted')
            ->where('created_at', '>', now()->subMinutes(30)) // Last 30 mins - SQLite compatible
            ->count();

        // Count unread messages
        $messageService = app(MessageService::class);
        $this->unreadMessages = $messageService->getUnreadCount($buyer->id, 'buyer');
    }

    /**
     * Switch to a different view
     */
    public function switchView(string $view): void
    {
        Log::info('=== HUB: Switching view ===', [
            'from' => $this->activeView,
            'to' => $view,
        ]);

        $this->activeView = $view;

        // Reset badge for viewed section
        if ($view === 'quote-inbox') {
            $this->unreadQuotes = 0;
        } elseif ($view === 'messaging') {
            // Messaging component will handle marking as read
        }

        // Dispatch view changed event for analytics
        $this->dispatch('hub-view-changed', view: $view);
    }

    /**
     * Shortcut methods for view switching
     */
    public function switchToAI(): void
    {
        $this->switchView('ai-assistant');
    }

    public function switchToQuotes(): void
    {
        $this->switchView('quote-inbox');
    }

    public function switchToMessaging(): void
    {
        $this->switchView('messaging');
    }

    /**
     * Render hub
     */
    public function render()
    {
        return view('livewire.buyer.hub.communication-hub');
    }
}
