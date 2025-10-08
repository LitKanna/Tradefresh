<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CLEANING UP OLD QUOTES WITH INCORRECT VALIDITY ===\n\n";

// Delete quotes with validity_date > 1 hour from now (these have the 7-day bug)
$deleted = App\Models\Quote::where('buyer_id', 1)
    ->where('validity_date', '>', now()->addHours(1))
    ->delete();

echo "Deleted $deleted quotes with incorrect 7-day validity\n";
echo 'Remaining quotes: '.App\Models\Quote::where('buyer_id', 1)->count()."\n\n";

echo "✅ Database cleaned. New quotes will have correct 30-minute validity.\n";
