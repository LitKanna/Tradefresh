<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DataConsistencyValidator
{
    protected $errors = [];
    protected $warnings = [];
    protected $fixedIssues = [];
    protected $statistics = [];
    
    /**
     * Run complete data consistency validation
     */
    public function validateDatabase(bool $autoFix = false): array
    {
        $startTime = microtime(true);
        
        // Initialize results
        $this->errors = [];
        $this->warnings = [];
        $this->fixedIssues = [];
        $this->statistics = [];
        
        // Run validation checks
        $this->validateForeignKeyConstraints();
        $this->validateOrphanedRecords();
        $this->validateDataIntegrity();
        $this->validateBusinessRules();
        $this->validateFinancialConsistency();
        $this->validateInventoryConsistency();
        $this->validateTemporalConsistency();
        $this->validateRelationshipIntegrity();
        $this->validateDuplicateData();
        $this->validateRequiredFields();
        
        // Auto-fix issues if requested
        if ($autoFix) {
            $this->autoFixIssues();
        }
        
        $executionTime = round(microtime(true) - $startTime, 2);
        
        return $this->generateValidationReport($executionTime);
    }
    
    /**
     * Validate foreign key constraints
     */
    protected function validateForeignKeyConstraints(): void
    {
        $constraints = $this->getForeignKeyConstraints();
        
        foreach ($constraints as $constraint) {
            $violations = $this->checkForeignKeyViolations(
                $constraint->TABLE_NAME,
                $constraint->COLUMN_NAME,
                $constraint->REFERENCED_TABLE_NAME,
                $constraint->REFERENCED_COLUMN_NAME
            );
            
            if ($violations > 0) {
                $this->errors[] = [
                    'type' => 'foreign_key_violation',
                    'table' => $constraint->TABLE_NAME,
                    'column' => $constraint->COLUMN_NAME,
                    'referenced_table' => $constraint->REFERENCED_TABLE_NAME,
                    'violations' => $violations,
                    'severity' => 'critical',
                ];
            }
        }
    }
    
    /**
     * Validate orphaned records
     */
    protected function validateOrphanedRecords(): void
    {
        $checks = [
            // Orders without valid businesses
            [
                'table' => 'orders',
                'column' => 'buyer_business_id',
                'parent_table' => 'businesses',
                'parent_column' => 'id',
                'description' => 'Orders with non-existent buyer businesses',
            ],
            [
                'table' => 'orders',
                'column' => 'vendor_business_id',
                'parent_table' => 'businesses',
                'parent_column' => 'id',
                'description' => 'Orders with non-existent vendor businesses',
            ],
            // Order items without orders
            [
                'table' => 'order_items',
                'column' => 'order_id',
                'parent_table' => 'orders',
                'parent_column' => 'id',
                'description' => 'Order items without parent orders',
            ],
            // Products without businesses
            [
                'table' => 'products',
                'column' => 'business_id',
                'parent_table' => 'businesses',
                'parent_column' => 'id',
                'description' => 'Products without vendor businesses',
            ],
            // Users without businesses (except admins)
            [
                'table' => 'users',
                'column' => 'business_id',
                'parent_table' => 'businesses',
                'parent_column' => 'id',
                'description' => 'Users without businesses',
                'condition' => "user_type != 'admin'",
            ],
        ];
        
        foreach ($checks as $check) {
            $orphans = $this->findOrphanedRecords($check);
            
            if ($orphans > 0) {
                $this->errors[] = [
                    'type' => 'orphaned_records',
                    'description' => $check['description'],
                    'table' => $check['table'],
                    'count' => $orphans,
                    'severity' => 'high',
                ];
            }
        }
    }
    
    /**
     * Validate data integrity
     */
    protected function validateDataIntegrity(): void
    {
        // Check for invalid ABNs
        $invalidABNs = DB::table('businesses')
            ->whereRaw('LENGTH(abn) != 11')
            ->orWhereRaw('abn NOT REGEXP "^[0-9]+$"')
            ->count();
            
        if ($invalidABNs > 0) {
            $this->errors[] = [
                'type' => 'data_integrity',
                'description' => 'Invalid ABN format',
                'table' => 'businesses',
                'count' => $invalidABNs,
                'severity' => 'medium',
            ];
        }
        
        // Check for invalid email formats
        $invalidEmails = DB::table('users')
            ->whereRaw('email NOT REGEXP "^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$"')
            ->count();
            
        if ($invalidEmails > 0) {
            $this->warnings[] = [
                'type' => 'data_integrity',
                'description' => 'Invalid email format',
                'table' => 'users',
                'count' => $invalidEmails,
                'severity' => 'low',
            ];
        }
        
        // Check for negative quantities
        $negativeQuantities = DB::table('products')
            ->where('stock_quantity', '<', 0)
            ->count();
            
        if ($negativeQuantities > 0) {
            $this->errors[] = [
                'type' => 'data_integrity',
                'description' => 'Negative stock quantities',
                'table' => 'products',
                'count' => $negativeQuantities,
                'severity' => 'high',
            ];
        }
        
        // Check for invalid dates
        $this->validateDateConsistency();
    }
    
    /**
     * Validate business rules
     */
    protected function validateBusinessRules(): void
    {
        // Orders with invalid status transitions
        $invalidStatusTransitions = DB::table('orders as o1')
            ->join('orders as o2', 'o1.id', '=', 'o2.id')
            ->where('o1.status', 'pending')
            ->where('o2.delivered_at', '!=', null)
            ->count();
            
        if ($invalidStatusTransitions > 0) {
            $this->errors[] = [
                'type' => 'business_rule_violation',
                'description' => 'Orders with invalid status transitions',
                'count' => $invalidStatusTransitions,
                'severity' => 'medium',
            ];
        }
        
        // Products with price inconsistencies
        $priceInconsistencies = DB::table('products')
            ->whereRaw('base_price > compare_price')
            ->where('compare_price', '>', 0)
            ->count();
            
        if ($priceInconsistencies > 0) {
            $this->warnings[] = [
                'type' => 'business_rule_violation',
                'description' => 'Products where base price exceeds compare price',
                'table' => 'products',
                'count' => $priceInconsistencies,
                'severity' => 'low',
            ];
        }
        
        // Validate credit limits
        $creditExceeded = DB::table('businesses')
            ->whereRaw('outstanding_balance > credit_limit')
            ->where('credit_limit', '>', 0)
            ->count();
            
        if ($creditExceeded > 0) {
            $this->errors[] = [
                'type' => 'business_rule_violation',
                'description' => 'Businesses exceeding credit limits',
                'table' => 'businesses',
                'count' => $creditExceeded,
                'severity' => 'high',
            ];
        }
    }
    
    /**
     * Validate financial consistency
     */
    protected function validateFinancialConsistency(): void
    {
        // Validate order totals
        $incorrectTotals = DB::select("
            SELECT COUNT(*) as count
            FROM orders o
            WHERE ABS(o.total_amount - (o.subtotal + o.tax_amount + o.delivery_fee - o.discount_amount)) > 0.01
        ")[0]->count;
        
        if ($incorrectTotals > 0) {
            $this->errors[] = [
                'type' => 'financial_inconsistency',
                'description' => 'Orders with incorrect total calculations',
                'table' => 'orders',
                'count' => $incorrectTotals,
                'severity' => 'critical',
            ];
        }
        
        // Validate invoice balances
        $incorrectBalances = DB::select("
            SELECT COUNT(*) as count
            FROM invoices
            WHERE ABS(balance_due - (total_amount - amount_paid)) > 0.01
        ")[0]->count;
        
        if ($incorrectBalances > 0) {
            $this->errors[] = [
                'type' => 'financial_inconsistency',
                'description' => 'Invoices with incorrect balance calculations',
                'table' => 'invoices',
                'count' => $incorrectBalances,
                'severity' => 'critical',
            ];
        }
        
        // Validate payment transaction sums
        $this->validatePaymentTransactions();
    }
    
    /**
     * Validate inventory consistency
     */
    protected function validateInventoryConsistency(): void
    {
        // Check for oversold products
        $oversoldProducts = DB::select("
            SELECT COUNT(DISTINCT p.id) as count
            FROM products p
            JOIN (
                SELECT product_id, SUM(quantity) as total_ordered
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ('confirmed', 'processing', 'ready')
                GROUP BY product_id
            ) AS pending_orders ON p.id = pending_orders.product_id
            WHERE p.stock_quantity < pending_orders.total_ordered
            AND p.track_inventory = 1
            AND p.allow_backorder = 0
        ")[0]->count ?? 0;
        
        if ($oversoldProducts > 0) {
            $this->errors[] = [
                'type' => 'inventory_inconsistency',
                'description' => 'Products oversold (pending orders exceed stock)',
                'count' => $oversoldProducts,
                'severity' => 'high',
            ];
        }
        
        // Check for products below reorder point
        $lowStockProducts = DB::table('products')
            ->whereRaw('stock_quantity <= low_stock_threshold')
            ->where('track_inventory', true)
            ->where('status', 'active')
            ->count();
            
        if ($lowStockProducts > 0) {
            $this->warnings[] = [
                'type' => 'inventory_warning',
                'description' => 'Products at or below low stock threshold',
                'count' => $lowStockProducts,
                'severity' => 'medium',
            ];
        }
    }
    
    /**
     * Validate temporal consistency
     */
    protected function validateTemporalConsistency(): void
    {
        // Orders with invalid date sequences
        $invalidDates = DB::select("
            SELECT COUNT(*) as count
            FROM orders
            WHERE (confirmed_at IS NOT NULL AND confirmed_at < order_date)
            OR (delivered_at IS NOT NULL AND delivered_at < order_date)
            OR (delivered_at IS NOT NULL AND confirmed_at IS NOT NULL AND delivered_at < confirmed_at)
        ")[0]->count;
        
        if ($invalidDates > 0) {
            $this->errors[] = [
                'type' => 'temporal_inconsistency',
                'description' => 'Orders with invalid date sequences',
                'table' => 'orders',
                'count' => $invalidDates,
                'severity' => 'medium',
            ];
        }
        
        // Future dates that shouldn't be
        $futureDates = DB::select("
            SELECT COUNT(*) as count
            FROM orders
            WHERE order_date > NOW()
            OR confirmed_at > NOW()
            OR delivered_at > NOW()
        ")[0]->count;
        
        if ($futureDates > 0) {
            $this->warnings[] = [
                'type' => 'temporal_inconsistency',
                'description' => 'Orders with future dates in past events',
                'table' => 'orders',
                'count' => $futureDates,
                'severity' => 'low',
            ];
        }
    }
    
    /**
     * Validate relationship integrity
     */
    protected function validateRelationshipIntegrity(): void
    {
        // Circular references check
        $circularRefs = DB::select("
            SELECT COUNT(*) as count
            FROM categories c1
            JOIN categories c2 ON c1.parent_id = c2.id
            WHERE c2.parent_id = c1.id
        ")[0]->count;
        
        if ($circularRefs > 0) {
            $this->errors[] = [
                'type' => 'relationship_integrity',
                'description' => 'Circular references in category hierarchy',
                'table' => 'categories',
                'count' => $circularRefs,
                'severity' => 'critical',
            ];
        }
        
        // Many-to-many relationship consistency
        $this->validateManyToManyRelationships();
    }
    
    /**
     * Validate duplicate data
     */
    protected function validateDuplicateData(): void
    {
        // Duplicate ABNs
        $duplicateABNs = DB::select("
            SELECT COUNT(*) - COUNT(DISTINCT abn) as count
            FROM businesses
            WHERE abn IS NOT NULL
        ")[0]->count;
        
        if ($duplicateABNs > 0) {
            $this->errors[] = [
                'type' => 'duplicate_data',
                'description' => 'Duplicate ABN entries',
                'table' => 'businesses',
                'count' => $duplicateABNs,
                'severity' => 'critical',
            ];
        }
        
        // Duplicate email addresses
        $duplicateEmails = DB::select("
            SELECT COUNT(*) - COUNT(DISTINCT email) as count
            FROM users
        ")[0]->count;
        
        if ($duplicateEmails > 0) {
            $this->errors[] = [
                'type' => 'duplicate_data',
                'description' => 'Duplicate email addresses',
                'table' => 'users',
                'count' => $duplicateEmails,
                'severity' => 'critical',
            ];
        }
        
        // Duplicate SKUs
        $duplicateSKUs = DB::select("
            SELECT COUNT(*) - COUNT(DISTINCT sku) as count
            FROM products
            WHERE sku IS NOT NULL
        ")[0]->count;
        
        if ($duplicateSKUs > 0) {
            $this->errors[] = [
                'type' => 'duplicate_data',
                'description' => 'Duplicate product SKUs',
                'table' => 'products',
                'count' => $duplicateSKUs,
                'severity' => 'high',
            ];
        }
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequiredFields(): void
    {
        $requiredFieldChecks = [
            ['table' => 'businesses', 'field' => 'abn', 'description' => 'Businesses without ABN'],
            ['table' => 'businesses', 'field' => 'business_name', 'description' => 'Businesses without name'],
            ['table' => 'users', 'field' => 'email', 'description' => 'Users without email'],
            ['table' => 'products', 'field' => 'sku', 'description' => 'Products without SKU'],
            ['table' => 'orders', 'field' => 'order_number', 'description' => 'Orders without order number'],
        ];
        
        foreach ($requiredFieldChecks as $check) {
            if (Schema::hasTable($check['table']) && Schema::hasColumn($check['table'], $check['field'])) {
                $missing = DB::table($check['table'])
                    ->whereNull($check['field'])
                    ->orWhere($check['field'], '')
                    ->count();
                    
                if ($missing > 0) {
                    $this->warnings[] = [
                        'type' => 'missing_required_field',
                        'description' => $check['description'],
                        'table' => $check['table'],
                        'field' => $check['field'],
                        'count' => $missing,
                        'severity' => 'medium',
                    ];
                }
            }
        }
    }
    
    /**
     * Auto-fix identified issues
     */
    protected function autoFixIssues(): void
    {
        DB::transaction(function () {
            // Fix financial calculations
            $this->fixFinancialCalculations();
            
            // Fix inventory issues
            $this->fixInventoryIssues();
            
            // Fix date inconsistencies
            $this->fixDateInconsistencies();
            
            // Clean orphaned records
            $this->cleanOrphanedRecords();
            
            // Fix duplicate data
            $this->fixDuplicateData();
        });
    }
    
    /**
     * Fix financial calculations
     */
    protected function fixFinancialCalculations(): void
    {
        // Fix order totals
        $fixed = DB::update("
            UPDATE orders 
            SET total_amount = subtotal + tax_amount + delivery_fee - discount_amount
            WHERE ABS(total_amount - (subtotal + tax_amount + delivery_fee - discount_amount)) > 0.01
        ");
        
        if ($fixed > 0) {
            $this->fixedIssues[] = [
                'type' => 'financial_calculation',
                'description' => 'Fixed order total calculations',
                'count' => $fixed,
            ];
        }
        
        // Fix invoice balances
        $fixed = DB::update("
            UPDATE invoices 
            SET balance_due = total_amount - amount_paid
            WHERE ABS(balance_due - (total_amount - amount_paid)) > 0.01
        ");
        
        if ($fixed > 0) {
            $this->fixedIssues[] = [
                'type' => 'financial_calculation',
                'description' => 'Fixed invoice balance calculations',
                'count' => $fixed,
            ];
        }
    }
    
    /**
     * Fix inventory issues
     */
    protected function fixInventoryIssues(): void
    {
        // Set negative stock to zero
        $fixed = DB::table('products')
            ->where('stock_quantity', '<', 0)
            ->update(['stock_quantity' => 0]);
            
        if ($fixed > 0) {
            $this->fixedIssues[] = [
                'type' => 'inventory',
                'description' => 'Reset negative stock quantities to zero',
                'count' => $fixed,
            ];
        }
    }
    
    /**
     * Fix date inconsistencies
     */
    protected function fixDateInconsistencies(): void
    {
        // Fix future dates in historical data
        $fixed = DB::update("
            UPDATE orders 
            SET order_date = NOW()
            WHERE order_date > NOW()
        ");
        
        if ($fixed > 0) {
            $this->fixedIssues[] = [
                'type' => 'date_consistency',
                'description' => 'Fixed future dates in order history',
                'count' => $fixed,
            ];
        }
    }
    
    /**
     * Clean orphaned records
     */
    protected function cleanOrphanedRecords(): void
    {
        // Move orphaned order items to archive
        $orphanedItems = DB::select("
            SELECT oi.* 
            FROM order_items oi
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.id IS NULL
        ");
        
        if (count($orphanedItems) > 0) {
            // Archive orphaned items
            foreach ($orphanedItems as $item) {
                Log::warning('Orphaned order item found', ['item_id' => $item->id]);
            }
            
            // Delete orphaned items
            $deleted = DB::delete("
                DELETE oi FROM order_items oi
                LEFT JOIN orders o ON oi.order_id = o.id
                WHERE o.id IS NULL
            ");
            
            $this->fixedIssues[] = [
                'type' => 'orphaned_records',
                'description' => 'Removed orphaned order items',
                'count' => $deleted,
            ];
        }
    }
    
    /**
     * Fix duplicate data
     */
    protected function fixDuplicateData(): void
    {
        // Handle duplicate emails by appending timestamp
        $duplicates = DB::select("
            SELECT email, COUNT(*) as count
            FROM users
            GROUP BY email
            HAVING count > 1
        ");
        
        foreach ($duplicates as $duplicate) {
            $users = DB::table('users')
                ->where('email', $duplicate->email)
                ->orderBy('created_at', 'desc')
                ->skip(1)
                ->get();
                
            foreach ($users as $index => $user) {
                $newEmail = str_replace('@', '_' . ($index + 1) . '@', $user->email);
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['email' => $newEmail]);
                    
                $this->fixedIssues[] = [
                    'type' => 'duplicate_email',
                    'description' => "Changed duplicate email from {$user->email} to {$newEmail}",
                    'user_id' => $user->id,
                ];
            }
        }
    }
    
    /**
     * Helper method to get foreign key constraints
     */
    protected function getForeignKeyConstraints(): array
    {
        return DB::select("
            SELECT 
                TABLE_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE REFERENCED_TABLE_SCHEMA = ?
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [config('database.connections.mysql.database')]);
    }
    
    /**
     * Check foreign key violations
     */
    protected function checkForeignKeyViolations($table, $column, $referencedTable, $referencedColumn): int
    {
        try {
            return DB::select("
                SELECT COUNT(*) as count
                FROM `{$table}` t
                LEFT JOIN `{$referencedTable}` r ON t.`{$column}` = r.`{$referencedColumn}`
                WHERE t.`{$column}` IS NOT NULL
                AND r.`{$referencedColumn}` IS NULL
            ")[0]->count;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Find orphaned records
     */
    protected function findOrphanedRecords(array $check): int
    {
        $query = "
            SELECT COUNT(*) as count
            FROM `{$check['table']}` t
            LEFT JOIN `{$check['parent_table']}` p ON t.`{$check['column']}` = p.`{$check['parent_column']}`
            WHERE t.`{$check['column']}` IS NOT NULL
            AND p.`{$check['parent_column']}` IS NULL
        ";
        
        if (isset($check['condition'])) {
            $query .= " AND t.{$check['condition']}";
        }
        
        try {
            return DB::select($query)[0]->count;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Validate date consistency
     */
    protected function validateDateConsistency(): void
    {
        $tables = ['orders', 'invoices', 'deliveries', 'pickup_bookings'];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $invalidDates = DB::table($table)
                    ->whereRaw('created_at > updated_at')
                    ->count();
                    
                if ($invalidDates > 0) {
                    $this->warnings[] = [
                        'type' => 'date_inconsistency',
                        'description' => 'Records where created_at > updated_at',
                        'table' => $table,
                        'count' => $invalidDates,
                        'severity' => 'low',
                    ];
                }
            }
        }
    }
    
    /**
     * Validate payment transactions
     */
    protected function validatePaymentTransactions(): void
    {
        // Check if order payment totals match transaction sums
        $mismatches = DB::select("
            SELECT COUNT(DISTINCT o.id) as count
            FROM orders o
            LEFT JOIN (
                SELECT payable_id, SUM(amount) as total_paid
                FROM payment_transactions
                WHERE payable_type = 'App\\\\Models\\\\Order'
                AND status = 'completed'
                GROUP BY payable_id
            ) pt ON o.id = pt.payable_id
            WHERE o.payment_status = 'paid'
            AND ABS(o.amount_paid - COALESCE(pt.total_paid, 0)) > 0.01
        ")[0]->count;
        
        if ($mismatches > 0) {
            $this->errors[] = [
                'type' => 'payment_inconsistency',
                'description' => 'Orders where payment records don\'t match amount paid',
                'count' => $mismatches,
                'severity' => 'high',
            ];
        }
    }
    
    /**
     * Validate many-to-many relationships
     */
    protected function validateManyToManyRelationships(): void
    {
        // This would check pivot tables for consistency
        // Implementation depends on specific many-to-many relationships in the system
    }
    
    /**
     * Generate validation report
     */
    protected function generateValidationReport(float $executionTime): array
    {
        $totalIssues = count($this->errors) + count($this->warnings);
        $criticalCount = count(array_filter($this->errors, fn($e) => ($e['severity'] ?? '') === 'critical'));
        
        return [
            'summary' => [
                'status' => $criticalCount > 0 ? 'critical' : ($totalIssues > 0 ? 'warning' : 'healthy'),
                'total_issues' => $totalIssues,
                'critical_issues' => $criticalCount,
                'errors' => count($this->errors),
                'warnings' => count($this->warnings),
                'fixed_issues' => count($this->fixedIssues),
                'execution_time' => $executionTime . ' seconds',
                'validated_at' => Carbon::now()->toDateTimeString(),
            ],
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'fixed_issues' => $this->fixedIssues,
            'recommendations' => $this->generateRecommendations(),
        ];
    }
    
    /**
     * Generate recommendations based on validation results
     */
    protected function generateRecommendations(): array
    {
        $recommendations = [];
        
        if (count($this->errors) > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Run data consistency auto-fix',
                'command' => 'php artisan db:validate --fix',
            ];
        }
        
        $orphanedCount = count(array_filter($this->errors, fn($e) => $e['type'] === 'orphaned_records'));
        if ($orphanedCount > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Review and clean orphaned records',
                'description' => 'Orphaned records can impact performance and data integrity',
            ];
        }
        
        $duplicateCount = count(array_filter($this->errors, fn($e) => $e['type'] === 'duplicate_data'));
        if ($duplicateCount > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Implement unique constraints',
                'description' => 'Add database-level unique constraints to prevent duplicates',
            ];
        }
        
        return $recommendations;
    }
}