# 🏗️ Communication Hub - Unified Architecture

## 🎯 VISION

**One panel. Three features. Seamless experience.**

Replace the confusing quote panel with a **Communication Hub** that intelligently combines:
- 🤖 **AI Assistant** (default view) - Conversational RFQ creation
- 📬 **Quote Inbox** - Receive and review vendor quotes
- 💬 **Messaging** - Direct buyer-vendor communication

---

## ❌ CURRENT PROBLEMS

### **Problem 1: Fragmented User Experience**
```
Current State:
- Quote Panel (shows quotes)
- AI Chat (separate component)
- Messaging (completely different page)
- Weekly Planner (modal)

Result: User doesn't know where to go for what
```

### **Problem 2: Architectural Chaos**
```
Files involved:
- BuyerQuotePanel.php (Livewire)
- OrderCardAI.php (Livewire)
- BuyerMessenger.php (Livewire)
- Chat/Freshhhy.php (Livewire)
- Multiple CSS files
- Scattered JavaScript

Result: Hard to maintain, hard to extend, hard to debug
```

### **Problem 3: Poor Component Integration**
```
- Features don't talk to each other
- State management is scattered
- WebSocket listeners duplicated
- No unified notification system
```

---

## ✅ THE SOLUTION: UNIFIED COMMUNICATION HUB

### **Core Concept:**

**Think of it like Slack or Microsoft Teams:**
- One interface
- Multiple channels
- Seamless switching
- Unified notifications

**For Sydney Markets:**
```
┌─────────────────────────────────────────┐
│ [🤖] [📬] [💬]        [•••]             │ ← Top bar (icons)
├─────────────────────────────────────────┤
│                                         │
│  ACTIVE VIEW (changes based on icon):   │
│                                         │
│  🤖 AI Assistant (default)              │
│     - Conversational interface          │
│     - Create RFQs by chatting           │
│     - Shows RFQ confirmations           │
│     - Shows incoming quotes inline      │
│                                         │
│  📬 Quote Inbox (when clicked)          │
│     - List of vendor quotes             │
│     - Accept/reject actions             │
│     - Links to vendor chat              │
│                                         │
│  💬 Messages (when clicked)             │
│     - Vendor conversations              │
│     - Quote-related chats               │
│     - Real-time messaging               │
│                                         │
│  (Content area fills remaining space)   │
│                                         │
├─────────────────────────────────────────┤
│ Input / Actions (contextual)            │ ← Footer (changes per view)
└─────────────────────────────────────────┘
```

---

## 🏗️ ARCHITECTURE DESIGN

### **Component Hierarchy:**

```
CommunicationHub.php (Orchestrator - manages state)
│
├── HubNavigation (top icons)
│   ├── AI icon (default active)
│   ├── Quotes icon (badge shows count)
│   ├── Messages icon (badge shows unread)
│   └── Settings icon
│
├── AIAssistantView (default view)
│   ├── Chat messages
│   ├── RFQ preview cards (inline)
│   ├── Quote notifications (inline)
│   └── Input area
│
├── QuoteInboxView
│   ├── Quote list
│   ├── Filter/sort controls
│   ├── Quote cards
│   └── Actions (accept/reject)
│
└── MessagingView
    ├── Conversation list
    ├── Active chat
    ├── Message input
    └── Typing indicators
```

### **File Structure (Clean & Organized):**

```
app/Livewire/Buyer/Hub/
├── CommunicationHub.php          ← Main orchestrator (state management)
├── Views/
│   ├── AIAssistantView.php       ← AI chat component
│   ├── QuoteInboxView.php        ← Quote receiving component
│   └── MessagingView.php         ← Vendor messaging component
└── Components/
    ├── HubNavigation.php         ← Top navigation bar
    ├── QuoteCard.php             ← Reusable quote display
    ├── MessageThread.php         ← Reusable message thread
    └── NotificationBadge.php     ← Reusable badge component

resources/views/livewire/buyer/hub/
├── communication-hub.blade.php   ← Main hub container
├── views/
│   ├── ai-assistant.blade.php    ← AI chat UI
│   ├── quote-inbox.blade.php     ← Quotes UI
│   └── messaging.blade.php       ← Messaging UI
├── components/
│   ├── hub-navigation.blade.php  ← Navigation bar
│   ├── quote-card.blade.php      ← Quote card template
│   └── message-thread.blade.php  ← Message thread template
└── partials/
    ├── empty-states.blade.php    ← Empty state messages
    └── loading-states.blade.php  ← Loading skeletons

public/assets/css/buyer/hub/
├── hub-core.css                  ← Core hub styles (grid, layout)
├── hub-navigation.css            ← Navigation bar styles
├── ai-assistant.css              ← AI chat styles
├── quote-inbox.css               ← Quote list styles
├── messaging.css                 ← Messaging styles
└── hub-animations.css            ← Transitions, animations

app/Services/Hub/
├── HubStateService.php           ← Manages hub state
├── HubNotificationService.php    ← Unified notifications
└── HubAnalyticsService.php       ← Track usage patterns
```

