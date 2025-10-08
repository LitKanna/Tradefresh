<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BusinessFactory extends Factory
{
    protected $model = Business::class;
    
    // Valid Australian ABNs for testing
    protected $validABNs = [
        '51824753556', '89003783373', '75000024733', '64006580883',
        '86082541465', '31004085616', '45002117818', '93083380995',
        '48068213834', '53004085497', '66000004466', '41001678779',
        '84002939050', '24000000506', '11000000776', '83000000364'
    ];
    
    protected $businessNames = [
        'Fresh Produce Direct', 'Premium Meats Australia', 'Ocean Fresh Seafood',
        'Garden Fresh Vegetables', 'Quality Fruits & Co', 'Sydney Wholesale Foods',
        'Metro Markets Supply', 'Gourmet Food Traders', 'Farm Direct Produce'
    ];
    
    protected $suburbs = [
        'Sydney', 'Parramatta', 'Chatswood', 'Bondi', 'Manly', 'Newtown',
        'Surry Hills', 'Pyrmont', 'Alexandria', 'Marrickville', 'Leichhardt',
        'Strathfield', 'Burwood', 'Ashfield', 'Bankstown', 'Liverpool'
    ];
    
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $businessName = $this->faker->randomElement($this->businessNames) . ' Pty Ltd';
        $tradingName = str_replace(' Pty Ltd', '', $businessName);
        $businessType = $this->faker->randomElement(['vendor', 'buyer', 'both']);
        
        return [
            'abn' => $this->faker->randomElement($this->validABNs),
            'business_name' => $businessName,
            'trading_name' => $tradingName,
            'business_type' => $businessType,
            'entity_type' => $this->faker->randomElement(['sole_trader', 'partnership', 'company', 'trust']),
            'abn_registration_date' => Carbon::now()->subYears(rand(2, 20)),
            'gst_registered' => $this->faker->boolean(90),
            'acn' => $this->faker->boolean(60) ? $this->faker->numerify('#########') : null,
            
            // Contact Information
            'primary_email' => strtolower(str_replace(' ', '.', $tradingName)) . '@business.com.au',
            'secondary_email' => $this->faker->boolean(50) ? $this->faker->companyEmail : null,
            'phone' => '02 ' . $this->faker->numberBetween(8000, 9999) . ' ' . $this->faker->numberBetween(1000, 9999),
            'mobile' => '04' . $this->faker->numberBetween(10000000, 99999999),
            'fax' => $this->faker->boolean(30) ? '02 ' . $this->faker->numberBetween(8000, 9999) . ' ' . $this->faker->numberBetween(1000, 9999) : null,
            'website' => $this->faker->boolean(70) ? 'https://www.' . strtolower(str_replace(' ', '', $tradingName)) . '.com.au' : null,
            
            // Address Information
            'address_line1' => $this->faker->streetAddress,
            'address_line2' => $this->faker->boolean(30) ? $this->faker->secondaryAddress : null,
            'suburb' => $this->faker->randomElement($this->suburbs),
            'state' => 'NSW',
            'postcode' => (string) $this->faker->numberBetween(2000, 2200),
            'latitude' => -33.8688 + ($this->faker->numberBetween(-500, 500) / 10000),
            'longitude' => 151.2093 + ($this->faker->numberBetween(-500, 500) / 10000),
            
            // Business Details
            'business_description' => $this->faker->paragraph,
            'business_hours' => $this->generateBusinessHours(),
            'delivery_areas' => $this->generateDeliveryAreas(),
            'credit_limit' => $this->faker->randomElement([10000, 25000, 50000, 100000, 250000]),
            'outstanding_balance' => 0,
            'payment_terms_days' => $this->faker->randomElement([7, 14, 30, 60]),
            
            // Status and Verification
            'status' => 'active',
            'is_verified' => true,
            'verified_at' => Carbon::now()->subDays(rand(30, 365)),
            'requires_approval' => false,
            'approved_at' => Carbon::now()->subDays(rand(30, 365)),
            
            // Subscription and Billing
            'subscription_tier' => $this->faker->randomElement(['free', 'basic', 'premium', 'enterprise']),
            'subscription_expires_at' => Carbon::now()->addMonths(rand(1, 12)),
            'auto_renew' => $this->faker->boolean(80),
            
            // Compliance and Documentation
            'certifications' => $this->generateCertifications(),
            'insurance_details' => $this->generateInsuranceDetails(),
            'insurance_expiry' => Carbon::now()->addMonths(rand(6, 24)),
            'haccp_certified' => $businessType === 'vendor' ? $this->faker->boolean(70) : false,
            'organic_certified' => $businessType === 'vendor' ? $this->faker->boolean(30) : false,
            
            // Performance Metrics
            'average_rating' => $this->faker->randomFloat(1, 3.5, 5.0),
            'total_reviews' => $this->faker->numberBetween(10, 500),
            'total_orders' => $this->faker->numberBetween(50, 5000),
            'total_revenue' => $this->faker->randomFloat(2, 50000, 5000000),
            'on_time_delivery_rate' => $this->faker->randomFloat(2, 85, 100),
            
            // Settings and Preferences
            'notification_preferences' => $this->generateNotificationPreferences(),
            'payment_methods' => $this->generatePaymentMethods(),
            'preferred_currency' => 'AUD',
            'timezone' => 'Australia/Sydney',
        ];
    }
    
    /**
     * Indicate that the business is a vendor.
     */
    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type' => 'vendor',
            'haccp_certified' => $this->faker->boolean(70),
            'organic_certified' => $this->faker->boolean(30),
        ]);
    }
    
    /**
     * Indicate that the business is a buyer.
     */
    public function buyer(): static
    {
        return $this->state(fn (array $attributes) => [
            'business_type' => 'buyer',
            'haccp_certified' => false,
            'organic_certified' => false,
        ]);
    }
    
    /**
     * Indicate that the business is premium tier.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'premium',
            'credit_limit' => $this->faker->randomElement([100000, 250000, 500000]),
            'average_rating' => $this->faker->randomFloat(1, 4.5, 5.0),
        ]);
    }
    
    /**
     * Indicate that the business is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'outstanding_balance' => $this->faker->randomFloat(2, 1000, 50000),
        ]);
    }
    
    /**
     * Generate business hours
     */
    protected function generateBusinessHours(): array
    {
        $standardHours = ['open' => '09:00', 'close' => '17:00'];
        $marketHours = ['open' => '03:00', 'close' => '12:00'];
        
        $hours = $this->faker->boolean(50) ? $standardHours : $marketHours;
        
        return [
            'monday' => $hours,
            'tuesday' => $hours,
            'wednesday' => $hours,
            'thursday' => $hours,
            'friday' => $hours,
            'saturday' => $this->faker->boolean(70) ? ['open' => '09:00', 'close' => '13:00'] : 'closed',
            'sunday' => 'closed',
        ];
    }
    
    /**
     * Generate delivery areas
     */
    protected function generateDeliveryAreas(): array
    {
        return $this->faker->randomElements($this->suburbs, rand(3, 8));
    }
    
    /**
     * Generate certifications
     */
    protected function generateCertifications(): array
    {
        $certifications = [];
        
        if ($this->faker->boolean(60)) {
            $certifications[] = [
                'name' => 'Food Safety Certification',
                'issuer' => 'NSW Food Authority',
                'expiry' => Carbon::now()->addMonths(rand(6, 24))->format('Y-m-d'),
            ];
        }
        
        if ($this->faker->boolean(40)) {
            $certifications[] = [
                'name' => 'ISO 9001:2015',
                'issuer' => 'Standards Australia',
                'expiry' => Carbon::now()->addYears(rand(1, 3))->format('Y-m-d'),
            ];
        }
        
        return $certifications;
    }
    
    /**
     * Generate insurance details
     */
    protected function generateInsuranceDetails(): array
    {
        return [
            'provider' => $this->faker->randomElement(['Allianz', 'QBE', 'Suncorp', 'NRMA']),
            'policy_number' => strtoupper($this->faker->bothify('POL-####-????')),
            'public_liability' => $this->faker->randomElement([5000000, 10000000, 20000000]),
            'product_liability' => $this->faker->randomElement([5000000, 10000000, 20000000]),
        ];
    }
    
    /**
     * Generate notification preferences
     */
    protected function generateNotificationPreferences(): array
    {
        return [
            'email' => [
                'orders' => $this->faker->boolean(90),
                'payments' => $this->faker->boolean(95),
                'marketing' => $this->faker->boolean(40),
            ],
            'sms' => [
                'orders' => $this->faker->boolean(60),
                'payments' => $this->faker->boolean(70),
                'marketing' => $this->faker->boolean(20),
            ],
            'push' => [
                'orders' => $this->faker->boolean(80),
                'payments' => $this->faker->boolean(85),
                'marketing' => $this->faker->boolean(30),
            ],
        ];
    }
    
    /**
     * Generate payment methods
     */
    protected function generatePaymentMethods(): array
    {
        $methods = [];
        
        if ($this->faker->boolean(90)) {
            $methods[] = 'credit_card';
        }
        
        if ($this->faker->boolean(70)) {
            $methods[] = 'bank_transfer';
        }
        
        if ($this->faker->boolean(50)) {
            $methods[] = 'bpay';
        }
        
        if ($this->faker->boolean(30)) {
            $methods[] = 'account_credit';
        }
        
        return $methods;
    }
}