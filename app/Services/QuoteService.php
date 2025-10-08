<?php

namespace App\Services;

use App\Events\VendorQuoteSubmitted;
use App\Models\Quote;
use App\Models\RFQ;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuoteService
{
    /**
     * Create a new quote from vendor for an RFQ
     */
    public function createVendorQuote(array $data, int $vendorId, int $rfqId)
    {
        return DB::transaction(function () use ($data, $vendorId, $rfqId) {
            $vendor = Vendor::findOrFail($vendorId);
            $rfq = RFQ::findOrFail($rfqId);

            // Generate quote number
            $quoteNumber = $this->generateQuoteNumber();

            // Prepare line items if we have quantity/price info
            $lineItems = [];
            if (isset($data['unit_price']) && isset($data['quantity'])) {
                $lineItems[] = [
                    'description' => 'Quote for RFQ #'.$rfq->rfq_number,
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'total' => $data['unit_price'] * $data['quantity'],
                ];
            }

            // Calculate final amount
            $taxAmount = $data['tax_amount'] ?? ($data['total_amount'] * 0.1); // 10% GST
            $deliveryCharge = $data['delivery_cost'] ?? 0;
            $discountAmount = $data['discount_amount'] ?? 0;
            $finalAmount = $data['total_amount'] + $taxAmount + $deliveryCharge - $discountAmount;

            // Create the quote using actual database columns
            $quote = Quote::create([
                'rfq_id' => $rfqId,
                'buyer_id' => $rfq->buyer_id,
                'vendor_id' => $vendorId,
                'quote_number' => $quoteNumber,
                'total_amount' => $data['total_amount'],
                'tax_amount' => $taxAmount,
                'delivery_charge' => $deliveryCharge,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'line_items' => json_encode($lineItems),
                'terms_conditions' => json_encode(['payment_terms' => $data['payment_terms'] ?? 'Net 30']),
                'notes' => $data['special_notes'] ?? null,
                'vendor_message' => $data['vendor_notes'] ?? null,
                'validity_date' => $data['valid_until'] ?? now()->addMinutes(30),  // 30-minute acceptance window
                'proposed_delivery_date' => $data['delivery_date'] ?? $rfq->delivery_date,
                'proposed_delivery_time' => $data['delivery_time'] ?? 'Morning',
                'payment_terms_days' => $data['payment_terms_days'] ?? 30,
                'payment_method' => $data['payment_method'] ?? 'Bank Transfer',
                'is_negotiable' => $data['is_negotiable'] ?? true,
                'revision_number' => 1,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Broadcast the quote to the buyer
            $this->broadcastQuoteToBuyer($quote, $vendor, $rfq);

            Log::info('Quote submitted by vendor', [
                'quote_id' => $quote->id,
                'vendor_id' => $vendorId,
                'rfq_id' => $rfqId,
                'total_amount' => $quote->total_amount,
            ]);

            return $quote;
        });
    }

    /**
     * Broadcast quote to buyer via WebSocket
     */
    private function broadcastQuoteToBuyer($quote, $vendor, $rfq)
    {
        try {
            // Dispatch the broadcast event
            event(new VendorQuoteSubmitted($quote, $vendor, $rfq));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast vendor quote', [
                'quote_id' => $quote->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate unique quote number
     */
    private function generateQuoteNumber()
    {
        $prefix = 'QT';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get quotes for a specific RFQ
     */
    public function getRFQQuotes(int $rfqId)
    {
        return Quote::with(['vendor', 'items'])
            ->where('rfq_id', $rfqId)
            ->where('status', 'pending')
            ->orderBy('total_amount', 'asc')
            ->get();
    }

    /**
     * Accept a quote
     */
    public function acceptQuote(int $quoteId, int $buyerId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('buyer_id', $buyerId)
            ->firstOrFail();

        if (! $quote->canBeAccepted()) {
            throw new \Exception('Quote cannot be accepted');
        }

        $quote->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Close the RFQ
        $quote->rfq->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        // Reject other quotes
        Quote::where('rfq_id', $quote->rfq_id)
            ->where('id', '!=', $quote->id)
            ->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => 'Another quote was accepted',
            ]);

        return $quote;
    }
}
