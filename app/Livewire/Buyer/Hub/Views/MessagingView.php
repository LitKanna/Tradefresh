<?php

namespace App\Livewire\Buyer\Hub\Views;

use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Messaging View - Buyer-Vendor Communication
 *
 * FEATURES:
 * - Conversation list (left sidebar, 120px)
 * - Active chat (right area, 260px)
 * - Real-time WebSocket updates
 * - Typing indicators
 * - Unread badges
 *
 * UX PRINCIPLE: Familiar messaging interface (like WhatsApp/Slack)
 */
class MessagingView extends Component
{
    // UI state
    public bool $showConversationList = true;

    public bool $showActiveChat = false;

    public bool $conversationsLoaded = false;

    // Data
    public array $conversations = [];

    public ?array $activePartner = null;

    public array $chatMessages = [];

    #[Validate('required|max:1000')]
    public string $messageInput = '';

    // Loading states
    public bool $isSending = false;

    protected MessageService $messageService;

    public function boot(MessageService $messageService): void
    {
        $this->messageService = $messageService;
    }

    /**
     * Listeners - Include WebSocket events
     */
    protected function getListeners(): array
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            return [
                'messaging-new-message' => 'handleNewMessage',
                'open-vendor-chat' => 'openVendorChat',
            ];
        }

        return [
            // Real-time message from vendors via WebSocket
            "echo-private:messages.buyer.{$buyer->id},.message.sent" => 'handleNewMessage',

            // Manual triggers
            'messaging-new-message' => 'handleNewMessage',
            'open-vendor-chat' => 'openVendorChat',
        ];
    }

    /**
     * Mount - Load conversations
     */
    public function mount(): void
    {
        $this->loadConversations();
    }

    /**
     * Load conversation list
     */
    public function loadConversations(): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            $this->conversations = [];
            $this->conversationsLoaded = true;

            return;
        }

        try {
            $this->conversations = $this->messageService->getConversations($buyer->id, 'buyer');
            $this->conversationsLoaded = true;

            Log::info('Conversations loaded in hub', [
                'buyer_id' => $buyer->id,
                'count' => count($this->conversations),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load conversations in hub', [
                'buyer_id' => $buyer->id,
                'error' => $e->getMessage(),
            ]);

            $this->conversations = [];
            $this->conversationsLoaded = true;
        }
    }

    /**
     * Open chat with vendor
     */
    public function openChat(int $partnerId): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            return;
        }

        // Find conversation
        $conversation = collect($this->conversations)->firstWhere('partner_id', $partnerId);

        if (! $conversation) {
            Log::warning('Conversation not found', ['partner_id' => $partnerId]);

            return;
        }

        $this->activePartner = [
            'id' => $partnerId,
            'name' => $conversation['partner_name'],
            'type' => $conversation['partner_type'],
        ];

        // Load messages
        try {
            $messages = $this->messageService->getChatMessages(
                $buyer->id,
                'buyer',
                $partnerId,
                'vendor'
            );

            $this->chatMessages = $messages->map(function ($msg) use ($buyer) {
                return [
                    'id' => $msg->id,
                    'from' => $msg->sender_id === $buyer->id && $msg->sender_type === 'buyer' ? 'buyer' : 'vendor',
                    'message' => $msg->message ?? $msg->content,
                    'time' => $msg->created_at->format('g:i A'),
                    'is_read' => $msg->is_read,
                ];
            })->toArray();

            // Mark as read
            $this->messageService->markConversationRead($buyer->id, 'buyer', $partnerId, 'vendor');

            // Update UI
            $this->showConversationList = false;
            $this->showActiveChat = true;

            // Reload conversations to update unread count
            $this->loadConversations();

            // Notify parent hub to refresh badge
            $this->dispatch('refreshHub');

            // Scroll to bottom
            $this->dispatch('scroll-to-bottom');

        } catch (\Exception $e) {
            Log::error('Failed to open chat', [
                'buyer_id' => $buyer->id,
                'partner_id' => $partnerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Open chat with specific vendor (from other views)
     */
    public function openVendorChat($vendorId): void
    {
        // Check if conversation exists
        $existingConv = collect($this->conversations)->firstWhere('partner_id', $vendorId);

        if ($existingConv) {
            $this->openChat($vendorId);
        } else {
            // Create new conversation placeholder
            $buyer = auth('buyer')->user();
            if (! $buyer) {
                return;
            }

            // Find vendor
            $vendor = \App\Models\Vendor::find($vendorId);

            if (! $vendor) {
                Log::warning('Vendor not found for chat', ['vendor_id' => $vendorId]);

                return;
            }

            $this->activePartner = [
                'id' => $vendorId,
                'name' => $vendor->business_name,
                'type' => 'vendor',
            ];

            $this->chatMessages = [];
            $this->showConversationList = false;
            $this->showActiveChat = true;
        }
    }

    /**
     * Back to conversation list
     */
    public function backToList(): void
    {
        $this->showActiveChat = false;
        $this->showConversationList = true;
        $this->activePartner = null;
        $this->chatMessages = [];
        $this->messageInput = '';
    }

    /**
     * Send message
     */
    public function sendMessage(): void
    {
        $this->validate();

        if (! $this->activePartner || $this->isSending) {
            return;
        }

        $buyer = auth('buyer')->user();

        if (! $buyer) {
            return;
        }

        $this->isSending = true;

        try {
            // Send via service
            $message = $this->messageService->sendMessage(
                $buyer->id,
                'buyer',
                $this->activePartner['id'],
                $this->activePartner['type'],
                $this->messageInput,
                null
            );

            // Add to UI
            $this->chatMessages[] = [
                'id' => $message->id,
                'from' => 'buyer',
                'message' => $this->messageInput,
                'time' => now()->format('g:i A'),
                'is_read' => false,
            ];

            // Clear input
            $this->reset('messageInput');

            // Scroll to bottom
            $this->dispatch('scroll-to-bottom');

            Log::info('Message sent from hub', [
                'buyer_id' => $buyer->id,
                'vendor_id' => $this->activePartner['id'],
                'message_id' => $message->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send message from hub', [
                'buyer_id' => $buyer->id,
                'partner_id' => $this->activePartner['id'],
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'title' => 'Send Failed',
                'message' => 'Please try again',
                'duration' => 3000,
            ]);
        } finally {
            $this->isSending = false;
        }
    }

    /**
     * Handle new message from WebSocket
     */
    public function handleNewMessage($messageData): void
    {
        Log::info('=== Messaging: New message received ===', [
            'message_data' => $messageData,
        ]);

        // Reload conversations
        $this->loadConversations();

        // If active chat with this sender, add to messages
        if ($this->showActiveChat &&
            $this->activePartner &&
            $this->activePartner['id'] == $messageData['sender_id']) {

            $this->chatMessages[] = [
                'id' => $messageData['id'],
                'from' => 'vendor',
                'message' => $messageData['message'],
                'time' => now()->format('g:i A'),
                'is_read' => false,
            ];

            // Auto-scroll
            $this->dispatch('scroll-to-bottom');

            // Mark as read
            try {
                $message = Message::find($messageData['id']);
                if ($message) {
                    $message->markAsRead();
                }
            } catch (\Exception $e) {
                // Continue anyway
            }
        }

        // Notify parent to refresh badge
        $this->dispatch('refreshHub');
    }

    /**
     * Get unread count for current view
     */
    public function getUnreadCount(): int
    {
        return collect($this->conversations)->where('unread', true)->count();
    }

    /**
     * Render view
     */
    public function render()
    {
        return view('livewire.buyer.hub.views.messaging');
    }
}
