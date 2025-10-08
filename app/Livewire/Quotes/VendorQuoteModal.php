<?php

namespace App\Livewire\Quotes;

use App\Events\VendorQuoteSubmitted;
use App\Models\Quote;
use App\Models\RFQ;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class VendorQuoteModal extends Component
{
    // Modal State
    public bool $showQuoteModal = false;

    // Selected RFQ Data
    public ?array $selectedRFQ = null;

    // Quote Items (vendor pricing)
    public array $quoteItems = [];

    // Quote metadata
    public string $deliveryTerms = '';

    public string $validUntil = '';

    public ?int $selectedRfqId = null;

    /**
     * Listen for open quote modal event from RFQ panel
     */
    public function getListeners(): array
    {
        return [
            'open-quote-modal' => 'openQuoteModal',
        ];
    }

    /**
     * Open quote modal for specific RFQ
     */
    #[On('open-quote-modal')]
    public function openQuoteModal(int $rfqId): void
    {
        try {
            // Load RFQ from database
            $rfq = RFQ::with(['buyer'])->find($rfqId);

            if (! $rfq) {
                session()->flash('error', 'RFQ not found');

                return;
            }

            // Map to array format
            $items = $rfq->items ?? [];
            $this->selectedRFQ = [
                'id' => $rfq->id,
                'rfq_number' => $rfq->rfq_number ?? 'RFQ-'.str_pad($rfqId, 6, '0', STR_PAD_LEFT),
                'buyer_id' => $rfq->buyer_id,
                'business_name' => $rfq->buyer->business_name ?? 'Unknown Buyer',
                'delivery_date' => $rfq->delivery_date,
                'delivery_time' => $rfq->delivery_time ?? 'Morning',
                'delivery_address' => $rfq->delivery_address ?? '',
                'special_instructions' => $rfq->delivery_instructions ?? $rfq->special_instructions ?? '',
                'items' => collect($items ?: [])->map(function ($item) {
                    return [
                        'name' => $item['product_name'] ?? $item['name'] ?? 'Unknown Product',
                        'quantity' => $item['quantity'] ?? 0,
                        'unit' => $item['unit'] ?? 'kg',
                        'notes' => $item['notes'] ?? null,
                    ];
                })->toArray(),
            ];

            // Initialize quote items with empty pricing
            $this->quoteItems = [];
            $this->validUntil = now()->addDays(7)->format('Y-m-d\TH:i');

            foreach ($this->selectedRFQ['items'] as $index => $item) {
                $this->quoteItems[$index] = [
                    'price' => 0,
                    'available_quantity' => $item['quantity'],
                    'notes' => '',
                ];
            }

            $this->showQuoteModal = true;
            $this->selectedRfqId = $rfqId;

            Log::info('Quote modal opened for RFQ', ['rfq_id' => $rfqId]);
        } catch (\Exception $e) {
            Log::error('Error opening quote modal', [
                'rfq_id' => $rfqId,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'Unable to open quote form');
        }
    }

    /**
     * Close quote modal
     */
    public function closeQuoteModal(): void
    {
        $this->showQuoteModal = false;
        $this->selectedRFQ = null;
        $this->quoteItems = [];
        $this->deliveryTerms = '';
        $this->validUntil = '';
        $this->selectedRfqId = null;
    }

    /**
     * Calculate total quote amount
     */
    public function calculateTotal(): float
    {
        if (! $this->selectedRFQ) {
            return 0;
        }

        $total = 0;
        foreach ($this->selectedRFQ['items'] as $index => $item) {
            $price = floatval($this->quoteItems[$index]['price'] ?? 0);
            $quantity = floatval($this->quoteItems[$index]['available_quantity'] ?? $item['quantity']);
            $total += $price * $quantity;
        }

        return $total;
    }

    /**
     * Submit quote to buyer
     */
    public function submitQuote(): void
    {
        // Validate that all prices are filled
        foreach ($this->quoteItems as $item) {
            if (empty($item['price']) || floatval($item['price']) <= 0) {
                session()->flash('error', 'Please enter prices for all items');

                return;
            }
        }

        $vendor = auth('vendor')->user();

        if (! $vendor) {
            session()->flash('error', 'You must be logged in to submit quotes');
            $this->closeQuoteModal();

            return;
        }

        try {
            $rfq = RFQ::find($this->selectedRFQ['id']);

            if (! $rfq) {
                session()->flash('error', 'RFQ not found');
                $this->closeQuoteModal();

                return;
            }

            // Calculate total and build line items
            $total = 0;
            $lineItems = [];

            foreach ($this->selectedRFQ['items'] as $index => $item) {
                $unitPrice = floatval($this->quoteItems[$index]['price']);
                $quantity = floatval($this->quoteItems[$index]['available_quantity'] ?? $item['quantity']);
                $itemTotal = $unitPrice * $quantity;
                $total += $itemTotal;

                $lineItems[] = [
                    'description' => $item['name'],
                    'quantity' => $quantity,
                    'unit' => $item['unit'] ?? 'kg',
                    'unit_price' => $unitPrice,
                    'total' => $itemTotal,
                    'notes' => $this->quoteItems[$index]['notes'] ?? '',
                ];
            }

            // Create the quote
            $quote = Quote::create([
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'buyer_id' => $rfq->buyer_id,
                'total_amount' => $total,
                'delivery_charge' => 25, // Fixed delivery for now
                'final_amount' => $total + 25,
                'line_items' => $lineItems,
                'notes' => $this->deliveryTerms,
                'validity_date' => $this->validUntil ?: now()->addDays(7),
                'status' => 'submitted',
                'quote_number' => 'Q-'.str_pad(Quote::count() + 1, 6, '0', STR_PAD_LEFT),
                'proposed_delivery_date' => $rfq->delivery_date,
                'payment_terms_days' => 30,
                'submitted_at' => now(),
            ]);

            // Broadcast the quote to the buyer via WebSocket
            event(new VendorQuoteSubmitted($quote, $vendor, $rfq));

            Log::info('Quote submitted successfully', [
                'quote_id' => $quote->id,
                'rfq_id' => $rfq->id,
                'vendor_id' => $vendor->id,
                'buyer_id' => $rfq->buyer_id,
            ]);

            session()->flash('success', 'Quote sent successfully to '.$this->selectedRFQ['business_name']);

            // Close modal and refresh RFQs
            $this->closeQuoteModal();
            $this->dispatch('refreshRfqs');
        } catch (\Exception $e) {
            Log::error('Quote submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Unable to send quote. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.quotes.vendor-quote-modal', [
            'quoteTotalAmount' => $this->calculateTotal(),
        ]);
    }
}
