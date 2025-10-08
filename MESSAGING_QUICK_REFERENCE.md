# Messaging Refactor - Quick Reference Card

**One-page cheat sheet for implementation**

---

## 🚀 QUICK START COMMANDS

```bash
# Step 1: Create Components
php artisan make:livewire Messaging/BuyerMessenger --no-interaction
php artisan make:livewire Messaging/VendorMessenger --no-interaction

# Step 2: Create Asset Directories
mkdir -p public/assets/css/buyer/messaging
mkdir -p public/assets/js/buyer/messaging

# Step 3: Clear Caches (after implementation)
php artisan livewire:delete-cached-components
php artisan config:clear
php artisan view:clear
```

---

## 📋 CODE EXTRACTION CHECKLIST

### From Buyer Dashboard Controller (`app/Livewire/Buyer/Dashboard.php`)

**EXTRACT (move to BuyerMessenger.php)**:
- Lines 22-35: Properties
- Lines 65-123: `loadMessages()`
- Lines 125-128: `toggleMessages()`
- Lines 130-183: `openChat()`
- Lines 185-191: `closeChat()`
- Lines 193-241: `sendMessage()`
- Lines 243-278: `onMessageReceived()`
- Line 59: WebSocket listener (from `getListeners()`)

**KEEP (in Dashboard.php)**:
```php
public $showMessenger = false;
public $unreadMessagesCount = 0;

#[On('unreadCountUpdated')]
public function updateUnreadCount() { ... }
```

---

### From Buyer Dashboard View (`resources/views/livewire/buyer/dashboard.blade.php`)

**EXTRACT (move to buyer-messenger.blade.php)**:
- Lines 279-317: Messages overlay UI
- Lines 401-450: Chat messenger modal

**EXTRACT (move to messenger.css)**:
- Lines 5448-5846: All messaging CSS (~400 lines)

**REPLACE** (Lines 268-275):
```blade
<button wire:click="$set('showMessenger', true)" class="messaging-icon-btn">
    <svg>...</svg>
    @if($unreadMessagesCount > 0)
        <span class="message-badge">{{ $unreadMessagesCount }}</span>
    @endif
</button>

@if($showMessenger)
    @livewire('messaging.buyer-messenger', ['isOpen' => true])
@endif
```

---

## 📂 FILE STRUCTURE

```
NEW FILES:
├── app/Livewire/Messaging/
│   ├── BuyerMessenger.php (350 lines)
│   └── VendorMessenger.php (360 lines)
├── resources/views/livewire/messaging/
│   ├── buyer-messenger.blade.php (150 lines)
│   └── vendor-messenger.blade.php (150 lines)
├── public/assets/css/buyer/messaging/
│   └── messenger.css (400 lines)
├── public/assets/js/buyer/messaging/
│   └── messenger.js
├── public/vendor-dashboard/css/
│   └── messaging.css (400 lines)
└── public/vendor-dashboard/js/
    └── messaging.js

MODIFIED FILES:
├── app/Livewire/Buyer/Dashboard.php (525 → 332 lines)
├── app/Livewire/Vendor/Dashboard.php (1050 → 814 lines)
├── resources/views/livewire/buyer/dashboard.blade.php (5849 → 5150 lines)
└── resources/views/livewire/vendor/dashboard.blade.php (1900 → 1140 lines)
```

---

## 🎯 COMPONENT TEMPLATE

### BuyerMessenger.php

```php
<?php

namespace App\Livewire\Messaging;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class BuyerMessenger extends Component
{
    public $isOpen = false;
    public $showMessagesOverlay = false;
    public $showChatMessenger = false;
    public $messages = [];
    public $unreadMessagesCount = 0;
    public $activeChatVendor = null;
    public $chatMessages = [];
    public $newMessage = '';

    public function mount($isOpen = false)
    {
        $this->isOpen = $isOpen;
        $this->showMessagesOverlay = $isOpen;
        $this->loadMessages();
    }

    public function getListeners()
    {
        $buyer = Auth::guard('buyer')->user();
        if (!$buyer) return [];

        return [
            "echo-private:messages.buyer.{$buyer->id},.message.sent" => 'onMessageReceived',
        ];
    }

    public function loadMessages() { /* COPY FROM Dashboard.php */ }
    public function toggleMessages() { /* COPY FROM Dashboard.php */ }
    public function openChat($vendorId) { /* COPY FROM Dashboard.php */ }
    public function closeChat() { /* COPY FROM Dashboard.php */ }
    public function sendMessage() { /* COPY FROM Dashboard.php */ }
    public function onMessageReceived($event) { /* COPY FROM Dashboard.php */ }

    public function render()
    {
        return view('livewire.messaging.buyer-messenger');
    }
}
```

---

### buyer-messenger.blade.php

```blade
<div>
    <!-- Link CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/messaging/messenger.css') }}">

    <!-- Messages Overlay -->
    @if($showMessagesOverlay)
    <div class="messages-overlay" wire:click.self="toggleMessages">
        <div class="messages-container">
            <!-- COPY FROM dashboard.blade.php lines 283-315 -->
        </div>
    </div>
    @endif

    <!-- Chat Messenger Modal -->
    @if($showChatMessenger && $activeChatVendor)
    <div class="chat-messenger-overlay" wire:click.self="closeChat">
        <div class="chat-messenger-container">
            <!-- COPY FROM dashboard.blade.php lines 405-448 -->
        </div>
    </div>
    @endif

    <!-- Link JS -->
    <script src="{{ asset('assets/js/buyer/messaging/messenger.js') }}"></script>
</div>
```

---

### messenger.js

```javascript
document.addEventListener('livewire:init', function () {
    Livewire.on('scroll-chat-to-bottom', () => {
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            setTimeout(() => {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 100);
        }
    });
});
```

---

## 📊 PERFORMANCE TARGETS

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Dashboard Load | 700ms | 400ms | **40% faster** |
| Buyer Dashboard Lines | 5,849 | 5,150 | **12% smaller** |
| Vendor Dashboard Lines | 1,900 | 1,140 | **40% smaller** |
| Messaging WebSocket | Always on | On-demand | **Lazy load** |

---

## ✅ TESTING CHECKLIST

**Functional**:
- [ ] Click message icon → messenger opens
- [ ] Send message → saves to DB
- [ ] Receive message → real-time update
- [ ] Unread count updates correctly
- [ ] Close messenger → no URL change

**Performance**:
- [ ] Dashboard loads in ≤ 450ms
- [ ] Messenger loads in ≤ 350ms
- [ ] No memory leaks

**Design**:
- [ ] Neumorphic design maintained
- [ ] Green theme (#10B981) consistent
- [ ] Responsive (1080p → 4K)

---

## 🔧 TROUBLESHOOTING

**Component not found**:
```bash
php artisan livewire:delete-cached-components
php artisan config:clear
```

**WebSocket not working**:
- Check Laravel Reverb is running
- Verify Echo listeners in `getListeners()`

**CSS conflicts**:
- Clear browser cache
- Check CSS scoping
- Verify asset paths

---

## 📞 SUPPORT

**Documentation**:
- `MESSAGING_REFACTOR_PLAN.md` - Full plan
- `MESSAGING_IMPLEMENTATION_GUIDE.md` - Step-by-step guide
- `MESSAGING_ARCHITECTURE_DIAGRAM.md` - Visual diagrams
- `MESSAGING_REFACTOR_SUMMARY.md` - Executive summary

**Commands**:
```bash
# View logs
tail -f storage/logs/laravel.log

# Clear all caches
php artisan optimize:clear

# Test WebSocket
php artisan reverb:start
```

---

**Ready to implement!**
