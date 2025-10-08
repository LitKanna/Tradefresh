# Messaging System - Implementation Guide

This guide provides the exact commands and code snippets to implement the messaging refactor.

---

## STEP 1: CREATE LIVEWIRE COMPONENTS

### 1.1 Create Components Using Artisan

```bash
# Create Buyer Messenger Component
php artisan make:livewire Messaging/BuyerMessenger --no-interaction

# Create Vendor Messenger Component
php artisan make:livewire Messaging/VendorMessenger --no-interaction
```

This will create:
- `app/Livewire/Messaging/BuyerMessenger.php`
- `app/Livewire/Messaging/VendorMessenger.php`
- `resources/views/livewire/messaging/buyer-messenger.blade.php`
- `resources/views/livewire/messaging/vendor-messenger.blade.php`

---

## STEP 2: CREATE ASSET DIRECTORIES

```bash
# Create buyer messaging asset directories
mkdir -p public/assets/css/buyer/messaging
mkdir -p public/assets/js/buyer/messaging

# Create vendor messaging asset files
# (Note: public/vendor-dashboard/css already exists)
```

---

## STEP 3: IMPLEMENTATION CHECKLIST

### Backend Components

**File**: `app/Livewire/Messaging/BuyerMessenger.php`

**Extract from**: `app/Livewire/Buyer/Dashboard.php`
- Lines 22-35: Properties
- Lines 65-123: `loadMessages()`
- Lines 125-128: `toggleMessages()`
- Lines 130-183: `openChat()`
- Lines 185-191: `closeChat()`
- Lines 193-241: `sendMessage()`
- Lines 243-278: `onMessageReceived()`
- WebSocket listener from `getListeners()`

**New structure**:
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

    // ... COPY all messaging methods from Dashboard.php here ...

    public function render()
    {
        return view('livewire.messaging.buyer-messenger');
    }
}
```

**File**: `app/Livewire/Messaging/VendorMessenger.php`

**Extract from**: `app/Livewire/Vendor/Dashboard.php`
- Lines 52-66: Properties
- Lines 101-159: `loadMessages()`
- Lines 161-164: `toggleMessages()`
- Lines 166-226: `openChat()`
- Lines 228-238: `closeChat()`
- Lines 240-289: `sendMessage()`
- Lines 291-326: `onMessageReceived()`
- WebSocket listener from `getListeners()`

**New structure**: (Similar to BuyerMessenger, but for vendor)

---

### Frontend Views

**File**: `resources/views/livewire/messaging/buyer-messenger.blade.php`

**Extract from**: `resources/views/livewire/buyer/dashboard.blade.php`
- Lines 279-317: Messages overlay UI
- Lines 401-450: Chat messenger modal

**New structure**:
```blade
<div>
    <!-- Link to dedicated CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/buyer/messaging/messenger.css') }}">

    <!-- Messages Overlay -->
    @if($showMessagesOverlay)
    <div class="messages-overlay" wire:click.self="toggleMessages">
        <div class="messages-container">
            <!-- ... COPY messages overlay HTML here ... -->
        </div>
    </div>
    @endif

    <!-- Chat Messenger Modal -->
    @if($showChatMessenger && $activeChatVendor)
    <div class="chat-messenger-overlay" wire:click.self="closeChat">
        <div class="chat-messenger-container">
            <!-- ... COPY chat messenger HTML here ... -->
        </div>
    </div>
    @endif

    <!-- Link to dedicated JS -->
    <script src="{{ asset('assets/js/buyer/messaging/messenger.js') }}"></script>
</div>
```

**File**: `resources/views/livewire/messaging/vendor-messenger.blade.php`

**Extract from**: `resources/views/livewire/vendor/dashboard.blade.php`
- Lines 255-293: Messages overlay UI
- Lines 362-411: Chat messenger modal

**New structure**: (Similar to buyer-messenger.blade.php)

---

### CSS Assets

**File**: `public/assets/css/buyer/messaging/messenger.css`

**Extract from**: `resources/views/livewire/buyer/dashboard.blade.php`
- Lines 5448-5846: All messaging CSS (~400 lines)

**Structure**:
```css
/* ===================================
   BUYER MESSAGING SYSTEM STYLES
   Extracted from dashboard.blade.php
   =================================== */

