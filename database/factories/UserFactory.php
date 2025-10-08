<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UserFactory extends Factory
{
    protected $model = User::class;
    
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $userType = $this->faker->randomElement(['vendor', 'buyer', 'staff']);
        $role = $this->generateRole($userType);
        
        return [
            'business_id' => Business::factory(),
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make('password'),
            'phone' => '02 ' . $this->faker->numberBetween(8000, 9999) . ' ' . $this->faker->numberBetween(1000, 9999),
            'mobile' => '04' . $this->faker->numberBetween(10000000, 99999999),
            
            // User Type and Role
            'user_type' => $userType,
            'role' => $role,
            'permissions' => $this->generatePermissions($role),
            
            // Profile Information
            'avatar' => $this->faker->boolean(30) ? $this->faker->imageUrl(200, 200, 'people') : null,
            'position' => $this->generatePosition($role),
            'department' => $this->generateDepartment($userType),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'employee_id' => strtoupper($this->faker->bothify('EMP####')),
            
            // Authentication and Security
            'remember_token' => Str::random(10),
            'two_factor_enabled' => $this->faker->boolean(20),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'last_login_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'last_login_ip' => $this->faker->ipv4,
            'login_attempts' => 0,
            'locked_until' => null,
            
            // Status
            'is_active' => true,
            'must_change_password' => false,
            'password_changed_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
            
            // Preferences
            'preferences' => $this->generatePreferences(),
            'language' => 'en',
            'timezone' => 'Australia/Sydney',
        ];
    }
    
    /**
     * Indicate that the user is unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
    
    /**
     * Indicate that the user is a vendor.
     */
    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'vendor',
            'role' => $this->generateRole('vendor'),
        ]);
    }
    
    /**
     * Indicate that the user is a buyer.
     */
    public function buyer(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'buyer',
            'role' => $this->generateRole('buyer'),
        ]);
    }
    
    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
            'role' => 'super_admin',
            'business_id' => null,
            'two_factor_enabled' => true,
        ]);
    }
    
    /**
     * Indicate that the user is a driver.
     */
    public function driver(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'driver',
            'role' => 'driver',
            'permissions' => ['delivery.view', 'delivery.update', 'route.view'],
        ]);
    }
    
    /**
     * Indicate that the user has two-factor authentication enabled.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt(Str::random(32)),
            'two_factor_recovery_codes' => encrypt(json_encode(array_map(fn() => Str::random(10), range(1, 8)))),
            'two_factor_confirmed_at' => Carbon::now(),
        ]);
    }
    
    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'last_login_at' => $this->faker->dateTimeBetween('-180 days', '-60 days'),
        ]);
    }
    
    /**
     * Indicate that the user is locked.
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'login_attempts' => 5,
            'locked_until' => Carbon::now()->addMinutes(30),
        ]);
    }
    
    /**
     * Generate role based on user type
     */
    protected function generateRole(string $userType): string
    {
        $roles = [
            'vendor' => ['admin', 'manager', 'sales', 'warehouse', 'accounting'],
            'buyer' => ['purchaser', 'manager', 'assistant', 'finance'],
            'staff' => ['support', 'operations', 'customer_service'],
            'admin' => ['super_admin', 'system_admin', 'support_admin'],
        ];
        
        return $this->faker->randomElement($roles[$userType] ?? ['staff']);
    }
    
    /**
     * Generate permissions based on role
     */
    protected function generatePermissions(string $role): array
    {
        $permissions = [
            'admin' => ['*'],
            'super_admin' => ['*'],
            'manager' => [
                'orders.*', 'products.*', 'reports.view', 'users.view', 
                'users.create', 'users.update', 'invoices.*', 'payments.*'
            ],
            'sales' => [
                'orders.view', 'orders.create', 'orders.update', 
                'products.view', 'customers.view', 'quotes.*'
            ],
            'warehouse' => [
                'orders.view', 'orders.update', 'inventory.*', 
                'pickup.manage', 'delivery.view'
            ],
            'purchaser' => [
                'orders.*', 'products.view', 'vendors.view', 
                'invoices.view', 'payments.create'
            ],
            'accounting' => [
                'invoices.*', 'payments.*', 'reports.financial', 
                'credit.manage', 'statements.*'
            ],
            'driver' => [
                'delivery.view', 'delivery.update', 'route.view'
            ],
        ];
        
        return $permissions[$role] ?? ['dashboard.view'];
    }
    
    /**
     * Generate position based on role
     */
    protected function generatePosition(string $role): string
    {
        $positions = [
            'admin' => 'Administrator',
            'super_admin' => 'System Administrator',
            'manager' => $this->faker->randomElement(['Operations Manager', 'Sales Manager', 'General Manager']),
            'sales' => $this->faker->randomElement(['Sales Representative', 'Account Executive', 'Sales Coordinator']),
            'warehouse' => $this->faker->randomElement(['Warehouse Supervisor', 'Inventory Controller', 'Dispatch Officer']),
            'purchaser' => $this->faker->randomElement(['Purchasing Officer', 'Procurement Manager', 'Buyer']),
            'accounting' => $this->faker->randomElement(['Accountant', 'Finance Officer', 'Bookkeeper']),
            'driver' => 'Delivery Driver',
        ];
        
        return $positions[$role] ?? 'Staff Member';
    }
    
    /**
     * Generate department based on user type
     */
    protected function generateDepartment(string $userType): string
    {
        $departments = [
            'vendor' => $this->faker->randomElement(['Sales', 'Operations', 'Warehouse', 'Finance']),
            'buyer' => $this->faker->randomElement(['Purchasing', 'Operations', 'Finance', 'Administration']),
            'staff' => $this->faker->randomElement(['Support', 'IT', 'HR', 'Marketing']),
            'admin' => 'Administration',
        ];
        
        return $departments[$userType] ?? 'General';
    }
    
    /**
     * Generate user preferences
     */
    protected function generatePreferences(): array
    {
        return [
            'theme' => $this->faker->randomElement(['light', 'dark', 'auto']),
            'notifications' => [
                'email' => $this->faker->boolean(80),
                'push' => $this->faker->boolean(60),
                'sms' => $this->faker->boolean(40),
            ],
            'dashboard' => [
                'widgets' => $this->faker->randomElements(
                    ['orders', 'revenue', 'inventory', 'notifications', 'calendar', 'tasks'],
                    rand(3, 6)
                ),
                'default_view' => $this->faker->randomElement(['grid', 'list', 'calendar']),
            ],
            'display' => [
                'items_per_page' => $this->faker->randomElement([10, 25, 50, 100]),
                'date_format' => 'dd/MM/yyyy',
                'time_format' => '24h',
                'currency_position' => 'before',
            ],
        ];
    }
}