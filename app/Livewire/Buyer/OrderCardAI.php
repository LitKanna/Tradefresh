<?php

namespace App\Livewire\Buyer;

use App\Services\FreshhhyAIService;
use App\Services\RFQService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * OrderCardAI - Conversational RFQ Creation
 *
 * USER EXPERIENCE GOALS:
 * 1. Fast, responsive chat interface
 * 2. Clear feedback on every action
 * 3. Easy to cancel or go back
 * 4. Fallback to manual form always available
 * 5. Mobile-friendly
 */
class OrderCardAI extends Component
{
    // Chat state
    public array $messages = [];

    public string $userInput = '';

    public bool $isTyping = false;

    public ?string $conversationId = null;

    // RFQ state
    public bool $showRfqPreview = false;

    public array $rfqData = [];

    public bool $isSubmitting = false;

    // UI state
    public bool $showManualFormLink = false;

    public ?string $errorMessage = null;

    public bool $rateLimitHit = false;

    protected FreshhhyAIService $aiService;

    protected RFQService $rfqService;

    /**
     * Boot services
     *
     * CHALLENGE: Dependency injection in Livewire
     * SOLUTION: Use boot() method
     */
    public function boot(FreshhhyAIService $ai, RFQService $rfq)
    {
        $this->aiService = $ai;
        $this->rfqService = $rfq;
    }

    /**
     * Mount component
     *
     * CHALLENGE: What if rate limit is already hit?
     * SOLUTION: Check on mount, show message immediately
     */
    public function mount()
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            $this->errorMessage = 'Please log in to use AI assistant';