---

## 🎨 DETAILED HUB DESIGN

### **Top Navigation Bar (60px):**

```
┌─────────────────────────────────────────────────────────┐
│  [🤖]      [📬 3]      [💬 12]              [•••]       │
│  AI        Quotes      Messages             More        │
│  Active    Badge:3     Badge:12                         │
└─────────────────────────────────────────────────────────┘
```

**Design Specs:**
- Height: 60px (fixed)
- 4 icon buttons (equal width)
- Active state: Green underline (#10B981, 3px)
- Inactive state: Gray (#9CA3AF)
- Badges: Red dot with number (unread counts)
- Neumorphic background (#E8EBF0)
- Border-bottom: 1px solid rgba(184,190,199,0.2)

**State Indicators:**
- Active icon: Green color + bottom border
- Badge on quotes: Shows count of pending quotes
- Badge on messages: Shows unread message count
- Smooth transition (0.3s) when switching

---

### **View 1: AI ASSISTANT (Default View)**

```
┌─────────────────────────────────────────┐
│ [🤖] [📬 3] [💬 12]          [•••]      │ ← Navigation (60px)
├─────────────────────────────────────────┤
│                                         │
│ ⬅️ Hi! I'm Freshhhy. What do you      │ ← AI greeting
│    need today?                         │
│                                         │
│             I need 50kg tomatoes     ➡️ │ ← User
│                                         │
│ ⬅️ Got it! When do you need them?     │ ← AI
│                                         │
│             Friday morning           ➡️ │ ← User
│                                         │
│ ⬅️ Perfect! Creating RFQ now...        │ ← AI
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ ✅ RFQ Created!                     │ │ ← Inline notification
│ │ #RFQ-20251007-A3F2                  │ │
│ │ Broadcasting to vendors...          │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ⬅️ I've sent your request to 47       │ ← AI confirmation
│    vendors. Quotes will arrive soon!  │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ 📬 NEW QUOTE RECEIVED!              │ │ ← Quote notification (inline)
│ │ ABC Vendors - $450.00               │ │
│ │ [View Details] [Accept]             │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ⬅️ ABC Vendors just sent a quote for  │ ← AI explains
│    $450. That's 5% below market avg!  │
│                                         │
│             Show me the quote        ➡️ │ ← User
│                                         │
│ (Scrollable conversation flow)          │
│                                         │
├─────────────────────────────────────────┤
│ Type your message...       [Send]       │ ← Input (70px)
└─────────────────────────────────────────┘
```

**Key Difference from old design:**
- Everything happens in **one conversation flow**
- AI guides the entire process
- RFQ creation inline (not separate preview)
- Quote notifications appear in chat
- Natural, conversational UX

---

### **View 2: QUOTE INBOX**

```
┌─────────────────────────────────────────┐
│ [🤖] [📬 3] [💬 12]          [•••]      │ ← Navigation (60px)
│       Active                            │
├─────────────────────────────────────────┤
│ Pending Quotes (3)        [Sort ▼]      │ ← Subheader (40px)
├─────────────────────────────────────────┤
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ ABC Vendors           [28:45] ⏱️    │ │ ← Quote card (100px)
│ │ RFQ #RFQ-20251007-A3F2              │ │
│ │ 50kg Roma Tomatoes                  │ │
│ │ $450.00 • Delivery Friday 6AM       │ │
│ │ [View] [Accept] [💬 Chat]           │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ XYZ Trading           [14:22] ⏱️    │ │
│ │ RFQ #RFQ-20251007-A3F2              │ │
│ │ 50kg Roma Tomatoes                  │ │
│ │ $425.00 • Delivery Friday 6AM       │ │
│ │ [View] [Accept] [💬 Chat]           │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ Fresh Produce Co.     [08:15] ⏱️    │ │
│ │ RFQ #RFQ-20251007-A3F2              │ │
│ │ 50kg Roma Tomatoes                  │ │
│ │ $438.50 • Delivery Friday 6AM       │ │
│ │ [View] [Accept] [💬 Chat]           │ │
│ └─────────────────────────────────────┘ │
│                                         │
│ (Scrollable list)                       │
│                                         │
├─────────────────────────────────────────┤
│ 3 quotes • Expires in 28:45             │ ← Footer (40px)
└─────────────────────────────────────────┘
```

**Enhanced quote cards:**
- Taller (100px vs old 84px) - more breathing room
- Direct chat button (talk to vendor)
- Grouped by RFQ
- Sort options (price, time, rating)

---

### **View 3: MESSAGING**

```
┌─────────────────────────────────────────┐
│ [🤖] [📬 3] [💬 12]          [•••]      │ ← Navigation (60px)
│               Active                    │
├─────────────────────────────────────────┤
│ ┌───────┬───────────────────────────┐   │
│ │ ABC   │ ABC Vendors               │   │ ← Active chat header
│ │ XYZ   │ Quote #RFQ-20251007-A3F2  │   │
│ │ Fresh │                           │   │
│ │       │───────────────────────────│   │
│ │ (12)  │                           │   │
│ │       │ ⬅️ Hi, can you deliver   │   │
│ │List   │    at 7AM instead?        │   │
│ │of     │                           │   │
│ │convos │          Yes, 7AM works ➡️│   │
│ │       │                           │   │
│ │120px  │ ⬅️ Great! Confirmed.      │   │
│ │width  │                           │   │
│ │       │          Thank you!      ➡️│   │
│ │       │                           │   │
│ │       │ (Scrollable chat)         │   │
│ │       │                           │   │
│ │       │───────────────────────────│   │
│ │       │ Type message...    [Send] │   │ ← Input (60px)
│ └───────┴───────────────────────────┘   │
├─────────────────────────────────────────┤
│ 12 conversations • 3 unread             │ ← Footer (40px)
└─────────────────────────────────────────┘
```

**Split layout:**
- Left: Conversation list (120px)
- Right: Active chat (260px)
- Click conversation → opens chat
- Badge shows unread per conversation

---

## 🎯 DEFAULT VIEW BEHAVIOR

**When buyer opens dashboard:**

```
1. Hub loads with AI Assistant view active
2. AI greeting appears:
   "Hi Joe's Restaurant! 👋 Ready to order fresh produce?"

3. Buyer types naturally:
   "I need tomatoes and lettuce for Friday"

4. AI extracts data:
   ⬅️ "How many kg of tomatoes and lettuce?"

5. Buyer: "50kg tomatoes, 30kg lettuce"

6. AI creates RFQ inline:
   ┌─────────────────────────────┐
   │ ✅ RFQ CREATED              │
   │ #RFQ-20251007-A3F2          │
   │ • 50kg Roma Tomatoes        │
   │ • 30kg Iceberg Lettuce      │
   │ • Delivery: Friday 6AM      │
   │ Sent to 47 vendors          │
   └─────────────────────────────┘

7. Vendor quote arrives (WebSocket):
   [📬 3] badge appears on Quotes icon

   AI notifies inline:
   ⬅️ "🎉 ABC Vendors quoted $450!
       That's 5% below market average.
       [View] [Accept] [Chat with vendor]"

8. User can:
   - Accept directly from AI chat
   - Click [📬 3] to see all quotes
   - Click [💬] to message vendor
```

**Everything in one flow. No context switching.**

---

## 🧩 COMPONENT ARCHITECTURE

### **1. CommunicationHub.php (Orchestrator)**

**Responsibility:** Manage hub state, coordinate child components

```php
<?php

namespace App\Livewire\Buyer\Hub;

use Livewire\Component;

class CommunicationHub extends Component
{
    // State management
    public string $activeView = 'ai-assistant'; // ai-assistant, quote-inbox, messaging
    public int $unreadQuotes = 0;
    public int $unreadMessages = 0;

    // WebSocket listeners (unified)
    public function getListeners(): array
    {
        return [
            'echo:buyer.{$buyerId},quote.received' => 'onQuoteReceived',
            'echo:buyer.{$buyerId},message.received' => 'onMessageReceived',
            'refreshHub' => 'refresh',
        ];
    }

    // Handle quote received
    public function onQuoteReceived($event): void
    {
        $this->unreadQuotes++;

        // Notify AI view if active
        if ($this->activeView === 'ai-assistant') {
            $this->dispatch('quote-received-notify', quoteData: $event);
        }
    }

    // Switch views
    public function switchView(string $view): void
    {
        $this->activeView = $view;

        // Reset unread counts
        if ($view === 'quote-inbox') {
            $this->unreadQuotes = 0;
        } elseif ($view === 'messaging') {
            $this->unreadMessages = 0;
        }
    }

    public function render()
    {
        return view('livewire.buyer.hub.communication-hub');
    }
}
```

**Why this works:**
- ✅ Single source of truth for hub state
- ✅ Unified WebSocket listener (no duplication)
- ✅ Coordinates child components
- ✅ Manages badge counts
- ✅ Easy to extend (add new views)

---

### **2. AIAssistantView.php (Default View)**

**Responsibility:** Conversational AI interface with inline RFQ/Quote handling

```php
<?php

namespace App\Livewire\Buyer\Hub\Views;

use Livewire\Component;
use App\Services\FreshhhyAIService;
use App\Services\RFQService;

class AIAssistantView extends Component
{
    public array $messages = [];
    public string $userInput = '';
    public bool $isTyping = false;
    public ?string $conversationId = null;

    // Listen for quote notifications from parent
    protected $listeners = [
        'quote-received-notify' => 'handleQuoteNotification'
    ];

    public function handleQuoteNotification($quoteData): void
    {
        // Add quote notification to chat
        $this->messages[] = [
            'role' => 'system',
            'type' => 'quote-notification',
            'content' => "🎉 New quote from {$quoteData['vendor']}!",
            'data' => $quoteData,
            'timestamp' => now()->toISOString()
        ];
    }

    public function render()
    {
        return view('livewire.buyer.hub.views.ai-assistant');
    }
}
```

**Handles:**
- AI conversation
- Inline RFQ creation notifications
- Inline quote received notifications
- Quick actions (accept quote from chat)

---

### **3. QuoteInboxView.php**

**Responsibility:** Display and manage vendor quotes

```php
<?php

namespace App\Livewire\Buyer\Hub\Views;

use Livewire\Component;
use App\Models\Quote;

class QuoteInboxView extends Component
{
    public array $quotes = [];
    public string $sortBy = 'time'; // time, price, rating
    public string $filterStatus = 'pending';

    public function mount(): void
    {
        $this->loadQuotes();
    }

    public function loadQuotes(): void
    {
        $buyer = auth('buyer')->user();

        $query = Quote::where('buyer_id', $buyer->id)
            ->where('status', $this->filterStatus)
            ->with(['vendor', 'rfq']);

        // Apply sorting
        switch($this->sortBy) {
            case 'price':
                $query->orderBy('total_amount', 'asc');
                break;
            case 'rating':
                $query->join('vendors', 'quotes.vendor_id', '=', 'vendors.id')
                      ->orderBy('vendors.rating', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $this->quotes = $query->get()->toArray();
    }

    public function render()
    {
        return view('livewire.buyer.hub.views.quote-inbox');
    }
}
```

---

### **4. MessagingView.php**

**Responsibility:** Buyer-vendor direct messaging

```php
<?php

namespace App\Livewire\Buyer\Hub\Views;

use Livewire\Component;
use App\Models\Message;
use App\Services\MessageService;

class MessagingView extends Component
{
    public array $conversations = [];
    public ?int $activeConversationId = null;
    public array $messages = [];
    public string $messageInput = '';

    public function selectConversation(int $conversationId): void
    {
        $this->activeConversationId = $conversationId;
        $this->loadMessages();
    }

    public function sendMessage(): void
    {
        if (empty($this->messageInput)) return;

        // Send message via service
        $messageService = app(MessageService::class);
        $messageService->send(
            from: auth('buyer')->id(),
            to: $this->getRecipientId(),
            message: $this->messageInput
        );

        $this->messageInput = '';
        $this->loadMessages();
    }

    public function render()
    {
        return view('livewire.buyer.hub.views.messaging');
    }
}
```

---

## 🎨 UNIFIED HUB BLADE TEMPLATE

**Main container:** `communication-hub.blade.php`

```blade
<div class="communication-hub-container" style="
    width: 380px;
    height: 100%;
    display: flex;
    flex-direction: column;
    background: var(--neuro-bg);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: var(--card-shadow-normal);
">
    {{-- Top Navigation --}}
    <div class="hub-navigation">
        <button
            class="hub-nav-icon {{ $activeView === 'ai-assistant' ? 'active' : '' }}"
            wire:click="switchView('ai-assistant')"
        >
            <svg>🤖</svg>
            <span>AI</span>
        </button>

        <button
            class="hub-nav-icon {{ $activeView === 'quote-inbox' ? 'active' : '' }}"
            wire:click="switchView('quote-inbox')"
        >
            <svg>📬</svg>
            <span>Quotes</span>
            @if($unreadQuotes > 0)
                <span class="badge">{{ $unreadQuotes }}</span>
            @endif
        </button>

        <button
            class="hub-nav-icon {{ $activeView === 'messaging' ? 'active' : '' }}"
            wire:click="switchView('messaging')"
        >
            <svg>💬</svg>
            <span>Messages</span>
            @if($unreadMessages > 0)
                <span class="badge">{{ $unreadMessages }}</span>
            @endif
        </button>

        <button class="hub-nav-icon">
            <svg>•••</svg>
            <span>More</span>
        </button>
    </div>

    {{-- Dynamic View Container --}}
    <div class="hub-view-container" style="flex: 1; overflow: hidden;">
        @if($activeView === 'ai-assistant')
            @livewire('buyer.hub.views.ai-assistant-view')

        @elseif($activeView === 'quote-inbox')
            @livewire('buyer.hub.views.quote-inbox-view')

        @elseif($activeView === 'messaging')
            @livewire('buyer.hub.views.messaging-view')
        @endif
    </div>
</div>
```

**Simple. Clean. Extensible.**

---

## 🎯 NEUMORPHIC DESIGN SYSTEM

### **Hub Container:**

```css
.communication-hub-container {
    background: #E8EBF0;  /* Neumorphic base */
    border-radius: 20px;

    /* Soft raised shadow */
    box-shadow: 8px 8px 16px rgba(184, 190, 199, 0.4),
                -8px -8px 16px rgba(255, 255, 255, 0.9);
}
```

### **Navigation Icons:**

```css
.hub-nav-icon {
    flex: 1;
    padding: 12px 8px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #9CA3AF;
    transition: all 0.3s ease;
    cursor: pointer;
}

.hub-nav-icon.active {
    color: #10B981;
    border-bottom-color: #10B981;
    background: rgba(16, 185, 129, 0.05);
}

.hub-nav-icon .badge {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #EF4444;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}
```

### **Message Bubbles (Consistent across AI & Messaging):**

```css
/* User message (right-aligned, green) */
.message-bubble.user {
    align-self: flex-end;
    background: linear-gradient(135deg, #10B981, #059669);
    color: white;
    padding: 10px 14px;
    border-radius: 16px 16px 4px 16px;
    max-width: 75%;
    font-size: 13px;
    line-height: 1.4;

    /* Subtle shadow */
    box-shadow: 2px 2px 8px rgba(16, 185, 129, 0.2);
}

/* AI/Vendor message (left-aligned, white) */
.message-bubble.ai,
.message-bubble.vendor {
    align-self: flex-start;
    background: white;
    color: #1F2937;
    padding: 10px 14px;
    border-radius: 16px 16px 16px 4px;
    max-width: 75%;
    font-size: 13px;
    line-height: 1.4;
    border: 1px solid #E5E7EB;

    /* Subtle shadow */
    box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.05);
}

/* System notification (centered, light green) */
.message-bubble.system {
    align-self: center;
    background: #ECFDF5;
    color: #065F46;
    padding: 8px 12px;
    border-radius: 12px;
    max-width: 90%;
    font-size: 12px;
    text-align: center;
    border: 1px solid #10B981;
}
```

### **Quote Cards (Inbox view):**

```css
.quote-card {
    background: #E8EBF0;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 8px;

    /* Inset neumorphic */
    box-shadow: inset 3px 3px 6px #B8BEC7,
                inset -3px -3px 6px #FFFFFF;

    transition: all 0.3s ease;
}

.quote-card:hover {
    box-shadow: inset 4px 4px 8px #B8BEC7,
                inset -4px -4px 8px #FFFFFF;
}

.quote-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.quote-card-vendor {
    font-weight: 600;
    font-size: 13px;
    color: #1F2937;
}

.quote-card-timer {
    background: #E8EBF0;
    color: #10B981;
    padding: 4px 8px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;

    /* Timer inset */
    box-shadow: inset 2px 2px 4px #B8BEC7,
                inset -2px -2px 4px #FFFFFF;
}

.quote-card-timer.urgent {
    color: #000000;  /* Black when < 5 mins */
}

.quote-card-actions {
    display: flex;
    gap: 6px;
    margin-top: 8px;
}

.quote-action-btn {
    flex: 1;
    padding: 8px;
    background: #E8EBF0;
    border: none;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;

    /* Raised neumorphic */
    box-shadow: 3px 3px 6px #B8BEC7,
                -3px -3px 6px #FFFFFF;

    transition: all 0.2s ease;
}

.quote-action-btn:hover {
    /* Pressed inset */
    box-shadow: inset 2px 2px 4px #B8BEC7,
                inset -2px -2px 4px #FFFFFF;
}

.quote-action-btn.accept {
    background: linear-gradient(135deg, #10B981, #059669);
    color: white;
    box-shadow: 3px 3px 6px rgba(16, 185, 129, 0.3),
                -3px -3px 6px rgba(255, 255, 255, 0.7);
}
```

---

## 🔄 TRANSITIONS & ANIMATIONS

### **View Switching:**

```css
/* Smooth fade transition */
.hub-view-container {
    position: relative;
}

.hub-view {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

### **Badge Pulse (New notification):**

```css
.badge.new {
    animation: badgePulse 1s ease-in-out 3;
}

@keyframes badgePulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
}
```

### **Typing Indicator:**

```css
.typing-indicator {
    display: flex;
    gap: 4px;
    padding: 10px 14px;
    background: white;
    border-radius: 16px;
    width: fit-content;
}

.typing-dot {
    width: 6px;
    height: 6px;
    background: #10B981;
    border-radius: 50%;
    animation: typing 1.4s infinite;
}

.typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
        opacity: 0.5;
    }
    30% {
        transform: translateY(-8px);
        opacity: 1;
    }
}
```

---

## 📋 IMPLEMENTATION ROADMAP

### **Phase 1: Foundation (Week 1)**
- [ ] Create hub file structure
- [ ] Build CommunicationHub orchestrator
- [ ] Build HubNavigation component
- [ ] Create hub-core.css with layout
- [ ] Test view switching

### **Phase 2: AI Assistant View (Week 2)**
- [ ] Build AIAssistantView component
- [ ] Integrate FreshhhyAIService
- [ ] Add inline RFQ creation
- [ ] Add inline quote notifications
- [ ] Style with neumorphic design

### **Phase 3: Quote Inbox View (Week 3)**
- [ ] Build QuoteInboxView component
- [ ] Migrate quote cards
- [ ] Add sort/filter controls
- [ ] Integrate quick actions
- [ ] Add timer system

### **Phase 4: Messaging View (Week 4)**
- [ ] Build MessagingView component
- [ ] Two-column layout (conversations + chat)
- [ ] Integrate MessageService
- [ ] Real-time WebSocket updates
- [ ] Typing indicators

### **Phase 5: Integration & Polish (Week 5)**
- [ ] Unified WebSocket listeners
- [ ] Cross-view notifications
- [ ] Transitions and animations
- [ ] Mobile responsiveness
- [ ] Performance optimization

### **Phase 6: Testing & Launch (Week 6)**
- [ ] User testing with 5 buyers
- [ ] Fix edge cases
- [ ] Analytics integration
- [ ] Documentation
- [ ] Production rollout

---

## 🎯 SUCCESS METRICS

**How we'll know it works:**

1. **User Clarity:** Buyers know where to go for what (< 5 second decision time)
2. **Task Completion:** 80%+ of RFQs created via AI (vs manual forms)
3. **Quote Response:** Buyers respond to quotes within 10 mins (vs 30+ mins before)
4. **Message Engagement:** 50%+ of buyers use messaging for vendor communication
5. **Error Rate:** < 2% of AI conversations fail to create RFQ
6. **User Satisfaction:** 8/10+ rating on hub usability

---

## 🚀 NEXT STEP

I'll create the **complete implementation** with:
- All component files
- All blade templates
- All CSS (standalone files)
- Integration guide
- Testing checklist

**Ready for me to build this?** This will take ~12 file creations but will solve your hub problem permanently.

Say "yes, build the unified hub" and I'll implement everything.
