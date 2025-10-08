# Freshhhy AI - Conversational RFQ Creation

## 🎯 What Is This?

**Freshhhy AI** is a conversational AI assistant that lets buyers create RFQs (Request for Quotes) using natural language instead of filling out boring forms.

**Example:**
- **Old way:** Fill out 10 form fields (product, quantity, delivery date, etc.)
- **New way:** Type "I need 50kg tomatoes for Friday morning" → Done!

---

## 📋 Setup Instructions

### **Step 1: ✅ ALREADY DONE - You Have Google Gemini!**

**GOOD NEWS:** You already have Google Gemini API configured!

**Your keys (from .env):**
```env
GEMINI_API_KEY=AIzaSyAeGLO53dDHFzRYJpla0sHRTGOhgurObuk ✅
GOOGLE_PLACES_API_KEY=AIzaSyAX3aCpXK_WlBxuoiXY6oT8f7bh8MhBx24 ✅
```

**COST: 100% FREE!**
- Model: `gemini-2.0-flash-exp`
- Free tier: 1500 requests/day
- 15 requests/minute
- **Zero cost for unlimited conversations!**

Compare to OpenAI:
- OpenAI GPT-4o-mini: $0.001-$0.003/conversation = $60-180/month for 100 buyers
- **Gemini: $0** 🎉

### **Step 2: Configuration (Already Set Up)**

Your `.env` already has:

```env
GEMINI_API_KEY=AIzaSyAeGLO53dDHFzRYJpla0sHRTGOhgurObuk
```

No changes needed!

### **Step 3: Install Dependencies**

```bash
composer install
npm install
```

### **Step 4: Run Database Migration**

The `AiConversation` model already exists, but make sure the table is created:

```bash
php artisan migrate
```

If you need to create it, the migration should have:

```php
Schema::create('ai_conversations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('buyer_id')->constrained()->onDelete('cascade');
    $table->json('messages')->nullable();
    $table->json('partial_rfq_data')->nullable();
    $table->string('status')->default('active'); // active, completed, abandoned
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

### **Step 5: Add to Buyer Dashboard**

In your buyer dashboard blade file, replace the current order card panel with:

```blade
{{-- resources/views/livewire/buyer/dashboard.blade.php --}}

<div class="order-card-panel">
    @livewire('buyer.order-card-ai')
</div>
```

### **Step 6: Test It**

1. Log in as a buyer
2. Open dashboard
3. You should see Freshhhy AI chat interface
4. Try: "I need 50kg tomatoes for Friday"

---

## 🎨 User Experience Features

### ✅ What Users Love

1. **Fast Responses** (< 1 second!)
   - Uses `gemini-2.0-flash-exp` (Google's fastest model)
   - 5-second timeout to prevent long waits
   - FREE unlimited usage!

2. **Clear Communication**
   - Asks ONE question at a time
   - Responses under 50 words
   - No jargon

3. **Always Confirms Before Submitting**
   - Shows full RFQ preview
   - Edit button available
   - Cancel anytime

4. **Mobile-Friendly**
   - Touch-optimized buttons
   - Smooth scrolling
   - Responsive design

5. **Fallback to Manual Form**
   - If AI fails, suggest manual form
   - Link always available

---

## 🛡️ Safety Features

### **1. Rate Limiting**
- **20 AI requests per buyer per day**
- Prevents cost explosion
- After limit: Manual form suggested

### **2. Conversation Turn Limit**
- **15 messages maximum** per conversation
- Prevents endless back-and-forth
- "Start Over" button available

### **3. Input Sanitization**
- Prevents prompt injection attacks
- Detects phrases like "ignore previous instructions"
- Limits input to 500 characters

### **4. Data Validation**
- AI-extracted data is validated before showing preview
- Checks for:
  - Valid delivery dates (not in past)
  - Reasonable quantities (not 10000kg by typo)
  - Product names make sense
  - Required fields present

### **5. Error Handling**
- OpenAI API down → Show friendly error + manual form link
- Timeout → Suggest retry or manual form
- Invalid data → Ask clarifying questions

---

## 📊 How It Works (Technical Flow)

```
1. Buyer types message
   ↓
2. FreshhhyAIService sanitizes input
   ↓
3. Builds system prompt with buyer context
   ↓
4. Calls OpenAI API (gpt-4o-mini)
   ↓
5. AI responds or calls create_rfq_draft function
   ↓
6. Validate extracted data
   ↓
7. Show RFQ preview to buyer
   ↓
8. Buyer confirms
   ↓
9. Call existing RFQService.createRFQ()
   ↓
10. Broadcast to vendors via WebSocket
    ↓
