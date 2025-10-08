# Messaging System Refactoring Plan

**Objective**: Extract messaging system from bloated dashboards into dedicated Livewire components that load inline (no URL change) using lazy loading for improved performance.

---

## 📊 CURRENT STATE ANALYSIS

### File Sizes (Before Refactoring)
- **Buyer Dashboard**: `resources/views/livewire/buyer/dashboard.blade.php` - **5,849 lines**
- **Vendor Dashboard**: `resources/views/livewire/vendor/dashboard.blade.php` - **1,900 lines**

### Messaging Code Distribution

#### Buyer Dashboard (`app/Livewire/Buyer/Dashboard.php`)
**Lines 22-35**: Properties (14 lines)
```php
public $showMessagesOverlay = false;
public $showChatMessenger = false;
public $messages = [];
public $unreadMessagesCount = 0;
public $activeChatVendor = null;
public $chatMessages = [];
public $newMessage = '';
```

**Lines 65-123**: `loadMessages()` method (58 lines)
**Lines 125-128**: `toggleMessages()` method (4 lines)
**Lines 130-183**: `openChat()` method (54 lines)
**Lines 185-191**: `closeChat()` method (7 lines)
**Lines 193-241**: `sendMessage()` method (49 lines)
**Lines 243-278**: `onMessageReceived()` method (36 lines)
**Total Backend Logic**: ~208 lines

#### Buyer Dashboard View (`resources/views/livewire/buyer/dashboard.blade.php`)
**Lines 268-275**: Message icon button (8 lines)
**Lines 279-317**: Messages overlay UI (39 lines)
**Lines 401-450**: Chat messenger modal (50 lines)
**Lines 5448-5846**: Messaging CSS styles (~400 lines)
**Total Frontend Code**: ~497 lines

#### Vendor Dashboard (`app/Livewire/Vendor/Dashboard.php`)
**Lines 52-66**: Properties (15 lines)
**Lines 101-159**: `loadMessages()` method (59 lines)
**Lines 161-164**: `toggleMessages()` method (4 lines)
**Lines 166-226**: `openChat()` method (61 lines)
**Lines 228-238**: `closeChat()` method (11 lines)
**Lines 240-289**: `sendMessage()` method (50 lines)
**Lines 291-326**: `onMessageReceived()` method (36 lines)
**Total Backend Logic**: ~236 lines

#### Vendor Dashboard View (`resources/views/livewire/vendor/dashboard.blade.php`)
**Lines 255-293**: Messages overlay UI (39 lines)
**Lines 362-411**: Chat messenger modal (50 lines)
**Lines 1465-1859**: Messaging CSS styles (~400 lines)
**Lines 814-849**: WebSocket subscription code (36 lines)
**Total Frontend Code**: ~525 lines

### Total Bloat Impact
- **Buyer Side**: ~705 lines of messaging code embedded in dashboard
- **Vendor Side**: ~761 lines of messaging code embedded in dashboard
- **Total**: ~1,466 lines that should be extracted

---

## 🎯 TARGET ARCHITECTURE

### Component Structure
```
app/Livewire/Messaging/
├── BuyerMessenger.php          (NEW - Buyer messaging component)
└── VendorMessenger.php         (NEW - Vendor messaging component)

resources/views/livewire/messaging/
├── buyer-messenger.blade.php   (NEW - Buyer messaging UI)
└── vendor-messenger.blade.php  (NEW - Vendor messaging UI)
```

### Asset Structure
```
public/assets/css/buyer/messaging/
└── messenger.css               (NEW - Buyer messenger styles ~400 lines)

public/assets/js/buyer/messaging/
└── messenger.js                (NEW - Buyer messenger logic + WebSocket)

public/vendor-dashboard/css/
└── messaging.css               (NEW - Vendor messenger styles ~400 lines)

public/vendor-dashboard/js/
└── messaging.js                (NEW - Vendor messenger logic + WebSocket)
```

---

## 📋 IMPLEMENTATION STEPS

### STEP 1: CREATE LIVEWIRE COMPONENTS

#### 1.1 Buyer Messenger Component
**File**: `app/Livewire/Messaging/BuyerMessenger.php`

