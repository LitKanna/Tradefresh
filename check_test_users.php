<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Buyer;
use App\Models\Vendor;

echo "\n=== EXISTING TEST ACCOUNTS ===\n\n";

echo "BUYERS:\n";
$buyers = Buyer::all();
foreach ($buyers as $buyer) {
    echo "  - {$buyer->email} (ID: {$buyer->id}) - {$buyer->business_name}\n";
}

echo "\nVENDORS:\n";
$vendors = Vendor::all();
foreach ($vendors as $vendor) {
    echo "  - {$vendor->email} (ID: {$vendor->id}) - {$vendor->business_name}\n";
}

echo "\n";
