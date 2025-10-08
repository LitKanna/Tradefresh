<?php

namespace App\Livewire\Messaging;

use App\Models\Message;
use App\Services\MessageService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Lazy]
class VendorMessenger extends Component
{
    protected MessageService $messageService;

    // UI State
    public bool $showConversations = true;
    public bool $showChat = false;
    public bool $conversationsLoaded = false;

    // Data
    public array $conversations = [];

    #[Locked]
    public ?array $activePartner = null;

    public array $chatMessages = [];

    #[Validate('required|max:1000')]
    public string $newMessage = '';

    public function boot(MessageService $messageService): void
    {
        $this->messageService = $messageService;
    }

    public function placeholder()
    {
        return view('livewire.messaging.messenger-skeleton');
    }

    public function mount(): void
    {
        // Empty - no database queries on mount for instant rendering
        $this->conversationsLoaded = false;
    }

    public function getListeners(): array
    {
        $vendor = auth('vendor')->user();
        if (!$vendor) {
            return [];
        }

        return [
            "echo-private:messages.vendor.{$vendor->id},.message.sent" => 'onMessageReceived',
        ];
    }

    #[Computed]
    public function unreadCount(): int
    {
        return collect($this->conversations)->where('unread', true)->count();
    }

    public function loadConversations(): void
    {
        $vendor = auth('vendor')->user();

        if (!$vendor) {
            $this->conversations = [];
            $this->conversationsLoaded = true;
            return;
        }

        $this->conversations = $this->messageService->getConversations($vendor->id, 'vendor');
        $this->conversationsLoaded = true;
    }

    public function openChat(int $partnerId): void
    {
        $vendor = auth('vendor')->user();

        if (!$vendor) {
            return;
        }

        // Find partner from conversations
        $conversation = collect($this->conversations)->firstWhere('partner_id', $partnerId);

        if (!$conversation) {
            return;
        }

        $this->activePartner = [
            'id' => $partnerId,
            'name' => $conversation['partner_name'],
            'type' => $conversation['partner_type'],
        ];

        // Load chat messages
        $messages = $this->messageService->getChatMessages(
            $vendor->id,
            'vendor',
            $partnerId,
            'buyer'
        );

        $this->chatMessages = $messages->map(function ($msg) use ($vendor) {
            return [
                'id' => $msg->id,
                'from' => $msg->sender_id === $vendor->id && $msg->sender_type === 'vendor' ? 'vendor' : 'buyer',
                'message' => $msg->message ?? $msg->content,
                'time' => $msg->created_at->format('g:i A'),
                'is_read' => $msg->is_read,
            ];
        })->toArray();

        // Mark conversation as read
        $markedCount = $this->messageService->markConversationRead($vendor->id, 'vendor', $partnerId, 'buyer');

        // Notify Dashboard to update badge if messages were marked as read
        if ($markedCount > 0) {
            $this->dispatch('messages-read');
        }

        // Update UI state
        $this->showConversations = false;
        $this->showChat = true;

        // Reload conversations to update unread count
        $this->loadConversations();

        // Scroll to bottom
        $this->dispatch('scroll-chat-to-bottom');
    }

    public function backToList(): void
    {
        $this->showChat = false;
        $this->showConversations = true;
        $this->activePartner = null;
        $this->chatMessages = [];
        $this->newMessage = '';
    }

    public function closeChat(): void
    {
        $this->backToList();
    }

    public function closeMessenger(): void
    {
        // Notify Dashboard to sync badge from database
        $this->dispatch('messenger-closed');
    }

    public function sendMessage(): void
    {
        $this->validate();

        if (!$this->activePartner) {
            return;
        }

        $vendor = auth('vendor')->user();

        if (!$vendor) {
            return;
        }

        try {
            // Send via service
            $message = $this->messageService->sendMessage(
                $vendor->id,
                'vendor',
                $this->activePartner['id'],
                $this->activePartner['type'],
                $this->newMessage,
                null // quote_id if needed
            );

            // Add to UI
            $this->chatMessages[] = [
                'id' => $message->id,
                'from' => 'vendor',
                'message' => $this->newMessage,
                'time' => now()->format('g:i A'),
                'is_read' => false,
            ];

            // Clear input
            $this->reset('newMessage');

            // Scroll to bottom
            $this->dispatch('scroll-chat-to-bottom');

        } catch (\Exception $e) {
            Log::error('Failed to send message', [
                'error' => $e->getMessage(),
                'vendor_id' => $vendor->id,
                'partner_id' => $this->activePartner['id'],
            ]);

            session()->flash('error', 'Failed to send message. Please try again.');
        }
    }

    public function onMessageReceived(array $event): void
    {
        Log::info('Vendor received message via WebSocket', [
            'event' => $event,
            'vendor_id' => auth('vendor')->id(),
        ]);

        // Reload conversations to update list
        $this->loadConversations();

        // If chat is open with this sender, add message to chat
        if ($this->showChat &&
            $this->activePartner &&
            $this->activePartner['id'] == $event['sender_id']) {

            $this->chatMessages[] = [
                'id' => $event['id'],
                'from' => 'buyer',
                'message' => $event['message'],
                'time' => now()->format('g:i A'),
                'is_read' => false,
            ];

            // Auto-scroll
            $this->dispatch('scroll-chat-to-bottom');

            // Mark as read
            $message = Message::find($event['id']);
            if ($message) {
                $message->markAsRead();
            }
        } else {
            // Show notification toast
            $this->dispatch('show-toast', [
                'type' => 'info',
                'title' => 'New Message',
                'message' => "New message from {$event['sender_name']}",
                'duration' => 5000,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.messaging.vendor-messenger');
    }
}
