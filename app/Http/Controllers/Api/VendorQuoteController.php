<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuoteService;
use App\Models\RFQ;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VendorQuoteController extends Controller
{
    private QuoteService $quoteService;

    public function __construct(QuoteService $quoteService)
    {
        $this->quoteService = $quoteService;
    }

    /**
     * Submit a quote for an RFQ
     */
    public function submitQuote(Request $request, $rfqId)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'total_amount' => 'required|numeric|min:0',
                'unit_price' => 'nullable|numeric|min:0',
                'quantity' => 'nullable|numeric|min:0',
                'delivery_date' => 'nullable|date',
                'delivery_cost' => 'nullable|numeric|min:0',
                'payment_terms' => 'nullable|string|max:100',
                'special_notes' => 'nullable|string|max:1000',
                'vendor_notes' => 'nullable|string|max:1000',
                'lead_time_days' => 'nullable|integer|min:0',
                'minimum_order_quantity' => 'nullable|numeric|min:0',
                'sample_available' => 'nullable|boolean',
                'certification_provided' => 'nullable|boolean',
            ]);

            // Check if RFQ exists and is open
            $rfq = RFQ::findOrFail($rfqId);

            if ($rfq->status !== 'open') {
                return response()->json([
                    'success' => false,
                    'message' => 'This RFQ is no longer accepting quotes'
                ], 422);
            }

            // Get vendor from auth
            $vendor = auth('vendor')->user();

            if (!$vendor) {
                // For testing, create a mock vendor
                $vendor = \App\Models\Vendor::first();
                if (!$vendor) {
                    // Create a test vendor if none exists
                    $vendor = \App\Models\Vendor::create([
                        'name' => 'Test Vendor',
                        'email' => 'vendor@test.com',
                        'password' => bcrypt('password'),
                        'company_name' => 'Fresh Produce Co',
                        'phone' => '0412345678',
                        'address' => '123 Market St, Sydney Markets',
                        'abn' => '12345678901',
                        'status' => 'active',
                        'is_verified' => true,
                    ]);
                }
            }

            // Create the quote
            $quote = $this->quoteService->createVendorQuote($validated, $vendor->id, $rfq->id);

            Log::info('Quote submitted successfully', [
                'quote_id' => $quote->id,
                'vendor_id' => $vendor->id,
                'rfq_id' => $rfq->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quote submitted successfully!',
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit vendor quote', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit quote: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test endpoint to simulate vendor quote submission
     */
    public function testSubmitQuote($rfqId)
    {
        try {
            // Get the RFQ
            $rfq = RFQ::findOrFail($rfqId);

            // Parse items from JSON
            $items = json_decode($rfq->items, true) ?? [];

            // Calculate a random total amount based on items
            $baseAmount = 0;
            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? 10;
                $unitPrice = rand(5, 20); // Random price between $5-20
                $baseAmount += $quantity * $unitPrice;
            }

            // Add some variation
            $totalAmount = $baseAmount * (rand(90, 110) / 100);

            // Mock vendor data
            $vendors = [
                ['name' => 'Sydney Fresh Produce', 'rating' => 4.8],
                ['name' => 'Market Gardens Direct', 'rating' => 4.5],
                ['name' => 'Premium Fruits & Veg', 'rating' => 4.9],
                ['name' => 'Organic Valley Suppliers', 'rating' => 4.7],
                ['name' => 'Farmers Direct Market', 'rating' => 4.6],
            ];

            $randomVendor = $vendors[array_rand($vendors)];

            // Get or create a test vendor
            $vendor = \App\Models\Vendor::where('email', 'test' . rand(1, 5) . '@vendor.com')->first();
            if (!$vendor) {
                $vendor = \App\Models\Vendor::create([
                    'name' => $randomVendor['name'],
                    'email' => 'test' . rand(1, 100) . '@vendor.com',
                    'password' => bcrypt('password'),
                    'company_name' => $randomVendor['name'],
                    'phone' => '04' . rand(10000000, 99999999),
                    'address' => rand(1, 100) . ' Market St, Sydney Markets',
                    'abn' => (string)rand(10000000000, 99999999999),
                    'status' => 'active',
                    'is_verified' => true,
                    'rating' => $randomVendor['rating'],
                ]);
            }

            // Create the quote data
            $quoteData = [
                'total_amount' => round($totalAmount, 2),
                'unit_price' => round($totalAmount / max(array_sum(array_column($items, 'quantity')), 1), 2),
                'quantity' => array_sum(array_column($items, 'quantity')),
                'delivery_date' => $rfq->delivery_date,
                'delivery_cost' => rand(20, 100),
                'payment_terms' => 'Net ' . rand(1, 3) * 15,
                'special_notes' => 'Fresh stock available. Same day delivery possible.',
                'lead_time_days' => rand(1, 3),
                'sample_available' => rand(0, 1) == 1,
                'certification_provided' => true,
            ];

            // Submit the quote
            $quote = $this->quoteService->createVendorQuote($quoteData, $vendor->id, $rfq->id);

            return response()->json([
                'success' => true,
                'message' => 'Test quote submitted successfully!',
                'quote' => [
                    'id' => $quote->id,
                    'quote_number' => $quote->quote_number,
                    'vendor_name' => $vendor->company_name,
                    'total_amount' => $quote->total_amount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit test quote', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit test quote: ' . $e->getMessage(),
            ], 500);
        }
    }
}