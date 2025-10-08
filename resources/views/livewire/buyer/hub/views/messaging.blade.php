{{-- Messaging View - Buyer-Vendor Communication --}}
<div class="messaging-view">

    @if($showConversationList)
        {{-- Conversation List --}}
        <div class="conversation-list-container">
            @if(!$conversationsLoaded)
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <div>Loading conversations...</div>
                </div>

            @else
                @forelse($conversations as $conversation)
                    <div
                        wire:key="conv-{{ $conversation['partner_id'] }}"
                        wire:click="openChat({{ $conversation['partner_id'] }})"
                        class="conversation-item {{ $conversation['unread'] ? 'unread' : '' }}"
                    >
                        {{-- Avatar --}}
                        <div class="conv-avatar">
                            {{ substr($conversation['partner_name'], 0, 1) }}
                        </div>

                        {{-- Content --}}
                        <div class="conv-content">
                            <div class="conv-header">
                                <span class="conv-name">{{ $conversation['partner_name'] }}</span>
                                <span class="conv-time">{{ $conversation['time'] }}</span>
                            </div>
                            <div class="conv-preview">
                                {{ \Str::limit($conversation['last_message'], 35) }}
                            </div>
                        </div>

                        {{-- Unread Badge --}}
                        @if($conversation['unread'])
                            <div class="unread-indicator">{{ $conversation['unread_count'] }}</div>
                        @endif
                    </div>

                @empty
                    <div class="empty-state">
                        <div class="empty-icon">💬</div>
                        <div class="empty-text">No conversations yet</div>
                        <div class="empty-hint">Chat with vendors about quotes</div>
                    </div>
                @endforelse
            @endif
        </div>
    @endif

    @if($showActiveChat && $activePartner)
        {{-- Active Chat View --}}
        <div class="active-chat-container">

            {{-- Chat Header --}}
            <div class="chat-header">
                <button wire:click="backToList" class="back-btn" title="Back to conversations">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </button>

                <div class="chat-partner-info">
                    <div class="chat-avatar">
                        {{ substr($activePartner['name'], 0, 1) }}
                    </div>
                    <div>
                        <div class="partner-name">{{ $activePartner['name'] }}</div>
                        <div class="partner-status">Active</div>
                    </div>
                </div>
            </div>

            {{-- Chat Messages --}}
            <div
                class="chat-messages-container"
                x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
                x-init="$nextTick(() => scrollToBottom())"
                x-effect="scrollToBottom()"
            >
                @forelse($chatMessages as $msg)
                    <div wire:key="msg-{{ $msg['id'] }}" class="chat-message-row {{ $msg['from'] === 'buyer' ? 'sent' : 'received' }}">
                        <div class="chat-message-bubble">
                            <div class="message-text">{{ $msg['message'] }}</div>
                            <div class="message-time">{{ $msg['time'] }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-chat">
                        <div class="empty-icon">👋</div>
                        <div>Start the conversation!</div>
                    </div>
                @endforelse
            </div>

            {{-- Chat Input --}}
            <div class="chat-input-container">
                <form wire:submit.prevent="sendMessage" class="chat-input-form">
                    <input
                        type="text"
                        wire:model="messageInput"
                        placeholder="Type a message..."
                        class="chat-input"
                        autocomplete="off"
                        maxlength="1000"
                        @if($isSending) disabled @endif
                    />

                    <button
                        type="submit"
                        class="chat-send-btn"
                        @if($isSending || empty(trim($messageInput))) disabled @endif
                    >
                        @if($isSending)
                            <div class="btn-spinner"></div>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                            </svg>
                        @endif
                    </button>
                </form>
            </div>

        </div>
    @endif

</div>
