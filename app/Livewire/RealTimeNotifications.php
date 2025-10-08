<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class RealTimeNotifications extends Component
{
    public array $notifications = [];
    public int $unreadCount = 0;
    public bool $showDropdown = false;
    public ?int $userId = null;
    public ?string $userType = null;

    public function mount()
    {
        $this->userId = auth()->id();
        $this->userType = auth()->user()?->getMorphClass() ?? 'user';
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        // Load last 10 notifications from database
        $this->notifications = [];
        $this->unreadCount = 0;
    }

    #[On('echo:private-buyer.{userId},quote.received')]
    public function onQuoteReceived($event)
    {
        // Add new notification to the top
        $notification = [
            'id' => uniqid(),
            'title' => $event['notification']['title'] ?? 'New Quote',
            'message' => $event['notification']['message'] ?? 'You have received a new quote',
            'type' => $event['notification']['type'] ?? 'info',
            'icon' => $event['notification']['icon'] ?? '📬',
            'time' => now()->diffForHumans(),
            'read' => false,
            'data' => $event['quote'] ?? [],
        ];

        array_unshift($this->notifications, $notification);
        $this->unreadCount++;

        // Keep only last 10 notifications
        $this->notifications = array_slice($this->notifications, 0, 10);

        // Play notification sound
        $this->dispatch('play-notification-sound');

        // Show toast notification
        $this->dispatch('show-toast', [
            'type' => $notification['type'],
            'message' => $notification['message'],
        ]);
    }

    #[On('echo:private-rfq.{rfqId},vendor.typing')]
    public function onVendorTyping($event)
    {
        // Show typing indicator
        $this->dispatch('vendor-typing', [
            'vendor_id' => $event['vendor_id'],
            'vendor_name' => $event['vendor_name'],
            'is_typing' => $event['is_typing'],
        ]);
    }

    #[On('echo:market.prices,price.updated')]
    public function onPriceUpdate($event)
    {
        // Show price update notification if significant
        if ($event['alert'] === 'significant') {
            $icon = $event['direction'] === 'up' ? '📈' : '📉';
            $notification = [
                'id' => uniqid(),
                'title' => 'Price Alert',
                'message' => "{$event['product']} price {$event['direction']} by {$event['change_percent']}%",
                'type' => $event['direction'] === 'down' ? 'success' : 'warning',
                'icon' => $icon,
                'time' => now()->diffForHumans(),
                'read' => false,
                'data' => $event,
            ];

            array_unshift($this->notifications, $notification);
            $this->notifications = array_slice($this->notifications, 0, 10);
        }
    }

    public function toggleDropdown()
    {
        $this->showDropdown = !$this->showDropdown;

        if ($this->showDropdown) {
            $this->markAllAsRead();
        }
    }

    public function markAllAsRead()
    {
        foreach ($this->notifications as &$notification) {
            $notification['read'] = true;
        }
        $this->unreadCount = 0;
    }

    public function clearAll()
    {
        $this->notifications = [];
        $this->unreadCount = 0;
        $this->showDropdown = false;
    }

    public function render()
    {
        return view('livewire.real-time-notifications');
    }
}
