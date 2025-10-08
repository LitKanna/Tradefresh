<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\RFQ;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition()
    {
        return [
            'rfq_id' => RFQ::factory(),
            'vendor_id' => Vendor::factory(),
            'quote_number' => 'Q-' . $this->faker->unique()->numberBetween(1000, 9999),
            'items' => [
                [
                    'product_name' => $this->faker->randomElement(['Tomatoes', 'Lettuce', 'Carrots']),
                    'quantity' => $this->faker->numberBetween(1, 100),
                    'unit' => $this->faker->randomElement(['KG', 'BOX', 'EA']),
                    'price' => $this->faker->randomFloat(2, 1, 100),
                    'subtotal' => $this->faker->randomFloat(2, 10, 1000)
                ]
            ],
            'total_amount' => $this->faker->randomFloat(2, 10, 5000),
            'valid_until' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'delivery_date' => $this->faker->dateTimeBetween('+2 days', '+14 days')->format('Y-m-d'),
            'payment_terms' => $this->faker->randomElement(['Net 30', 'Net 60', 'COD']),
            'notes' => $this->faker->optional()->sentence(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    public function accepted()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
                'accepted_at' => now()
            ];
        });
    }

    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => $this->faker->sentence()
            ];
        });
    }
}