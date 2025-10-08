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
class BuyerMessenger extends Component
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
        $buyer = auth('buyer')->user();
        if (!$buyer) {
            return [];
        }

        return [
            "echo-private:messages.buyer.{$buyer->id},.message.sent" => 'onMessageReceived',
        ];
    }

    #[Computed]
    public function unreadCount(): int
    {
        return collect($this->conversations)->where('unread', true)->count();
    }

    public function loadConversations(): void
    {
        $buyer = auth('buyer')->user();

        if (!$buyer) {
            $this->conversations = [];
            $this->conversationsLoaded = true;
            return;
        }

        $this->conversations = $this->messageService->getConversations($buyer->id, 'buyer');
        $this->conversationsLoaded = true;
    }

    public function openChat(int $partnerId): void
    {
        $buyer = auth('buyer')->user();

        if (!$buyer) {
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

        // Mark conversation as read
        $markedCount = $this->messageService->markConversationRead($buyer->id, 'buyer', $partnerId, 'vendor');

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
        $this->dispatch('close-messenger');
    }

    public function sendMessage(): void
    {
        $this->validate();

        if (!$this->activePartner) {
            return;
        }

        $buyer = auth('buyer')->user();

        if (!$buyer) {
            return;
        }

        try {
            // Send via service
            $message = $this->messageService->sendMessage(
                $buyer->id,
                'buyer',
                $this->activePartner['id'],
                $this->activePartner['type'],
                $this->newMessage,
                null // quote_id if needed
            );

            // Add to UI
            $this->chatMessages[] = [
                'id' => $message->id,
                'from' => 'buyer',
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
                'buyer_id' => $buyer->id,
                'partner_id' => $this->activePartner['id'],
            ]);

            session()->flash('error', 'Failed to send message. Please try again.');
        }
    }

    public function onMessageReceived(array $event): void
    {
        Log::info('Buyer received message via WebSocket', [
            'event' => $event,
            'buyer_id' => auth('buyer')->id(),
        ]);

        // Reload conversations to update list
        $this->loadConversations();

        // If chat is open with this sender, add message to chat
        if ($this->showChat &&
            $this->activePartner &&
            $this->activePartner['id'] == $event['sender_id']) {

            $this->chatMessages[] = [
                'id' => $event['id'],
                'from' => 'vendor',
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
        return view('livewire.messaging.buyer-messenger');
    }
}
