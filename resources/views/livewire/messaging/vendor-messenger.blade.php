<div class="messages-full-overlay">
    <!-- Messenger Container - Single Panel Toggle -->
    <div class="messenger-container">

        @if($showConversations)
            <!-- CONVERSATION LIST VIEW -->
            <div class="conversations-panel">
                <div class="conversations-header">
                    <h3>Messages @if($this->unreadCount > 0)<span style="color: #10B981;">({{ $this->unreadCount }})</span>@endif</h3>
                    <button wire:click="closeMessenger" class="close-messenger-btn" title="Close Messenger">
                        <svg viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>

                <div class="conversations-list" wire:init="loadConversations" wire:poll.30s="loadConversations">
                    @if(!$conversationsLoaded)
                        <!-- Loading state while data loads -->
                        <div class="loading-state" style="text-align: center; padding: 40px; color: #9CA3AF;">
                            <div class="loading-spinner" style="margin: 0 auto 12px;"></div>
                            <p style="font-size: 14px;">Loading conversations...</p>
                        </div>
                    @else
                    @forelse($conversations as $conversation)
                        <div wire:key="conv-{{ $conversation['partner_id'] }}"
                             class="conversation-item {{ $conversation['unread'] ? 'unread' : '' }}"
                             wire:click="openChat({{ $conversation['partner_id'] }})">
                            <div class="conversation-avatar">
                                {{ substr($conversation['partner_name'], 0, 1) }}
                            </div>
                            <div class="conversation-content">
                                <div class="conversation-header">
                                    <span class="conversation-name">{{ $conversation['partner_name'] }}</span>
                                    <span class="conversation-time">{{ $conversation['time'] }}</span>
                                </div>
                                <div class="conversation-preview">{{ $conversation['last_message'] }}</div>
                            </div>
                            @if($conversation['unread'])
                                <div class="unread-badge"></div>
                            @endif
                        </div>
                    @empty
                        <div class="no-conversations">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <p>No conversations yet</p>
                        </div>
                    @endforelse
                    @endif
                </div>
            </div>
        @endif

        @if($showChat && $activePartner)
            <!-- ACTIVE CHAT VIEW -->
            <div class="chat-panel">
                <!-- Chat Header -->
                <div class="chat-header">
                    <button wire:click="backToList" class="back-btn" title="Back to Messages">
                        <svg viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path d="M19 12H5M12 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <div class="chat-partner-info">
                        <div class="chat-avatar">
                            {{ substr($activePartner['name'], 0, 1) }}
                        </div>
                        <div class="chat-partner-details">
                            <h4>{{ $activePartner['name'] }}</h4>
                            <span class="chat-partner-status">Active</span>
                        </div>
                    </div>
                    <button wire:click="closeMessenger" class="chat-close-btn" title="Close Messenger">
                        <svg viewBox="0 0 24 24" stroke="currentColor" fill="none">
                            <path d="M6 18L18 6M6 6l12 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>

                <!-- Chat Messages -->
                <div class="chat-messages">
                    @foreach($chatMessages as $index => $msg)
                        <div wire:key="msg-{{ $msg['id'] ?? $index }}" class="chat-message {{ $msg['from'] === 'vendor' ? 'sent' : 'received' }}">
                            <div class="chat-message-bubble">
                                <p>{{ $msg['message'] }}</p>
                                <span class="chat-message-time">{{ $msg['time'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Chat Input -->
                <div class="chat-input-container">
                    <input
                        type="text"
                        wire:model.live="newMessage"
                        wire:keydown.enter="sendMessage"
                        class="chat-input"
                        placeholder="Type a message..."
                        autocomplete="off"
                        maxlength="1000">

                    <button
                        wire:click="sendMessage"
                        wire:loading.attr="disabled"
                        class="chat-send-btn"
                        :disabled="!newMessage">
                        <span wire:loading.remove wire:target="sendMessage">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span wire:loading wire:target="sendMessage">
                            <div class="loading-spinner"></div>
                        </span>
                    </button>
                </div>
            </div>
        @endif
    </div>

    <!-- Load Unified Styles -->
    <link rel="stylesheet" href="{{ asset('assets/css/shared/messaging/messenger.css') }}">

    <!-- Load Unified JavaScript -->
    <script src="{{ asset('assets/js/shared/messaging/messenger.js') }}"></script>
</div>
