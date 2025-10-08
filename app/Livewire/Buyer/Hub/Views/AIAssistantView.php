<?php

namespace App\Livewire\Buyer\Hub\Views;

use App\Services\FreshhhyAIService;
use App\Services\RFQService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * AI Assistant View - Default Hub View
 *
 * FEATURES:
 * - Conversational RFQ creation
 * - Inline quote notifications
 * - Quick quote actions (accept/view)
 * - Natural language interface
 *
 * UX PRINCIPLE: Everything in one conversation flow
 */
class AIAssistantView extends Component
{
    // Chat state
    public array $messages = [];

    public string $userInput = '';

    public bool $isTyping = false;

    public ?string $conversationId = null;

    // RFQ state (minimal - just for confirmation messages)
    public bool $isSubmitting = false;

    // Error state
    public ?string $errorMessage = null;

    public bool $showManualFormLink = false;

    protected FreshhhyAIService $aiService;

    protected RFQService $rfqService;

    public function boot(FreshhhyAIService $ai, RFQService $rfq)
    {
        $this->aiService = $ai;
        $this->rfqService = $rfq;
    }

    /**
     * Listeners - Receive notifications from parent hub
     */
    protected $listeners = [
        'ai-quote-notification' => 'handleQuoteNotification',
    ];

    /**
     * Mount - Start AI conversation
     */
    public function mount(): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            $this->errorMessage = 'Please log in to use AI assistant';

