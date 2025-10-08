<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CLEANING UP EXPIRED QUOTES ===\n\n";

// Delete quotes with validity_date in the past
$expired = App\Models\Quote::where('buyer_id', 1)
    ->where('validity_date', '<=', now())
    ->get();

echo "Found {$expired->count()} expired quotes:\n";
foreach ($expired as $q) {
    echo "  - Quote #{$q->id}: validity={$q->validity_date}, status={$q->status}\n";
}

$deleted = $expired->each->delete()->count();
echo "\nDeleted $deleted expired quotes\n";
echo 'Remaining quotes: '.App\Models\Quote::where('buyer_id', 1)->count()."\n\n";

echo "✅ Database fully cleaned. Ready for fresh quotes with 30-minute validity.\n";
