# Order Card Hub Architecture - Clean Separation

## Concept: Hub + Standalone Features

```
┌─────────────────────────────────────────┐
│ BUYER DASHBOARD                         │
├─────────────────────────────────────────┤
│ Stats Widget                            │
│ Market Grid (Products)                  │
│                                         │
│ ORDER CARD HUB (Container Only)         │
│ ┌─────────────────────────────────────┐ │
│ │ [Quotes] [Messages] [Future]        │ │ ← Tabs/Sections
│ ├─────────────────────────────────────┤ │
│ │                                     │ │
│ │  Active Feature Component           │ │
│ │  (QuotePanel OR MessengerPanel)     │ │
│ │                                     │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

## File Structure

```
app/Livewire/Buyer/
├── Dashboard.php                   (Main orchestrator)
├── OrderCardHub.php                (NEW - Tab container)
├── Quotes/
│   └── QuotePanel.php             (Standalone feature)
└── Messaging/
    └── MessengerPanel.php         (Standalone feature - future)

resources/views/livewire/buyer/
├── dashboard.blade.php             (Main layout)
├── order-card-hub.blade.php        (NEW - Tab switcher)
├── quotes/
│   └── quote-panel.blade.php      (Pure quote feature)
└── messaging/
    └── messenger-panel.blade.php  (Pure messaging feature)
```

## Clean Separation Rules

### ✅ DO
- Order Card Hub = Pure container with tabs
- Each feature = Completely isolated component
- Features communicate via events to parent (Dashboard)
- CSS scoped to feature (quote-panel-*, messenger-panel-*)

### ❌ DON'T
- Mix features (no messaging button in quote panel)
- Cross-feature dependencies (quotes doesn't know about messages)
- Share state between features (each feature loads own data)
- Use global IDs that conflict (quotesContainer vs messagesContainer)

## Component Responsibilities

| Component | Responsibility | Owns |
|-----------|----------------|------|
| **Dashboard** | Orchestration, stats, products | Layout, grid, stats widget |
| **OrderCardHub** | Tab switching, feature routing | Tab bar, active feature state |
| **QuotePanel** | Quote display, timers, acceptance | Quote list, weekly planner, send |
| **MessengerPanel** | Chat conversations, messages | Conversation list, chat window |

## Future Features

Easy to add:
```
OrderCardHub
├── Quotes (active)
├── Messages
├── Orders History       ← NEW
├── Favorites           ← NEW
├── Analytics           ← NEW
```

Each new feature = New standalone component!
