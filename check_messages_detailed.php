<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$messages = App\Models\Message::latest()->take(5)->get();

echo 'Total messages: '.App\Models\Message::count()."\n\n";

if ($messages->count() > 0) {
    echo "Recent messages:\n";
    foreach ($messages as $msg) {
        echo "---\n";
        echo "ID: {$msg->id}\n";
        echo 'Quote ID: '.($msg->quote_id ?? 'NULL')."\n";
        echo "Message: {$msg->message}\n";
        echo "From: {$msg->sender_type} ID:{$msg->sender_id}\n";
        echo "To: {$msg->recipient_type} ID:{$msg->recipient_id}\n";
        echo 'Read: '.($msg->is_read ? 'Yes' : 'No')."\n";
        echo "Created: {$msg->created_at}\n";
    }
} else {
    echo "No messages found\n";
}

// Check if Echo/Reverb config is correct
echo "\n\nBroadcasting Configuration:\n";
echo 'Driver: '.config('broadcasting.default')."\n";
echo 'Reverb Host: '.config('broadcasting.connections.reverb.options.host')."\n";
echo 'Reverb Port: '.config('broadcasting.connections.reverb.options.port')."\n";