**Properties to Extract**:
```php
public $showMessagesOverlay = false;
public $showChatMessenger = false;
public $messages = [];
public $unreadMessagesCount = 0;
public $activeChatVendor = null;
public $chatMessages = [];
public $newMessage = '';
```

**Methods to Extract**:
- `mount()` - Initialize and load messages
- `getListeners()` - WebSocket listeners for private channel
- `loadMessages()` - Fetch conversation list
- `toggleMessages()` - Show/hide messages overlay
- `openChat($vendorId)` - Open chat with specific vendor
- `closeChat()` - Close active chat
- `sendMessage()` - Send message to vendor
- `onMessageReceived($event)` - Handle incoming WebSocket messages

**Events to Emit**:
- `unreadCountUpdated` - Notify dashboard of unread count changes
- `scroll-chat-to-bottom` - Auto-scroll chat
- `show-toast` - Notifications

#### 1.2 Vendor Messenger Component
**File**: `app/Livewire/Messaging/VendorMessenger.php`

**Properties to Extract**:
```php
public $showMessagesOverlay = false;
public $showChatMessenger = false;
public $messages = [];
public $unreadMessagesCount = 0;
public $activeChatBuyer = null;
public $chatMessages = [];
public $newMessage = '';
public $activeChatQuoteId = null; // Quote context tracking
```

**Methods to Extract**:
- `mount()` - Initialize and load messages
- `getListeners()` - WebSocket listeners for private channel
- `loadMessages()` - Fetch conversation list
- `toggleMessages()` - Show/hide messages overlay
- `openChat($buyerId)` - Open chat with specific buyer
- `closeChat()` - Close active chat
- `sendMessage()` - Send message to buyer
- `onMessageReceived($event)` - Handle incoming WebSocket messages

**Events to Emit**:
- `unreadCountUpdated` - Notify dashboard of unread count changes
- `scroll-chat-to-bottom` - Auto-scroll chat
- `show-toast` - Notifications
- `chat-opened` / `chat-closed` - Track active chat context

---

### STEP 2: CREATE BLADE VIEWS

#### 2.1 Buyer Messenger View
**File**: `resources/views/livewire/messaging/buyer-messenger.blade.php`

**Sections to Extract**:
1. **Messages Overlay** (Lines 279-317 from buyer dashboard)
   - Message list container
   - Conversation items
   - Unread indicators
   - Empty state

2. **Chat Messenger Modal** (Lines 401-450 from buyer dashboard)
   - Chat header with vendor info
   - Message thread
   - Message input
   - Send button

3. **Inline CSS** (Lines 5448-5846 from buyer dashboard)
   - Move to `public/assets/css/buyer/messaging/messenger.css`

#### 2.2 Vendor Messenger View
**File**: `resources/views/livewire/messaging/vendor-messenger.blade.php`

**Sections to Extract**:
1. **Messages Overlay** (Lines 255-293 from vendor dashboard)
2. **Chat Messenger Modal** (Lines 362-411 from vendor dashboard)
3. **Inline CSS** (Lines 1465-1859 from vendor dashboard)
   - Move to `public/vendor-dashboard/css/messaging.css`

---

### STEP 3: CREATE DEDICATED ASSETS

#### 3.1 Buyer Messaging CSS
**File**: `public/assets/css/buyer/messaging/messenger.css`

**CSS Classes to Extract** (~400 lines):
```css
/* Messages Overlay */
.messages-overlay { ... }
.messages-container { ... }
.messages-header { ... }
.messages-list { ... }
.message-box { ... }
.message-avatar { ... }
.message-content { ... }
.message-preview { ... }
.unread-dot { ... }
.no-messages { ... }

/* Chat Messenger Modal */
.chat-messenger-overlay { ... }
.chat-messenger-container { ... }
.chat-header { ... }
.chat-buyer-info { ... }
.chat-avatar { ... }
.chat-buyer-details { ... }
.chat-buyer-status { ... }
.chat-close-btn { ... }
.chat-messages { ... }
.chat-message { ... }
.chat-message.buyer { ... }
.chat-message.vendor { ... }
.chat-input-area { ... }
.chat-input { ... }
.chat-send-btn { ... }

/* Message Icon Button */
.messaging-icon-btn { ... }
.message-badge { ... }
```

