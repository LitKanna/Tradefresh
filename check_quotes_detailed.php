<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIAGNOSTIC REPORT ===\n\n";

// 1. Check buyer authentication
echo "1. BUYER AUTHENTICATION:\n";
$buyerId = Auth::guard('buyer')->id();
echo '   Current Buyer ID: '.($buyerId ?? 'NOT AUTHENTICATED')."\n";
if ($buyerId) {
    $buyer = Auth::guard('buyer')->user();
    echo '   Buyer Email: '.$buyer->email."\n";
    echo '   Buyer Name: '.$buyer->name."\n";
}
echo "\n";

// 2. Check recent quotes in database
echo "2. RECENT QUOTES IN DATABASE (Last 5):\n";
$quotes = \App\Models\Quote::orderBy('created_at', 'desc')->limit(5)->get();
foreach ($quotes as $quote) {
    echo "   Quote #{$quote->id}:\n";
    echo "     - Buyer ID: {$quote->buyer_id}\n";
    echo "     - Vendor ID: {$quote->vendor_id}\n";
    echo "     - RFQ ID: {$quote->rfq_id}\n";
    echo "     - Status: {$quote->status}\n";
    echo "     - Total: \${$quote->total_amount}\n";
    echo "     - Created: {$quote->created_at}\n";
    echo '     - Age: '.$quote->created_at->diffForHumans()."\n";
    echo "\n";
}

// 3. Check quotes for current buyer
if ($buyerId) {
    echo "3. QUOTES FOR CURRENT BUYER (ID: {$buyerId}):\n";
    $buyerQuotes = \App\Models\Quote::where('buyer_id', $buyerId)
        ->orderBy('created_at', 'desc')
        ->get();
    echo '   Total quotes: '.$buyerQuotes->count()."\n";
    foreach ($buyerQuotes as $quote) {
        echo "   Quote #{$quote->id} - Status: {$quote->status} - Amount: \${$quote->total_amount} - Created: {$quote->created_at->format('Y-m-d H:i:s')}\n";
    }
    echo "\n";

    // 4. Check submitted quotes only
    echo "4. SUBMITTED QUOTES FOR BUYER:\n";
    $submittedQuotes = \App\Models\Quote::where('buyer_id', $buyerId)
        ->where('status', 'submitted')
        ->orderBy('created_at', 'desc')
        ->get();
    echo '   Count: '.$submittedQuotes->count()."\n";
    foreach ($submittedQuotes as $quote) {
        echo "   Quote #{$quote->id} - Amount: \${$quote->total_amount} - Created: {$quote->created_at->format('Y-m-d H:i:s')}\n";
    }
    echo "\n";

    // 5. Check quotes within 7 days
    echo "5. SUBMITTED QUOTES WITHIN 7 DAYS:\n";
    $recentQuotes = \App\Models\Quote::where('buyer_id', $buyerId)
        ->where('status', 'submitted')
        ->where('created_at', '>', now()->subDays(7))
        ->orderBy('created_at', 'desc')
        ->get();
    echo '   Count: '.$recentQuotes->count()."\n";
    foreach ($recentQuotes as $quote) {
        $createdAt = $quote->created_at;
        $acceptanceDeadline = $createdAt->copy()->addMinutes(30);
        $isExpired = $acceptanceDeadline < now();
        $remainingMinutes = $isExpired ? 0 : now()->diffInMinutes($acceptanceDeadline);

        echo "   Quote #{$quote->id}:\n";
        echo "     - Amount: \${$quote->total_amount}\n";
        echo "     - Created: {$quote->created_at->format('Y-m-d H:i:s')}\n";
        echo "     - Acceptance Deadline: {$acceptanceDeadline->format('Y-m-d H:i:s')}\n";
        echo '     - Is Expired: '.($isExpired ? 'YES' : 'NO')."\n";
        echo "     - Remaining Time: {$remainingMinutes} minutes\n";
        echo "\n";
    }
}

// 6. Check Reverb configuration
echo "6. REVERB CONFIGURATION:\n";
echo '   BROADCAST_CONNECTION: '.env('BROADCAST_CONNECTION')."\n";
echo '   REVERB_APP_KEY: '.env('REVERB_APP_KEY')."\n";
echo '   REVERB_HOST: '.env('REVERB_HOST')."\n";
echo '   REVERB_PORT: '.env('REVERB_PORT')."\n";
echo "\n";

// 7. Check if Reverb server is running
echo "7. REVERB SERVER STATUS:\n";
$reverbHost = env('REVERB_HOST', 'localhost');
$reverbPort = env('REVERB_PORT', 9090);
$connection = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 1);
if ($connection) {
    echo "   STATUS: RUNNING ✓\n";
    fclose($connection);
} else {
    echo "   STATUS: NOT RUNNING ✗\n";
    echo "   ERROR: {$errstr} (Code: {$errno})\n";
}
echo "\n";

echo "=== END DIAGNOSTIC REPORT ===\n";
