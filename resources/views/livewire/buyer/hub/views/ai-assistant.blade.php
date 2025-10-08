{{-- AI Assistant View - Conversational RFQ Creation --}}
<div class="ai-assistant-view">

    {{-- Chat Messages Container --}}
    <div
        class="ai-chat-messages"
        x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
        x-init="$nextTick(() => scrollToBottom())"
        x-effect="scrollToBottom()"
    >
        @forelse($messages as $message)
            @if($message['role'] === 'user')
                {{-- User Message --}}
                <div class="message-row user-row">
                    <div class="message-bubble user-bubble">
                        {{ $message['content'] }}
                        <div class="message-time">
                            {{ \Carbon\Carbon::parse($message['timestamp'] ?? now())->format('g:i A') }}
                        </div>
                    </div>
                </div>

            @elseif($message['role'] === 'assistant')
                {{-- AI Message --}}
                <div class="message-row ai-row">
                    <div class="message-bubble ai-bubble">
                        {!! nl2br(e($message['content'])) !!}
                        <div class="message-time">
                            {{ \Carbon\Carbon::parse($message['timestamp'] ?? now())->format('g:i A') }}
                        </div>
                    </div>
                </div>

            @elseif($message['role'] === 'system')
                {{-- System Notifications --}}
                @if(($message['type'] ?? '') === 'rfq-created')
                    {{-- RFQ Created Notification --}}
                    <div class="message-row system-row">
                        <div class="system-notification success">
                            <div class="notification-icon">✅</div>
                            <div class="notification-content">
                                {!! nl2br(e($message['content'])) !!}
                            </div>
                        </div>
                    </div>

                @elseif(($message['type'] ?? '') === 'quote-notification')
                    {{-- Quote Received Notification --}}
                    <div class="message-row system-row">
                        <div class="quote-notification-card">
                            <div class="quote-notif-header">
                                <span class="quote-icon">📬</span>
                                <span class="quote-title">New Quote Received</span>
                            </div>
                            <div class="quote-notif-body">
                                <div class="quote-vendor">{{ $message['data']['vendor']['business_name'] ?? 'Vendor' }}</div>
                                <div class="quote-amount">${{ number_format($message['data']['total_amount'] ?? 0, 2) }}</div>
                                <div class="quote-delivery">
                                    Delivery: {{ $message['data']['delivery_date'] ?? 'TBD' }}
                                </div>
                            </div>
                            <div class="quote-notif-actions">
                                <button
                                    wire:click="viewQuoteDetails({{ $message['data']['id'] ?? 0 }})"
                                    class="quote-notif-btn view"
                                >
                                    View Details
                                </button>
                                <button
                                    wire:click="quickAcceptQuote({{ $message['data']['id'] ?? 0 }})"
                                    class="quote-notif-btn accept"
                                >
                                    Accept
                                </button>
                            </div>
                        </div>
                    </div>

                @elseif(($message['type'] ?? '') === 'quote-accepted')
                    {{-- Quote Accepted Confirmation --}}
                    <div class="message-row system-row">
                        <div class="system-notification success">
                            <div class="notification-icon">✅</div>
                            <div class="notification-content">
                                {!! nl2br(e($message['content'])) !!}
                            </div>
                        </div>
                    </div>

                @else
                    {{-- Generic System Message --}}
                    <div class="message-row system-row">
                        <div class="system-notification">
                            {!! nl2br(e($message['content'])) !!}
                        </div>
                    </div>
                @endif
            @endif
        @empty
            <div class="empty-state">
                <div class="empty-icon">🤖</div>
                <div class="empty-text">Start a conversation to create your RFQ</div>
            </div>
        @endforelse

        {{-- Typing Indicator --}}
        @if($isTyping)
            <div class="message-row ai-row">
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        @endif
    </div>

    {{-- Error Banner --}}
    @if($errorMessage)
        <div class="error-banner">
            <span>⚠️ {{ $errorMessage }}</span>
            @if($showManualFormLink)
                <button wire:click="$dispatch('open-manual-form')" class="manual-form-btn">
                    Manual Form
                </button>
            @endif
        </div>
    @endif

    {{-- Input Area --}}
    <div class="ai-input-container">
        <form wire:submit.prevent="sendMessage" class="ai-input-form">
            <textarea
                wire:model="userInput"
                placeholder="Type your request... (e.g., 'I need 50kg tomatoes for Friday')"
                rows="2"
                class="ai-input"
                @if($isTyping || $isSubmitting) disabled @endif
                onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); @this.sendMessage(); }"
            ></textarea>

            <button
                type="submit"
                class="ai-send-btn"
                @if($isTyping || $isSubmitting || empty(trim($userInput))) disabled @endif
            >
                @if($isTyping || $isSubmitting)
                    <div class="btn-spinner"></div>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                    </svg>
                @endif
            </button>
        </form>

        <div class="input-hint">Press Enter to send • Shift+Enter for new line</div>

        {{-- Start Over Button --}}
        <button wire:click="startOver" class="start-over-btn" title="Start New Conversation">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 4v6h6M23 20v-6h-6"/>
                <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/>
            </svg>
            Start Over
        </button>
    </div>

</div>
