<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// Simulate buyer authentication
auth('buyer')->loginUsingId(1);

// Create a request to the API
$request = Illuminate\Http\Request::create('/api/messages/send', 'POST', [
    'recipient_id' => 1,
    'recipient_type' => 'vendor',
    'message' => 'Test API message',
    'quote_id' => 72,
]);

$request->headers->set('Accept', 'application/json');
$request->headers->set('Content-Type', 'application/json');

echo "Testing API endpoint: POST /api/messages/send\n";
echo 'Authenticated as buyer ID: '.auth('buyer')->id()."\n\n";

try {
    $response = $kernel->handle($request);
    echo 'Response Status: '.$response->getStatusCode()."\n";
    echo 'Response Body: '.$response->getContent()."\n";

    if ($response->getStatusCode() === 200) {
        echo "\n✅ API endpoint working!\n";

        // Check if message is in database
        $count = App\Models\Message::count();
        echo "Total messages in database: {$count}\n";
    }
} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage()."\n";
    echo 'File: '.$e->getFile().':'.$e->getLine()."\n";
}

$kernel->terminate($request, $response ?? null);
