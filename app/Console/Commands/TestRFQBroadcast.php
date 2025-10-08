<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RFQService;
use App\Models\Buyer;

class TestRFQBroadcast extends Command
{
    protected $signature = 'rfq:broadcast-test {--buyer=1 : Buyer ID to use for testing}';
    protected $description = 'Test RFQ broadcasting to all online vendors';

    public function handle(): int
    {
        $this->info('🚀 Testing RFQ Broadcasting System');
        $this->line('==================================');

        $buyerId = $this->option('buyer');

        // Check if buyer exists
        $buyer = Buyer::find($buyerId);
        if (!$buyer) {
            $this->error("Buyer with ID {$buyerId} not found. Creating test buyer...");

            // Create a test buyer
            $buyer = Buyer::create([
                'id' => 1,
                'business_name' => 'Test Buyer Restaurant',
                'email' => 'buyer@test.com',
                'password' => bcrypt('password'),
                'phone' => '0400000000',
                'address' => '123 Test St, Sydney NSW',
                'suburb' => 'Sydney CBD',
                'postcode' => '2000',
                'abn' => '12345678901',
            ]);

            $this->info("✅ Test buyer created: {$buyer->business_name}");
            $buyerId = $buyer->id;
        }

        $this->info("Using buyer: {$buyer->business_name} (ID: {$buyerId})");

        // Create test RFQ data
        $rfqData = [
            'title' => 'Weekly Fresh Produce Order - Test Broadcast',
            'description' => 'This is a test RFQ broadcast to demonstrate real-time WebSocket functionality',
            'delivery_date' => now()->addDays(2)->toDateString(),
            'delivery_time' => 'Morning (6am - 12pm)',
            'delivery_location' => '123 Test Restaurant, Sydney NSW 2000',
            'special_instructions' => 'Please deliver to loading dock at rear. Call 0400000000 on arrival.',
            'payment_terms' => 'Net 30',
            'type' => 'weekly_order',
            'visibility' => 'public',
            'items' => [
                [
                    'product_name' => 'Roma Tomatoes',
                    'quantity' => 25,
                    'unit' => 'kg',
                    'category' => 'Vegetables',
                    'notes' => 'Must be fresh and firm',
                    'quality_grade' => 'Premium',
                ],
                [
                    'product_name' => 'Iceberg Lettuce',
                    'quantity' => 30,
                    'unit' => 'heads',
                    'category' => 'Vegetables',
                    'notes' => 'Large heads preferred',
                    'quality_grade' => 'Premium',
                ],
                [
                    'product_name' => 'Red Capsicum',
                    'quantity' => 15,
                    'unit' => 'kg',
                    'category' => 'Vegetables',
                    'notes' => 'Bright red color, no blemishes',
                    'quality_grade' => 'Premium',
                ],
                [
                    'product_name' => 'Fresh Basil',
                    'quantity' => 5,
                    'unit' => 'bunches',
                    'category' => 'Herbs',
                    'notes' => 'For pasta dishes',
                    'quality_grade' => 'Premium',
                ],
                [
                    'product_name' => 'Strawberries',
                    'quantity' => 10,
                    'unit' => 'punnets',
                    'category' => 'Fruits',
                    'notes' => 'For desserts',
                    'quality_grade' => 'Premium',
                ],
            ],
        ];

        $this->line('');
        $this->info('📋 RFQ Details:');
        $this->line("   Title: {$rfqData['title']}");
        $this->line("   Delivery: {$rfqData['delivery_date']} - {$rfqData['delivery_time']}");
        $this->line("   Items: " . count($rfqData['items']) . " products");
        $this->line('');

        $this->info('📡 Broadcasting RFQ to all online vendors...');

        try {
            $rfqService = new RFQService();
            $rfq = $rfqService->createRFQ($rfqData, $buyerId);

            $this->line('');
            $this->info('✅ RFQ Created Successfully!');
            $this->line("   RFQ ID: {$rfq->id}");
            $this->line("   Reference: {$rfq->reference_number}");
            $this->line('');

            $this->info('🎯 Broadcast Status:');
            $this->line('   ✓ Event dispatched to channel: vendors.all');
            $this->line('   ✓ Event name: rfq.new');
            $this->line('   ✓ All online vendors will receive notification');
            $this->line('');

            $this->warn('⚠️ Important: Make sure you have:');
            $this->line('   1. Started Reverb WebSocket server: php artisan reverb:start --debug');
            $this->line('   2. Vendors logged in and on dashboard to see real-time notification');
            $this->line('   3. Browser console open to see WebSocket events');
            $this->line('');

            $this->info('🔍 To verify the broadcast:');
            $this->line('   1. Check vendor dashboards - they should see a new RFQ notification');
            $this->line('   2. Check browser console for: "New RFQ received via WebSocket"');
            $this->line('   3. The RFQ panel should update automatically without refresh');

        } catch (\Exception $e) {
            $this->error('Failed to create and broadcast RFQ: ' . $e->getMessage());
            $this->line('');
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}