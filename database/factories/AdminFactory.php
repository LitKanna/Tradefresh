<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'phone' => $this->faker->numerify('+614########'),
            'role' => $this->faker->randomElement(['super_admin', 'admin', 'support']),
            'department' => $this->faker->randomElement(['Operations', 'Finance', 'IT', 'Customer Support']),
            'permissions' => json_encode($this->generatePermissions()),
            'is_active' => true,
            'last_login_at' => $this->faker->dateTimeThisMonth(),
            'two_factor_enabled' => $this->faker->boolean(30),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }
    
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'super_admin',
            'permissions' => json_encode(['*']),
        ]);
    }
    
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt(Str::random(32)),
        ]);
    }
    
    private function generatePermissions(): array
    {
        return $this->faker->randomElements([
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'vendors.view', 'vendors.approve', 'vendors.suspend',
            'buyers.view', 'buyers.edit', 'buyers.delete',
            'orders.view', 'orders.manage',
            'payments.view', 'payments.process',
            'reports.view', 'reports.generate',
            'settings.view', 'settings.edit',
        ], $this->faker->numberBetween(3, 8));
    }
}