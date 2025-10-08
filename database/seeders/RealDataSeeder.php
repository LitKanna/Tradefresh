<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;

class RealDataSeeder extends Seeder
{
    /**
     * Run the database seeds with REAL test data - no placeholders or mocks
     */
    public function run(): void
    {
        // Check if data already exists
        if (User::count() > 0) {
            echo "Users already exist, skipping user creation...\n";
            $createdUsers = User::all();
        } else {
        // Create real users with actual credentials
        $users = [
            [
                'name' => 'Sarah Mitchell',
                'first_name' => 'Sarah',
                'last_name' => 'Mitchell',
                'email' => 'sarah.mitchell@techcorp.com',
                'password' => Hash::make('SecurePass#2024'),
                'phone' => '+1-555-0101',
                'status' => 'active',
                'email_verified_at' => Carbon::now()->subDays(30),
                'timezone' => 'America/New_York',
                'language' => 'en',
                'currency' => 'USD',
            ],
            [
                'name' => 'David Chen',
                'first_name' => 'David',
                'last_name' => 'Chen',
                'email' => 'david.chen@globaltech.io',
                'password' => Hash::make('StrongKey$456'),
                'phone' => '+1-555-0102',
                'status' => 'active',
                'email_verified_at' => Carbon::now()->subDays(45),
                'timezone' => 'America/Los_Angeles',
                'language' => 'en',
                'currency' => 'USD',
            ],
            [
                'name' => 'Emily Rodriguez',
                'first_name' => 'Emily',
                'last_name' => 'Rodriguez',
                'email' => 'emily.r@innovate.co',
                'password' => Hash::make('ComplexPwd!789'),
                'phone' => '+1-555-0103',
                'status' => 'active',
                'email_verified_at' => Carbon::now()->subDays(15),
                'timezone' => 'America/Chicago',
                'language' => 'en',
                'currency' => 'USD',
            ],
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $createdUsers[] = User::create($userData);
        }
        }

        // Create real buyers with complete information
        if (Buyer::count() > 0) {
            echo "Buyers already exist, skipping buyer creation...\n";
            $createdBuyers = Buyer::all();
        } else {
        $buyers = [
            [
                'company_name' => 'Acme Corporation',
                'contact_name' => 'Robert Johnson',
                'email' => 'procurement@acmecorp.com',
                'password' => Hash::make('BuyerPass#123'),
                'phone' => '+1-555-0201',
                'billing_address' => '123 Business Park Drive',
                'billing_suburb' => 'Manhattan',
                'billing_state' => 'NY',
                'billing_country' => 'USA',
                'billing_postcode' => '10001',
                'shipping_address' => '123 Business Park Drive',
                'shipping_suburb' => 'Manhattan',
                'shipping_state' => 'NY',
                'shipping_country' => 'USA',
                'shipping_postcode' => '10001',
                'business_type' => 'distributor',
                'buyer_type' => 'wholesale',
                'preferred_payment_method' => 'bank_transfer',
                'tax_id' => 'US87-1234567',
                'website' => 'https://www.acmecorp.com',
                'notes' => 'Premium client since 2020',
                'credit_limit' => 50000.00,
                'payment_terms' => 'net_30',
                'status' => 'active',
                'verification_status' => 'verified',
            ],
            [
                'company_name' => 'Global Solutions Inc',
                'contact_name' => 'Jennifer Martinez',
                'email' => 'accounts@globalsolutions.net',
                'password' => Hash::make('GlobalKey$456'),
                'phone' => '+1-555-0202',
                'billing_address' => '456 Innovation Way',
                'billing_suburb' => 'SOMA',
                'billing_state' => 'CA',
                'billing_country' => 'USA',
                'billing_postcode' => '94105',
                'shipping_address' => '456 Innovation Way',
                'shipping_suburb' => 'SOMA',
                'shipping_state' => 'CA',
                'shipping_country' => 'USA',
                'shipping_postcode' => '94105',
                'business_type' => 'retailer',
                'buyer_type' => 'premium',
                'preferred_payment_method' => 'credit_card',
                'tax_id' => 'US94-7654321',
                'website' => 'https://www.globalsolutions.net',
                'notes' => 'Enterprise customer, quarterly billing',
                'credit_limit' => 100000.00,
                'payment_terms' => 'net_30',
                'status' => 'active',
                'verification_status' => 'verified',
            ],
            [
                'company_name' => 'TechStart Ventures',
                'contact_name' => 'Michael Anderson',
                'email' => 'billing@techstart.io',
                'password' => Hash::make('StartupSec!789'),
                'phone' => '+1-555-0203',
                'billing_address' => '789 Startup Lane',
                'billing_suburb' => 'Downtown',
                'billing_state' => 'TX',
                'billing_country' => 'USA',
                'billing_postcode' => '78701',
                'shipping_address' => '789 Startup Lane',
                'shipping_suburb' => 'Downtown',
                'shipping_state' => 'TX',
                'shipping_country' => 'USA',
                'shipping_postcode' => '78701',
                'business_type' => 'other',
                'buyer_type' => 'regular',
                'preferred_payment_method' => 'credit_card',
                'tax_id' => 'US73-9876543',
                'website' => 'https://www.techstart.io',
                'notes' => 'Startup customer, prepayment required',
                'credit_limit' => 10000.00,
                'payment_terms' => 'prepaid',
                'status' => 'active',
                'verification_status' => 'verified',
            ],
        ];

        $createdBuyers = [];
        foreach ($buyers as $buyerData) {
            $createdBuyers[] = Buyer::create($buyerData);
        }
        }

        // Create real vendors with complete profiles
        if (Vendor::count() > 0) {
            echo "Vendors already exist, skipping vendor creation...\n";
            $createdVendors = Vendor::all();
        } else {
        $vendors = [
            [
                'name' => 'Premium Supplies Co',
                'email' => 'sales@premiumsupplies.com',
                'phone' => '+1-555-0301',
                'company_name' => 'Premium Supplies Corporation',
                'business_type' => 'B2B Wholesale',
                'address' => '321 Industrial Boulevard',
                'city' => 'Chicago',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '60601',
                'tax_id' => 'US60-1112223',
                'website' => 'https://www.premiumsupplies.com',
                'description' => 'Leading supplier of premium office and industrial supplies',
                'established_year' => 2010,
                'employee_count' => 150,
                'payment_terms' => 'Net 30',
                'shipping_regions' => 'USA, Canada',
                'min_order_value' => 500.00,
                'status' => 'active',
                'verified' => true,
                'rating' => 4.8,
            ],
            [
                'name' => 'Tech Hardware Direct',
                'email' => 'orders@techhardware.net',
                'phone' => '+1-555-0302',
                'company_name' => 'Tech Hardware Direct LLC',
                'business_type' => 'Technology Distributor',
                'address' => '567 Technology Drive',
                'city' => 'Seattle',
                'state' => 'WA',
                'country' => 'USA',
                'postal_code' => '98101',
                'tax_id' => 'US98-4445556',
                'website' => 'https://www.techhardware.net',
                'description' => 'Specialized distributor of computer hardware and IT equipment',
                'established_year' => 2015,
                'employee_count' => 75,
                'payment_terms' => 'Net 15',
                'shipping_regions' => 'USA',
                'min_order_value' => 1000.00,
                'status' => 'active',
                'verified' => true,
                'rating' => 4.6,
            ],
            [
                'name' => 'Professional Services Group',
                'email' => 'contracts@proservices.org',
                'phone' => '+1-555-0303',
                'company_name' => 'Professional Services Group Inc',
                'business_type' => 'Professional Services',
                'address' => '890 Service Center Way',
                'city' => 'Boston',
                'state' => 'MA',
                'country' => 'USA',
                'postal_code' => '02101',
                'tax_id' => 'US02-7778889',
                'website' => 'https://www.proservices.org',
                'description' => 'Comprehensive consulting and professional services provider',
                'established_year' => 2008,
                'employee_count' => 250,
                'payment_terms' => 'Net 60',
                'shipping_regions' => 'USA, Europe',
                'min_order_value' => 2500.00,
                'status' => 'active',
                'verified' => true,
                'rating' => 4.9,
            ],
        ];

        $createdVendors = [];
        foreach ($vendors as $vendorData) {
            $createdVendors[] = Vendor::create($vendorData);
        }
        }

        // Create real invoices with actual business data
        $invoices = [
            [
                'invoice_number' => 'INV-2024-000001',
                'user_id' => $createdUsers[0]->id,
                'buyer_id' => $createdBuyers[0]->id,
                'vendor_id' => $createdVendors[0]->id,
                'status' => 'paid',
                'type' => 'standard',
                'subtotal' => 2500.00,
                'tax_amount' => 200.00,
                'discount_amount' => 50.00,
                'shipping_amount' => 25.00,
                'total_amount' => 2675.00,
                'paid_amount' => 2675.00,
                'balance_due' => 0.00,
                'currency' => 'USD',
                'invoice_date' => Carbon::now()->subDays(45),
                'due_date' => Carbon::now()->subDays(15),
                'paid_date' => Carbon::now()->subDays(20),
                'payment_terms' => 'Net 30',
                'terms_days' => 30,
                'bill_to_name' => 'Acme Corporation',
                'bill_to_company' => 'Acme Corporation',
                'bill_to_address' => '123 Business Park Drive, New York, NY 10001',
                'bill_to_email' => 'procurement@acmecorp.com',
                'bill_to_phone' => '+1-555-0201',
                'po_number' => 'PO-2024-8847',
                'reference_number' => 'REF-AC-001',
                'notes' => 'Thank you for your business',
                'is_recurring' => false,
            ],
            [
                'invoice_number' => 'INV-2024-000002',
                'user_id' => $createdUsers[1]->id,
                'buyer_id' => $createdBuyers[1]->id,
                'vendor_id' => $createdVendors[1]->id,
                'status' => 'partial',
                'type' => 'standard',
                'subtotal' => 15000.00,
                'tax_amount' => 1350.00,
                'discount_amount' => 500.00,
                'shipping_amount' => 0.00,
                'total_amount' => 15850.00,
                'paid_amount' => 8000.00,
                'balance_due' => 7850.00,
                'currency' => 'USD',
                'invoice_date' => Carbon::now()->subDays(30),
                'due_date' => Carbon::now()->addDays(15),
                'payment_terms' => 'Net 45',
                'terms_days' => 45,
                'bill_to_name' => 'Global Solutions Inc',
                'bill_to_company' => 'Global Solutions Inc',
                'bill_to_address' => '456 Innovation Way, San Francisco, CA 94105',
                'bill_to_email' => 'accounts@globalsolutions.net',
                'bill_to_phone' => '+1-555-0202',
                'po_number' => 'PO-2024-9923',
                'reference_number' => 'REF-GS-002',
                'notes' => 'Partial payment received. Balance due by ' . Carbon::now()->addDays(15)->format('Y-m-d'),
                'is_recurring' => false,
            ],
            [
                'invoice_number' => 'INV-2024-000003',
                'user_id' => $createdUsers[2]->id,
                'buyer_id' => $createdBuyers[2]->id,
                'vendor_id' => $createdVendors[2]->id,
                'status' => 'draft',
                'type' => 'proforma',
                'subtotal' => 8500.00,
                'tax_amount' => 680.00,
                'discount_amount' => 0.00,
                'shipping_amount' => 50.00,
                'total_amount' => 9230.00,
                'paid_amount' => 0.00,
                'balance_due' => 9230.00,
                'currency' => 'USD',
                'invoice_date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(7),
                'payment_terms' => 'Due on receipt',
                'terms_days' => 0,
                'bill_to_name' => 'TechStart Ventures',
                'bill_to_company' => 'TechStart Ventures',
                'bill_to_address' => '789 Startup Lane, Austin, TX 78701',
                'bill_to_email' => 'billing@techstart.io',
                'bill_to_phone' => '+1-555-0203',
                'po_number' => 'PO-2024-1156',
                'reference_number' => 'REF-TS-003',
                'notes' => 'Proforma invoice for approval',
                'is_recurring' => false,
            ],
        ];

        foreach ($invoices as $invoiceData) {
            $invoice = Invoice::create($invoiceData);

            // Create real invoice items for each invoice
            $items = $this->getInvoiceItems($invoice->id);
            foreach ($items as $itemData) {
                InvoiceItem::create($itemData);
            }
        }

        echo "Real data seeding completed successfully!\n";
        echo "Created:\n";
        echo "- " . count($createdUsers) . " users\n";
        echo "- " . count($createdBuyers) . " buyers\n";
        echo "- " . count($createdVendors) . " vendors\n";
        echo "- " . count($invoices) . " invoices with items\n";
    }

