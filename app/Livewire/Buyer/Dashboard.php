<?php

namespace App\Livewire\Buyer;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    // Quotes are now handled by QuotePanel component
    public $products = [];

    public $showMessenger = false;

    public $unreadMessagesCount = 0;

    public function mount()
    {
        $this->loadDashboardData();
        $this->updateUnreadCount();
    }

    public function loadDashboardData()
    {
        $this->loadProducts();
    }

    // loadQuotes() method moved to QuotePanel component
    // See: app/Livewire/Quotes/BuyerQuotePanel.php

    public function loadProducts()
    {
        try {
            $this->products = Product::where('is_active', true)
                ->with('category')
                ->limit(16)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'unit' => $product->unit,
                        'category' => $product->category->name ?? 'Uncategorized',
                        'vendor_id' => $product->vendor_id,
                        'in_stock' => $product->stock_quantity > 0,
                        'price_change' => 0,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading products', ['error' => $e->getMessage()]);
            $this->products = [];
        }
    }

    // onQuoteReceived() method moved to QuotePanel component
    // See: app/Livewire/Buyer/Quotes/QuotePanel.php

    public function getListeners()
    {
        $buyerId = Auth::guard('buyer')->id();

        if (! $buyerId) {
            Log::warning('No buyer ID for listeners');

            return [
                'messenger-closed' => 'onMessengerClosed',
                'messages-read' => 'onMessagesRead',
                '$refresh' => '$refresh',
            ];
        }

        Log::info('Setting up listeners for buyer', ['buyer_id' => $buyerId]);

        return [
            // Message real-time updates (always-on)
            "echo-private:messages.buyer.{$buyerId},.message.sent" => 'onMessageReceived',

            // Internal Livewire events
            'messenger-closed' => 'onMessengerClosed',
            'messages-read' => 'onMessagesRead',
            '$refresh' => '$refresh',
        ];
    }

    // refreshQuotes() method moved to QuotePanel component
    // Dashboard no longer handles quote logic

    #[On('unreadCountUpdated')]
    public function updateUnreadCount()
    {
        $buyer = Auth::guard('buyer')->user();
        if ($buyer) {
            $this->unreadMessagesCount = \App\Models\Message::forUser($buyer->id, 'buyer')
                ->where('is_read', false)
                ->count();
        } else {
            $this->unreadMessagesCount = 0;
        }
    }

    /**
     * Handle incoming message in real-time (always-on listener)
     */
    public function onMessageReceived($event)
    {
        // Safeguard: Check authentication (session expiry protection)
        $buyer = auth('buyer')->user();
        if (! $buyer) {
            return;
        }

        Log::info('Dashboard received message via WebSocket', [
            'buyer_id' => $buyer->id,
            'sender' => $event['sender_name'] ?? 'Unknown',
            'messenger_open' => $this->showMessenger,
        ]);

        // Only increment badge if messenger is CLOSED
        // (If open, BuyerMessenger component handles it)
        if (! $this->showMessenger) {
            $this->unreadMessagesCount++;

            // Show toast notification
            $this->dispatch('show-toast', [
                'type' => 'info',
                'title' => 'New Message',
                'message' => "New message from {$event['sender_name']}",
                'duration' => 5000,
            ]);

            // Optional: Play notification sound
            $this->dispatch('play-notification-sound');
        }
    }

    /**
     * Sync badge when messenger closes (fresh database count)
     */
    #[On('messenger-closed')]
    public function onMessengerClosed()
    {
        // Always re-query database for accurate count
        $this->updateUnreadCount();
        Log::info('Messenger closed - badge synced from database');
    }

    /**
     * Sync badge when messages are marked as read
     */
    #[On('messages-read')]
    public function onMessagesRead()
    {
        $this->updateUnreadCount();
        Log::info('Messages marked as read - badge updated');
    }

    #[On('close-messenger')]
    public function closeMessenger()
    {
        $this->showMessenger = false;
        // Sync badge count when closing
        $this->updateUnreadCount();
    }

    /**
     * Toggle messenger and sync badge on open
     */
    public function toggleMessenger()
    {
        $this->showMessenger = ! $this->showMessenger;

        if ($this->showMessenger) {
            // Opening messenger - get fresh count
            $this->updateUnreadCount();
            Log::info('Messenger opened - badge synced');
        }
    }

    public function render()
    {
        return view('livewire.buyer.dashboard', [
            'products' => $this->products,
        ]);
    }
}