11. Done! ✅
```

**Key Point:** AI integrates with your **existing RFQService** - no duplication of logic!

---

## 🧪 Testing Examples

### **Simple Request**
```
User: "I need tomatoes"
AI: "How many kg of tomatoes do you need, and when?"
User: "50kg for Friday"
AI: "Got it! Roma, Cherry, or Heirloom tomatoes?"
User: "Roma"
AI: [Shows RFQ preview]
```

### **Multi-Item Request**
```
User: "Can I get 50kg tomatoes and 30kg lettuce for tomorrow morning?"
AI: [Extracts all data, shows preview immediately]
```

### **Vague Request**
```
User: "I need vegetables"
AI: "Which vegetables do you need? (e.g., tomatoes, lettuce, carrots)"
```

### **Error Handling**
```
User: "I need 10000kg tomatoes"
AI: "That's a very large order (10000kg). Is this correct? Please confirm."
```

---

## 🔧 Customization

### **Change Rate Limits**

Edit `app/Services/FreshhhyAIService.php`:

```php
protected int $dailyRequestLimit = 20; // Change to 50 for premium buyers
protected int $conversationTurnLimit = 15; // Change to 20 if needed
```

### **Change AI Model**

Edit `.env`:

```env
# Faster, cheaper
OPENAI_MODEL=gpt-4o-mini

# Smarter, slower, more expensive
OPENAI_MODEL=gpt-4o

# Very cheap, less smart
OPENAI_MODEL=gpt-3.5-turbo
```

### **Add Product Catalog Matching**

Modify `FreshhhyAIService::buildSystemPrompt()` to include real product list:

```php
$products = Product::select('name')->limit(100)->pluck('name');

// Add to system prompt:
"AVAILABLE PRODUCTS:\n" . $products->implode(', ')
```

---

## 📈 Analytics & Monitoring

### **Track Usage**

```php
// How many AI conversations today?
$todayConversations = AiConversation::whereDate('created_at', today())->count();

// Which buyers use AI most?
$topUsers = AiConversation::select('buyer_id', DB::raw('count(*) as count'))
    ->groupBy('buyer_id')
    ->orderBy('count', 'desc')
    ->limit(10)
    ->get();

// Success rate (conversations that created RFQs)?
$successRate = AiConversation::where('status', 'completed')->count()
    / AiConversation::count() * 100;
```

### **Cost Tracking**

Monitor your OpenAI usage at: https://platform.openai.com/usage

Set spending limits to prevent surprises.

---

## ⚠️ Troubleshooting

### **"Failed to connect to OpenAI"**
- Check API key in `.env`
- Verify key is active at platform.openai.com
- Check internet connection
- Verify no firewall blocking OpenAI

### **"Rate limit reached"**
- Buyer has used 20 requests today
- Wait until tomorrow or increase limit
- Use manual form

### **"AI responses are slow"**
- Normal: 1-3 seconds for gpt-4o-mini
- If > 5 seconds: Check OpenAI status page
- Consider using gpt-3.5-turbo (faster, less smart)

### **"AI doesn't understand Australian slang"**
- Add examples to system prompt
- Train with specific terms
- Use explicit product names

---

## 🚀 Next Steps (Optional Enhancements)

### **1. Voice Input**
Use Web Speech API for voice-to-text:
```javascript
const recognition = new webkitSpeechRecognition();
recognition.onresult = (event) => {
    @this.userInput = event.results[0][0].transcript;
    @this.sendMessage();
};
```

### **2. Product Images**
Let buyers upload photo: "I want this"
Use OpenAI Vision API to identify product.

### **3. Conversation Analytics**
- Average conversation length
- Most common products requested
- Time of day patterns
- Success vs abandonment rate

### **4. Personalization**
- Pre-fill with buyer's usual orders
- Learn preferences over time
- Suggest reorder last week's items

---

## 📞 Support

**Common Issues:**
- Check Laravel logs: `storage/logs/laravel.log`
- Check browser console for JavaScript errors
- Verify Livewire is working properly

**OpenAI API Issues:**
- Status: https://status.openai.com
- Docs: https://platform.openai.com/docs

---

## 🎉 Success Metrics

**After 1 week, measure:**
- % of buyers who tried AI vs manual form
- % of AI conversations that created RFQs
- Average time to create RFQ (AI vs manual)
- User satisfaction (survey)
- Cost per RFQ created

**Target Goals:**
- 50%+ of RFQs created via AI
- < 30 seconds average time
- 80%+ success rate (conversation → RFQ created)
- < $0.01 cost per RFQ

---

**You're all set! 🚀**

Test with a few trusted buyers first, gather feedback, then roll out to everyone.