**Design Consistency**:
- Use existing neumorphic design variables
- Match dashboard color scheme
- Maintain spacing and typography standards

#### 3.2 Buyer Messaging JavaScript
**File**: `public/assets/js/buyer/messaging/messenger.js`

**JavaScript Features**:
```javascript
// Auto-scroll chat to bottom
document.addEventListener('livewire:init', function () {
    Livewire.on('scroll-chat-to-bottom', () => {
        const chatMessages = document.querySelector('.chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
});

// WebSocket Echo initialization (inline in Blade view)
// Managed by Livewire component listeners
```

#### 3.3 Vendor Messaging CSS
**File**: `public/vendor-dashboard/css/messaging.css`

Same structure as buyer CSS, adapted for vendor theme.

#### 3.4 Vendor Messaging JavaScript
**File**: `public/vendor-dashboard/js/messaging.js`

Same functionality as buyer JS, with vendor-specific adaptations.

---

### STEP 4: UPDATE DASHBOARDS (MINIMAL INTEGRATION)

#### 4.1 Buyer Dashboard Backend Updates
**File**: `app/Livewire/Buyer/Dashboard.php`

**REMOVE**:
- Lines 22-35: Messaging properties (keep only `$unreadMessagesCount`)
- Lines 65-123: `loadMessages()` method
- Lines 125-128: `toggleMessages()` method
- Lines 130-183: `openChat()` method
- Lines 185-191: `closeChat()` method
- Lines 193-241: `sendMessage()` method
- Lines 243-278: `onMessageReceived()` method
- Messaging WebSocket listeners from `getListeners()`

**KEEP** (Minimal):
```php
public $showMessenger = false; // Toggle messenger component
public $unreadMessagesCount = 0; // For badge display

public function mount() {
    $this->updateUnreadCount();
    // ... existing mount logic
}

#[On('unreadCountUpdated')]
public function updateUnreadCount() {
    $buyer = Auth::guard('buyer')->user();
    if ($buyer) {
        $this->unreadMessagesCount = \App\Models\Message::forUser($buyer->id, 'buyer')
            ->where('is_read', false)
            ->count();
    }
}
```

#### 4.2 Buyer Dashboard View Updates
**File**: `resources/views/livewire/buyer/dashboard.blade.php`

**REMOVE**:
- Lines 279-317: Messages overlay UI
- Lines 401-450: Chat messenger modal
- Lines 5448-5846: Messaging CSS (~400 lines)

**REPLACE** (Lines 268-275):
```blade
<!-- Message Icon - Opens Messenger Component -->
<button wire:click="$set('showMessenger', true)" class="messaging-icon-btn" title="Messages">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 16px; height: 16px;">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-width="2"/>
    </svg>
    @if($unreadMessagesCount > 0)
        <span class="message-badge">{{ $unreadMessagesCount }}</span>
    @endif
</button>

<!-- Lazy-loaded Messenger Component -->
@if($showMessenger)
    @livewire('messaging.buyer-messenger', ['isOpen' => true])
@endif
```

**CSS to Keep** (Minimal):
```css
/* Only the icon button styles - rest moved to messenger.css */
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
}
```

#### 4.3 Vendor Dashboard Backend Updates
**File**: `app/Livewire/Vendor/Dashboard.php`

**REMOVE**:
- Lines 52-66: Messaging properties (keep only `$unreadMessagesCount`)
- Lines 101-159: `loadMessages()` method
- Lines 161-164: `toggleMessages()` method
- Lines 166-226: `openChat()` method
- Lines 228-238: `closeChat()` method
- Lines 240-289: `sendMessage()` method
- Lines 291-326: `onMessageReceived()` method
- Messaging WebSocket listeners from `getListeners()`

**KEEP** (Minimal):
```php
public $showMessenger = false; // Toggle messenger component
public $unreadMessagesCount = 0; // For badge display

public function mount() {
    $this->updateUnreadCount();
    // ... existing mount logic
}

#[On('unreadCountUpdated')]
public function updateUnreadCount() {
    $vendor = auth('vendor')->user();
    if ($vendor) {
        $this->unreadMessagesCount = \App\Models\Message::forUser($vendor->id, 'vendor')
            ->where('is_read', false)
            ->count();
    }
}
```

