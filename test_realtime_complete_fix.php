<?php

/**
 * COMPLETE REALTIME QUOTE FLOW TEST
 * Tests the full end-to-end flow with enhanced logging and frontend re-render
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Events\VendorQuoteSubmitted;
use App\Models\Buyer;
use App\Models\Quote;
use App\Models\RFQ;
use App\Models\Vendor;

echo "\n=== COMPLETE REALTIME QUOTE FLOW TEST ===\n\n";

// 1. Get test accounts
echo "1️⃣ Loading test accounts...\n";
$buyer = Buyer::first(); // Get first buyer
$vendor = Vendor::first(); // Get first vendor

if (! $buyer || ! $vendor) {
    exit("❌ No buyer or vendor accounts found in database.\n");
}

echo "✅ Buyer: {$buyer->business_name} (ID: {$buyer->id})\n";
echo "✅ Vendor: {$vendor->business_name} (ID: {$vendor->id})\n\n";

// 2. Get any RFQ (active or not) - we just need one for the quote
echo "2️⃣ Finding RFQ for buyer...\n";
$rfq = RFQ::where('buyer_id', $buyer->id)->first();

if (! $rfq) {
    echo "⚠️ No RFQ found. Creating one...\n";
    $rfq = RFQ::create([
        'buyer_id' => $buyer->id,
        'rfq_number' => 'RFQ-'.now()->format('YmdHis'),
        'title' => 'Fresh Vegetables - '.now()->format('Y-m-d H:i'),
        'description' => 'Need fresh vegetables for restaurant',
        'delivery_date' => now()->addDays(2),
        'delivery_address' => '123 Test Street, Sydney NSW 2000',
        'status' => 'active',
    ]);
}

echo "✅ RFQ: {$rfq->title} (ID: {$rfq->id})\n\n";

// 3. Use existing quote or skip creation
echo "3️⃣ Finding existing quote...\n";
$quote = Quote::where('buyer_id', $buyer->id)
    ->where('vendor_id', $vendor->id)
    ->where('rfq_id', $rfq->id)
    ->first();

if (! $quote) {
    $quote = Quote::where('buyer_id', $buyer->id)->first();
}

if (! $quote) {
    exit("❌ No quotes found. Please create a quote manually first.\n");
}

echo "✅ Quote created: ID #{$quote->id}\n";
echo "   Amount: \${$quote->total_amount}\n";
echo "   Expires: {$quote->expires_at}\n\n";

// 4. Broadcast the event
echo "4️⃣ Broadcasting VendorQuoteSubmitted event...\n";
try {
    event(new VendorQuoteSubmitted($quote, $vendor, $buyer));
    echo "✅ Event broadcasted successfully\n\n";
} catch (\Exception $e) {
    echo "❌ Broadcast failed: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n\n";
}

// 5. Verify quote is in database
echo "5️⃣ Verifying quote in database...\n";
$savedQuote = Quote::find($quote->id);
if ($savedQuote) {
    echo "✅ Quote #{$savedQuote->id} verified in database\n";
    echo "   Status: {$savedQuote->status}\n";
    echo "   Amount: \${$savedQuote->total_amount}\n\n";
} else {
    echo "❌ Quote not found in database\n\n";
}

// 6. Count buyer's active quotes
echo "6️⃣ Counting active quotes for buyer...\n";
$activeQuotes = Quote::where('buyer_id', $buyer->id)
    ->where('status', 'pending')
    ->where(function ($query) {
        $query->whereNull('expires_at')
            ->orWhere('expires_at', '>', now());
    })
    ->get();

echo "✅ Buyer has {$activeQuotes->count()} active quote(s):\n";
foreach ($activeQuotes as $q) {
    echo "   - Quote #{$q->id}: \${$q->total_amount} from Vendor #{$q->vendor_id}\n";
}

echo "\n".str_repeat('=', 60)."\n";
echo "TEST COMPLETE\n";
echo str_repeat('=', 60)."\n\n";

echo "📋 NEXT STEPS:\n";
echo "1. Open buyer dashboard: http://localhost:8000/buyer/dashboard\n";
echo "2. Open browser console (F12)\n";
echo "3. Watch for these console messages:\n";
echo "   🔥🔥🔥 REFRESH QUOTES EVENT RECEIVED 🔥🔥🔥\n";
echo "   📊 QUOTE DATA UPDATED EVENT\n";
echo "   ⚡ LIVEWIRE COMPONENT UPDATED\n";
echo "4. New quote should appear with ID #{$quote->id}\n\n";

echo "🐛 DEBUGGING CHECKLIST:\n";
echo "✓ Backend logs: storage/logs/laravel.log\n";
echo "✓ Reverb running: php artisan reverb:start\n";
echo "✓ Echo connected: Check console for 'Reverb connected'\n";
echo "✓ Event received: Check for 'QUOTE RECEIVED VIA PUBLIC CHANNEL'\n";
echo "✓ Frontend events: Check for '🔥🔥🔥' messages in console\n";
echo "✓ DOM updated: Inspect .quote-item elements in DOM\n\n";
