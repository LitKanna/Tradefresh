<?php

namespace App\Livewire\Chat;

use App\Models\AiConversation;
use App\Models\Quote;
use App\Services\AI\FreshhyConversationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class Freshhhy extends Component
{
    public string $userMessage = '';

    public array $messages = [];

    public string $sessionId;

    public bool $isTyping = false;

    public string $currentState = 'greeting';

    public bool $isOpen = false;

    public int $unreadCount = 0;

    public ?int $pendingQuoteId = null;

    protected FreshhyConversationService $freshhyService;

    public function boot(FreshhyConversationService $freshhyService): void
    {
        $this->freshhyService = $freshhyService;
    }

    public function mount(): void
    {
        // Generate session ID if not exists
        $this->sessionId = session('freshhhy_session_id') ?? Str::uuid()->toString();
        session(['freshhhy_session_id' => $this->sessionId]);

        // Load conversation history
        $this->loadConversationHistory();

        // If new session, send greeting
        if (empty($this->messages)) {
            $this->addAiMessage("Hi! I'm Freshhhy, your personal shopping assistant! 🌿\n\nHow can I help you today? You can tell me what products you need, and I'll help you create a quote request!");
        }
    }

    public function loadConversationHistory(): void
    {
        $buyer = Auth::guard('web')->user();

        if (! $buyer) {
            return;
        }

        $conversations = AiConversation::where('session_id', $this->sessionId)
            ->where('buyer_id', $buyer->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $this->messages = [];

        foreach ($conversations as $conversation) {
            $this->messages[] = [
                'type' => 'user',
                'content' => $conversation->user_message,
                'timestamp' => $conversation->created_at->format('H:i'),
            ];

            $this->messages[] = [
                'type' => 'ai',
                'content' => $conversation->ai_response,
                'timestamp' => $conversation->created_at->format('H:i'),
                'data' => $conversation->extracted_data,
            ];

            $this->currentState = $conversation->conversation_state;
        }
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->userMessage))) {
            return;
        }

        $buyer = Auth::guard('web')->user();

        if (! $buyer) {
            $this->addAiMessage('Please log in to continue shopping.');

            return;
        }

        // Add user message to UI
        $this->addUserMessage($this->userMessage);

        // Show typing indicator
        $this->isTyping = true;

        try {
            // Send to Freshhhy service
            $response = $this->freshhyService->sendMessage(
                $this->userMessage,
                $this->sessionId,
                $buyer->id
            );

            $this->isTyping = false;

            // Add AI response to UI
            $this->addAiMessage(
                $response['ai_response'],
                $response['extracted_data'] ?? null
            );

            // Update conversation state
            $this->currentState = $response['conversation_state'];

            // If RFQ was created, notify user
            if (isset($response['rfq_id'])) {
                $this->dispatch('rfqCreated', rfqId: $response['rfq_id']);
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => 'Your quote request has been sent to vendors!',
                ]);
            }

            // Clear input
            $this->userMessage = '';

            // Scroll to bottom
            $this->dispatch('scrollToBottom');

        } catch (\Exception $e) {
            $this->isTyping = false;
            $this->addAiMessage('Oops! Something went wrong. Please try again. Error: '.$e->getMessage());
        }
    }

    public function acceptQuote(int $quoteId): void
    {
        $buyer = Auth::guard('web')->user();

        if (! $buyer) {
            $this->addAiMessage('Please log in to accept quotes.');

            return;
        }

        $this->isTyping = true;

        try {
            $response = $this->freshhyService->acceptQuote(
                $quoteId,
                $this->sessionId,
                $buyer->id
            );

            $this->isTyping = false;

            // Add AI response about payment
            $this->addAiMessage($response['ai_response']);

            // Update state
            $this->currentState = $response['conversation_state'];

            // Dispatch payment event if needed
            if (isset($response['payment_url'])) {
                $this->dispatch('redirectToPayment', url: $response['payment_url']);
            }

            // Notify buyer dashboard
            $this->dispatch('quoteAccepted', quoteId: $quoteId);

        } catch (\Exception $e) {
            $this->isTyping = false;
            $this->addAiMessage("Sorry, I couldn't process that. Error: ".$e->getMessage());
        }
    }

    public function rejectQuote(int $quoteId): void
    {
        $this->addAiMessage("No worries! Would you like me to:\n\n1. Find more vendors for you?\n2. Modify your request?\n3. Start a new search?");

        $this->currentState = 'gathering_info';
        $this->pendingQuoteId = null;
    }

    #[On('vendor-quote-received')]
    public function onVendorQuoteReceived($quoteId): void
    {
        // When vendor submits quote, Freshhhy notifies buyer
        $quote = Quote::find($quoteId);

        if (! $quote) {
            return;
        }

        // Present quote through Freshhhy
        try {
            $quotePresentation = $this->freshhyService->presentQuote(
                $quote,
                $this->sessionId
            );

            $this->addAiMessage($quotePresentation);
            $this->pendingQuoteId = $quote->id;
            $this->currentState = 'presenting_quote';

            // Show notification if chat is closed
            if (! $this->isOpen) {
                $this->unreadCount++;
                $this->dispatch('show-toast', [
                    'type' => 'info',
                    'message' => 'Freshhhy has a new quote for you!',
                ]);
            }

            // Play notification sound
            $this->dispatch('play-notification-sound');

        } catch (\Exception $e) {
            \Log::error('Freshhhy quote presentation failed: '.$e->getMessage());
        }
    }

    public function toggleChat(): void
    {
        $this->isOpen = ! $this->isOpen;

        if ($this->isOpen) {
            $this->unreadCount = 0;
            $this->dispatch('scrollToBottom');
        }
    }

    public function resetSession(): void
    {
        // Create new session
        $this->sessionId = Str::uuid()->toString();
        session(['freshhhy_session_id' => $this->sessionId]);

        // Clear messages
        $this->messages = [];
        $this->currentState = 'greeting';
        $this->pendingQuoteId = null;

        // Send new greeting
        $this->addAiMessage('Fresh start! What would you like to order today? 🌿');
    }

    protected function addUserMessage(string $content): void
    {
        $this->messages[] = [
            'type' => 'user',
            'content' => $content,
            'timestamp' => now()->format('H:i'),
        ];
    }

    protected function addAiMessage(string $content, ?array $data = null): void
    {
        $this->messages[] = [
            'type' => 'ai',
            'content' => $content,
            'timestamp' => now()->format('H:i'),
            'data' => $data,
        ];
    }

    public function render()
    {
        return view('livewire.chat.freshhhy');
    }
}
