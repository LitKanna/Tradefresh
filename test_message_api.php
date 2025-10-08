<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// Simulate buyer authentication
$buyer = App\Models\Buyer::find(1);
auth('buyer')->login($buyer);

// Test the API endpoint
$request = Illuminate\Http\Request::create('/api/messages/quote/73', 'GET');
$request->headers->set('Accept', 'application/json');

echo "Testing: GET /api/messages/quote/73\n";
echo "Authenticated as buyer: {$buyer->business_name}\n\n";

$response = $kernel->handle($request);

echo "Response Status: {$response->getStatusCode()}\n";
echo "Response Body:\n";
echo $response->getContent();

$kernel->terminate($request, $response);
