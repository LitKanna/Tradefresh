<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find hola and hey messages
$messages = App\Models\Message::where('message', 'LIKE', '%hola%')
    ->orWhere('message', 'LIKE', '%hey%')
    ->get();

echo "Vendor messages (hola/hey):\n";
foreach ($messages as $msg) {
    echo "---\n";
    echo "ID: {$msg->id}\n";
    echo "Message: {$msg->message}\n";
    echo "From: {$msg->sender_type} ID:{$msg->sender_id}\n";
    echo "To: {$msg->recipient_type} ID:{$msg->recipient_id}\n";
    echo 'Quote ID: '.($msg->quote_id ?? 'NULL')."\n";
    echo 'Read: '.($msg->is_read ? 'Yes' : 'No')."\n";
}

// Check latest 3 messages
echo "\n\nLatest 3 messages:\n";
$latest = App\Models\Message::latest()->take(3)->get();
foreach ($latest as $msg) {
    echo "---\n";
    echo "ID: {$msg->id}\n";
    echo "Message: {$msg->message}\n";
    echo "From: {$msg->sender_type} → To: {$msg->recipient_type}\n";
    echo 'Quote ID: '.($msg->quote_id ?? 'NULL')."\n";
}
