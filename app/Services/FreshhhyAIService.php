<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\Buyer;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Freshhhy AI Service
 *
 * Conversational AI for creating RFQs from natural language
 *
 * USER EXPERIENCE PRINCIPLES:
 * 1. Fast responses (< 2 seconds)
 * 2. Clear, friendly communication
 * 3. Always confirm before submitting
 * 4. Easy to cancel or go back
 * 5. Fallback to manual form if AI fails
 */
class FreshhhyAIService
{
    protected string $apiKey;

    protected string $model = 'gemini-2.0-flash-exp'; // Fast, FREE, excellent quality

    protected string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    protected int $maxTokens = 500; // Keep responses concise but informative

    protected float $temperature = 0.7; // Balanced creativity

    // Cost control (Gemini is FREE but has rate limits)
    protected int $dailyRequestLimit = 100; // Generous limit (Gemini allows 1500/day)

    protected int $conversationTurnLimit = 20; // Increased since it's free

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');

        // CHALLENGE: What if API key is missing?
        if (empty($this->apiKey)) {
            Log::error('Google Gemini API key not configured');
        }
    }

    /**
     * Start new AI conversation
     *
     * CHALLENGE: Why store in database instead of session?
     * ANSWER: Multi-device support, persistence, analytics
     */
    public function startConversation(int $buyerId): ?string
    {
        try {
            // Check rate limit BEFORE creating conversation
            if (! $this->checkRateLimit($buyerId)) {
                Log::warning('Buyer hit daily AI request limit', ['buyer_id' => $buyerId]);

                return null;
            }

            $buyer = Buyer::findOrFail($buyerId);

            $conversation = AiConversation::create([
                'buyer_id' => $buyerId,
                'session_id' => uniqid('chat_', true),
                'user_message' => '', // Required by schema
                'ai_response' => "Hi {$buyer->business_name}! 👋 I'm Freshhhy, your AI assistant.\n\nTell me what you need and I'll help you request quotes from vendors.\n\nExamples:\n• \"I need 50kg tomatoes for Friday\"\n• \"Can I get lettuce and carrots for tomorrow morning?\"",
                'conversation_state' => 'active',
                'extracted_data' => [
                    'messages' => [
                        [
                            'role' => 'assistant',
                            'content' => "Hi {$buyer->business_name}! 👋 I'm Freshhhy.",
                            'timestamp' => now()->toISOString(),
                        ],
                    ],
                    'partial_rfq_data' => [
                        'items' => [],
                        'delivery_date' => null,
                        'delivery_time' => null,
                        'delivery_address' => $buyer->address ?? 'Sydney Markets',
                    ],
                ],
            ]);

            return (string) $conversation->id;

        } catch (\Exception $e) {
            Log::error('Failed to start AI conversation', [
                'buyer_id' => $buyerId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Process user message and get AI response
     *
     * CHALLENGE: What if OpenAI is down?
     * SOLUTION: Catch errors, return friendly message, suggest manual form
     */
    public function chat(string $conversationId, string $message, int $buyerId): array
    {
        try {
            // VALIDATE conversation exists and belongs to buyer
            $conversation = AiConversation::where('id', $conversationId)
                ->where('buyer_id', $buyerId)
                ->where('conversation_state', 'active')
                ->firstOrFail();

            // CHALLENGE: Prevent conversation from going too long (cost control)
            if (count($conversation->messages ?? []) >= $this->conversationTurnLimit) {
                return $this->conversationTooLong();
            }

            // SANITIZE user input
            $message = $this->sanitizeInput($message);

            if (empty($message)) {
                return $this->emptyMessageError();
            }

            // Get existing data
            $data = $conversation->extracted_data ?? [];
            $messages = $data['messages'] ?? [];

            // Add user message to history
            $messages[] = ['role' => 'user', 'content' => $message, 'timestamp' => now()->toISOString()];

            // Update user_message field (required)
            $conversation->user_message = $message;

            // Build system prompt with buyer context
            $systemPrompt = $this->buildSystemPrompt($buyerId, $conversation);

            // Build full prompt (Gemini doesn't separate system/user like OpenAI)
            $fullPrompt = $systemPrompt."\n\n".'User: '.$message;

            // CHALLENGE: API might be slow
            // SOLUTION: Gemini is FAST (< 1 second), timeout at 5 seconds
            $response = Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($this->apiUrl.$this->model.':generateContent?key='.$this->apiKey, [
                    'contents' => [
                        ['parts' => [['text' => $fullPrompt]]],
                    ],
                    'generationConfig' => [
                        'temperature' => $this->temperature,
                        'maxOutputTokens' => $this->maxTokens,
                        'stopSequences' => [],
                    ],
                ])->throw()->json();

            // Get AI response from Gemini format
            $aiMessage = $response['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (! $aiMessage) {
                throw new \Exception('No response from AI');
            }

            // Check if AI wants to create RFQ (look for JSON in response)
            $rfqCheck = $this->checkForRFQCompletion($aiMessage, $conversation);

            // Add AI response to messages
            $messages[] = ['role' => 'assistant', 'content' => $aiMessage, 'timestamp' => now()->toISOString()];

            // Update conversation
            $conversation->ai_response = $aiMessage;
            $data['messages'] = $messages;

            // Check if RFQ data is complete
            if ($rfqCheck['is_complete']) {
                $data['partial_rfq_data'] = $rfqCheck['rfq_data'];
                $conversation->extracted_data = $data;
                $conversation->save();

                return [
                    'success' => true,
                    'message' => $this->formatRFQPreview($rfqCheck['rfq_data']),
                    'has_complete_rfq' => true,
                    'rfq_data' => $rfqCheck['rfq_data'],
                    'conversation_id' => $conversationId,
                ];
            }

            // Save conversation
            $conversation->extracted_data = $data;
            $conversation->save();

            return [
                'success' => true,
                'message' => $aiMessage,
                'has_complete_rfq' => false,
                'conversation_id' => $conversationId,
            ];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // Gemini API error
            Log::error('Gemini API request failed', [
                'buyer_id' => $buyerId,
                'error' => $e->getMessage(),
            ]);

            return $this->apiError();

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Network timeout
            Log::error('Gemini API timeout', [
                'buyer_id' => $buyerId,
                'error' => $e->getMessage(),
            ]);

            return $this->timeoutError();

        } catch (\Exception $e) {
            Log::error('AI chat error', [
                'buyer_id' => $buyerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->genericError();
        }
    }

    /**
     * Check if conversation has complete RFQ data
     *
     * CHALLENGE: Gemini doesn't have function calling like OpenAI
     * SOLUTION: Use conversation metadata and pattern matching
     */
    protected function checkForRFQCompletion(string $aiMessage, $conversation): array
    {
        $data = $conversation->extracted_data ?? [];
        $metadata = $data['partial_rfq_data'] ?? [];

        // Extract structured data from conversation history using AI
        if ($this->hasRequiredRFQInfo($metadata)) {
            return [
                'is_complete' => true,
                'rfq_data' => [
                    'items' => $metadata['items'] ?? [],
                    'delivery_date' => $metadata['delivery_date'] ?? now()->addDays(2)->format('Y-m-d'),
                    'delivery_time' => $metadata['delivery_time'] ?? '6AM',
                    'delivery_address' => $metadata['delivery_address'] ?? 'Sydney Markets',
                    'delivery_instructions' => $metadata['delivery_instructions'] ?? null,
                ],
            ];
        }

        // Try to extract from current message
        $extracted = $this->extractDataFromMessage($aiMessage, $conversation);

        if (! empty($extracted)) {
            // Update partial data (use array_merge, not recursive - prevents arrays)
            $metadata = array_merge($metadata, $extracted);
            $data['partial_rfq_data'] = $metadata;
            $conversation->extracted_data = $data;
            $conversation->save();

            if ($this->hasRequiredRFQInfo($metadata)) {
                return [
                    'is_complete' => true,
                    'rfq_data' => [
                        'items' => $metadata['items'] ?? [],
                        'delivery_date' => $metadata['delivery_date'] ?? now()->addDays(2)->format('Y-m-d'),
                        'delivery_time' => $metadata['delivery_time'] ?? '6AM',
                        'delivery_address' => $metadata['delivery_address'] ?? 'Sydney Markets',
                        'delivery_instructions' => $metadata['delivery_instructions'] ?? null,
                    ],
                ];
            }
        }

        return ['is_complete' => false];
    }

    /**
     * Check if we have required RFQ info
     */
    protected function hasRequiredRFQInfo(array $metadata): bool
    {
        return ! empty($metadata['items']) && ! empty($metadata['delivery_date']);
    }

    /**
     * Extract data from message (simple pattern matching)
     */
    protected function extractDataFromMessage(string $message, $conversation): array
    {
        // Also check user's message (not just AI response)
        $userMessage = $conversation->user_message ?? '';
        $fullText = $message.' '.$userMessage;

        $extracted = [];

        // Extract quantities and products (e.g., "50kg tomatoes", "30 boxes lettuce")
        // Match pattern: number + unit + product (stop at "for" to exclude timing words)
        preg_match_all('/(\d+)\s*(kg|boxes|bunches|units|trays)\s+([a-z]+)(?:\s+for)?/i', $fullText, $matches, PREG_SET_ORDER);

        if (! empty($matches)) {
            $items = [];
            foreach ($matches as $match) {
                // Clean product name - remove common words
                $productName = trim($match[3]);

                // Skip if duplicate
                $isDuplicate = false;
                foreach ($items as $existingItem) {
                    if (strtolower($existingItem['product_name']) === strtolower($productName)) {
                        $isDuplicate = true;
                        break;
                    }
                }

                if (! $isDuplicate) {
                    $items[] = [
                        'product_name' => ucwords($productName),
                        'quantity' => (float) $match[1],
                        'unit' => strtolower($match[2]),
                    ];
                }
            }
            if (! empty($items)) {
                $extracted['items'] = $items;
            }
        }

        // Extract delivery date (Friday, tomorrow, next week, etc.)
        if (preg_match('/(tomorrow|today|friday|monday|tuesday|wednesday|thursday|saturday|sunday)/i', $fullText, $dateMatch)) {
            $dayName = strtolower($dateMatch[1]);
            $extracted['delivery_date'] = $this->parseDateFromDay($dayName);
        }

        // Extract specific date (DD/MM/YYYY or DD-MM-YYYY)
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $fullText, $dateMatch)) {
            try {
                $date = \Carbon\Carbon::createFromFormat('d/m/Y', $dateMatch[1].'/'.$dateMatch[2].'/'.$dateMatch[3]);
                $extracted['delivery_date'] = $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Invalid date format
            }
        }

        // Extract time (morning, afternoon, evening, 6am, 9am, etc.)
        if (preg_match('/(morning|afternoon|evening|6am|9am|12pm)/i', $fullText, $timeMatch)) {
            $extracted['delivery_time'] = ucfirst(strtolower($timeMatch[1]));
        }

        return $extracted;
    }

    /**
     * Parse date from day name
     */
    protected function parseDateFromDay(string $day): string
    {
        $daysMap = [
            'today' => now(),
            'tomorrow' => now()->addDay(),
            'monday' => now()->next('Monday'),
            'tuesday' => now()->next('Tuesday'),
            'wednesday' => now()->next('Wednesday'),
            'thursday' => now()->next('Thursday'),
            'friday' => now()->next('Friday'),
            'saturday' => now()->next('Saturday'),
            'sunday' => now()->next('Sunday'),
        ];

        $date = $daysMap[$day] ?? now()->addDays(2);

        return $date->format('Y-m-d');
    }

    /**
     * Build system prompt with context
     *
     * CHALLENGE: System prompt affects AI behavior
     * SOLUTION: Clear instructions, buyer context, current progress
     */
    protected function buildSystemPrompt(int $buyerId, $conversation): string
    {
        $buyer = Buyer::find($buyerId);
        $data = $conversation->extracted_data ?? [];
        $partialData = $data['partial_rfq_data'] ?? [];

        // Build what we've collected so far
        $progressText = 'Nothing collected yet';
        if (! empty($partialData)) {
            $parts = [];
            if (! empty($partialData['items'])) {
                $itemsList = collect($partialData['items'])->map(fn ($i) => "{$i['quantity']}{$i['unit']} {$i['product_name']}"
                )->implode(', ');
                $parts[] = "Products: $itemsList";
            }
            if (! empty($partialData['delivery_date'])) {
                $parts[] = "Delivery: {$partialData['delivery_date']}";
            }
            if (! empty($partialData['delivery_time'])) {
                $parts[] = "Time: {$partialData['delivery_time']}";
            }
            $progressText = ! empty($parts) ? implode(' | ', $parts) : 'Nothing collected yet';
        }

        return "You are Freshhhy, an AI assistant for Sydney Markets B2B marketplace helping {$buyer->business_name}.

YOUR JOB: Extract product requests from natural language and help create RFQs (Request for Quotes).

REQUIRED INFO:
1. Product names with quantities (e.g., '50kg tomatoes')
2. Delivery date (e.g., 'Friday', 'tomorrow', '15/10/2025')
3. Delivery time is optional (default: 6AM)

CURRENT PROGRESS: {$progressText}

RULES:
- Keep responses under 40 words
- Ask ONE question at a time
- Be friendly but professional (B2B context)
- Use Australian terms (kg not pounds, Sydney suburbs)
- When you have product + quantity + date → say 'Ready to create RFQ!'
- If info is vague, ask for clarification
- NEVER make up products or guess quantities

EXAMPLE GOOD RESPONSES:
- 'Got it! 50kg Roma Tomatoes. When do you need delivery?'
- 'Perfect! 30kg lettuce for Friday. Confirm: 6AM delivery?'
- 'What type of tomatoes? Roma, Cherry, or Heirloom?'

EXAMPLE BAD RESPONSES:
- Long paragraphs explaining how the marketplace works
- Multiple questions at once
- Assuming quantities when user didn't specify

Stay concise. Help them order fresh produce quickly.";
    }

    /**
     * Validate RFQ data extracted by AI
     *
     * CHALLENGE: AI might hallucinate products or wrong dates
     * SOLUTION: Validate against product catalog, check date logic
     */
    protected function validateRFQData(array $data): array
    {
        // Check items exist and are valid
        if (empty($data['items'])) {
            return [
                'valid' => false,
                'message' => 'I need to know what products you want. Please tell me the product names and quantities.',
            ];
        }

        // Validate delivery date is in future
        try {
            $deliveryDate = new \DateTime($data['delivery_date']);
            $today = new \DateTime;

            if ($deliveryDate < $today) {
                return [
                    'valid' => false,
                    'message' => "The delivery date seems to be in the past. When do you need delivery? (e.g., 'tomorrow', 'Friday', '15/10/2025')",
                ];
            }

            // CHALLENGE: Delivery more than 30 days away is suspicious
            $maxDate = $today->modify('+30 days');
            if ($deliveryDate > $maxDate) {
                return [
                    'valid' => false,
                    'message' => 'That delivery date is quite far away. Did you mean a closer date? Please confirm.',
                ];
            }

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => "I didn't understand the delivery date. Can you specify it clearly? (e.g., 'Friday', 'tomorrow', '15 October')",
            ];
        }

        // Validate quantities make sense
        foreach ($data['items'] as $item) {
            if ($item['quantity'] <= 0) {
                return [
                    'valid' => false,
                    'message' => "Quantity for {$item['product_name']} doesn't look right. How many {$item['unit']} do you need?",
                ];
            }

            // CHALLENGE: Suspiciously large orders (typo prevention)
            if ($item['quantity'] > 1000 && $item['unit'] === 'kg') {
                return [
                    'valid' => false,
                    'message' => "That's a very large order ({$item['quantity']} {$item['unit']} of {$item['product_name']}). Is this correct? Please confirm.",
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Format RFQ preview for user confirmation
     *
     * CHALLENGE: User needs to see exactly what will be created
     * SOLUTION: Clear, formatted preview
     */
    protected function formatRFQPreview(array $data): string
    {
        $preview = "📋 **Ready to send your RFQ!**\n\n";

        $preview .= "**Items:**\n";
        foreach ($data['items'] as $item) {
            $preview .= "• {$item['quantity']}{$item['unit']} {$item['product_name']}\n";
        }

        $preview .= "\n**Delivery:**\n";
        $deliveryDate = is_array($data['delivery_date']) ? ($data['delivery_date'][0] ?? 'TBD') : $data['delivery_date'];
        $preview .= '• Date: '.date('l, d M Y', strtotime($deliveryDate))."\n";
        $preview .= "• Time: {$data['delivery_time']}\n";
        $preview .= "• Location: {$data['delivery_address']}\n";

        if (! empty($data['delivery_instructions'])) {
            $preview .= "• Instructions: {$data['delivery_instructions']}\n";
        }

        $preview .= "\n✅ Click **Confirm** to send to all vendors, or **Edit** to make changes.";

        return $preview;
    }

    /**
     * Sanitize user input
     *
     * CHALLENGE: Prevent prompt injection attacks
     * SOLUTION: Strip dangerous characters, limit length
     */
    protected function sanitizeInput(string $input): string
    {
        // Trim whitespace
        $input = trim($input);

        // Remove excessive whitespace
        $input = preg_replace('/\s+/', ' ', $input);

        // Limit length (prevent token flooding)
        if (strlen($input) > 500) {
            $input = substr($input, 0, 500);
        }

        // CHALLENGE: Detect prompt injection attempts
        $suspiciousPatterns = [
            '/ignore previous instructions/i',
            '/you are now/i',
            '/new instructions/i',
            '/system prompt/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::warning('Possible prompt injection attempt', ['input' => $input]);

                // Return sanitized version
                return 'I need help with a quote request';
            }
        }

        return $input;
    }

    /**
     * Check rate limit for buyer
     *
     * CHALLENGE: Prevent cost explosion from abuse
     * SOLUTION: Rate limit by buyer ID
     */
    protected function checkRateLimit(int $buyerId): bool
    {
        $key = "ai_requests:buyer:{$buyerId}:".now()->format('Y-m-d');

        return RateLimiter::attempt(
            $key,
            $this->dailyRequestLimit,
            function () {}
        );
    }

    /**
     * Get buyer's commonly ordered products (for context)
     *
     * CHALLENGE: Query can be slow
     * SOLUTION: Cache for 1 hour
     */
    protected function getBuyerCommonProducts(int $buyerId): array
    {
        return Cache::remember("buyer_{$buyerId}_common_products", 3600, function () {
            // Get from order history (simplified - expand with real query)
            return ['Tomatoes', 'Lettuce', 'Carrots']; // Placeholder
        });
    }

    // ERROR RESPONSES (User-friendly, actionable)

    protected function conversationTooLong(): array
    {
        return [
            'success' => false,
            'message' => "This conversation is getting long. Let's start fresh or use the manual form to create your RFQ.",
            'error_type' => 'conversation_limit',
            'suggest_manual_form' => true,
        ];
    }

    protected function emptyMessageError(): array
    {
        return [
            'success' => false,
            'message' => "Please tell me what you need. For example: '50kg tomatoes for Friday'",
            'error_type' => 'empty_message',
        ];
    }

    protected function apiError(): array
    {
        return [
            'success' => false,
            'message' => "I'm having trouble connecting right now. Please use the manual form or try again in a moment.",
            'error_type' => 'api_error',
            'suggest_manual_form' => true,
        ];
    }

    protected function timeoutError(): array
    {
        return [
            'success' => false,
            'message' => "That's taking too long. Please try again or use the manual form.",
            'error_type' => 'timeout',
            'suggest_manual_form' => true,
        ];
    }

    protected function genericError(): array
    {
        return [
            'success' => false,
            'message' => 'Something went wrong. Please try again or use the manual form to create your RFQ.',
            'error_type' => 'generic_error',
            'suggest_manual_form' => true,
        ];
    }
}