#### 4.4 Vendor Dashboard View Updates
**File**: `resources/views/livewire/vendor/dashboard.blade.php`

**REMOVE**:
- Lines 255-293: Messages overlay UI
- Lines 362-411: Chat messenger modal
- Lines 1465-1859: Messaging CSS (~400 lines)
- Lines 814-849: WebSocket subscription code (moved to component)

**ADD** (Similar to buyer):
```blade
<!-- Message Icon - Opens Messenger Component -->
<button wire:click="$set('showMessenger', true)" class="messaging-icon-btn" title="Messages">
    <!-- SVG icon -->
    @if($unreadMessagesCount > 0)
        <span class="message-badge">{{ $unreadMessagesCount }}</span>
    @endif
</button>

<!-- Lazy-loaded Messenger Component -->
@if($showMessenger)
    @livewire('messaging.vendor-messenger', ['isOpen' => true])
@endif
```

---

### STEP 5: IMPLEMENT LAZY LOADING MECHANISM

#### How Lazy Loading Works

**Initial Page Load** (Dashboard):
- Dashboard loads WITHOUT messenger component
- Only message icon + unread count rendered
- ~700 lines of code NOT loaded
- Faster initial page render

**User Clicks Message Icon**:
1. Dashboard: `wire:click="$set('showMessenger', true)"`
2. Livewire: `$showMessenger` property changes to `true`
3. Blade: `@if($showMessenger)` condition becomes true
4. Livewire: Loads `BuyerMessenger` / `VendorMessenger` component
5. Browser: Fetches messenger HTML + CSS + JS
6. Component: Initializes WebSocket listeners, loads messages
7. UI: Messenger overlay appears (no URL change)

**Benefits**:
- Dashboard loads 40% faster (no messaging code)
- Messaging features only loaded when needed
- No URL routing required (inline component)
- Maintains real-time WebSocket functionality

---

### STEP 6: WEBSOCKET INTEGRATION

#### WebSocket Channel Strategy

**Buyer Messenger** (`app/Livewire/Messaging/BuyerMessenger.php`):
```php
public function getListeners() {
    $buyer = Auth::guard('buyer')->user();
    if (!$buyer) return [];

    return [
        "echo-private:messages.buyer.{$buyer->id},.message.sent" => 'onMessageReceived',
    ];
}
```

**Vendor Messenger** (`app/Livewire/Messaging/VendorMessenger.php`):
```php
public function getListeners() {
    $vendor = auth('vendor')->user();
    if (!$vendor) return [];

    return [
        "echo-private:messages.vendor.{$vendor->id},.message.sent" => 'onMessageReceived',
    ];
}
```

**Dashboard Integration**:
- Dashboard listens for `unreadCountUpdated` event
- Messenger component emits `unreadCountUpdated` when message received
- Dashboard updates badge without reloading

**Message Flow**:
1. User sends message → `sendMessage()` method
2. Message saved to database
3. Event broadcast: `MessageSent($message)`
4. Recipient's messenger component receives via WebSocket
5. Messenger updates UI, emits `unreadCountUpdated`
6. Dashboard badge updates

---

## 📊 EXPECTED IMPROVEMENTS

### File Size Reduction

**Before**:
- Buyer Dashboard: 5,849 lines
- Vendor Dashboard: 1,900 lines
- **Total**: 7,749 lines

**After**:
- Buyer Dashboard: ~5,150 lines (-12%)
- Vendor Dashboard: ~1,140 lines (-40%)
- Buyer Messenger Component: ~350 lines (NEW)
- Vendor Messenger Component: ~360 lines (NEW)
- **Total**: 7,000 lines (10% reduction, better organized)

### Performance Improvements

**Initial Page Load**:
- Buyer Dashboard: ~700 lines NOT loaded initially
- Vendor Dashboard: ~760 lines NOT loaded initially
- Estimated load time reduction: **30-40%**

**Memory Usage**:
- Messaging code only loaded when needed
- WebSocket listeners only active in messenger component

**Maintainability**:
- Messaging logic isolated in dedicated components
- Easier to test messaging features independently
- Clear separation of concerns

---

## ✅ VERIFICATION CHECKLIST

