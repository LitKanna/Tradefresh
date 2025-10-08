<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\Buyer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\RFQ;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreshhyConversationService
{
    /**
     * Conversation states
     */
    const STATE_GATHERING_INFO = 'gathering_info';

    const STATE_AWAITING_QUOTES = 'awaiting_quotes';

    const STATE_PRESENTING_QUOTE = 'presenting_quote';

    const STATE_PAYMENT_GUIDANCE = 'payment_guidance';

    const STATE_COMPLETED = 'completed';

    /**
     * Gemini API configuration
     */
    private string $apiKey;

    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

    /**
     * System personality and context
     */
    private string $systemPrompt = 'You are Freshhhy, a friendly AI assistant for Sydney Markets B2B marketplace.
        You help buyers create RFQs (Request for Quotes) for fresh produce.
        Your personality: friendly, uses fresh produce emojis (🥦🍎🥕🍓), knowledgeable about produce quality and seasonality.

        IMPORTANT RULES:
        1. Respond in the same language as the user
        2. Extract product information in English for database storage
        3. Be conversational and helpful
        4. Ask clarifying questions about delivery date/time, pickup vs delivery, specific varieties
        5. Provide market insights when relevant
        6. Use emojis appropriately but professionally

        When extracting information, identify:
        - Products (name, quantity, unit)
        - Delivery date and time
        - Delivery method (pickup/delivery)
        - Special requirements
        - Budget if mentioned';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
    }

    /**
     * Send a message and get AI response
     */
    public function sendMessage(string $userMessage, string $sessionId, int $buyerId): array
    {
        try {
            // Get or create conversation
            $conversation = $this->getOrCreateConversation($sessionId, $buyerId);

            // Store user message
            $this->storeMessage($conversation->id, 'user', $userMessage);

            // Get conversation context
            $context = $this->buildConversationContext($conversation);

            // Generate AI response based on state
            $response = $this->generateResponse($userMessage, $context, $conversation);

            // Store AI response
            $this->storeMessage($conversation->id, 'assistant', $response['message']);

            // Extract and process RFQ data if in gathering state
            if ($conversation->state === self::STATE_GATHERING_INFO) {
                $extractedData = $this->extractRfqData($userMessage, $conversation);
                if ($this->isRfqComplete($extractedData, $conversation)) {
                    $rfq = $this->createRfq($extractedData, $conversation, $buyerId);
                    $conversation->update([
                        'state' => self::STATE_AWAITING_QUOTES,
                        'metadata' => array_merge($conversation->metadata ?? [], [
                            'rfq_id' => $rfq->id,
                            'rfq_created_at' => now()->toISOString(),
                        ]),
                    ]);
                    $response['rfq_created'] = true;
                    $response['rfq_id'] = $rfq->id;
                }
            }

            return [
                'success' => true,
                'message' => $response['message'],
                'state' => $conversation->state,
                'session_id' => $sessionId,
                'extracted_data' => $response['extracted_data'] ?? null,
                'rfq_created' => $response['rfq_created'] ?? false,
                'rfq_id' => $response['rfq_id'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('FreshhyConversationService error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => "I'm having trouble processing that right now 😔. Please try again.",
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Present a quote to the user with analysis
     */
    public function presentQuote(Quote $quote, string $sessionId): string
    {
        try {
            $conversation = AiConversation::where('session_id', $sessionId)->firstOrFail();

            // Update state
            $conversation->update([
                'state' => self::STATE_PRESENTING_QUOTE,
                'metadata' => array_merge($conversation->metadata ?? [], [
                    'current_quote_id' => $quote->id,
                    'quote_presented_at' => now()->toISOString(),
                ]),
            ]);

            // Get vendor details
            $vendor = Vendor::find($quote->vendor_id);

            // Analyze quote vs market rates
            $marketAnalysis = $this->analyzeQuoteVsMarket($quote);

            // Build presentation message
            $message = $this->buildQuotePresentation($quote, $vendor, $marketAnalysis);

            // Store the presentation
            $this->storeMessage($conversation->id, 'assistant', $message);

            return $message;

        } catch (\Exception $e) {
            Log::error('Quote presentation error: '.$e->getMessage());

            return "I couldn't present that quote properly. Please refresh and try again.";
        }
    }

    /**
     * Accept a quote and guide through payment
     */
    public function acceptQuote(int $quoteId, string $sessionId, int $buyerId): array
    {
        try {
            $conversation = AiConversation::where('session_id', $sessionId)->firstOrFail();
            $quote = Quote::findOrFail($quoteId);

            // Verify quote belongs to buyer's RFQ
            if ($quote->rfq->buyer_id !== $buyerId) {
                throw new \Exception('Unauthorized quote access');
            }

            // Update quote status
            $quote->update(['status' => 'accepted']);

            // Update conversation state
            $conversation->update([
                'state' => self::STATE_PAYMENT_GUIDANCE,
                'metadata' => array_merge($conversation->metadata ?? [], [
                    'accepted_quote_id' => $quoteId,
                    'quote_accepted_at' => now()->toISOString(),
                ]),
            ]);

            // Generate payment guidance message
            $message = $this->generatePaymentGuidance($quote);

            // Store message
            $this->storeMessage($conversation->id, 'assistant', $message);

            return [
                'success' => true,
                'message' => $message,
                'quote_id' => $quoteId,
                'total_amount' => $quote->total_price,
                'payment_url' => route('buyer.checkout', ['quote' => $quoteId]),
            ];

        } catch (\Exception $e) {
            Log::error('Quote acceptance error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'There was an issue accepting this quote. Please try again.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get conversation summary
     */
    public function getConversationSummary(string $sessionId): array
    {
        try {
            $conversation = AiConversation::where('session_id', $sessionId)->first();

            if (! $conversation) {
                return [
                    'exists' => false,
                    'message' => 'No conversation found',
                ];
            }

            $messages = DB::table('ai_conversation_messages')
                ->where('conversation_id', $conversation->id)
                ->orderBy('created_at', 'asc')
                ->get();

            $metadata = $conversation->metadata ?? [];

            return [
                'exists' => true,
                'session_id' => $sessionId,
                'state' => $conversation->state,
                'message_count' => $messages->count(),
                'started_at' => $conversation->created_at,
                'last_message_at' => $messages->last()->created_at ?? null,
                'rfq_id' => $metadata['rfq_id'] ?? null,
                'accepted_quote_id' => $metadata['accepted_quote_id'] ?? null,
                'extracted_products' => $metadata['products'] ?? [],
                'delivery_date' => $metadata['delivery_date'] ?? null,
                'messages' => $messages->map(function ($msg) {
                    return [
                        'role' => $msg->role,
                        'content' => $msg->content,
                        'timestamp' => $msg->created_at,
                    ];
                })->toArray(),
            ];

        } catch (\Exception $e) {
            Log::error('Conversation summary error: '.$e->getMessage());

            return [
                'exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Private: Get or create conversation
     */
    private function getOrCreateConversation(string $sessionId, int $buyerId): AiConversation
    {
        return AiConversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'buyer_id' => $buyerId,
                'state' => self::STATE_GATHERING_INFO,
                'metadata' => [
                    'started_at' => now()->toISOString(),
                    'language' => 'en',
                    'products' => [],
                    'requirements' => [],
                ],
            ]
        );
    }

    /**
     * Private: Store a message in the conversation
     */
    private function storeMessage(int $conversationId, string $role, string $content): void
    {
        DB::table('ai_conversation_messages')->insert([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Private: Build conversation context for AI
     */
    private function buildConversationContext(AiConversation $conversation): array
    {
        // Get recent messages
        $messages = DB::table('ai_conversation_messages')
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse();

        $metadata = $conversation->metadata ?? [];

        return [
            'state' => $conversation->state,
            'messages' => $messages->map(fn ($m) => ['role' => $m->role, 'content' => $m->content]),
            'extracted_data' => [
                'products' => $metadata['products'] ?? [],
                'delivery_date' => $metadata['delivery_date'] ?? null,
                'delivery_method' => $metadata['delivery_method'] ?? null,
                'special_requirements' => $metadata['special_requirements'] ?? null,
            ],
            'rfq_id' => $metadata['rfq_id'] ?? null,
        ];
    }

    /**
     * Private: Generate AI response using Gemini
     */
    private function generateResponse(string $userMessage, array $context, AiConversation $conversation): array
    {
        $statePrompts = [
            self::STATE_GATHERING_INFO => 'Help the user specify their produce needs. Ask about quantities, delivery date, and any special requirements.',
            self::STATE_AWAITING_QUOTES => 'The RFQ has been created. Let the user know vendors are reviewing it and quotes will arrive soon.',
            self::STATE_PRESENTING_QUOTE => 'Present and analyze the quote for the user. Highlight key details and value.',
            self::STATE_PAYMENT_GUIDANCE => 'Guide the user through the payment process. Be encouraging and helpful.',
            self::STATE_COMPLETED => 'The order is complete. Thank the user and offer any final assistance.',
        ];

        $prompt = $this->systemPrompt."\n\nCurrent state: ".$conversation->state."\n";
        $prompt .= $statePrompts[$conversation->state] ?? '';
        $prompt .= "\n\nConversation context: ".json_encode($context);
        $prompt .= "\n\nUser message: ".$userMessage;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'?key='.$this->apiKey, [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 500,
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiMessage = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'I understand. How can I help you further?';

                // Extract structured data if in gathering state
                $extractedData = null;
                if ($conversation->state === self::STATE_GATHERING_INFO) {
                    $extractedData = $this->extractRfqData($userMessage, $conversation);
                }

                return [
                    'message' => $aiMessage,
                    'extracted_data' => $extractedData,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Gemini API error: '.$e->getMessage());
        }

        // Fallback response
        return [
            'message' => $this->getFallbackResponse($conversation->state),
        ];
    }

    /**
     * Private: Extract RFQ data from user message
     */
    private function extractRfqData(string $userMessage, AiConversation $conversation): array
    {
        $metadata = $conversation->metadata ?? [];
        $existingProducts = $metadata['products'] ?? [];

        // Use Gemini to extract structured data
        $extractionPrompt = "Extract product information from this message. Return JSON only.
            Message: '$userMessage'

            Extract:
            - products: array of {name, quantity, unit}
            - delivery_date: YYYY-MM-DD format if mentioned
            - delivery_method: 'pickup' or 'delivery' if mentioned
            - special_requirements: any special requests

            Return valid JSON only, no explanation.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl.'?key='.$this->apiKey, [
                'contents' => [
                    ['parts' => [['text' => $extractionPrompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 200,
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $extractedText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

                // Clean and parse JSON
                $extractedText = preg_replace('/```json\s*|\s*```/', '', $extractedText);
                $extracted = json_decode($extractedText, true) ?? [];

                // Merge with existing data
                if (! empty($extracted['products'])) {
                    $existingProducts = array_merge($existingProducts, $extracted['products']);
                }

                // Update conversation metadata
                $conversation->update([
                    'metadata' => array_merge($metadata, [
                        'products' => $existingProducts,
                        'delivery_date' => $extracted['delivery_date'] ?? $metadata['delivery_date'] ?? null,
                        'delivery_method' => $extracted['delivery_method'] ?? $metadata['delivery_method'] ?? null,
                        'special_requirements' => $extracted['special_requirements'] ?? $metadata['special_requirements'] ?? null,
                    ]),
                ]);

                return $extracted;
            }

        } catch (\Exception $e) {
            Log::error('Data extraction error: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Private: Check if RFQ data is complete
     */
    private function isRfqComplete(array $extractedData, AiConversation $conversation): bool
    {
        $metadata = $conversation->metadata ?? [];

        // Check for required fields
        $hasProducts = ! empty($metadata['products']) && count($metadata['products']) > 0;
        $hasDeliveryDate = ! empty($metadata['delivery_date']);
        $hasDeliveryMethod = ! empty($metadata['delivery_method']);

        return $hasProducts && $hasDeliveryDate && $hasDeliveryMethod;
    }

    /**
     * Private: Create RFQ from extracted data
     */
    private function createRfq(array $extractedData, AiConversation $conversation, int $buyerId): RFQ
    {
        $metadata = $conversation->metadata ?? [];

        // Create RFQ
        $rfq = RFQ::create([
            'buyer_id' => $buyerId,
            'title' => $this->generateRfqTitle($metadata['products']),
            'description' => $this->generateRfqDescription($metadata),
            'delivery_date' => Carbon::parse($metadata['delivery_date']),
            'delivery_method' => $metadata['delivery_method'] ?? 'delivery',
            'status' => 'active',
            'special_requirements' => $metadata['special_requirements'] ?? null,
            'created_via' => 'ai_conversation',
            'ai_session_id' => $conversation->session_id,
        ]);

        // Add RFQ items
        foreach ($metadata['products'] as $product) {
            // Find or create product
            $productModel = Product::firstOrCreate(
                ['name' => $product['name']],
                [
                    'category' => $this->categorizeProduct($product['name']),
                    'unit' => $product['unit'] ?? 'kg',
                    'price' => 0, // Will be set by vendors
                ]
            );

            DB::table('rfq_items')->insert([
                'rfq_id' => $rfq->id,
                'product_id' => $productModel->id,
                'quantity' => $product['quantity'] ?? 1,
                'unit' => $product['unit'] ?? 'kg',
                'notes' => $product['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $rfq;
    }

    /**
     * Private: Analyze quote against market rates
     */
    private function analyzeQuoteVsMarket(Quote $quote): array
    {
        // Get average prices for similar quotes
        $avgPrice = Quote::where('status', 'accepted')
            ->whereHas('rfq', function ($q) {
                $q->where('delivery_date', '>=', now()->subDays(30));
            })
            ->avg('total_price') ?? $quote->total_price;

        $comparison = 'competitive';
        $percentDiff = 0;

        if ($avgPrice > 0) {
            $percentDiff = (($quote->total_price - $avgPrice) / $avgPrice) * 100;

            if ($percentDiff < -10) {
                $comparison = 'excellent value';
            } elseif ($percentDiff > 10) {
                $comparison = 'above average';
            }
        }

        return [
            'comparison' => $comparison,
            'percent_difference' => round($percentDiff, 1),
            'average_price' => $avgPrice,
            'quote_price' => $quote->total_price,
        ];
    }

    /**
     * Private: Build quote presentation message
     */
    private function buildQuotePresentation(Quote $quote, ?Vendor $vendor, array $marketAnalysis): string
    {
        $vendorName = $vendor ? $vendor->business_name : 'Unknown Vendor';
        $comparison = $marketAnalysis['comparison'];
        $percentDiff = abs($marketAnalysis['percent_difference']);

        $message = "🎉 Great news! You've received a quote from **$vendorName**!\n\n";
        $message .= "📋 **Quote Details:**\n";
        $message .= '• Total Price: $'.number_format($quote->total_price, 2)."\n";
        $message .= '• Delivery: '.Carbon::parse($quote->delivery_date)->format('l, F j, Y')."\n";
        $message .= '• Valid Until: '.Carbon::parse($quote->valid_until)->format('F j, Y')."\n\n";

        $message .= "📊 **Market Analysis:**\n";
        if ($comparison === 'excellent value') {
            $message .= "• 🌟 This quote is {$percentDiff}% below market average - excellent value!\n";
        } elseif ($comparison === 'above average') {
            $message .= "• 📈 This quote is {$percentDiff}% above market average\n";
        } else {
            $message .= "• ✅ This quote is competitively priced\n";
        }

        if ($vendor && $vendor->rating) {
            $message .= '• ⭐ Vendor Rating: '.number_format($vendor->rating, 1)."/5\n";
        }

        $message .= "\n💡 **What would you like to do?**\n";
        $message .= "• Accept this quote and proceed to payment\n";
        $message .= "• Wait for more quotes from other vendors\n";
        $message .= "• Ask me for more details about this vendor\n";

        return $message;
    }

    /**
     * Private: Generate payment guidance message
     */
    private function generatePaymentGuidance(Quote $quote): string
    {
        $message = "🎊 Excellent choice! Your quote has been accepted!\n\n";
        $message .= "💳 **Payment Process:**\n";
        $message .= "1. You'll be redirected to our secure checkout\n";
        $message .= "2. Review your order details\n";
        $message .= "3. Enter payment information (we accept all major cards)\n";
        $message .= "4. Confirm your delivery address\n";
        $message .= "5. Complete the payment\n\n";

        $message .= "📦 **What happens next:**\n";
        $message .= "• You'll receive an order confirmation via email\n";
        $message .= "• The vendor will prepare your order\n";
        $message .= "• You'll get updates on delivery status\n";
        $message .= '• Fresh produce will arrive on '.Carbon::parse($quote->delivery_date)->format('F j, Y')."\n\n";

        $message .= "🔒 Your payment is secure and protected. Need help? I'm here to assist! 😊\n\n";
        $message .= 'Ready to checkout? Click the payment button to proceed.';

        return $message;
    }

    /**
     * Private: Get fallback response based on state
     */
    private function getFallbackResponse(string $state): string
    {
        $responses = [
            self::STATE_GATHERING_INFO => "I'm here to help you find the freshest produce! 🥦🍎 What would you like to order today?",
            self::STATE_AWAITING_QUOTES => 'Your RFQ is with our vendors! Quotes should start arriving shortly. 📨',
            self::STATE_PRESENTING_QUOTE => "Here's a quote for your consideration. Would you like to accept it or wait for more options?",
            self::STATE_PAYMENT_GUIDANCE => "Let's complete your order! The checkout process is quick and secure. 💳",
            self::STATE_COMPLETED => 'Thank you for your order! Is there anything else I can help you with today? 😊',
        ];

        return $responses[$state] ?? 'How can I help you with fresh produce today? 🌽';
    }

    /**
     * Private: Generate RFQ title from products
     */
    private function generateRfqTitle(array $products): string
    {
        if (empty($products)) {
            return 'Fresh Produce Order';
        }

        $names = array_slice(array_column($products, 'name'), 0, 3);
        $title = implode(', ', $names);

        if (count($products) > 3) {
            $title .= ' and '.(count($products) - 3).' more';
        }

        return $title;
    }

    /**
     * Private: Generate RFQ description from metadata
     */
    private function generateRfqDescription(array $metadata): string
    {
        $description = "Order requested via AI conversation.\n\n";
        $description .= "**Products:**\n";

        foreach ($metadata['products'] as $product) {
            $description .= "• {$product['name']}: {$product['quantity']} {$product['unit']}\n";
        }

        if (! empty($metadata['special_requirements'])) {
            $description .= "\n**Special Requirements:**\n";
            $description .= $metadata['special_requirements'];
        }

        return $description;
    }

    /**
     * Private: Categorize product name
     */
    private function categorizeProduct(string $productName): string
    {
        $productLower = strtolower($productName);

        $categories = [
            'vegetables' => ['tomato', 'potato', 'carrot', 'lettuce', 'cucumber', 'onion', 'pepper', 'broccoli'],
            'fruits' => ['apple', 'banana', 'orange', 'strawberry', 'grape', 'mango', 'pear', 'peach'],
            'dairy' => ['milk', 'cheese', 'yogurt', 'butter', 'cream'],
            'herbs' => ['basil', 'oregano', 'thyme', 'rosemary', 'parsley', 'cilantro'],
            'flowers' => ['rose', 'tulip', 'lily', 'sunflower', 'orchid'],
        ];

        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($productLower, $keyword) !== false) {
                    return $category;
                }
            }
        }

        return 'produce';
    }
}
