<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyzeDatabaseStructure extends Command
{
    protected $signature = 'db:analyze-structure';
    protected $description = 'Analyze database structure and identify missing components';

    private $missingTables = [];
    private $missingColumns = [];
    private $issues = [];

    public function handle()
    {
        $this->info('=== DATABASE STRUCTURE ANALYSIS ===');
        $this->newLine();
        
        // Tables expected based on model and service analysis
        $expectedStructure = $this->getExpectedStructure();
        
        // Analyze each table
        foreach ($expectedStructure as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $this->missingTables[] = $table;
                $this->error("❌ Missing table: $table");
            } else {
                $this->info("✅ Table exists: $table");
                
                // Check columns
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        $this->missingColumns[] = "$table.$column";
                        $this->warn("   ⚠️ Missing column: $column");
                    }
                }
            }
        }
        
        // Check specific relationships and constraints
        $this->checkRelationships();
        
        // Summary
        $this->newLine();
        $this->info('=== ANALYSIS SUMMARY ===');
        $this->table(
            ['Component', 'Count'],
            [
                ['Missing Tables', count($this->missingTables)],
                ['Missing Columns', count($this->missingColumns)],
                ['Relationship Issues', count($this->issues)]
            ]
        );
        
        if (count($this->missingTables) > 0) {
            $this->newLine();
            $this->error('Missing Tables:');
            foreach ($this->missingTables as $table) {
                $this->line(" - $table");
            }
        }
        
        if (count($this->missingColumns) > 0) {
            $this->newLine();
            $this->warn('Missing Columns:');
            foreach ($this->missingColumns as $column) {
                $this->line(" - $column");
            }
        }
        
        if (count($this->issues) > 0) {
            $this->newLine();
            $this->warn('Relationship Issues:');
            foreach ($this->issues as $issue) {
                $this->line(" - $issue");
            }
        }
        
        // Generate migration suggestions
        $this->generateMigrationSuggestions();
        
        return 0;
    }
    
    private function getExpectedStructure()
    {
        return [
            // Core tables from Buyer model
            'buyers' => [
                'id', 'name', 'email', 'password', 'credit_limit', 'credit_used', 
                'payment_terms', 'business_id', 'is_primary_contact', 'can_place_orders',
                'can_view_invoices', 'can_manage_users'
            ],
            
            // From Order model
            'orders' => [
                'id', 'buyer_id', 'vendor_id', 'supplier_id', 'order_number', 
                'status', 'total_amount', 'delivered_at', 'expected_delivery_date',
                'created_at', 'updated_at', 'deleted_at'
            ],
            
            // From Invoice model  
            'invoices' => [
                'id', 'buyer_id', 'user_id', 'order_id', 'status', 'total_amount',
                'payment_status', 'due_date', 'created_at'
            ],
            
            // From DashboardMetricsService
            'buyer_activities' => [
                'id', 'buyer_id', 'activity_type', 'description', 'metadata',
                'icon', 'color', 'is_read', 'created_at'
            ],
            
            'dashboard_preferences' => [
                'id', 'buyer_id', 'widget_layout', 'visible_widgets',
                'default_date_range', 'auto_refresh', 'refresh_interval'
            ],
            
            'dashboard_metrics' => [
                'id', 'buyer_id', 'created_at'
            ],
            
            'credit_transactions' => [
                'id', 'buyer_id', 'amount', 'type', 'balance_after', 'created_at'
            ],
            
            'payments' => [
                'id', 'buyer_id', 'amount', 'payment_method', 'created_at'
            ],
            
            'categories' => [
                'id', 'name', 'created_at'
            ],
            
            'price_history' => [
                'id', 'product_id', 'old_price', 'new_price', 
                'change_percentage', 'created_at'
            ],
            
            'favorite_vendors' => [
                'id', 'buyer_id', 'vendor_id', 'created_at'
            ],
            
            'approvals' => [
                'id', 'buyer_id', 'status', 'created_at'
            ],
            
            // From relationships
            'order_items' => [
                'id', 'order_id', 'product_id', 'quantity', 'price',
                'original_price', 'subtotal', 'total'
            ],
            
            'vendor_categories' => [
                'id', 'vendor_id', 'category_id'
            ],
            
            // Additional required tables
            'notifications' => [
                'id', 'notifiable_id', 'notifiable_type', 'is_read', 'created_at'
            ],
            
            'activities' => [
                'id', 'subject_id', 'subject_type', 'created_at'
            ],
            
            'shopping_lists' => [
                'id', 'buyer_id', 'created_at'
            ],
            
            'business_users' => [
                'id', 'buyer_id', 'business_id', 'status', 'invited_by', 'approved_by'
            ],
            
            'pickup_bookings' => [
                'id', 'buyer_id', 'created_at'
            ],
            
            'order_status_history' => [
                'id', 'order_id', 'status', 'notes', 'user_id', 'created_at'
            ]
        ];
    }
    
    private function checkRelationships()
    {
        $this->newLine();
        $this->info('Checking Relationships...');
        
        // Check if suppliers table has proper relationship with orders
        if (Schema::hasTable('orders') && Schema::hasTable('suppliers')) {
            if (!Schema::hasColumn('orders', 'supplier_id')) {
                $this->issues[] = 'orders.supplier_id foreign key missing';
            }
        }
        
        // Check buyer_supplier pivot
        if (!Schema::hasTable('buyer_supplier')) {
            $this->issues[] = 'buyer_supplier pivot table missing';
        }
        
        // Check buyer_product pivot
        if (!Schema::hasTable('buyer_product')) {
            $this->issues[] = 'buyer_product pivot table missing';
        }
        
        // Check if invoices has buyer_id
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'buyer_id')) {
            $this->issues[] = 'invoices.buyer_id column missing';
        }
    }
    
    private function generateMigrationSuggestions()
    {
        if (count($this->missingTables) === 0 && count($this->missingColumns) === 0) {
            $this->newLine();
            $this->info('✅ No missing components detected!');
            return;
        }
        
        $this->newLine();
        $this->info('=== MIGRATION SUGGESTIONS ===');
        $this->line('Create the following migrations to fix missing components:');
        $this->newLine();
        
        $migrationCount = 1;
        
        // Suggest migrations for missing tables
        foreach ($this->missingTables as $table) {
            $this->line("$migrationCount. php artisan make:migration create_{$table}_table");
            $migrationCount++;
        }
        
        // Suggest migrations for missing columns
        $columnsByTable = [];
        foreach ($this->missingColumns as $column) {
            [$table, $col] = explode('.', $column);
            if (!isset($columnsByTable[$table])) {
                $columnsByTable[$table] = [];
            }
            $columnsByTable[$table][] = $col;
        }
        
        foreach ($columnsByTable as $table => $columns) {
            $this->line("$migrationCount. php artisan make:migration add_missing_columns_to_{$table}_table");
            $migrationCount++;
        }
    }
}