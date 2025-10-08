<?php

namespace Database\Factories;

use App\Models\RFQ;
use App\Models\Buyer;
use Illuminate\Database\Eloquent\Factories\Factory;

class RFQFactory extends Factory
{
    protected $model = RFQ::class;

    public function definition()
    {
        return [
            'buyer_id' => Buyer::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->sentence(10),
            'items' => [
                [
                    'product_name' => $this->faker->randomElement(['Tomatoes', 'Lettuce', 'Carrots', 'Apples', 'Bananas']),
                    'quantity' => $this->faker->numberBetween(1, 100),
                    'unit' => $this->faker->randomElement(['KG', 'BOX', 'EA', 'CARTON'])
                ]
            ],
            'delivery_date' => $this->faker->dateTimeBetween('+1 day', '+14 days')->format('Y-m-d'),
            'delivery_location' => $this->faker->address(),
            'payment_terms' => $this->faker->randomElement(['Net 30', 'Net 60', 'COD']),
            'notes' => $this->faker->optional()->sentence(),
            'status' => 'open',
            'urgency' => $this->faker->randomElement(['normal', 'urgent', 'critical']),
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    public function urgent()
    {
        return $this->state(function (array $attributes) {
            return [
                'urgency' => 'urgent',
                'delivery_date' => now()->addHours(12)->format('Y-m-d')
            ];
        });
    }

    public function withMultipleItems()
    {
        return $this->state(function (array $attributes) {
            return [
                'items' => [
                    [
                        'product_name' => 'Tomatoes',
                        'quantity' => 10,
                        'unit' => 'KG'
                    ],
                    [
                        'product_name' => 'Lettuce',
                        'quantity' => 5,
                        'unit' => 'BOX'
                    ],
                    [
                        'product_name' => 'Carrots',
                        'quantity' => 20,
                        'unit' => 'KG'
                    ]
                ]
            ];
        });
    }
}