            return;
        }

        // Start new conversation
        $this->conversationId = $this->aiService->startConversation($buyer->id);

        if (! $this->conversationId) {
            $this->errorMessage = 'AI assistant unavailable. Please try again later.';
            $this->showManualFormLink = true;

            return;
        }

        // Load greeting from conversation
        $this->loadInitialMessages();
    }

    /**
     * Load initial greeting
     */
    protected function loadInitialMessages(): void
    {
        try {
            $conversation = \App\Models\AiConversation::find($this->conversationId);

            if ($conversation) {
                $data = $conversation->extracted_data ?? [];
                $this->messages = $data['messages'] ?? [];

                // If no messages, add greeting
                if (empty($this->messages) && $conversation->ai_response) {
                    $this->messages = [[
                        'role' => 'assistant',
                        'content' => $conversation->ai_response,
                        'timestamp' => $conversation->created_at->toISOString(),
                    ]];
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load AI greeting', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send message to AI
     */
    public function sendMessage(): void
    {
        if (empty(trim($this->userInput)) || $this->isTyping || $this->isSubmitting) {
            return;
        }

        $buyer = auth('buyer')->user();
        if (! $buyer) {
            $this->errorMessage = 'Session expired. Please refresh.';

            return;
        }

        // Add user message (optimistic UI)
        $userMessage = trim($this->userInput);
        $this->messages[] = [
            'role' => 'user',
            'content' => $userMessage,
            'timestamp' => now()->toISOString(),
        ];

        $this->userInput = '';
        $this->isTyping = true;
        $this->errorMessage = null;

        try {
            // Call AI
            $response = $this->aiService->chat(
                conversationId: $this->conversationId,
                message: $userMessage,
                buyerId: $buyer->id
            );

            if ($response['success']) {
                // Add AI response
                $this->messages[] = [
                    'role' => 'assistant',
                    'content' => $response['message'],
                    'timestamp' => now()->toISOString(),
                ];

                // If RFQ is complete, create it
                if ($response['has_complete_rfq'] ?? false) {
                    $this->createRFQFromAI($response['rfq_data']);
                }
            } else {
                $this->handleAIError($response);
            }

        } catch (\Exception $e) {
            Log::error('AI chat error in hub', [
                'buyer_id' => $buyer->id,
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'AI error. Please try again.';
            $this->messages[] = [
                'role' => 'system',
                'content' => $this->errorMessage,
                'timestamp' => now()->toISOString(),
            ];
        } finally {
            $this->isTyping = false;
        }

        // Auto-scroll to bottom
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Create RFQ from AI-extracted data
     */
    protected function createRFQFromAI(array $rfqData): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            return;
        }

        $this->isSubmitting = true;

        try {
            // Use existing RFQService
            $rfq = $this->rfqService->createRFQ(
                data: $rfqData,
                buyerId: $buyer->id
            );

            // Add success notification to chat
            $this->messages[] = [
                'role' => 'system',
                'type' => 'rfq-created',
                'content' => "✅ **RFQ Created!**\n\nRFQ #{$rfq->rfq_number}\nSent to all vendors via WebSocket\n\nVendors are reviewing your request now. Quotes will appear here when they arrive!",
                'data' => [
                    'rfq_id' => $rfq->id,
                    'rfq_number' => $rfq->rfq_number,
                ],
                'timestamp' => now()->toISOString(),
            ];

            // Dispatch to parent (updates any RFQ lists if visible)
            $this->dispatch('rfq-created', rfqId: $rfq->id);

            Log::info('RFQ created via AI', [
                'buyer_id' => $buyer->id,
                'rfq_id' => $rfq->id,
                'rfq_number' => $rfq->rfq_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create RFQ from AI', [
                'buyer_id' => $buyer->id,
                'rfq_data' => $rfqData,
                'error' => $e->getMessage(),
            ]);

            $this->messages[] = [
                'role' => 'system',
                'content' => '❌ Failed to create RFQ. Please try again or use the manual form.',
                'timestamp' => now()->toISOString(),
            ];
        } finally {
            $this->isSubmitting = false;
        }
    }

    /**
     * Handle quote notification from parent hub
     */
    public function handleQuoteNotification($quoteData): void
    {
        Log::info('=== AI: Quote notification received ===', [
            'quote_data' => $quoteData,
        ]);

        // Add inline quote card to conversation
        $this->messages[] = [
            'role' => 'system',
            'type' => 'quote-notification',
            'content' => '📬 **New Quote Received!**',
            'data' => $quoteData,
            'timestamp' => now()->toISOString(),
        ];

        // AI explains the quote
        $vendorName = $quoteData['vendor']['business_name'] ?? 'A vendor';
        $amount = number_format($quoteData['total_amount'] ?? 0, 2);

        $this->messages[] = [
            'role' => 'assistant',
            'content' => "Great news! {$vendorName} just sent a quote for \${$amount}. Would you like to view details or accept it?",
            'timestamp' => now()->toISOString(),
        ];

        // Auto-scroll
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Quick accept quote from AI chat
     */
    public function quickAcceptQuote(int $quoteId): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            return;
        }

        try {
            $quote = Quote::where('id', $quoteId)
                ->where('buyer_id', $buyer->id)
                ->firstOrFail();

            // Accept quote
            $quote->update(['status' => 'accepted', 'accepted_at' => now()]);

            // Close RFQ
            $quote->rfq->update(['status' => 'closed', 'closed_at' => now()]);

            // Reject other quotes
            Quote::where('rfq_id', $quote->rfq_id)
                ->where('id', '!=', $quote->id)
                ->update(['status' => 'rejected', 'rejected_at' => now()]);

            // Add confirmation to chat
            $this->messages[] = [
                'role' => 'system',
                'type' => 'quote-accepted',
                'content' => "✅ **Quote Accepted!**\n\nOrder confirmed with {$quote->vendor->business_name}\nDelivery: {$quote->proposed_delivery_date->format('l, d M Y')} at {$quote->proposed_delivery_time}\n\nYou'll receive order confirmation via email shortly.",
                'timestamp' => now()->toISOString(),
            ];

            // Refresh quote count
            $this->dispatch('refreshHub');

            Log::info('Quote accepted from AI chat', [
                'buyer_id' => $buyer->id,
                'quote_id' => $quoteId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to accept quote from AI', [
                'buyer_id' => $buyer->id,
                'quote_id' => $quoteId,
                'error' => $e->getMessage(),
            ]);

            $this->messages[] = [
                'role' => 'system',
                'content' => '❌ Failed to accept quote. Please try again.',
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * View quote details (switches to quote inbox)
     */
    public function viewQuoteDetails(int $quoteId): void
    {
        $this->dispatch('switch-to-quotes');
        $this->dispatch('highlight-quote', quoteId: $quoteId);
    }

    /**
     * Start new conversation
     */
    public function startOver(): void
    {
        $buyer = auth('buyer')->user();

        if (! $buyer) {
            return;
        }

        // Mark current conversation
        if ($this->conversationId) {
            try {
                \App\Models\AiConversation::where('id', $this->conversationId)
                    ->update(['status' => 'restarted']);
            } catch (\Exception $e) {
                // Continue anyway
            }
        }

        // Start fresh
        $this->conversationId = $this->aiService->startConversation($buyer->id);
        $this->messages = [];
        $this->userInput = '';
        $this->errorMessage = null;

        $this->loadInitialMessages();
    }

    /**
     * Handle AI errors
     */
    protected function handleAIError(array $response): void
    {
        $this->errorMessage = $response['message'] ?? 'AI error occurred';

        if ($response['suggest_manual_form'] ?? false) {
            $this->showManualFormLink = true;
        }

        $this->messages[] = [
            'role' => 'system',
            'content' => $this->errorMessage,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Render view
     */
    public function render()
    {
        return view('livewire.buyer.hub.views.ai-assistant');
    }
}
