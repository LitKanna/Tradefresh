<?php

/**
 * SIMPLE FRONTEND REFRESH TEST
 * Just tests if the Livewire refresh mechanisms work
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Buyer;
use App\Models\Quote;

echo "\n=== SIMPLE FRONTEND REFRESH TEST ===\n\n";

// Get buyer
$buyer = Buyer::first();
if (! $buyer) {
    exit("❌ No buyer found\n");
}

echo "✅ Buyer: {$buyer->business_name} (ID: {$buyer->id})\n";

// Count current quotes
$quotes = Quote::where('buyer_id', $buyer->id)
    ->where('status', 'pending')
    ->where(function ($query) {
        $query->whereNull('expires_at')
            ->orWhere('expires_at', '>', now());
    })
    ->get();

echo "📊 Current active quotes: {$quotes->count()}\n\n";

foreach ($quotes as $quote) {
    echo "  - Quote #{$quote->id}: \${$quote->total_amount}\n";
}

echo "\n".str_repeat('=', 60)."\n";
echo "MANUAL TESTING INSTRUCTIONS\n";
echo str_repeat('=', 60)."\n\n";

echo "1. Open buyer dashboard: http://localhost:8000/buyer/dashboard\n";
echo "2. Open browser console (F12)\n";
echo "3. The dashboard should show {$quotes->count()} active quote(s)\n\n";

echo "4. In the console, manually trigger a refresh:\n";
echo "   > Livewire.find(document.querySelector('[wire\\\\:id]').getAttribute('wire:id')).call('refreshQuotes')\n\n";

echo "5. Watch for console output:\n";
echo "   🔥🔥🔥 REFRESH QUOTES EVENT RECEIVED 🔥🔥🔥\n";
echo "   ⚡ LIVEWIRE COMPONENT UPDATED\n";
echo "   📊 Current quote count in DOM\n\n";

echo "6. Verify quotes re-render and timers restart\n\n";

echo "✅ If you see the console messages and quotes update, the fix is working!\n\n";
