<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUOTE TIMEZONE DIAGNOSTIC ===\n\n";

echo "Server Configuration:\n";
echo '- Timezone: '.config('app.timezone')."\n";
echo '- Current server time: '.now()->toDateTimeString()."\n";
echo '- Current UTC time: '.now('UTC')->toDateTimeString()."\n";
echo '- Offset: '.now()->format('P')."\n\n";

echo "Checking quotes for buyer_id=1:\n\n";

$quotes = App\Models\Quote::where('buyer_id', 1)
    ->where('validity_date', '>', now())
    ->take(5)
    ->get(['id', 'created_at', 'validity_date']);

echo 'Found '.$quotes->count()." active quotes\n\n";

foreach ($quotes as $quote) {
    echo "Quote #{$quote->id}:\n";
    echo "  Created: {$quote->created_at->toDateTimeString()} ({$quote->created_at->timezone})\n";
    echo "  Validity: {$quote->validity_date->toDateTimeString()} ({$quote->validity_date->timezone})\n";
    echo '  Timestamp (ms): '.($quote->validity_date->timestamp * 1000)."\n";
    echo '  Remaining: '.now()->diffInMinutes($quote->validity_date)." minutes\n";
    echo "\n";
}

echo "\nAll quotes for buyer_id=1:\n";
$allQuotes = App\Models\Quote::where('buyer_id', 1)->count();
$activeQuotes = App\Models\Quote::where('buyer_id', 1)->where('validity_date', '>', now())->count();
$expiredQuotes = App\Models\Quote::where('buyer_id', 1)->where('validity_date', '<=', now())->count();

echo "- Total: $allQuotes\n";
echo "- Active (validity_date > now): $activeQuotes\n";
echo "- Expired (validity_date <= now): $expiredQuotes\n";
