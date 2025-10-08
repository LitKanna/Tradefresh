<?php

namespace Database\Seeders;

use App\Models\Buyer;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorCategory;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComprehensiveB2BSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data to avoid conflicts
        $this->clearExistingData();
        
        // Create warehouses
        $warehouses = $this->createWarehouses();
        
        // Create vendor categories
        $categories = $this->createVendorCategories();
        
        // Create vendors
        $vendors = $this->createVendors($categories);
        
        // Create products
        $products = $this->createProducts($vendors, $categories);
        
        // Create inventory
        $this->createInventory($products, $warehouses);
        
        // Create users
        $users = $this->createUsers();
        
        // Create buyers
        $buyers = $this->createBuyers($users);
        
        // Create customers
        $customers = $this->createCustomers($users);
        
        // Create orders
        $orders = $this->createOrders($buyers, $vendors, $products, $warehouses);
        
        // Create invoices
        $invoices = $this->createInvoices($orders, $buyers, $vendors);
        
        // Create payments
        $this->createPayments($invoices, $buyers);
        
        // Create quotes
        $this->createQuotes($buyers, $vendors, $products);
        
        // Create purchase orders
        $this->createPurchaseOrders($vendors, $buyers, $products, $warehouses);
        
        // Create shipments
        $this->createShipments($orders, $invoices);
        
        // Update analytics summary
        $this->updateAnalyticsSummary();
    }

    private function clearExistingData(): void
    {
        // Disable foreign key constraints
        \DB::statement('PRAGMA foreign_keys = OFF');
        
        // Clear tables in correct order
        \DB::table('analytics_summary')->truncate();
        \DB::table('shipments')->truncate();
        \DB::table('purchase_order_items')->truncate();
        \DB::table('purchase_orders')->truncate();
        \DB::table('quote_items')->truncate();
        \DB::table('quotes')->truncate();
        \DB::table('payments')->truncate();
        \DB::table('invoice_items')->truncate();
        \DB::table('invoices')->truncate();
        \DB::table('order_items')->truncate();
        \DB::table('orders')->truncate();
        \DB::table('inventory')->truncate();
        \DB::table('products')->truncate();
        \DB::table('customers')->truncate();
        \DB::table('buyers')->truncate();
        \DB::table('vendors')->truncate();
        \DB::table('vendor_categories')->truncate();
        \DB::table('warehouses')->truncate();
        
        // Re-enable foreign key constraints
        \DB::statement('PRAGMA foreign_keys = ON');
    }

    private function createWarehouses(): array
    {
        $warehouses = [
            [
                'name' => 'Main Distribution Center',
                'code' => 'WH-MAIN-001',
                'address' => '100 Industrial Way',
                'city' => 'Dallas',
                'state' => 'TX',
                'zip_code' => '75201',
                'country' => 'USA',
                'phone' => '(214) 555-0100',
                'email' => 'main@warehouse.com',
                'manager_name' => 'John Smith',
                'type' => 'main',
                'is_active' => true,
                'operating_hours' => ['monday' => '6:00-22:00', 'tuesday' => '6:00-22:00'],
                'latitude' => 32.7767,
                'longitude' => -96.7970,
            ],
            [
                'name' => 'East Coast Facility',
                'code' => 'WH-EAST-002',
                'address' => '200 Commerce Street',
                'city' => 'Newark',
                'state' => 'NJ',
                'zip_code' => '07102',
                'country' => 'USA',
                'phone' => '(973) 555-0200',
                'email' => 'east@warehouse.com',
                'manager_name' => 'Sarah Johnson',
                'type' => 'satellite',
                'is_active' => true,
            ],
            [
                'name' => 'West Coast Hub',
                'code' => 'WH-WEST-003',
                'address' => '300 Logistics Blvd',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'zip_code' => '90001',
                'country' => 'USA',
                'phone' => '(213) 555-0300',
                'email' => 'west@warehouse.com',
                'manager_name' => 'Michael Chen',
                'type' => 'satellite',
                'is_active' => true,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::create($warehouse);
        }

        return Warehouse::all()->toArray();
    }

    private function createVendorCategories(): array
    {
        $categories = [
            ['name' => 'Electronics', 'slug' => 'electronics', 'description' => 'Electronic products and components'],
            ['name' => 'Office Supplies', 'slug' => 'office-supplies', 'description' => 'Office and stationery supplies'],
            ['name' => 'Industrial Equipment', 'slug' => 'industrial-equipment', 'description' => 'Heavy machinery and tools'],
            ['name' => 'Food & Beverage', 'slug' => 'food-beverage', 'description' => 'Food and beverage products'],
            ['name' => 'Medical Supplies', 'slug' => 'medical-supplies', 'description' => 'Medical and healthcare products'],
            ['name' => 'Construction Materials', 'slug' => 'construction-materials', 'description' => 'Building and construction supplies'],
        ];

        foreach ($categories as $category) {
            VendorCategory::create($category);
        }

        return VendorCategory::all()->toArray();
    }

    private function createVendors($categories): array
    {
        $vendors = [
            [
                'name' => 'John Tech',
                'company_name' => 'TechPro Distributors',
                'email' => 'sales@techpro.com',
                'phone' => '(555) 123-4567',
                'address' => '123 Tech Street',
                'city' => 'San Francisco',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '94025',
                'business_type' => 'Distributor',
                'category_id' => 1,
                'status' => 'active',
                'rating' => 4.8,
                'verified' => true,
                'verified_at' => now(),
                'website' => 'https://techpro.com',
                'established_year' => 2015,
                'employee_count' => 150,
                'annual_revenue' => 5000000,
                'total_reviews' => 125,
                'total_orders' => 450,
                'total_sales' => 2500000,
                'quality_rating' => 4.7,
            ],
            [
                'name' => 'Sarah Office',
                'company_name' => 'Office World Inc',
                'email' => 'contact@officeworld.com',
                'phone' => '(555) 234-5678',
                'address' => '456 Business Ave',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'postal_code' => '10001',
                'business_type' => 'Wholesaler',
                'category_id' => 2,
                'status' => 'active',
                'rating' => 4.5,
                'verified' => true,
                'verified_at' => now(),
                'website' => 'https://officeworld.com',
                'established_year' => 2010,
                'employee_count' => 200,
                'annual_revenue' => 8000000,
                'total_reviews' => 200,
                'total_orders' => 650,
                'total_sales' => 4500000,
                'quality_rating' => 4.4,
            ],
            [
                'name' => 'Mike Industrial',
                'company_name' => 'Industrial Solutions LLC',
                'email' => 'info@industrialsolutions.com',
                'phone' => '(555) 345-6789',
                'address' => '789 Factory Road',
                'city' => 'Detroit',
                'state' => 'MI',
                'country' => 'USA',
                'postal_code' => '48201',
                'business_type' => 'Manufacturer',
                'category_id' => 3,
                'status' => 'active',
                'rating' => 4.6,
                'verified' => true,
                'verified_at' => now(),
                'website' => 'https://industrialsolutions.com',
                'established_year' => 2008,
                'employee_count' => 300,
                'annual_revenue' => 12000000,
                'total_reviews' => 175,
                'total_orders' => 520,
                'total_sales' => 6800000,
                'quality_rating' => 4.5,
            ],
            [
                'name' => 'Lisa Fresh',
                'company_name' => 'FreshFoods Wholesale',
                'email' => 'orders@freshfoods.com',
                'phone' => '(555) 456-7890',
                'address' => '321 Market Street',
                'city' => 'Chicago',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '60601',
                'business_type' => 'Distributor',
                'category_id' => 4,
                'status' => 'active',
                'rating' => 4.7,
                'verified' => true,
                'verified_at' => now(),
                'website' => 'https://freshfoods.com',
                'established_year' => 2012,
                'employee_count' => 180,
                'annual_revenue' => 7500000,
                'total_reviews' => 210,
                'total_orders' => 780,
                'total_sales' => 5200000,
                'quality_rating' => 4.6,
            ],
            [
                'name' => 'Robert Med',
                'company_name' => 'MedSupply Direct',
                'email' => 'sales@medsupply.com',
                'phone' => '(555) 567-8901',
                'address' => '654 Health Plaza',
                'city' => 'Boston',
                'state' => 'MA',
                'country' => 'USA',
                'postal_code' => '02101',
                'business_type' => 'Distributor',
                'category_id' => 5,
                'status' => 'active',
                'rating' => 4.9,
                'verified' => true,
                'verified_at' => now(),
                'website' => 'https://medsupply.com',
                'established_year' => 2009,
                'employee_count' => 250,
                'annual_revenue' => 15000000,
                'total_reviews' => 320,
                'total_orders' => 950,
                'total_sales' => 9500000,
                'quality_rating' => 4.8,
            ],
        ];

        foreach ($vendors as $vendor) {
            Vendor::create($vendor);
        }

        return Vendor::all()->toArray();
    }

    private function createProducts($vendors, $categories): array
    {
        $products = [
            // Electronics
            ['name' => 'Laptop Pro 15"', 'sku' => 'LP-15-2024', 'price' => 1299.99, 'cost' => 950.00, 'quantity' => 150, 'vendor_id' => 1, 'category_id' => 1],
            ['name' => 'Wireless Mouse', 'sku' => 'WM-BT-001', 'price' => 29.99, 'cost' => 15.00, 'quantity' => 500, 'vendor_id' => 1, 'category_id' => 1],
            ['name' => '27" Monitor 4K', 'sku' => 'MON-27-4K', 'price' => 449.99, 'cost' => 320.00, 'quantity' => 75, 'vendor_id' => 1, 'category_id' => 1],
            ['name' => 'USB-C Hub', 'sku' => 'HUB-USBC-7', 'price' => 59.99, 'cost' => 25.00, 'quantity' => 300, 'vendor_id' => 1, 'category_id' => 1],
            
            // Office Supplies
            ['name' => 'Office Chair Ergonomic', 'sku' => 'CHR-ERG-001', 'price' => 399.99, 'cost' => 250.00, 'quantity' => 50, 'vendor_id' => 2, 'category_id' => 2],
            ['name' => 'Standing Desk', 'sku' => 'DSK-STD-001', 'price' => 599.99, 'cost' => 380.00, 'quantity' => 30, 'vendor_id' => 2, 'category_id' => 2],
            ['name' => 'Paper A4 (Box)', 'sku' => 'PPR-A4-500', 'price' => 45.99, 'cost' => 25.00, 'quantity' => 1000, 'vendor_id' => 2, 'category_id' => 2],
            ['name' => 'Pen Set Professional', 'sku' => 'PEN-PRO-12', 'price' => 24.99, 'cost' => 10.00, 'quantity' => 200, 'vendor_id' => 2, 'category_id' => 2],
            
            // Industrial Equipment
            ['name' => 'Power Drill Industrial', 'sku' => 'DRL-IND-750', 'price' => 299.99, 'cost' => 180.00, 'quantity' => 100, 'vendor_id' => 3, 'category_id' => 3],
            ['name' => 'Safety Helmet', 'sku' => 'HLM-SAF-001', 'price' => 39.99, 'cost' => 20.00, 'quantity' => 500, 'vendor_id' => 3, 'category_id' => 3],
            ['name' => 'Tool Set Professional', 'sku' => 'TLS-PRO-150', 'price' => 599.99, 'cost' => 350.00, 'quantity' => 40, 'vendor_id' => 3, 'category_id' => 3],
            ['name' => 'Industrial Gloves (Pack)', 'sku' => 'GLV-IND-100', 'price' => 89.99, 'cost' => 45.00, 'quantity' => 200, 'vendor_id' => 3, 'category_id' => 3],
            
            // Food & Beverage
            ['name' => 'Coffee Beans Premium 5kg', 'sku' => 'COF-PRM-5KG', 'price' => 149.99, 'cost' => 85.00, 'quantity' => 100, 'vendor_id' => 4, 'category_id' => 4],
            ['name' => 'Organic Tea Set', 'sku' => 'TEA-ORG-SET', 'price' => 79.99, 'cost' => 40.00, 'quantity' => 150, 'vendor_id' => 4, 'category_id' => 4],
            ['name' => 'Snack Box Variety', 'sku' => 'SNK-VAR-50', 'price' => 99.99, 'cost' => 55.00, 'quantity' => 80, 'vendor_id' => 4, 'category_id' => 4],
            
            // Medical Supplies
            ['name' => 'Surgical Masks (Box 100)', 'sku' => 'MSK-SRG-100', 'price' => 49.99, 'cost' => 20.00, 'quantity' => 1000, 'vendor_id' => 5, 'category_id' => 5],
            ['name' => 'Hand Sanitizer 5L', 'sku' => 'SAN-HND-5L', 'price' => 39.99, 'cost' => 15.00, 'quantity' => 500, 'vendor_id' => 5, 'category_id' => 5],
            ['name' => 'First Aid Kit Professional', 'sku' => 'FAK-PRO-001', 'price' => 199.99, 'cost' => 120.00, 'quantity' => 100, 'vendor_id' => 5, 'category_id' => 5],
            ['name' => 'Medical Gloves (Box 200)', 'sku' => 'GLV-MED-200', 'price' => 79.99, 'cost' => 35.00, 'quantity' => 800, 'vendor_id' => 5, 'category_id' => 5],
            ['name' => 'Digital Thermometer', 'sku' => 'THM-DIG-001', 'price' => 29.99, 'cost' => 12.00, 'quantity' => 300, 'vendor_id' => 5, 'category_id' => 5],
        ];

        foreach ($products as &$product) {
            // Replace quantity with stock_quantity
            $product['stock_quantity'] = $product['quantity'] ?? 100;
            unset($product['quantity']);
            
            $product['description'] = "High-quality {$product['name']} for professional use";
            $product['slug'] = \Str::slug($product['name']);
            $product['min_order_quantity'] = 10;
            $product['unit'] = 'piece';
            $product['is_active'] = true;
            $product['is_featured'] = rand(0, 1) == 1;
            $product['weight'] = rand(100, 5000) / 100;
            
            Product::create($product);
        }

        return Product::all()->toArray();
    }

    private function createInventory($products, $warehouses): void
    {
        foreach (Product::all() as $product) {
            foreach (Warehouse::all() as $warehouse) {
                $onHand = rand(50, 500);
                $reserved = rand(0, min(50, $onHand));
                
                Inventory::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'quantity_on_hand' => $onHand,
                    'quantity_reserved' => $reserved,
                    'quantity_available' => $onHand - $reserved,
                    'reorder_point' => rand(20, 50),
                    'reorder_quantity' => rand(100, 200),
                    'location' => 'Aisle ' . rand(1, 10) . '-' . chr(rand(65, 72)),
                    'bin_number' => 'BIN-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'last_restocked' => now()->subDays(rand(1, 30)),
                    'last_counted' => now()->subDays(rand(1, 7)),
                ]);
            }
        }
    }

    private function createUsers(): array
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@b2b.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'John Buyer',
                'email' => 'john@buyer.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sarah Manager',
                'email' => 'sarah@company.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Mike Purchaser',
                'email' => 'mike@business.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Lisa Procurement',
                'email' => 'lisa@corp.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }

        return User::all()->toArray();
    }

    private function createBuyers($users): array
    {
        $buyers = [
            [
                'company_name' => 'Tech Solutions Inc',
                'contact_name' => 'John Buyer',
                'email' => 'john@techsolutions.com',
                'password' => Hash::make('password'),
                'phone' => '(555) 111-2222',
                'business_type' => 'retailer',
                'buyer_type' => 'premium',
                'billing_address' => '123 Tech Street',
                'billing_suburb' => 'San Francisco',
                'billing_state' => 'CA',
                'billing_postcode' => '94105',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 50000,
            ],
            [
                'company_name' => 'Global Enterprises',
                'contact_name' => 'Sarah Manager',
                'email' => 'sarah@globalent.com',
                'password' => Hash::make('password'),
                'phone' => '(555) 222-3333',
                'business_type' => 'distributor',
                'buyer_type' => 'wholesale',
                'billing_address' => '456 Business Ave',
                'billing_suburb' => 'New York',
                'billing_state' => 'NY',
                'billing_postcode' => '10001',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 75000,
            ],
            [
                'company_name' => 'Manufacturing Corp',
                'contact_name' => 'Mike Purchaser',
                'email' => 'mike@mfgcorp.com',
                'password' => Hash::make('password'),
                'phone' => '(555) 333-4444',
                'business_type' => 'other',
                'buyer_type' => 'premium',
                'billing_address' => '789 Industrial Blvd',
                'billing_suburb' => 'Detroit',
                'billing_state' => 'MI',
                'billing_postcode' => '48201',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 100000,
            ],
            [
                'company_name' => 'Healthcare Systems',
                'contact_name' => 'Lisa Procurement',
                'email' => 'lisa@healthcare.com',
                'password' => Hash::make('password'),
                'phone' => '(555) 444-5555',
                'business_type' => 'other',
                'buyer_type' => 'regular',
                'billing_address' => '321 Medical Plaza',
                'billing_suburb' => 'Boston',
                'billing_state' => 'MA',
                'billing_postcode' => '02101',
                'status' => 'active',
                'verification_status' => 'verified',
                'credit_limit' => 60000,
            ],
        ];

        foreach ($buyers as $buyer) {
            Buyer::create($buyer);
        }

        return Buyer::all()->toArray();
    }

    private function createCustomers($users): array
    {
        $customers = [
            [
                'customer_code' => 'CUST-001',
                'company_name' => 'Alpha Industries',
                'contact_name' => 'Robert Smith',
                'email' => 'robert@alpha.com',
                'phone' => '(555) 666-7777',
                'billing_address' => '100 Alpha Way, Seattle, WA 98101',
                'shipping_address' => '100 Alpha Way, Seattle, WA 98101',
                'tax_id' => 'TAX-567890',
                'credit_limit' => 50000.00,
                'current_balance' => 12500.00,
                'payment_terms' => 'Net 30',
                'payment_days' => 30,
                'status' => 'active',
                'customer_type' => 'wholesale',
                'assigned_salesperson' => 1,
            ],
            [
                'customer_code' => 'CUST-002',
                'company_name' => 'Beta Corporation',
                'contact_name' => 'Jennifer Davis',
                'email' => 'jennifer@beta.com',
                'phone' => '(555) 777-8888',
                'billing_address' => '200 Beta Street, Portland, OR 97201',
                'shipping_address' => '200 Beta Street, Portland, OR 97201',
                'tax_id' => 'TAX-678901',
                'credit_limit' => 75000.00,
                'current_balance' => 32000.00,
                'payment_terms' => 'Net 45',
                'payment_days' => 45,
                'status' => 'active',
                'customer_type' => 'distributor',
                'assigned_salesperson' => 1,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }

        return Customer::all()->toArray();
    }

    private function createOrders($buyers, $vendors, $products, $warehouses): array
    {
        $orders = [];
        $statuses = ['confirmed', 'preparing', 'ready_for_pickup', 'in_transit', 'delivered', 'completed'];
        
        foreach (Buyer::all() as $buyer) {
            for ($i = 0; $i < rand(3, 8); $i++) {
                $vendor = Vendor::inRandomOrder()->first();
                $warehouse = Warehouse::inRandomOrder()->first();
                $subtotal = 0;
                
                $order = Order::create([
                    'order_number' => 'ORD-' . now()->format('Y') . str_pad(Order::count() + 1, 6, '0', STR_PAD_LEFT),
                    'user_id' => $buyer->user_id,
                    'buyer_id' => $buyer->id,
                    'vendor_id' => $vendor->id,
                    'warehouse_id' => $warehouse->id,
                    'status' => $statuses[array_rand($statuses)],
                    'payment_status' => 'paid',
                    'payment_method' => 'credit_card',
                    'fulfillment_type' => rand(0, 1) ? 'delivery' : 'pickup',
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'shipping_amount' => rand(0, 1) ? rand(10, 50) : 0,
                    'total_amount' => 0,
                    'notes' => 'B2B order for ' . $buyer->company_name,
                    'order_date' => now()->subDays(rand(1, 60)),
                ]);
                
                // Add order items
                $productCount = rand(2, 6);
                $vendorProducts = Product::where('vendor_id', $vendor->id)->inRandomOrder()->take($productCount)->get();
                
                foreach ($vendorProducts as $product) {
                    $quantity = rand(5, 50);
                    $price = $product->price;
                    $total = $quantity * $price;
                    $subtotal += $total;
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'quantity' => $quantity,
                        'unit_price' => $price,
                        'discount_amount' => 0,
                        'tax_amount' => $total * 0.08,
                        'total_price' => $total + ($total * 0.08),
                    ]);
                }
                
                // Update order totals
                $taxAmount = $subtotal * 0.08;
                $order->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $subtotal + $taxAmount + $order->shipping_amount,
                ]);
                
                $orders[] = $order;
            }
        }
        
        return $orders;
    }

    private function createInvoices($orders, $buyers, $vendors): array
    {
        $invoices = [];
        
        foreach ($orders as $order) {
            $invoice = Invoice::create([
                'invoice_number' => 'INV-' . now()->format('Y') . str_pad(Invoice::count() + 1, 6, '0', STR_PAD_LEFT),
                'user_id' => $order->user_id,
                'buyer_id' => $order->buyer_id,
                'vendor_id' => $order->vendor_id,
                'order_id' => $order->id,
                'status' => in_array($order->status, ['completed', 'delivered']) ? 'paid' : 'sent',
                'type' => 'standard',
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'discount_amount' => $order->discount_amount,
                'shipping_amount' => $order->shipping_amount,
                'total_amount' => $order->total_amount,
                'paid_amount' => in_array($order->status, ['completed', 'delivered']) ? $order->total_amount : 0,
                'balance_due' => in_array($order->status, ['completed', 'delivered']) ? 0 : $order->total_amount,
                'currency' => 'USD',
                'invoice_date' => $order->order_date,
                'due_date' => $order->order_date->addDays(30),
                'paid_date' => in_array($order->status, ['completed', 'delivered']) ? $order->order_date->addDays(rand(5, 25)) : null,
                'payment_terms' => 'Net 30',
                'terms_days' => 30,
                'bill_to_name' => $order->buyer->contact_name,
                'bill_to_company' => $order->buyer->company_name,
                'bill_to_address' => $order->buyer->address,
                'bill_to_email' => $order->buyer->email,
                'bill_to_phone' => $order->buyer->phone,
                'notes' => 'Invoice for order ' . $order->order_number,
            ]);
            
            // Create invoice items from order items
            foreach ($order->items as $orderItem) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $orderItem->product_name,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'discount_amount' => $orderItem->discount_amount,
                    'tax_amount' => $orderItem->tax_amount,
                    'total' => $orderItem->total_price,
                    'sort_order' => $orderItem->id,
                ]);
            }
            
            $invoices[] = $invoice;
        }
        
        return $invoices;
    }

    private function createPayments($invoices, $buyers): void
    {
        foreach ($invoices as $invoice) {
            if ($invoice->status === 'paid') {
                Payment::create([
                    'payment_number' => 'PAY-' . now()->format('Y') . str_pad(Payment::count() + 1, 6, '0', STR_PAD_LEFT),
                    'invoice_id' => $invoice->id,
                    'order_id' => $invoice->order_id,
                    'user_id' => $invoice->user_id,
                    'buyer_id' => $invoice->buyer_id,
                    'amount' => $invoice->total_amount,
                    'currency' => 'USD',
                    'status' => 'completed',
                    'payment_method' => 'credit_card',
                    'reference_number' => 'REF-' . strtoupper(uniqid()),
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'processed_at' => $invoice->paid_date,
                    'notes' => 'Payment for invoice ' . $invoice->invoice_number,
                ]);
            }
        }
    }

    private function createQuotes($buyers, $vendors, $products): void
    {
        foreach (Buyer::all() as $buyer) {
            for ($i = 0; $i < rand(2, 4); $i++) {
                $vendor = Vendor::inRandomOrder()->first();
                $subtotal = 0;
                
                $quote = Quote::create([
                    'quote_number' => 'QUO-' . now()->format('Y') . str_pad(Quote::count() + 1, 6, '0', STR_PAD_LEFT),
                    'buyer_id' => $buyer->id,
                    'vendor_id' => $vendor->id,
                    'created_by' => $buyer->user_id,
                    'status' => ['draft', 'sent', 'viewed', 'accepted', 'rejected'][rand(0, 4)],
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => rand(0, 1) ? rand(50, 200) : 0,
                    'shipping_amount' => rand(0, 1) ? rand(10, 50) : 0,
                    'total_amount' => 0,
                    'quote_date' => now()->subDays(rand(1, 30)),
                    'valid_until' => now()->addDays(rand(15, 45)),
                    'notes' => 'Quote requested by ' . $buyer->company_name,
                ]);
                
                // Add quote items
                $productCount = rand(2, 5);
                $vendorProducts = Product::where('vendor_id', $vendor->id)->inRandomOrder()->take($productCount)->get();
                
                foreach ($vendorProducts as $product) {
                    $quantity = rand(10, 100);
                    $price = $product->price * 0.95; // 5% discount for bulk
                    $total = $quantity * $price;
                    $subtotal += $total;
                    
                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'item_name' => $product->name,
                        'description' => $product->description,
                        'quantity' => $quantity,
                        'unit_price' => $price,
                        'discount_amount' => 0,
                        'tax_amount' => $total * 0.08,
                        'total_price' => $total + ($total * 0.08),
                    ]);
                }
                
                // Update quote totals
                $taxAmount = $subtotal * 0.08;
                $quote->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $subtotal + $taxAmount + $quote->shipping_amount - $quote->discount_amount,
                ]);
            }
        }
    }

    private function createPurchaseOrders($vendors, $buyers, $products, $warehouses): void
    {
        foreach (Buyer::all() as $buyer) {
            for ($i = 0; $i < rand(2, 4); $i++) {
                $vendor = Vendor::inRandomOrder()->first();
                $warehouse = Warehouse::inRandomOrder()->first();
                $subtotal = 0;
                
                $po = PurchaseOrder::create([
                    'po_number' => PurchaseOrder::generatePoNumber(),
                    'vendor_id' => $vendor->id,
                    'buyer_id' => $buyer->id,
                    'created_by' => $buyer->user_id,
                    'status' => ['pending', 'approved', 'ordered', 'received'][rand(0, 3)],
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'shipping_amount' => rand(50, 200),
                    'total_amount' => 0,
                    'order_date' => now()->subDays(rand(1, 45)),
                    'expected_date' => now()->addDays(rand(7, 30)),
                    'ship_to_warehouse' => $warehouse->code,
                    'shipping_address' => $warehouse->address . ', ' . $warehouse->city . ', ' . $warehouse->state . ' ' . $warehouse->zip_code,
                    'payment_terms' => 'Net 30',
                    'notes' => 'Purchase order for warehouse restocking',
                ]);
                
                // Add PO items
                $productCount = rand(3, 7);
                $vendorProducts = Product::where('vendor_id', $vendor->id)->inRandomOrder()->take($productCount)->get();
                
                foreach ($vendorProducts as $product) {
                    $quantity = rand(50, 200);
                    $price = $product->cost ?? $product->price * 0.7;
                    $total = $quantity * $price;
                    $subtotal += $total;
                    
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'product_id' => $product->id,
                        'item_name' => $product->name,
                        'item_sku' => $product->sku,
                        'description' => $product->description,
                        'quantity_ordered' => $quantity,
                        'quantity_received' => $po->status === 'received' ? $quantity : rand(0, $quantity),
                        'unit_price' => $price,
                        'total_price' => $total,
                    ]);
                }
                
                // Update PO totals
                $taxAmount = $subtotal * 0.08;
                $po->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $subtotal + $taxAmount + $po->shipping_amount,
                    'received_date' => $po->status === 'received' ? now()->subDays(rand(1, 5)) : null,
                ]);
            }
        }
    }

    private function createShipments($orders, $invoices): void
    {
        $carriers = ['UPS', 'FedEx', 'USPS', 'DHL'];
        $serviceTypes = ['ground', 'express', 'priority', 'overnight'];
        
        foreach ($orders as $order) {
            if (in_array($order->status, ['in_transit', 'delivered', 'completed'])) {
                $shipment = Shipment::create([
                    'tracking_number' => strtoupper(uniqid('TRK')),
                    'order_id' => $order->id,
                    'invoice_id' => Invoice::where('order_id', $order->id)->first()?->id,
                    'carrier' => $carriers[array_rand($carriers)],
                    'service_type' => $serviceTypes[array_rand($serviceTypes)],
                    'status' => $order->status === 'in_transit' ? 'in_transit' : 'delivered',
                    'weight' => rand(100, 5000) / 100,
                    'weight_unit' => 'lbs',
                    'dimensions' => ['length' => rand(10, 50), 'width' => rand(10, 40), 'height' => rand(5, 30)],
                    'shipping_cost' => $order->shipping_amount,
                    'ship_from_address' => $order->vendor->address,
                    'ship_to_address' => $order->buyer->address,
                    'shipped_at' => $order->order_date->addDays(rand(1, 3)),
                    'delivered_at' => in_array($order->status, ['delivered', 'completed']) ? $order->order_date->addDays(rand(4, 7)) : null,
                ]);
            }
        }
    }

    private function updateAnalyticsSummary(): void
    {
        // Create analytics summary data for the dashboard
        $dates = [];
        for ($i = 30; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->format('Y-m-d');
        }
        
        foreach ($dates as $date) {
            $dateObj = \Carbon\Carbon::parse($date);
            
            // Revenue metrics
            $dailyRevenue = Invoice::whereDate('invoice_date', $date)
                ->where('status', 'paid')
                ->sum('total_amount');
            
            \DB::table('analytics_summary')->updateOrInsert(
                ['date' => $date, 'metric_type' => 'revenue', 'metric_name' => 'daily_revenue'],
                ['value' => $dailyRevenue, 'created_at' => now(), 'updated_at' => now()]
            );
            
            // Order metrics
            $dailyOrders = Order::whereDate('order_date', $date)->count();
            
            \DB::table('analytics_summary')->updateOrInsert(
                ['date' => $date, 'metric_type' => 'orders', 'metric_name' => 'daily_orders'],
                ['value' => $dailyOrders, 'created_at' => now(), 'updated_at' => now()]
            );
            
            // Customer metrics
            $newCustomers = Buyer::whereDate('created_at', $date)->count();
            
            \DB::table('analytics_summary')->updateOrInsert(
                ['date' => $date, 'metric_type' => 'customers', 'metric_name' => 'new_customers'],
                ['value' => $newCustomers, 'created_at' => now(), 'updated_at' => now()]
            );
            
            // Product metrics
            $productsSold = OrderItem::whereHas('order', function($q) use ($date) {
                $q->whereDate('order_date', $date);
            })->sum('quantity');
            
            \DB::table('analytics_summary')->updateOrInsert(
                ['date' => $date, 'metric_type' => 'products', 'metric_name' => 'products_sold'],
                ['value' => $productsSold, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}