<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessTypes = ['sole_trader', 'partnership', 'company', 'trust'];
        $vendorTypes = ['grower', 'wholesaler', 'importer', 'processor'];
        $subscriptionPlans = ['basic', 'professional', 'enterprise'];
        $paymentTerms = ['prepaid', 'net_7', 'net_14', 'net_30', 'net_60'];
        
        $businessName = $this->faker->company() . ' ' . ['Produce', 'Foods', 'Wholesale', 'Trading', 'Supplies'][array_rand(['Produce', 'Foods', 'Wholesale', 'Trading', 'Supplies'])];
        
        return [
            'business_name' => $businessName,
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => $this->faker->phoneNumber(),
            'abn' => $this->faker->numerify('###########'),
            'business_type' => $this->faker->randomElement($businessTypes),
            'vendor_type' => $this->faker->randomElement($vendorTypes),
            
            // Address
            'address' => $this->faker->streetAddress(),
            'suburb' => $this->faker->city(),
            'state' => 'NSW',
            'postcode' => $this->faker->postcode(),
            'country' => 'Australia',
            
            // Business Details
            'website' => 'www.' . Str::slug($businessName) . '.com.au',
            'description' => $this->faker->paragraph(3),
            'logo' => null,
            'banner_image' => null,
            
            // Status & Verification
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'verification_status' => 'verified',
            'verification_documents' => json_encode([]),
            
            // Subscription & Billing
            'subscription_plan' => $this->faker->randomElement($subscriptionPlans),
            'subscription_expires_at' => now()->addYear(),
            'commission_rate' => $this->faker->randomFloat(1, 5.0, 12.0),
            
            // Business Settings
            'payment_terms' => $this->faker->randomElement($paymentTerms),
            'minimum_order_value' => $this->faker->randomFloat(2, 50.00, 500.00),
            'delivery_radius' => $this->faker->numberBetween(20, 100),
            'delivery_fee' => $this->faker->randomFloat(2, 10.00, 30.00),
            'free_delivery_threshold' => $this->faker->randomFloat(2, 200.00, 1000.00),
            'operating_hours' => json_encode([
                'monday' => ['open' => '05:00', 'close' => '15:00'],
                'tuesday' => ['open' => '05:00', 'close' => '15:00'],
                'wednesday' => ['open' => '05:00', 'close' => '15:00'],
                'thursday' => ['open' => '05:00', 'close' => '15:00'],
                'friday' => ['open' => '05:00', 'close' => '15:00'],
                'saturday' => ['open' => '06:00', 'close' => '13:00'],
                'sunday' => 'closed'
            ]),
            
            // Ratings & Stats
            'rating' => $this->faker->randomFloat(1, 3.5, 5.0),
            'total_reviews' => $this->faker->numberBetween(0, 500),
            'total_sales' => $this->faker->numberBetween(0, 1000000),
            
            // Timestamps
            'joined_at' => $this->faker->dateTimeBetween('-3 years', '-1 month'),
            'approved_at' => $this->faker->dateTimeBetween('-3 years', '-1 month'),
            
            // Additional
            'settings' => json_encode([
                'auto_accept_orders' => $this->faker->boolean(30),
                'notification_email' => true,
                'notification_sms' => $this->faker->boolean(70)
            ]),
            'metadata' => json_encode([
                'established_year' => $this->faker->numberBetween(1990, 2020),
                'employee_count' => $this->faker->numberBetween(1, 50),
                'warehouse_size' => $this->faker->randomElement(['small', 'medium', 'large'])
            ])
        ];
    }

    /**
     * Indicate that the vendor is verified.
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
     * Indicate that the vendor is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_status' => 'unverified',
            'status' => 'inactive',
            'approved_at' => null,
        ]);
    }

    /**
     * Indicate that the vendor has a premium subscription.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'enterprise',
            'commission_rate' => $this->faker->randomFloat(1, 5.0, 8.0), // Lower commission for premium
        ]);
    }
}