            return;
        }

        // Start new AI conversation
        $this->conversationId = $this->aiService->startConversation($buyer->id);

        if (! $this->conversationId) {
            // Rate limit hit or service unavailable
            $this->rateLimitHit = true;
            $this->showManualFormLink = true;
            $this->errorMessage = "You've reached your daily AI request limit. Please use the manual form.";

            return;
        }

        // Load initial greeting
        $this->loadInitialMessages();
    }

    /**
     * Load initial greeting message
     *
     * CHALLENGE: Don't query AI for greeting (costs money)
     * SOLUTION: Use pre-defined greeting from conversation
     */
    protected function loadInitialMessages()
    {
        try {
            $conversation = \App\Models\AiConversation::find($this->conversationId);

            if ($conversation && ! empty($conversation->messages)) {
                $this->messages = $conversation->messages;
            }
        } catch (\Exception $e) {
            Log::error('Failed to load initial messages', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send user message to AI
     *
     * CHALLENGE: User might spam send button
     * SOLUTION: Disable while processing
     */
    public function sendMessage()
    {
        // Validation
        if (empty(trim($this->userInput))) {
            return;
        }

        if ($this->isTyping || $this->isSubmitting) {
            return; // Already processing
        }

        $buyer = auth('buyer')->user();
        if (! $buyer) {
            $this->errorMessage = 'Session expired. Please refresh the page.';

            return;
        }

        // Store message locally first (optimistic UI)
        $userMessage = trim($this->userInput);
        $this->messages[] = [
            'role' => 'user',
            'content' => $userMessage,
            'timestamp' => now()->toISOString(),
        ];

        // Clear input immediately (responsive feel)
        $this->userInput = '';

        // Show typing indicator
        $this->isTyping = true;
        $this->errorMessage = null;

        try {
            // Call AI service
            $response = $this->aiService->chat(
                conversationId: $this->conversationId,
                message: $userMessage,
                buyerId: $buyer->id
            );

            // CHALLENGE: Handle different response types
            if ($response['success']) {

                // Add AI response to messages
                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => $response['message'],
                    'timestamp' => now()->toISOString(),
                ];

                // Check if AI has complete RFQ
                if ($response['has_complete_rfq'] ?? false) {
                    $this->rfqData = $response['rfq_data'];
                    $this->showRfqPreview = true;
                }

            } else {
                // Handle error response
                $this->handleAIError($response);
            }

        } catch (\Exception $e) {
            Log::error('AI chat error in Livewire', [
                'buyer_id' => $buyer->id,
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'Something went wrong. Please try again or use the manual form.';
            $this->showManualFormLink = true;
        } finally {
            $this->isTyping = false;
        }
    }

    /**
     * Handle AI error responses
     *
     * CHALLENGE: Different errors need different UX
     * SOLUTION: Switch on error type
     */
    protected function handleAIError(array $response)
    {
        $this->errorMessage = $response['message'] ?? 'Unknown error occurred';

        // Show manual form link for certain errors
        if ($response['suggest_manual_form'] ?? false) {
            $this->showManualFormLink = true;
        }

        // Special handling for rate limit
        if ($response['error_type'] === 'conversation_limit') {
            $this->rateLimitHit = true;
        }

        // Add error message to chat
        $this->messages[] = [
            'role' => 'system',
            'content' => $this->errorMessage,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Confirm and create RFQ
     *
     * CHALLENGE: User might double-click confirm
     * SOLUTION: Disable button while submitting
     */
    public function confirmRfq()
    {
        if ($this->isSubmitting) {
            return; // Already submitting
        }

        $buyer = auth('buyer')->user();
        if (! $buyer) {
            $this->errorMessage = 'Session expired. Please refresh the page.';

            return;
        }

        $this->isSubmitting = true;
        $this->errorMessage = null;

        try {
            // Use existing RFQService to create and broadcast
            $rfq = $this->rfqService->createRFQ(
                data: $this->rfqData,
                buyerId: $buyer->id
            );

            // Success! Show confirmation
            $this->messages[] = [
                'role' => 'assistant',
                'content' => "✅ **Success!** Your RFQ #{$rfq->rfq_number} has been sent to all vendors.\n\nVendors are being notified now and will start submitting quotes soon. You'll see them appear in your quote panel on the right.",
                'timestamp' => now()->toISOString(),
            ];

            // Reset state
            $this->showRfqPreview = false;
            $this->rfqData = [];

            // Dispatch event to refresh quote panel
            $this->dispatch('refreshQuotes');

            // CHALLENGE: How to notify user on other components?
            // SOLUTION: Livewire event to BuyerQuotePanel
            $this->dispatch('show-toast', [
                'type' => 'success',
                'title' => 'RFQ Created!',
                'message' => 'Your request has been sent to vendors. Watch for incoming quotes!',
                'duration' => 5000,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create RFQ from AI', [
                'buyer_id' => $buyer->id,
                'rfq_data' => $this->rfqData,
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'Failed to create RFQ. Please try again or use the manual form.';
            $this->showManualFormLink = true;

            $this->messages[] = [
                'role' => 'system',
                'content' => $this->errorMessage,
                'timestamp' => now()->toISOString(),
            ];
        } finally {
            $this->isSubmitting = false;
        }
    }

    /**
     * Cancel RFQ preview and go back to chat
     *
     * CHALLENGE: User might want to edit before re-creating
     * SOLUTION: Keep conversation context, just close preview
     */
    public function cancelPreview()
    {
        $this->showRfqPreview = false;

        // Add message explaining what happened
        $this->messages[] = [
            'role' => 'assistant',
            'content' => 'No problem! What would you like to change?',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Start over - new conversation
     *
     * CHALLENGE: User might have hit limits
     * SOLUTION: Check rate limit before starting new conversation
     */
    public function startOver()
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            $this->errorMessage = 'Please log in to continue';

            return;
        }

        // Mark current conversation as completed
        if ($this->conversationId) {
            try {
                \App\Models\AiConversation::where('id', $this->conversationId)
                    ->update(['status' => 'restarted']);
            } catch (\Exception $e) {
                Log::warning('Failed to mark conversation as restarted', [
                    'conversation_id' => $this->conversationId,
                ]);
            }
        }

        // Start fresh
        $this->conversationId = $this->aiService->startConversation($buyer->id);

        if (! $this->conversationId) {
            $this->rateLimitHit = true;
            $this->showManualFormLink = true;
            $this->errorMessage = "You've reached your daily AI request limit.";

            return;
        }

        // Reset state
        $this->messages = [];
        $this->userInput = '';
        $this->showRfqPreview = false;
        $this->rfqData = [];
        $this->errorMessage = null;
        $this->rateLimitHit = false;

        // Load greeting
        $this->loadInitialMessages();
    }

    /**
     * Open manual RFQ form
     *
     * CHALLENGE: Where is the manual form?
     * SOLUTION: Dispatch event to parent dashboard component
     */
    public function openManualForm()
    {
        $this->dispatch('open-manual-rfq-form');
    }

    /**
     * Render component
     */
    public function render()
    {
        return view('livewire.buyer.order-card-ai');
    }
}
