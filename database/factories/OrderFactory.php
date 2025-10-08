<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Buyer;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 2000);
        $gst = $subtotal * 0.1;
        $deliveryFee = $this->faker->boolean(70) ? $this->faker->randomFloat(2, 10, 50) : 0;
        $total = $subtotal + $gst + $deliveryFee;
        
        return [
            'order_number' => 'ORD-' . date('Y') . '-' . str_pad($this->faker->unique()->numberBetween(1000, 9999), 6, '0', STR_PAD_LEFT),
            'buyer_id' => Buyer::factory(),
            'vendor_id' => Vendor::factory(),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'processing', 'ready', 'delivered', 'completed']),
            'order_date' => $this->faker->dateTimeThisMonth(),
            'requested_delivery_date' => $this->faker->dateTimeBetween('now', '+1 week'),
            'actual_delivery_date' => null,
            'delivery_address' => $this->faker->streetAddress(),
            'delivery_suburb' => $this->faker->randomElement(['Sydney', 'Parramatta', 'Chatswood', 'Bondi', 'Manly']),
            'delivery_postcode' => $this->faker->numerify('2###'),
            'delivery_instructions' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'subtotal' => $subtotal,
            'gst_amount' => $gst,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $total,
            'payment_method' => $this->faker->randomElement(['credit', 'cod', 'direct_debit']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'overdue', 'refunded']),
            'payment_terms' => $this->faker->randomElement(['COD', 'Net 7', 'Net 14', 'Net 30']),
            'notes' => $this->faker->boolean(30) ? $this->faker->paragraph() : null,
            'internal_notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'priority' => $this->faker->randomElement(['normal', 'urgent', 'rush']),
            'tracking_number' => $this->faker->boolean(60) ? 'TRK' . $this->faker->numerify('########') : null,
            'invoice_number' => null, // Will be generated when invoiced
            'confirmed_at' => null,
            'processed_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }
    
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }
    
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
            'confirmed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }
    
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
            'confirmed_at' => $this->faker->dateTimeBetween('-2 weeks', '-1 week'),
            'processed_at' => $this->faker->dateTimeBetween('-1 week', '-3 days'),
            'delivered_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'tracking_number' => 'TRK' . $this->faker->numerify('########'),
        ]);
    }
    
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'payment_status' => 'refunded',
            'cancelled_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'cancellation_reason' => $this->faker->randomElement([
                'Out of stock',
                'Customer request',
                'Delivery unavailable',
                'Payment failed',
                'Quality issues',
            ]),
        ]);
    }
    
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
            'requested_delivery_date' => $this->faker->dateTimeBetween('now', '+2 days'),
            'delivery_fee' => $attributes['delivery_fee'] * 1.5, // Rush delivery fee
        ]);
    }
    
    public function largeBulkOrder(): static
    {
        $subtotal = $this->faker->randomFloat(2, 5000, 20000);
        $gst = $subtotal * 0.1;
        $deliveryFee = $this->faker->randomFloat(2, 50, 150);
        
        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'gst_amount' => $gst,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $subtotal + $gst + $deliveryFee,
            'priority' => 'normal',
            'payment_terms' => 'Net 30',
        ]);
    }
}