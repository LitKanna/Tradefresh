<?php

namespace Database\Factories;

use App\Models\Buyer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Buyer>
 */
class BuyerFactory extends Factory
{
    protected $model = Buyer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessTypes = ['restaurant', 'cafe', 'grocery', 'retailer', 'distributor', 'other'];
        $buyerTypes = ['regular', 'premium', 'wholesale'];
        $paymentTerms = ['prepaid', 'net_7', 'net_14', 'net_30', 'net_60'];
        $paymentMethods = ['credit_card', 'bank_transfer', 'cash'];
        
        $companyName = $this->faker->company() . ' ' . $this->faker->randomElement(['Restaurant', 'Cafe', 'Kitchen', 'Bistro', 'Deli', 'Market']);
        
        return [
            'company_name' => $companyName,
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => $this->faker->phoneNumber(),
            'abn' => $this->faker->numerify('###########'),
            'business_type' => $this->faker->randomElement($businessTypes),
            'buyer_type' => $this->faker->randomElement($buyerTypes),
            
            // Billing Address
            'billing_address' => $this->faker->streetAddress(),
            'billing_suburb' => $this->faker->city(),
            'billing_state' => 'NSW',
            'billing_postcode' => $this->faker->postcode(),
            'billing_country' => 'Australia',
            
            // Shipping Address (sometimes same as billing)
            'shipping_address' => $this->faker->boolean(70) ? $this->faker->streetAddress() : null,
            'shipping_suburb' => $this->faker->boolean(70) ? $this->faker->city() : null,
            'shipping_state' => $this->faker->boolean(70) ? 'NSW' : null,
            'shipping_postcode' => $this->faker->boolean(70) ? $this->faker->postcode() : null,
            'shipping_country' => $this->faker->boolean(70) ? 'Australia' : null,
            
            // Business Details
            'website' => $this->faker->boolean(60) ? $this->faker->url() : null,
            
            // Status & Verification
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'verification_status' => 'verified',
            
            // Credit & Payment
            'credit_limit' => $this->faker->randomFloat(2, 1000.00, 50000.00),
            'credit_used' => function (array $attributes) {
                return $this->faker->randomFloat(2, 0, $attributes['credit_limit'] * 0.6); // Max 60% of credit used
            },
            'payment_terms' => $this->faker->randomElement($paymentTerms),
            'preferred_payment_method' => $this->faker->randomElement($paymentMethods),
            
            // Tax Settings
            'tax_exempt' => $this->faker->boolean(20), // 20% chance of being tax exempt
            'tax_id' => function (array $attributes) {
                return $attributes['tax_exempt'] ? null : 'TAX' . $this->faker->numerify('######');
            },
            
            // Notes
            'notes' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            
            // Timestamps
            'joined_at' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
            'approved_at' => $this->faker->dateTimeBetween('-2 years', '-1 month'),
            
            // Additional
            'preferences' => json_encode([
                'preferred_delivery_time' => $this->faker->randomElement(['morning', 'afternoon', 'evening']),
                'quality_requirements' => $this->faker->randomElement(['standard', 'premium', 'budget']),
                'eco_friendly' => $this->faker->boolean(30)
            ]),
            'metadata' => json_encode([
                'seating_capacity' => $this->faker->numberBetween(20, 200),
                'cuisine_type' => $this->faker->randomElement(['Italian', 'Asian', 'Modern Australian', 'French', 'Mediterranean']),
                'peak_hours' => $this->faker->randomElement(['breakfast', 'lunch', 'dinner', 'all_day'])
            ])
        ];
    }

    /**
     * Indicate that the buyer is a restaurant.
     */
    public function restaurant(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type' => 'restaurant',
            'buyer_type' => 'premium',
            'credit_limit' => $this->faker->randomFloat(2, 5000.00, 25000.00),
        ]);
    }

    /**
     * Indicate that the buyer is a cafe.
     */
    public function cafe(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type' => 'cafe',
            'buyer_type' => 'regular',
            'credit_limit' => $this->faker->randomFloat(2, 2000.00, 10000.00),
        ]);
    }

    /**
     * Indicate that the buyer is a grocery store.
     */
    public function grocery(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type' => 'grocery',
            'buyer_type' => 'wholesale',
            'credit_limit' => $this->faker->randomFloat(2, 10000.00, 50000.00),
        ]);
    }

    /**
     * Indicate that the buyer is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'verified',
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the buyer is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'unverified',
            'status' => 'inactive',
            'approved_at' => null,
        ]);
    }
}