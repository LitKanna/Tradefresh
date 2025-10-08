<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\PriceUpdate;
use App\Services\WebSocketService;

class TestWebSocket extends Command
{
    protected $signature = 'websocket:test {--type=price : Type of event to test (price|quote|typing)}';
    protected $description = 'Test WebSocket broadcasting functionality';

    public function handle(): int
    {
        $type = $this->option('type');
        $websocketService = new WebSocketService();

        switch ($type) {
            case 'price':
                $this->testPriceUpdate();
                break;

            case 'quote':
                $this->testQuoteReceived($websocketService);
                break;

            case 'typing':
                $this->testVendorTyping($websocketService);
                break;

            default:
                $this->error("Unknown event type: {$type}");
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function testPriceUpdate(): void
    {
        $this->info('Testing Price Update broadcast...');

        $product = 'Test Tomatoes';
        $oldPrice = 5.99;
        $newPrice = 4.49;
        $vendorId = 1;
        $vendorName = 'Test Vendor';

        event(new PriceUpdate($product, $oldPrice, $newPrice, $vendorId, $vendorName));

        $this->info('✅ Price update event broadcasted successfully!');
        $this->line("Product: {$product}");
        $this->line("Old Price: ${$oldPrice}");
        $this->line("New Price: ${$newPrice}");
        $this->line("Vendor: {$vendorName}");
        $this->line('');
        $this->comment('Check your browser console for the received event.');
    }

    private function testQuoteReceived($websocketService): void
    {
        $this->info('Testing Quote Received broadcast...');

        // Create mock objects
        $quote = new \stdClass();
        $quote->id = 999;
        $quote->rfq_id = 123;
        $quote->total_price = 299.99;
        $quote->message = 'Test quote from WebSocket test';

        $vendor = new \stdClass();
        $vendor->id = 1;
        $vendor->business_name = 'Test Vendor Co';

        $buyer = new \stdClass();
        $buyer->id = 1;
        $buyer->business_name = 'Test Buyer Co';

        $result = $websocketService->broadcastQuote($quote, $vendor, $buyer);

        if ($result) {
            $this->info('✅ Quote received event broadcasted successfully!');
            $this->line("Quote ID: {$quote->id}");
            $this->line("Total: ${$quote->total_price}");
            $this->line("From: {$vendor->business_name}");
            $this->line("To: {$buyer->business_name}");
        } else {
            $this->error('Failed to broadcast quote event');
        }

        $this->line('');
        $this->comment('Check the buyer dashboard for the notification.');
    }

    private function testVendorTyping($websocketService): void
    {
        $this->info('Testing Vendor Typing indicator...');

        $rfqId = 123;
        $vendorId = 1;
        $vendorName = 'Fresh Farms Produce';

        // Start typing
        $websocketService->broadcastTyping($rfqId, $vendorId, $vendorName, true);
        $this->info("✅ Vendor typing indicator ON for {$vendorName}");

        sleep(3);

        // Stop typing
        $websocketService->broadcastTyping($rfqId, $vendorId, $vendorName, false);
        $this->info("✅ Vendor typing indicator OFF for {$vendorName}");

        $this->line('');
        $this->comment('Check the RFQ page to see the typing indicator.');
    }
}