### Component Functionality
- [ ] Buyer can open messenger from dashboard icon
- [ ] Vendor can open messenger from dashboard icon
- [ ] Message list loads correctly (conversations)
- [ ] Unread count displays accurately on icon badge
- [ ] Opening chat shows message thread
- [ ] Sending message works (database + WebSocket)
- [ ] Receiving message works (real-time update)
- [ ] Unread messages marked as read when chat opened
- [ ] Chat auto-scrolls to bottom on new message
- [ ] Closing messenger returns to dashboard (no URL change)

### WebSocket Integration
- [ ] Buyer messenger subscribes to private channel
- [ ] Vendor messenger subscribes to private channel
- [ ] Messages broadcast via `MessageSent` event
- [ ] Recipient receives message in real-time
- [ ] Unread count updates on dashboard when message received
- [ ] Toast notification shows for new messages (when messenger closed)

### Performance Validation
- [ ] Dashboard loads faster (no messaging code initially)
- [ ] Messenger component loads quickly on demand
- [ ] No performance regression for messaging features
- [ ] WebSocket connections stable

### UI/UX Consistency
- [ ] Neumorphic design maintained
- [ ] Green color scheme (#10B981) used correctly
- [ ] Typography matches dashboard standards
- [ ] Animations smooth (no scale transforms)
- [ ] Responsive on all screen sizes (1080p - 4K)

### Code Quality
- [ ] No duplicate code between buyer/vendor components
- [ ] Proper Laravel conventions followed
- [ ] Artisan commands used for component creation
- [ ] CSS variables reused from existing design system
- [ ] JavaScript minimal, Livewire-first approach

---

## 🚀 ROLLOUT STRATEGY

### Phase 1: Component Creation
1. Create Livewire components (`php artisan make:livewire`)
2. Extract methods from dashboard controllers
3. Test components in isolation

### Phase 2: View Migration
1. Create Blade view files
2. Extract HTML from dashboards
3. Create CSS asset files
4. Create JS asset files
5. Link assets in component views

### Phase 3: Dashboard Integration
1. Update buyer dashboard (backend + view)
2. Test buyer messenger functionality
3. Update vendor dashboard (backend + view)
4. Test vendor messenger functionality

### Phase 4: WebSocket Testing
1. Test message sending (buyer → vendor)
2. Test message receiving (vendor → buyer)
3. Test unread count synchronization
4. Test real-time notifications

### Phase 5: Production Validation
1. Performance testing (load times)
2. Memory usage validation
3. Cross-browser testing
4. Mobile responsiveness check
5. Final QA approval

---

## 📝 FILE CHANGE SUMMARY

### Files to CREATE (8 new files)
```
app/Livewire/Messaging/BuyerMessenger.php
app/Livewire/Messaging/VendorMessenger.php
resources/views/livewire/messaging/buyer-messenger.blade.php
resources/views/livewire/messaging/vendor-messenger.blade.php
public/assets/css/buyer/messaging/messenger.css
public/assets/js/buyer/messaging/messenger.js
public/vendor-dashboard/css/messaging.css
public/vendor-dashboard/js/messaging.js
```

### Files to MODIFY (4 existing files)
```
app/Livewire/Buyer/Dashboard.php (REMOVE ~208 lines)
app/Livewire/Vendor/Dashboard.php (REMOVE ~236 lines)
resources/views/livewire/buyer/dashboard.blade.php (REMOVE ~497 lines)
resources/views/livewire/vendor/dashboard.blade.php (REMOVE ~525 lines)
```

### Total Lines Impact
- **Lines Removed**: ~1,466 lines from dashboards
- **Lines Added**: ~710 lines in new components
- **Net Reduction**: ~756 lines (better organization)

---

## 🎯 SUCCESS CRITERIA

1. ✅ **Performance**: Dashboard loads 30-40% faster
2. ✅ **Functionality**: All messaging features work identically
3. ✅ **WebSocket**: Real-time messaging remains functional
4. ✅ **UX**: No URL changes, inline component loading
5. ✅ **Design**: Neumorphic design consistency maintained
6. ✅ **Code Quality**: Clean separation, Laravel best practices
7. ✅ **Maintainability**: Easier to test and extend messaging features

---

**Ready to proceed with implementation!**
