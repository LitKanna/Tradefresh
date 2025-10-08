{{-- Communication Hub - Unified Interface --}}
<div class="communication-hub-container">

    {{-- Top Navigation Bar --}}
    <div class="hub-navigation">
        {{-- AI Assistant Icon --}}
        <button
            wire:click="switchView('ai-assistant')"
            class="hub-nav-icon {{ $activeView === 'ai-assistant' ? 'active' : '' }}"
            title="AI Assistant"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 8V4M12 4L9 7M12 4L15 7"/>
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 16v4M12 20l-3-3M12 20l3-3"/>
            </svg>
            <span class="nav-label">AI</span>
        </button>

        {{-- Quote Inbox Icon --}}
        <button
            wire:click="switchView('quote-inbox')"
            class="hub-nav-icon {{ $activeView === 'quote-inbox' ? 'active' : '' }}"
            title="Quotes"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                <path d="M8 9h8M8 13h6"/>
            </svg>
            <span class="nav-label">Quotes</span>
            @if($unreadQuotes > 0)
                <span class="nav-badge">{{ $unreadQuotes }}</span>
            @endif
        </button>

        {{-- Messaging Icon --}}
        <button
            wire:click="switchView('messaging')"
            class="hub-nav-icon {{ $activeView === 'messaging' ? 'active' : '' }}"
            title="Messages"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            </svg>
            <span class="nav-label">Messages</span>
            @if($unreadMessages > 0)
                <span class="nav-badge">{{ $unreadMessages }}</span>
            @endif
        </button>

        {{-- More Options Icon --}}
        <button class="hub-nav-icon" title="More Options">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="1"/>
                <circle cx="12" cy="5" r="1"/>
                <circle cx="12" cy="19" r="1"/>
            </svg>
            <span class="nav-label">More</span>
        </button>
    </div>

    {{-- Dynamic View Container --}}
    <div class="hub-view-container">
        @if($activeView === 'ai-assistant')
            <div class="hub-view" wire:key="view-ai">
                @livewire('buyer.hub.views.ai-assistant-view')
            </div>

        @elseif($activeView === 'quote-inbox')
            <div class="hub-view" wire:key="view-quotes">
                @livewire('buyer.hub.views.quote-inbox-view')
            </div>

        @elseif($activeView === 'messaging')
            <div class="hub-view" wire:key="view-messaging">
                @livewire('buyer.hub.views.messaging-view')
            </div>
        @endif
    </div>

</div>

{{-- Load Hub CSS via push (doesn't create root element) --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/hub-core.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/hub-navigation.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/ai-assistant.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/quote-inbox.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/messaging.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/buyer/hub/hub-animations.css') }}">
@endpush
