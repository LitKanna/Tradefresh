<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Quote;

echo "=== QUOTE DIAGNOSIS ===\n\n";

$quotes = Quote::where('status', 'submitted')
    ->where('buyer_id', 1)
    ->orderBy('id')
    ->get(['id', 'validity_date', 'created_at']);

echo 'Total quotes for buyer 1 with status=submitted: '.$quotes->count()."\n";
echo 'Quotes with future validity_date: '.$quotes->filter(fn ($q) => $q->validity_date > now())->count()."\n\n";

echo "First 5 quotes:\n";
foreach ($quotes->take(5) as $quote) {
    $isFuture = $quote->validity_date > now() ? 'YES' : 'NO';
    echo "Quote #{$quote->id} - Validity: {$quote->validity_date->toDateTimeString()} - Created: {$quote->created_at->toDateTimeString()} - Is Future: {$isFuture}\n";
}

echo "\nLast 5 quotes:\n";
foreach ($quotes->reverse()->take(5) as $quote) {
    $isFuture = $quote->validity_date > now() ? 'YES' : 'NO';
    echo "Quote #{$quote->id} - Validity: {$quote->validity_date->toDateTimeString()} - Created: {$quote->created_at->toDateTimeString()} - Is Future: {$isFuture}\n";
}

echo "\n\nChecking expired quotes (past validity_date):\n";
$expiredQuotes = Quote::where('status', 'submitted')
    ->where('buyer_id', 1)
    ->where('validity_date', '<=', now())
    ->get(['id', 'validity_date']);

echo 'Expired quotes count: '.$expiredQuotes->count()."\n";
foreach ($expiredQuotes as $quote) {
    echo "Quote #{$quote->id} - Expired: {$quote->validity_date->toDateTimeString()}\n";
}