/* Messages Overlay Styles */
.messages-overlay { ... }
.messages-container { ... }
.messages-header { ... }
/* ... rest of messaging CSS ... */

/* Chat Messenger Styles */
.chat-messenger-overlay { ... }
.chat-messenger-container { ... }
/* ... rest of chat CSS ... */
```

**File**: `public/vendor-dashboard/css/messaging.css`

**Extract from**: `resources/views/livewire/vendor/dashboard.blade.php`
- Lines 1465-1859: All messaging CSS (~400 lines)

---

### JavaScript Assets

**File**: `public/assets/js/buyer/messaging/messenger.js`

**Content**:
```javascript
/**
 * Buyer Messaging System JavaScript
 * Handles chat auto-scroll and UI interactions
 */

document.addEventListener('livewire:init', function () {
    // Auto-scroll chat to bottom
    Livewire.on('scroll-chat-to-bottom', () => {
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            setTimeout(() => {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 100);
        }
    });

    // Handle messenger close
    Livewire.on('messenger-closed', () => {
        // Cleanup if needed
    });
});
```

**File**: `public/vendor-dashboard/js/messaging.js`

**Content**: (Similar to buyer messenger.js)

---

## STEP 4: UPDATE DASHBOARDS

### Buyer Dashboard Backend

**File**: `app/Livewire/Buyer/Dashboard.php`

**REMOVE these lines**:
- Lines 22-35 (Properties - EXCEPT keep one)
- Lines 65-123 (`loadMessages()`)
- Lines 125-128 (`toggleMessages()`)
- Lines 130-183 (`openChat()`)
- Lines 185-191 (`closeChat()`)
- Lines 193-241 (`sendMessage()`)
- Lines 243-278 (`onMessageReceived()`)
- Remove messaging listener from `getListeners()` (line 59)

**ADD these**:
```php
// Keep only this property
public $showMessenger = false;
public $unreadMessagesCount = 0;

public function mount()
{
    $this->userId = Auth::guard('buyer')->id();
    $this->updateUnreadCount(); // ADD THIS
    $this->loadDashboardData();
}

// ADD THIS METHOD
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
```

### Buyer Dashboard View

**File**: `resources/views/livewire/buyer/dashboard.blade.php`

**REMOVE**:
- Lines 279-317 (Messages overlay)
- Lines 401-450 (Chat messenger modal)
- Lines 5448-5846 (Messaging CSS)

**MODIFY** (Lines 268-275):
Replace with:
```blade
<!-- Message Icon Button - Opens Messenger Component -->
<button wire:click="$set('showMessenger', true)" class="messaging-icon-btn" title="Messages">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 16px; height: 16px;">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-width="2"/>
    </svg>
    @if($unreadMessagesCount > 0)
        <span class="message-badge">{{ $unreadMessagesCount }}</span>
    @endif
</button>
```

**ADD** (After order card section, before closing main container):
```blade
<!-- Lazy-loaded Messenger Component -->
@if($showMessenger)
    @livewire('messaging.buyer-messenger', ['isOpen' => true])
@endif
```

**KEEP** (Minimal CSS for icon):
```css
/* Messaging Icon Button - Keep only this in dashboard */
.messaging-icon-btn {
    width: 28px;
    height: 28px;
    border: none;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: all 0.2s ease;
}

.messaging-icon-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    transform: translateY(-1px);
}

