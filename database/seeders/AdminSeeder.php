<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@sydneymarkets.com',
                'password' => Hash::make('admin123'),
                'phone' => '0400100001',
                'role' => 'super_admin',
                'department' => 'Management',
                'status' => 'active',
                'email_verified_at' => now(),
                'permissions' => json_encode(['*']), // All permissions
            ],
            [
                'name' => 'John Manager',
                'email' => 'manager@sydneymarkets.com',
                'password' => Hash::make('manager123'),
                'phone' => '0400100002',
                'role' => 'admin',
                'department' => 'Operations',
                'status' => 'active',
                'email_verified_at' => now(),
                'permissions' => json_encode([
                    'dashboard.view',
                    'users.view',
                    'users.manage',
                    'orders.view',
                    'orders.manage',
                    'vendors.view',
                    'vendors.manage',
                    'buyers.view',
                    'reports.view'
                ]),
            ],
            [
                'name' => 'Sarah Support',
                'email' => 'support@sydneymarkets.com',
                'password' => Hash::make('support123'),
                'phone' => '0400100003',
                'role' => 'support',
                'department' => 'Customer Service',
                'status' => 'active',
                'email_verified_at' => now(),
                'permissions' => json_encode([
                    'dashboard.view',
                    'tickets.view',
                    'tickets.manage',
                    'orders.view',
                    'users.view',
                    'messages.view',
                    'messages.send'
                ]),
            ],
            [
                'name' => 'Mike Moderator',
                'email' => 'moderator@sydneymarkets.com',
                'password' => Hash::make('moderator123'),
                'phone' => '0400100004',
                'role' => 'moderator',
                'department' => 'Quality Control',
                'status' => 'active',
                'email_verified_at' => now(),
                'permissions' => json_encode([
                    'dashboard.view',
                    'products.review',
                    'vendors.review',
                    'reports.flag',
                    'content.moderate'
                ]),
            ],
        ];

        foreach ($admins as $admin) {
            Admin::updateOrCreate(
                ['email' => $admin['email']],
                $admin
            );
        }

        $this->command->info('Admin accounts seeded successfully.');
    }
}