    /**
     * Get invoice items based on invoice ID
     */
    private function getInvoiceItems($invoiceId): array
    {
        $itemSets = [
            1 => [
                [
                    'invoice_id' => $invoiceId,
                    'product_id' => null,
                    'item_type' => 'product',
                    'description' => 'Professional Laptop Stand',
                    'description' => 'Ergonomic aluminum laptop stand with adjustable height',
                    'sku' => 'TECH-LS-001',
                    'quantity' => 5,
                    'unit_price' => 89.99,
                    'discount_amount' => 10.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 35.20,
                    'total' => 439.95,
                    'sort_order' => 1,
                ],
                [
                    'invoice_id' => $invoiceId,
                    'product_id' => null,
                    'item_type' => 'product',
                    'description' => 'Wireless Keyboard and Mouse Combo',
                    'description' => 'Bluetooth 5.0 keyboard and mouse set',
                    'sku' => 'TECH-KM-002',
                    'quantity' => 10,
                    'unit_price' => 125.00,
                    'discount_amount' => 25.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 98.00,
                    'total' => 1225.00,
                    'sort_order' => 2,
                ],
                [
                    'invoice_id' => $invoiceId,
                    'product_id' => null,
                    'item_type' => 'product',
                    'description' => 'USB-C Hub Multiport Adapter',
                    'description' => '7-in-1 USB-C hub with HDMI, USB 3.0, SD card reader',
                    'sku' => 'TECH-HB-003',
                    'quantity' => 8,
                    'unit_price' => 49.99,
                    'discount_amount' => 15.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 30.79,
                    'total' => 384.92,
                    'sort_order' => 3,
                ],
            ],
            2 => [
                [
                    'invoice_id' => $invoiceId,
                    'product_id' => null,
                    'item_type' => 'product',
                    'description' => 'Enterprise Server Rack 42U',
                    'description' => 'Full-size server rack with cable management',
                    'sku' => 'SRV-RK-042',
                    'quantity' => 2,
                    'unit_price' => 2500.00,
                    'discount_amount' => 250.00,
                    'tax_rate' => 9.00,
                    'tax_amount' => 427.50,
                    'total' => 4750.00,
                    'sort_order' => 1,
                ],
                [
                    'invoice_id' => $invoiceId,
                    'product_id' => null,
                    'item_type' => 'product',
                    'description' => 'Network Switch 48-Port Gigabit',
                    'description' => 'Managed network switch with PoE+ support',
                    'sku' => 'NET-SW-048',
                    'quantity' => 3,
                    'unit_price' => 1800.00,
                    'discount_amount' => 150.00,
                    'tax_rate' => 9.00,
                    'tax_amount' => 471.60,
                    'total' => 5250.00,
                    'sort_order' => 2,
                ],
                [
                    'invoice_id' => $invoiceId,
                    'product_id' => null,
                    'item_type' => 'product',
                    'description' => 'Firewall Security Appliance',
                    'description' => 'Next-gen firewall with threat prevention',
                    'sku' => 'SEC-FW-001',
                    'quantity' => 1,
                    'unit_price' => 5000.00,
                    'discount_amount' => 100.00,
                    'tax_rate' => 9.00,
                    'tax_amount' => 441.00,
                    'total' => 4900.00,
                    'sort_order' => 3,
                ],
            ],
            3 => [
                [
                    'invoice_id' => $invoiceId,
                    'product_id' => null,
                    'item_type' => 'product',
                    'description' => 'Cloud Migration Consulting',
                    'description' => 'AWS cloud migration assessment and planning',
                    'sku' => 'SVC-CM-001',
                    'quantity' => 40,
                    'unit_price' => 150.00,
                    'discount_amount' => 0.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 480.00,
                    'total' => 6000.00,
                    'sort_order' => 1,
                ],
                [
                    'invoice_id' => $invoiceId,
                    'product_id' => null,
                    'item_type' => 'product',
                    'description' => 'Security Audit Service',
                    'description' => 'Comprehensive security assessment and reporting',
                    'sku' => 'SVC-SA-002',
                    'quantity' => 1,
                    'unit_price' => 2500.00,
                    'discount_amount' => 0.00,
                    'tax_rate' => 8.00,
                    'tax_amount' => 200.00,
                    'total' => 2500.00,
                    'sort_order' => 2,
                ],
            ],
        ];

        // Cycle through item sets
        $setIndex = (($invoiceId - 1) % 3) + 1;
        return $itemSets[$setIndex];
    }
}