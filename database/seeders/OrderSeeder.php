<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buyers = Buyer::all();
        $vendors = Vendor::all();
        $products = Product::where('is_active', true)->get();

        if ($buyers->isEmpty() || $vendors->isEmpty() || $products->isEmpty()) {
            $this->command->error('Please run BuyerSeeder, VendorSeeder, and ProductSeeder first.');
            return;
        }

        $createdOrders = 0;
        $createdOrderItems = 0;

        // Create orders for the last 3 months
        for ($i = 0; $i < 50; $i++) {
            $buyer = $buyers->random();
            $vendor = $vendors->random();
            
            // Get products from this vendor
            $vendorProducts = $products->where('vendor_id', $vendor->id);
            if ($vendorProducts->isEmpty()) continue;

            $orderDate = now()->subDays(rand(1, 90));
            
            $order = Order::create([
                'order_number' => 'ORD-' . str_pad($i + 10001, 6, '0', STR_PAD_LEFT),
                'buyer_id' => $buyer->id,
                'vendor_id' => $vendor->id,
                'status' => $this->getOrderStatus($orderDate),
                'order_date' => $orderDate->format('Y-m-d'),
                'delivery_date' => $orderDate->addDays(rand(1, 3))->format('Y-m-d'),
                'delivery_address' => $buyer->shipping_address,
                'delivery_suburb' => $buyer->shipping_suburb,
                'delivery_state' => $buyer->shipping_state,
                'delivery_postcode' => $buyer->shipping_postcode,
                'delivery_instructions' => $this->getDeliveryInstructions($buyer->business_type),
                'payment_method' => $buyer->preferred_payment_method,
                'payment_terms' => $buyer->payment_terms,
                'currency' => 'AUD',
                'subtotal' => 0, // Will be calculated after items
                'delivery_fee' => $vendor->delivery_fee,
                'gst_amount' => 0, // Will be calculated
                'discount_amount' => 0,
                'total_amount' => 0, // Will be calculated
                'notes' => $this->getOrderNotes(),
                'metadata' => json_encode([
                    'source' => ['web', 'phone', 'email'][rand(0, 2)],
                    'priority' => ['normal', 'high', 'urgent'][rand(0, 2)],
                    'special_handling' => rand(0, 10) > 8
                ])
            ]);

            // Add 2-8 items to each order
            $itemCount = rand(2, 8);
            $selectedProducts = $vendorProducts->random(min($itemCount, $vendorProducts->count()));
            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand($product->min_order_quantity, min(50, $product->max_order_quantity ?? 50));
                $unitPrice = $product->price * (0.9 + (rand(0, 20) / 100)); // ±10% price variation
                $lineTotal = $quantity * $unitPrice;
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'unit' => $product->unit,
                    'specifications' => 'Standard quality',
                    'metadata' => json_encode([
                        'original_price' => $product->price,
                        'discount_applied' => $unitPrice < $product->price,
                        'product_brand' => $product->brand
                    ])
                ]);

                $createdOrderItems++;
            }

            // Calculate totals
            $gstAmount = ($subtotal + $vendor->delivery_fee) * 0.1;
            $totalAmount = $subtotal + $vendor->delivery_fee + $gstAmount;

            $order->update([
                'subtotal' => $subtotal,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount
            ]);

            $createdOrders++;
        }

        $this->command->info("Orders seeded successfully. Created {$createdOrders} orders with {$createdOrderItems} order items.");
    }

    private function getOrderStatus($orderDate): string
    {
        $daysAgo = now()->diffInDays($orderDate);
        
        if ($daysAgo > 30) {
            return ['completed', 'completed', 'completed', 'cancelled'][rand(0, 3)]; // Mostly completed for old orders
        } elseif ($daysAgo > 7) {
            return ['completed', 'delivered', 'cancelled'][rand(0, 2)];
        } elseif ($daysAgo > 2) {
            return ['delivered', 'in_transit', 'completed'][rand(0, 2)];
        } else {
            return ['pending', 'confirmed', 'preparing', 'in_transit'][rand(0, 3)];
        }
    }

    private function getDeliveryInstructions($businessType): string
    {
        $instructions = [
            'restaurant' => 'Deliver to kitchen entrance between 6AM-10AM. Call chef on arrival.',
            'cafe' => 'Use rear entrance. Morning delivery preferred. Stack items neatly.',
            'grocery' => 'Loading dock access. Check all expiry dates before unloading.',
            'distributor' => 'Warehouse entrance B. Quality inspection will be conducted.',
            'other' => 'Please call 15 minutes before arrival. Use main entrance.'
        ];

        return $instructions[$businessType] ?? $instructions['other'];
    }

    private function getOrderNotes(): ?string
    {
        $notes = [
            null, // 40% chance of no notes
            null,
            'Please ensure all items are fresh and within use-by date.',
            'Quality check required on delivery.',
            'Rush order - please prioritize.',
            'Regular customer - handle with care.',
            'Call before delivery to confirm timing.',
            'Special packaging required for fragile items.'
        ];

        return $notes[array_rand($notes)];
    }
}