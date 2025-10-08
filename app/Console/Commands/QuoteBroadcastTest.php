<?php

namespace App\Console\Commands;

use App\Events\VendorQuoteSubmitted;
use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\RFQ;
use App\Models\Quote;
use Illuminate\Console\Command;
use Carbon\Carbon;

class QuoteBroadcastTest extends Command
{
    protected $signature = 'quote:broadcast-test {--buyer-id=1} {--vendor-id=1} {--rfq-id=}';
    protected $description = 'Test broadcasting a vendor quote to buyer dashboard via WebSocket';

    public function handle()
    {
        $buyerId = $this->option('buyer-id');
        $vendorId = $this->option('vendor-id');
        $rfqId = $this->option('rfq-id');

        $this->info('🚀 Testing Quote Broadcasting via WebSocket');
        $this->info('===========================================');

        // Try to get real data first
        $buyer = Buyer::find($buyerId);
        $vendor = Vendor::find($vendorId);
        $rfq = $rfqId ? RFQ::find($rfqId) : null;

        // Create mock RFQ if not found
        if (!$rfq) {
            $this->warn('⚠️ No RFQ found. Creating mock RFQ...');
            $rfq = $this->createMockRFQ($buyerId);
        }

        // Create mock vendor if not found
        if (!$vendor) {
            $this->warn('⚠️ No vendor found. Creating mock vendor...');
            $vendor = $this->createMockVendor();
        }

        // Create mock quote
        $quote = $this->createMockQuote($rfq, $vendor);

        $this->info("📋 RFQ: #{$rfq->id} - {$rfq->reference_number}");
        $this->info("🏪 Vendor: {$vendor->business_name}");
        $this->info("💰 Quote Total: \$" . number_format($quote->total_amount, 2));
        $this->info("📡 Broadcasting to buyer channel: buyer.{$rfq->buyer_id}");

        // Broadcast the event
        try {
            event(new VendorQuoteSubmitted($quote, $vendor, $rfq));
            $this->info('');
            $this->info('✅ Quote broadcasted successfully!');
            $this->info('');
            $this->info('🎯 To see it in action:');
            $this->info('   1. Make sure Reverb is running: php artisan reverb:start --debug');
            $this->info('   2. Login as a buyer and go to the dashboard');
            $this->info('   3. Watch the Vendor Quotes panel for the new quote');
            $this->info('');
            $this->info('📊 Quote Details Sent:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Quote ID', $quote->id],
                    ['Vendor', $vendor->business_name],
                    ['Total Amount', '$' . number_format($quote->total_amount, 2)],
                    ['Delivery Fee', '$' . number_format($quote->delivery_fee, 2)],
                    ['Valid For', $quote->validity_hours . ' hours'],
                    ['Items', count($quote->items)],
                    ['Channel', "buyer.{$rfq->buyer_id}"],
                ]
            );
        } catch (\Exception $e) {
            $this->error('❌ Error broadcasting quote: ' . $e->getMessage());
            $this->error('Make sure Reverb is running: php artisan reverb:start --debug');
        }

        return Command::SUCCESS;
    }

    private function createMockRFQ($buyerId)
    {
        return new class($buyerId) {
            public $id;
            public $buyer_id;
            public $reference_number;
            public $delivery_date;
            public $items;
            public $created_at;

            public function __construct($buyerId)
            {
                $this->id = rand(1000, 9999);
                $this->buyer_id = $buyerId;
                $this->reference_number = 'RFQ-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
                $this->delivery_date = Carbon::now()->addDays(2)->toDateString();
                $this->created_at = Carbon::now();
                $this->items = collect([
                    (object)['name' => 'Fresh Tomatoes', 'quantity' => 50, 'unit' => 'kg'],
                    (object)['name' => 'Iceberg Lettuce', 'quantity' => 30, 'unit' => 'heads'],
                    (object)['name' => 'Carrots', 'quantity' => 25, 'unit' => 'kg'],
                ]);
            }

            public function items()
            {
                return $this->items;
            }
        };
    }

    private function createMockVendor()
    {
        $vendors = [
            ['name' => 'Fresh Produce Co', 'suburb' => 'Flemington', 'rating' => 4.8],
            ['name' => 'Sydney Farm Direct', 'suburb' => 'Homebush', 'rating' => 4.6],
            ['name' => 'Premium Vegetables', 'suburb' => 'Strathfield', 'rating' => 4.9],
            ['name' => 'Green Valley Supplies', 'suburb' => 'Parramatta', 'rating' => 4.7],
        ];

        $vendor = $vendors[array_rand($vendors)];

        return new class($vendor) {
            public $id;
            public $business_name;
            public $suburb;
            public $rating;
            public $completed_orders;
            public $delivery_time;

            public function __construct($data)
            {
                $this->id = rand(100, 999);
                $this->business_name = $data['name'];
                $this->suburb = $data['suburb'];
                $this->rating = $data['rating'];
                $this->completed_orders = rand(50, 500);
                $this->delivery_time = '24-48 hours';
            }
        };
    }

    private function createMockQuote($rfq, $vendor)
    {
        $items = collect();
        $total = 0;

        foreach ($rfq->items as $item) {
            $unitPrice = rand(20, 80) / 10; // Random price between 2.0 and 8.0
            $totalPrice = $item->quantity * $unitPrice;
            $total += $totalPrice;

            $items->push((object)[
                'id' => rand(1000, 9999),
                'product_name' => $item->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'availability' => 'in_stock',
                'notes' => null,
            ]);
        }

        return new class($rfq, $vendor, $items, $total) {
            public $id;
            public $rfq_id;
            public $vendor_id;
            public $total_amount;
            public $delivery_fee;
            public $notes;
            public $validity_hours;
            public $created_at;
            public $status;
            public $items;

            public function __construct($rfq, $vendor, $items, $total)
            {
                $this->id = rand(10000, 99999);
                $this->rfq_id = $rfq->id;
                $this->vendor_id = $vendor->id;
                $this->total_amount = $total;
                $this->delivery_fee = 25;
                $this->notes = 'Fresh stock available. Same day delivery possible.';
                $this->validity_hours = 24;
                $this->created_at = Carbon::now();
                $this->status = 'pending';
                $this->items = $items;
            }
        };
    }
}