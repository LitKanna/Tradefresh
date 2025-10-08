<div class="freshhhy-wrapper">
    <!-- Simple Footer Button -->
    <button
        @click="$wire.set('isOpen', !$wire.isOpen)"
        class="footer-btn freshhhy-btn"
        :class="{ 'active': $wire.isOpen }"
    >
        <svg x-show="!$wire.isOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" stroke-width="1.5"/>
        </svg>
        <svg x-show="$wire.isOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M6 18L18 6M6 6l12 12" stroke-width="1.5"/>
        </svg>
        <span x-text="$wire.isOpen ? 'Close' : 'Freshhhy'"></span>
        @if($unreadCount > 0)
            <span class="mini-badge pulse">{{ $unreadCount }}</span>
        @endif
    </button>

    <!-- FULL OVERLAY - Covers entire quote panel -->
    @if($isOpen)
        <div class="freshhhy-overlay" wire:transition>
            <!-- Green Header -->
            <div class="fresh-header">
                <div class="fresh-avatar-bounce">🌿</div>
                <div class="fresh-titles">
                    <h2>Freshhhy</h2>
                    <p>AI Shopping Assistant</p>
                </div>
                <button wire:click="resetSession" class="fresh-restart">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2"/></svg>
                </button>
            </div>

            <!-- Messages -->
            <div class="fresh-messages" id="freshhhy-messages-container">
                @forelse($messages as $message)
                    @if($message['type'] === 'user')
                        <div class="msg-row right">
                            <div class="msg-bubble user-msg">
                                {{ $message['content'] }}
                                <span class="msg-time">{{ $message['timestamp'] }}</span>
                            </div>
                        </div>
                    @else
                        <div class="msg-row left">
                            <div class="msg-avatar">🌿</div>
                            <div class="msg-bubble ai-msg">
                                {!! nl2br(e($message['content'])) !!}

                                @if(isset($message['data']['quote_id']))
                                    <div class="quote-embed">
                                        <div class="quote-header">💰 Quote #{{ $message['data']['quote_id'] }}</div>
                                        <div class="quote-body">
                                            <div><span>Vendor:</span> <strong>{{ $message['data']['vendor_name'] ?? 'N/A' }}</strong></div>
                                            <div><span>Total:</span> <strong class="price">${{ number_format($message['data']['total_price'] ?? 0, 2) }}</strong></div>
                                        </div>
                                        <div class="quote-btns">
                                            <button wire:click="acceptQuote({{ $message['data']['quote_id'] }})" class="btn-accept">✓ Accept</button>
                                            <button wire:click="rejectQuote({{ $message['data']['quote_id'] }})" class="btn-decline">✗ Decline</button>
                                        </div>
                                    </div>
                                @endif

                                <span class="msg-time">{{ $message['timestamp'] }}</span>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="empty-chat">
                        <div class="empty-icon">💬</div>
                        <p>Start chatting!</p>
                    </div>
                @endforelse

                @if($isTyping)
                    <div class="msg-row left">
                        <div class="msg-avatar">🌿</div>
                        <div class="typing-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Input -->
            <div class="fresh-input-box">
                <form wire:submit.prevent="sendMessage">
                    <input type="text" wire:model.live="userMessage" placeholder="Type a message... (e.g., I need 45 box cauli)" autocomplete="off">
                    <button type="submit" :disabled="!$wire.userMessage.trim()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" stroke-width="2"/></svg>
                    </button>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('scrollToBottom', () => {
                setTimeout(() => {
                    const el = document.getElementById('freshhhy-messages-container');
                    if (el) el.scrollTop = el.scrollHeight;
                }, 100);
            });

            @auth('web')
                const buyerId = {{ auth('web')->id() }};
                if (window.Echo) {
                    window.Echo.private(`buyers.${buyerId}`)
                        .listen('VendorQuoteSubmitted', (e) => {
                            Livewire.dispatch('vendor-quote-received', { quoteId: e.quote_id });
                        });
                }
            @endauth
        });
    </script>
    @endpush

    @push('styles')
    <style>
        /* ========================================
           FRESHHHY AI CHAT OVERLAY
           Professional B2B Messaging Interface
           ======================================== */

        .freshhhy-wrapper {
            position: relative;
            flex: 1;
            z-index: 101;
        }

        .freshhhy-btn svg {
            width: 18px;
            height: 18px;
        }

        .freshhhy-btn.active {
            background: linear-gradient(135deg, #059669, #047857);
        }

        /* FULL OVERLAY - COVERS ENTIRE QUOTE PANEL */
        .freshhhy-overlay {
            position: fixed;
            top: 72px;
            right: 14px;
            width: 380px;
            height: calc(100vh - 80px);
            background: #E0E5EC;
            border-radius: 32px;
            display: flex;
            flex-direction: column;
            z-index: 999;
            /* Professional neumorphic shadow */
            box-shadow:
                12px 12px 24px #B8BEC7,
                -12px -12px 24px #FFFFFF;
            animation: slideIn 0.25s ease-out;
            overflow: hidden;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* ========================================
           HEADER - Professional Green Branding
           ======================================== */

        .fresh-header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative;
            overflow: hidden;
            /* Elevated header */
            box-shadow:
                0 4px 12px rgba(16, 185, 129, 0.25),
                0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Subtle shimmer effect */
        .fresh-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
            animation: shimmer 4s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .fresh-avatar-bounce {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
            animation: avatarBounce 2.5s ease-in-out infinite;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @keyframes avatarBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        .fresh-titles {
            flex: 1;
            color: white;
            position: relative;
            z-index: 1;
        }

        .fresh-titles h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .fresh-titles p {
            margin: 2px 0 0 0;
            font-size: 12px;
            opacity: 0.9;
            font-weight: 500;
        }

        .fresh-restart {
            width: 38px;
            height: 38px;
            background: rgba(255, 255, 255, 0.15);
            border: none;
            border-radius: 50%;
            padding: 0;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .fresh-restart:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: rotate(-180deg);
        }

        .fresh-restart:active {
            background: rgba(255, 255, 255, 0.3);
        }

        .fresh-restart svg {
            width: 20px;
            height: 20px;
        }

        /* ========================================
           MESSAGES AREA - Professional Chat Design
           ======================================== */

        .fresh-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #E0E5EC;
        }

        /* Professional scrollbar */
        .fresh-messages::-webkit-scrollbar {
            width: 6px;
        }

        .fresh-messages::-webkit-scrollbar-track {
            background: #E0E5EC;
        }

        .fresh-messages::-webkit-scrollbar-thumb {
            background: #B8BEC7;
            border-radius: 3px;
        }

        .fresh-messages::-webkit-scrollbar-thumb:hover {
            background: #9CA3AF;
        }

        .msg-row {
            display: flex;
            gap: 10px;
            animation: messageSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .msg-row.right {
            justify-content: flex-end;
        }

        .msg-row.left {
            justify-content: flex-start;
        }

        /* AI Avatar */
        .msg-avatar {
            width: 34px;
            height: 34px;
            background: #E0E5EC;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            /* Neumorphic raised avatar */
            box-shadow:
                3px 3px 6px #B8BEC7,
                -3px -3px 6px #FFFFFF;
        }

        /* Message Bubbles */
        .msg-bubble {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.5;
            word-wrap: break-word;
            position: relative;
        }

        /* User Messages - Green Gradient */
        .user-msg {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border-bottom-right-radius: 4px;
            /* Raised green bubble */
            box-shadow:
                4px 4px 8px rgba(16, 185, 129, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.5);
        }

        /* AI Messages - Neumorphic White */
        .ai-msg {
            background: #E0E5EC;
            color: #374151;
            border-bottom-left-radius: 4px;
            /* Raised neumorphic bubble */
            box-shadow:
                4px 4px 8px #B8BEC7,
                -4px -4px 8px #FFFFFF;
            border: 1px solid #C5C8CC;
        }

        .msg-time {
            font-size: 10px;
            opacity: 0.65;
            display: block;
            margin-top: 6px;
            font-weight: 500;
        }

        .user-msg .msg-time {
            color: rgba(255, 255, 255, 0.9);
        }

        .ai-msg .msg-time {
            color: #9CA3AF;
        }

        /* ========================================
           QUOTE EMBED CARDS - Professional Design
           ======================================== */

        .quote-embed {
            margin-top: 12px;
            border-radius: 12px;
            overflow: hidden;
            background: #E0E5EC;
            /* Neumorphic container */
            box-shadow:
                inset 2px 2px 4px #B8BEC7,
                inset -2px -2px 4px #FFFFFF;
        }

        .quote-header {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 12px 14px;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quote-body {
            background: #E0E5EC;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-size: 13px;
        }

        .quote-body > div {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .quote-body span {
            color: #6B7280;
            font-weight: 500;
        }

        .quote-body strong {
            color: #374151;
            font-weight: 700;
        }

        .quote-body .price {
            color: #10B981;
            font-size: 17px;
            font-weight: 800;
        }

        /* Quote Action Buttons */
        .quote-btns {
            display: flex;
            gap: 10px;
            padding: 12px 14px;
            background: #E0E5EC;
            border-top: 1px solid #C5C8CC;
        }

        .quote-btns button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-accept {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            /* Raised button */
            box-shadow:
                3px 3px 6px rgba(16, 185, 129, 0.3),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }

        .btn-accept:hover {
            background: linear-gradient(135deg, #059669, #047857);
            box-shadow:
                2px 2px 4px rgba(16, 185, 129, 0.4),
                -1px -1px 2px rgba(255, 255, 255, 0.9);
        }

        .btn-accept:active {
            box-shadow:
                inset 2px 2px 4px rgba(5, 150, 105, 0.5),
                inset -1px -1px 2px rgba(255, 255, 255, 0.3);
        }

        .btn-decline {
            background: #E0E5EC;
            color: #6B7280;
            /* Neumorphic button */
            box-shadow:
                3px 3px 6px #B8BEC7,
                -3px -3px 6px #FFFFFF;
        }

        .btn-decline:hover {
            box-shadow:
                2px 2px 4px #B8BEC7,
                -2px -2px 4px #FFFFFF;
        }

        .btn-decline:active {
            box-shadow:
                inset 2px 2px 4px #B8BEC7,
                inset -2px -2px 4px #FFFFFF;
        }

        /* ========================================
           TYPING INDICATOR
           ======================================== */

        .typing-dots {
            background: #E0E5EC;
            padding: 14px 18px;
            border-radius: 16px 16px 16px 4px;
            display: flex;
            gap: 5px;
            /* Neumorphic bubble */
            box-shadow:
                4px 4px 8px #B8BEC7,
                -4px -4px 8px #FFFFFF;
            border: 1px solid #C5C8CC;
        }

        .typing-dots span {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #059669);
            animation: typingDot 1.4s infinite ease-in-out;
        }

        .typing-dots span:nth-child(1) { animation-delay: 0s; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typingDot {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.6;
            }
            30% {
                transform: translateY(-6px);
                opacity: 1;
            }
        }

        /* ========================================
           INPUT AREA - Professional B2B Design
           ======================================== */

        .fresh-input-box {
            padding: 18px 20px;
            background: #E0E5EC;
            border-top: 1px solid #C5C8CC;
            /* Subtle top shadow */
            box-shadow:
                0 -2px 8px rgba(184, 190, 199, 0.15);
        }

        .fresh-input-box form {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .fresh-input-box input {
            flex: 1;
            padding: 12px 18px;
            border: none;
            border-radius: 24px;
            background: #E0E5EC;
            font-size: 14px;
            color: #374151;
            outline: none;
            /* Pressed neumorphic input */
            box-shadow:
                inset 4px 4px 8px #B8BEC7,
                inset -4px -4px 8px #FFFFFF;
            transition: all 0.2s ease;
        }

        .fresh-input-box input:focus {
            border: 1px solid #10B981;
            box-shadow:
                inset 3px 3px 6px #B8BEC7,
                inset -3px -3px 6px #FFFFFF,
                0 0 0 2px rgba(16, 185, 129, 0.1);
        }

        .fresh-input-box input::placeholder {
            color: #9CA3AF;
        }

        .fresh-input-box button {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #059669);
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
            /* Raised green button */
            box-shadow:
                4px 4px 8px rgba(16, 185, 129, 0.4),
                -2px -2px 4px rgba(255, 255, 255, 0.8);
        }

        .fresh-input-box button:hover:not(:disabled) {
            background: linear-gradient(135deg, #059669, #047857);
            box-shadow:
                2px 2px 4px rgba(16, 185, 129, 0.5),
                -1px -1px 2px rgba(255, 255, 255, 0.9);
        }

        .fresh-input-box button:active:not(:disabled) {
            box-shadow:
                inset 2px 2px 4px rgba(5, 150, 105, 0.6),
                inset -1px -1px 2px rgba(255, 255, 255, 0.3);
        }

        .fresh-input-box button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: #B8BEC7;
            box-shadow:
                2px 2px 4px #B8BEC7,
                -2px -2px 4px #FFFFFF;
        }

        .fresh-input-box button svg {
            width: 20px;
            height: 20px;
        }

        /* ========================================
           EMPTY STATE
           ======================================== */

        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #9CA3AF;
            text-align: center;
            padding: 40px 20px;
        }

        .empty-icon {
            font-size: 56px;
            margin-bottom: 16px;
            animation: floatIcon 3.5s ease-in-out infinite;
            opacity: 0.5;
        }

        @keyframes floatIcon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-chat p {
            font-size: 15px;
            font-weight: 600;
            margin: 0;
            color: #6B7280;
        }

        /* ========================================
           RESPONSIVE ADJUSTMENTS
           ======================================== */

        @media (max-width: 480px) {
            .freshhhy-overlay {
                width: 100%;
                right: 0;
                left: 0;
                border-radius: 0;
            }

            .msg-bubble {
                max-width: 85%;
            }
        }
    </style>
    @endpush
</div>