.message-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #10B981;
    color: white;
    font-size: 9px;
    font-weight: 600;
    padding: 2px 5px;
    border-radius: 8px;
    min-width: 16px;
    text-align: center;
}
```

---

### Vendor Dashboard Backend

**File**: `app/Livewire/Vendor/Dashboard.php`

**Same changes as buyer dashboard** (remove messaging methods, keep minimal properties)

### Vendor Dashboard View

**File**: `resources/views/livewire/vendor/dashboard.blade.php`

**Same changes as buyer dashboard view**

---

## STEP 5: TESTING SEQUENCE

### Unit Testing (Individual Components)

1. **Test Buyer Messenger Component**:
   ```bash
   # Navigate to /buyer/dashboard
   # Click message icon
   # Verify messenger overlay appears
   # Check message list loads
   # Open a chat
   # Send a test message
   # Close messenger
   ```

2. **Test Vendor Messenger Component**:
   ```bash
   # Navigate to /vendor/dashboard
   # Click message icon
   # Verify messenger overlay appears
   # Check message list loads
   # Open a chat
   # Send a test message
   # Close messenger
   ```

### Integration Testing (Cross-User)

1. **Test Real-time Messaging**:
   ```bash
   # Open buyer dashboard in one browser
   # Open vendor dashboard in another browser
   # Buyer opens chat with vendor
   # Buyer sends message
   # Verify vendor receives message in real-time
   # Vendor replies
   # Verify buyer receives message in real-time
   ```

2. **Test Unread Count Updates**:
   ```bash
   # Close messenger on both sides
   # Send message from buyer
   # Verify vendor's message badge shows unread count
   # Vendor opens messenger
   # Verify unread count clears
   ```

### Performance Testing

1. **Dashboard Load Time**:
   ```bash
   # Clear browser cache
   # Measure time to load buyer dashboard (should be 30-40% faster)
   # Measure time to load vendor dashboard (should be 30-40% faster)
   ```

2. **Messenger Load Time**:
   ```bash
   # Click message icon
   # Measure time for messenger component to appear
   # Should be < 500ms
   ```

---

## STEP 6: ROLLBACK PLAN

If issues occur, rollback is simple:

### Option 1: Git Revert
```bash
git log --oneline -10  # Find commit before refactor
git revert <commit_hash>
git push
```

### Option 2: Manual Rollback
1. Delete new messenger components
2. Restore dashboard files from backup
3. Clear Livewire cache: `php artisan livewire:delete-cached-components`
4. Clear browser cache

---

## VERIFICATION CHECKLIST

### Functionality Checks
- [ ] Message icon shows on both dashboards
- [ ] Unread badge appears with correct count
- [ ] Clicking icon opens messenger (no page reload)
- [ ] Message list displays conversations
- [ ] Opening chat shows message thread
- [ ] Sending message works (saves to database)
- [ ] Receiving message works (WebSocket real-time)
- [ ] Unread messages marked as read when chat opened
- [ ] Chat auto-scrolls to bottom on new message
- [ ] Closing messenger returns to dashboard
- [ ] No URL change throughout messaging flow

### Performance Checks
- [ ] Dashboard loads faster (measure with DevTools)
- [ ] Messenger component loads quickly on demand
- [ ] No lag or delay in messaging features
- [ ] WebSocket connections stable

### Design Checks
- [ ] Neumorphic design maintained
- [ ] Green theme colors used correctly
- [ ] Typography matches dashboard
- [ ] Animations smooth (no scale transforms)
- [ ] Responsive on mobile, tablet, desktop
- [ ] Works on 1080p, 1440p, 4K screens

### Code Quality Checks
- [ ] No duplicate code
- [ ] Laravel conventions followed
- [ ] No PHP errors in logs
- [ ] No JavaScript console errors
- [ ] CSS properly scoped (no conflicts)

---

## SUCCESS METRICS

**Before Refactor**:
- Buyer dashboard: 5,849 lines
- Vendor dashboard: 1,900 lines
- Initial load includes all messaging code

**After Refactor**:
- Buyer dashboard: ~5,150 lines (-12%)
- Vendor dashboard: ~1,140 lines (-40%)
- Initial load excludes messaging code
- Messaging loads on-demand (lazy loading)

**Performance Target**:
- 30-40% faster dashboard load time
- All messaging features remain functional
- Real-time WebSocket unchanged
- No regressions

---

## TROUBLESHOOTING

### Issue: Messenger doesn't appear when clicking icon
**Solution**: Check browser console for errors. Verify `$showMessenger` property exists in dashboard component.

### Issue: WebSocket messages not received
**Solution**: Check Laravel Reverb is running. Verify Echo listeners in messenger component's `getListeners()` method.

### Issue: Unread count not updating
**Solution**: Verify `unreadCountUpdated` event is emitted from messenger component and listened to in dashboard.

### Issue: CSS conflicts or broken styling
**Solution**: Ensure messaging CSS is properly scoped. Check for duplicate class names. Clear browser cache.

### Issue: Component not found error
**Solution**: Run `php artisan livewire:delete-cached-components` and `php artisan config:clear`.

---

**Ready for implementation!**
