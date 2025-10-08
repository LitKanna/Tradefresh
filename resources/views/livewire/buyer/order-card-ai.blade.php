{{-- Order Card AI - Conversational RFQ Creation --}}
<div class="order-card-ai-container" style="height: 100%; display: flex; flex-direction: column;">

    {{-- Header --}}
    <div class="ai-chat-header" style="
        padding: 16px;
        background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        color: white;
        border-radius: 12px 12px 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    ">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="
                width: 40px;
                height: 40px;
                background: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
            ">
                🤖
            </div>
            <div>
                <div style="font-weight: 600; font-size: 16px;">Freshhhy AI</div>
                <div style="font-size: 12px; opacity: 0.9;">
                    @if($isTyping)
                        Typing...
                    @else
                        Online
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div style="display: flex; gap: 8px;">
            @if(!$rateLimitHit)
                <button
                    wire:click="startOver"
                    type="button"
                    title="Start new conversation"
                    style="
                        background: rgba(255,255,255,0.2);
                        color: white;
                        border: none;
                        border-radius: 8px;
                        padding: 8px 12px;
                        cursor: pointer;
                        font-size: 12px;
                        transition: background 0.2s;
                    "
                    onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.2)'"
                >
                    🔄 Start Over
                </button>
            @endif
        </div>
    </div>

    {{-- Error Banner --}}
    @if($errorMessage)
        <div style="
            padding: 12px;
            background: #FEF2F2;
            border-left: 4px solid #EF4444;
            color: #991B1B;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        ">
            <span>⚠️ {{ $errorMessage }}</span>
            @if($showManualFormLink)
                <button
                    wire:click="openManualForm"
                    style="
                        background: #EF4444;
                        color: white;
                        border: none;
                        padding: 6px 12px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 12px;
                    "
                >
                    Manual Form
                </button>
            @endif
        </div>
    @endif

    {{-- Chat Messages --}}
    <div
        class="chat-messages-container"
        wire:poll.3s
        style="
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #F9FAFB;
            scroll-behavior: smooth;
        "
        x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
        x-init="$nextTick(() => scrollToBottom())"
        x-effect="scrollToBottom()"
    >
        @forelse($messages as $message)
            <div style="margin-bottom: 16px; display: flex; {{ $message['role'] === 'user' ? 'justify-content: flex-end' : 'justify-content: flex-start' }};">

                {{-- User Message --}}
                @if($message['role'] === 'user')
                    <div style="
                        max-width: 75%;
                        background: #10B981;
                        color: white;
                        padding: 12px 16px;
                        border-radius: 18px 18px 4px 18px;
                        font-size: 14px;
                        line-height: 1.5;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    ">
                        {{ $message['content'] }}
                        <div style="font-size: 10px; opacity: 0.7; margin-top: 4px; text-align: right;">
                            {{ \Carbon\Carbon::parse($message['timestamp'] ?? now())->format('g:i A') }}
                        </div>
                    </div>

                {{-- AI Message --}}
                @elseif($message['role'] === 'assistant')
                    <div style="
                        max-width: 75%;
                        background: white;
                        color: #1F2937;
                        padding: 12px 16px;
                        border-radius: 18px 18px 18px 4px;
                        font-size: 14px;
                        line-height: 1.5;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        border: 1px solid #E5E7EB;
                    ">
                        {!! nl2br(e($message['content'])) !!}
                        <div style="font-size: 10px; color: #9CA3AF; margin-top: 4px;">
                            {{ \Carbon\Carbon::parse($message['timestamp'] ?? now())->format('g:i A') }}
                        </div>
                    </div>

                {{-- System Message (Errors) --}}
                @elseif($message['role'] === 'system')
                    <div style="
                        max-width: 85%;
                        background: #FEF2F2;
                        color: #991B1B;
                        padding: 10px 14px;
                        border-radius: 8px;
                        font-size: 13px;
                        border-left: 3px solid #EF4444;
                    ">
                        {{ $message['content'] }}
                    </div>
                @endif
            </div>
        @empty
            <div style="text-align: center; padding: 40px 20px; color: #9CA3AF;">
                <div style="font-size: 48px; margin-bottom: 16px;">💬</div>
                <div style="font-size: 14px;">Start a conversation to create your RFQ</div>
            </div>
        @endforelse

        {{-- Typing Indicator --}}
        @if($isTyping)
            <div style="display: flex; justify-content: flex-start; margin-bottom: 16px;">
                <div style="
                    background: white;
                    padding: 12px 16px;
                    border-radius: 18px;
                    border: 1px solid #E5E7EB;
                    display: flex;
                    gap: 6px;
                    align-items: center;
                ">
                    <div class="typing-dot" style="
                        width: 8px;
                        height: 8px;
                        background: #10B981;
                        border-radius: 50%;
                        animation: typing 1.4s infinite;
                    "></div>
                    <div class="typing-dot" style="
                        width: 8px;
                        height: 8px;
                        background: #10B981;
                        border-radius: 50%;
                        animation: typing 1.4s infinite 0.2s;
                    "></div>
                    <div class="typing-dot" style="
                        width: 8px;
                        height: 8px;
                        background: #10B981;
                        border-radius: 50%;
                        animation: typing 1.4s infinite 0.4s;
                    "></div>
                </div>
            </div>
        @endif
    </div>

    {{-- RFQ Preview Panel (shown when AI has complete data) --}}
    @if($showRfqPreview && !empty($rfqData))
        <div style="
            padding: 16px;
            background: #ECFDF5;
            border-top: 2px solid #10B981;
            border-bottom: 2px solid #10B981;
        ">
            <div style="font-weight: 600; color: #065F46; margin-bottom: 12px; font-size: 14px;">
                📋 Review Your Request
            </div>

            {{-- Items --}}
            <div style="background: white; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                <div style="font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 8px;">ITEMS</div>
                @foreach($rfqData['items'] as $item)
                    <div style="
                        display: flex;
                        justify-content: space-between;
                        padding: 6px 0;
                        border-bottom: 1px solid #F3F4F6;
                        font-size: 13px;
                    ">
                        <span>{{ $item['product_name'] }}</span>
                        <span style="font-weight: 600; color: #10B981;">{{ $item['quantity'] }}{{ $item['unit'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Delivery Info --}}
            <div style="background: white; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                <div style="font-size: 12px; font-weight: 600; color: #6B7280; margin-bottom: 8px;">DELIVERY</div>
                <div style="font-size: 13px; line-height: 1.6;">
                    <div>📅 {{ \Carbon\Carbon::parse($rfqData['delivery_date'])->format('l, d M Y') }}</div>
                    <div>🕐 {{ $rfqData['delivery_time'] ?? '6AM' }}</div>
                    <div>📍 {{ $rfqData['delivery_address'] ?? 'Sydney Markets' }}</div>
                    @if(!empty($rfqData['delivery_instructions']))
                        <div style="margin-top: 6px; padding: 6px; background: #F9FAFB; border-radius: 4px; font-size: 12px;">
                            💬 {{ $rfqData['delivery_instructions'] }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div style="display: flex; gap: 8px;">
                <button
                    wire:click="confirmRfq"
                    type="button"
                    @if($isSubmitting) disabled @endif
                    style="
                        flex: 1;
                        background: #10B981;
                        color: white;
                        border: none;
                        padding: 12px;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: {{ $isSubmitting ? 'not-allowed' : 'pointer' }};
                        font-size: 14px;
                        opacity: {{ $isSubmitting ? '0.6' : '1' }};
                    "
                >
                    @if($isSubmitting)
                        ⏳ Sending...
                    @else
                        ✅ Confirm & Send to Vendors
                    @endif
                </button>

                <button
                    wire:click="cancelPreview"
                    type="button"
                    @if($isSubmitting) disabled @endif
                    style="
                        flex: 0.3;
                        background: #E5E7EB;
                        color: #374151;
                        border: none;
                        padding: 12px;
                        border-radius: 8px;
                        font-weight: 600;
                        cursor: {{ $isSubmitting ? 'not-allowed' : 'pointer' }};
                        font-size: 14px;
                    "
                >
                    ✏️ Edit
                </button>
            </div>
        </div>
    @endif

    {{-- Input Area --}}
    @if(!$rateLimitHit && !$showRfqPreview)
        <form wire:submit.prevent="sendMessage" style="
            padding: 16px;
            background: white;
            border-top: 1px solid #E5E7EB;
            border-radius: 0 0 12px 12px;
        ">
            <div style="display: flex; gap: 8px; align-items: flex-end;">
                <textarea
                    wire:model="userInput"
                    placeholder="Type your request... (e.g., 'I need 50kg tomatoes for Friday')"
                    rows="2"
                    @if($isTyping || $isSubmitting) disabled @endif
                    style="
                        flex: 1;
                        padding: 12px;
                        border: 2px solid #E5E7EB;
                        border-radius: 12px;
                        font-size: 14px;
                        resize: none;
                        font-family: inherit;
                        transition: border-color 0.2s;
                        background: {{ ($isTyping || $isSubmitting) ? '#F9FAFB' : 'white' }};
                    "
                    onfocus="this.style.borderColor='#10B981'"
                    onblur="this.style.borderColor='#E5E7EB'"
                    onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); @this.sendMessage(); }"
                ></textarea>

                <button
                    type="submit"
                    @if($isTyping || $isSubmitting || empty(trim($userInput))) disabled @endif
                    style="
                        background: {{ ($isTyping || $isSubmitting || empty(trim($userInput))) ? '#D1D5DB' : '#10B981' }};
                        color: white;
                        border: none;
                        padding: 12px 20px;
                        border-radius: 12px;
                        cursor: {{ ($isTyping || $isSubmitting) ? 'not-allowed' : 'pointer' }};
                        font-weight: 600;
                        font-size: 14px;
                        transition: background 0.2s;
                        white-space: nowrap;
                    "
                >
                    Send
                </button>
            </div>

            <div style="margin-top: 8px; font-size: 11px; color: #9CA3AF; text-align: center;">
                Press Enter to send • Shift+Enter for new line
            </div>
        </form>
    @endif

    {{-- Rate Limit Message --}}
    @if($rateLimitHit)
        <div style="
            padding: 20px;
            background: #FEF2F2;
            border-top: 2px solid #EF4444;
            text-align: center;
        ">
            <div style="font-size: 48px; margin-bottom: 12px;">🚫</div>
            <div style="font-weight: 600; color: #991B1B; margin-bottom: 8px;">Daily Limit Reached</div>
            <div style="font-size: 13px; color: #7F1D1D; margin-bottom: 12px;">
                You've used all 20 AI requests for today. Please use the manual form or try again tomorrow.
            </div>
            <button
                wire:click="openManualForm"
                style="
                    background: #10B981;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 8px;
                    font-weight: 600;
                    cursor: pointer;
                    font-size: 14px;
                "
            >
                Open Manual Form
            </button>
        </div>
    @endif
</div>

{{-- Animations --}}
<style>
    @keyframes typing {
        0%, 60%, 100% {
            transform: translateY(0);
            opacity: 0.7;
        }
        30% {
            transform: translateY(-10px);
            opacity: 1;
        }
    }

    /* Smooth scroll */
    .chat-messages-container::-webkit-scrollbar {
        width: 6px;
    }

    .chat-messages-container::-webkit-scrollbar-track {
        background: #F3F4F6;
    }

    .chat-messages-container::-webkit-scrollbar-thumb {
        background: #D1D5DB;
        border-radius: 3px;
    }

    .chat-messages-container::-webkit-scrollbar-thumb:hover {
        background: #9CA3AF;
    }
</style>
