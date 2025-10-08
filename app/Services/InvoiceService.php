<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * Generate invoice from order
     */
    public function generateFromOrder(Order $order): Invoice
    {
        // Check if invoice already exists
        if ($order->invoice) {
            return $order->invoice;
        }

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'seller_id' => $order->seller_id,
            'status' => $order->payment_status === 'completed' ? 'paid' : 'sent',
            'type' => 'invoice',
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'shipping_cost' => $order->shipping_cost,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'paid_amount' => $order->payment_status === 'completed' ? $order->total_amount : 0,
            'balance_due' => $order->payment_status === 'completed' ? 0 : $order->total_amount,
            'currency' => $order->currency,
            'issue_date' => now(),
            'due_date' => $this->calculateDueDate($order),
            'paid_at' => $order->paid_at,
            'tax_rate' => 10, // GST
            'is_tax_inclusive' => true,
            'payment_terms_days' => $order->payment_terms_days ?? 30,
            'payment_terms_text' => $this->getPaymentTermsText($order),
            'payment_instructions' => $this->getPaymentInstructions($order),
            'seller_company_name' => $order->seller->company_name ?? $order->seller->name,
            'seller_abn' => $order->seller->abn ?? '',
            'seller_address' => $this->formatAddress($order->seller),
            'seller_email' => $order->seller->email,
            'seller_phone' => $order->seller->phone ?? '',
            'buyer_company_name' => $order->billing_company_name,
            'buyer_abn' => $order->billing_abn,
            'buyer_address' => $this->formatBillingAddress($order),
            'buyer_email' => $order->billing_email,
            'buyer_phone' => $order->billing_phone,
            'line_items' => $this->getLineItems($order),
            'notes' => $order->notes,
            'footer_text' => $this->getFooterText(),
            'metadata' => [
                'order_number' => $order->order_number,
                'quote_id' => $order->quote_id,
                'payment_method' => $order->payment_method,
            ],
        ]);

        // Generate PDF
        $this->generatePDF($invoice);

        return $invoice;
    }

    /**
     * Generate invoice from quote
     */
    public function generateFromQuote(Quote $quote): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'buyer_id' => $quote->buyer_id,
            'seller_id' => $quote->vendor_id,
            'status' => 'draft',
            'type' => 'proforma',
            'subtotal' => $quote->subtotal,
            'tax_amount' => $quote->tax_amount,
            'shipping_cost' => $quote->shipping_cost ?? 0,
            'discount_amount' => $quote->discount_amount ?? 0,
            'total_amount' => $quote->total_amount,
            'paid_amount' => 0,
            'balance_due' => $quote->total_amount,
            'currency' => $quote->currency ?? 'AUD',
            'issue_date' => now(),
            'due_date' => now()->addDays($quote->payment_terms ?? 30),
            'tax_rate' => 10,
            'is_tax_inclusive' => true,
            'payment_terms_days' => $quote->payment_terms ?? 30,
            'payment_terms_text' => $this->getPaymentTermsTextFromQuote($quote),
            'seller_company_name' => $quote->vendor->company_name ?? $quote->vendor->name,
            'seller_abn' => $quote->vendor->abn ?? '',
            'seller_address' => $this->formatAddress($quote->vendor),
            'seller_email' => $quote->vendor->email,
            'seller_phone' => $quote->vendor->phone ?? '',
            'buyer_company_name' => $quote->buyer->company_name ?? $quote->buyer->name,
            'buyer_abn' => $quote->buyer->abn ?? '',
            'buyer_address' => $this->formatAddress($quote->buyer),
            'buyer_email' => $quote->buyer->email,
            'buyer_phone' => $quote->buyer->phone ?? '',
            'line_items' => $this->getLineItemsFromQuote($quote),
            'notes' => $quote->notes ?? '',
            'footer_text' => $this->getFooterText(),
            'metadata' => [
                'quote_id' => $quote->id,
                'quote_number' => $quote->quote_number ?? '',
            ],
        ]);

        // Generate PDF
        $this->generatePDF($invoice);

        return $invoice;
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePDF(Invoice $invoice): string
    {
        $data = [
            'invoice' => $invoice,
            'logo' => public_path('images/logo.png'),
            'company' => [
                'name' => config('app.name'),
                'address' => config('marketplace.address'),
                'phone' => config('marketplace.phone'),
                'email' => config('marketplace.email'),
                'abn' => config('marketplace.abn'),
            ],
        ];

        $pdf = Pdf::loadView('invoices.pdf', $data);
        
        // Set paper size and orientation
        $pdf->setPaper('A4', 'portrait');

        // Generate filename
        $filename = "invoices/{$invoice->invoice_number}.pdf";
        
        // Save to storage
        Storage::disk('public')->put($filename, $pdf->output());

        // Update invoice with PDF path
        $invoice->update([
            'pdf_path' => $filename,
            'public_url' => Storage::disk('public')->url($filename),
        ]);

        return $filename;
    }

    /**
     * Send invoice via email
     */
    public function sendInvoice(Invoice $invoice): bool
    {
        try {
            // Generate PDF if not exists
            if (!$invoice->pdf_path) {
                $this->generatePDF($invoice);
            }

            // Send email notification
            $invoice->buyer->notify(new \App\Notifications\InvoiceGenerated($invoice));

            // Mark as sent
            $invoice->markAsSent();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send invoice: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice && preg_match('/INV-(\d{4})(\d{2})-(\d+)/', $lastInvoice->invoice_number, $matches)) {
            $sequence = intval($matches[3]) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $sequence);
    }

    /**
     * Calculate due date based on payment terms
     */
    protected function calculateDueDate(Order $order): Carbon
    {
        $days = $order->payment_terms_days ?? 30;
        
        if ($order->payment_method === 'credit_terms') {
            return now()->addDays($days);
        }

        // Immediate payment for other methods
        return now();
    }

    /**
     * Get payment terms text
     */
    protected function getPaymentTermsText(Order $order): string
    {
        $days = $order->payment_terms_days ?? 30;

        return match($order->payment_method) {
            'credit_terms' => "Net {$days} days",
            'card' => 'Payment upon checkout',
            'bank_transfer' => 'Payment via bank transfer within 7 days',
            default => "Net {$days} days"
        };
    }

    /**
     * Get payment terms text from quote
     */
    protected function getPaymentTermsTextFromQuote(Quote $quote): string
    {
        $days = $quote->payment_terms ?? 30;
        return "Net {$days} days";
    }

    /**
     * Get payment instructions
     */
    protected function getPaymentInstructions(Order $order): string
    {
        return match($order->payment_method) {
            'bank_transfer' => $this->getBankTransferInstructions(),
            'credit_terms' => $this->getCreditTermsInstructions($order),
            default => ''
        };
    }

    /**
     * Get bank transfer instructions
     */
    protected function getBankTransferInstructions(): string
    {
        return "Please transfer the payment to:\n" .
               "Bank: Commonwealth Bank\n" .
               "Account Name: " . config('app.name') . "\n" .
               "BSB: 062-000\n" .
               "Account Number: 1234 5678\n" .
               "Reference: Invoice number";
    }

    /**
     * Get credit terms instructions
     */
    protected function getCreditTermsInstructions(Order $order): string
    {
        $days = $order->payment_terms_days ?? 30;
        return "Payment is due within {$days} days from the invoice date. " .
               "Late payments may incur additional charges.";
    }

    /**
     * Format address for display
     */
    protected function formatAddress($entity): string
    {
        $parts = [];

        if ($entity->address) $parts[] = $entity->address;
        if ($entity->city) $parts[] = $entity->city;
        if ($entity->state) $parts[] = $entity->state;
        if ($entity->postcode) $parts[] = $entity->postcode;
        if ($entity->country) $parts[] = $entity->country;

        return implode(', ', array_filter($parts));
    }

    /**
     * Format billing address from order
     */
    protected function formatBillingAddress(Order $order): string
    {
        $parts = [
            $order->billing_address,
            $order->billing_city,
            $order->billing_state,
            $order->billing_postcode,
            $order->billing_country
        ];

        return implode(', ', array_filter($parts));
    }

    /**
     * Get line items from order
     */
    protected function getLineItems(Order $order): array
    {
        $items = [];

        if ($order->quote && $order->quote->items) {
            foreach ($order->quote->items as $item) {
                $items[] = [
                    'description' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit ?? 'each',
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount ?? 0,
                    'tax' => $item->tax ?? 0,
                    'total' => $item->total_price,
                ];
            }
        }

        return $items;
    }

    /**
     * Get line items from quote
     */
    protected function getLineItemsFromQuote(Quote $quote): array
    {
        $items = [];

        if ($quote->items) {
            foreach ($quote->items as $item) {
                $items[] = [
                    'description' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit ?? 'each',
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount ?? 0,
                    'tax' => $item->tax ?? 0,
                    'total' => $item->total_price,
                ];
            }
        }

        return $items;
    }

    /**
     * Get footer text for invoice
     */
    protected function getFooterText(): string
    {
        return "Thank you for your business!\n" .
               "For any queries regarding this invoice, please contact us at " . 
               config('marketplace.email', 'support@sydneymarkets.com');
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice, float $amount = null): bool
    {
        $paidAmount = $amount ?? $invoice->total_amount;

        return $invoice->update([
            'status' => 'paid',
            'paid_amount' => $paidAmount,
            'balance_due' => max(0, $invoice->total_amount - $paidAmount),
            'paid_at' => now(),
        ]);
    }

    /**
     * Apply late fee to overdue invoice
     */
    public function applyLateFee(Invoice $invoice): bool
    {
        if (!$invoice->is_overdue) {
            return false;
        }

        $lateFee = $invoice->calculateLateFee();

        if ($lateFee <= 0) {
            return false;
        }

        return $invoice->update([
            'late_fee_amount' => $lateFee,
            'total_amount' => $invoice->total_amount + $lateFee,
            'balance_due' => $invoice->balance_due + $lateFee,
        ]);
    }

    /**
     * Create credit note
     */
    public function createCreditNote(Invoice $invoice, float $amount, string $reason): Invoice
    {
        $creditNote = Invoice::create([
            'invoice_number' => $this->generateCreditNoteNumber(),
            'order_id' => $invoice->order_id,
            'buyer_id' => $invoice->buyer_id,
            'seller_id' => $invoice->seller_id,
            'status' => 'sent',
            'type' => 'credit_note',
            'subtotal' => -$amount,
            'tax_amount' => -($amount * 0.10), // 10% GST
            'total_amount' => -($amount * 1.10),
            'currency' => $invoice->currency,
            'issue_date' => now(),
            'seller_company_name' => $invoice->seller_company_name,
            'seller_abn' => $invoice->seller_abn,
            'seller_address' => $invoice->seller_address,
            'seller_email' => $invoice->seller_email,
            'seller_phone' => $invoice->seller_phone,
            'buyer_company_name' => $invoice->buyer_company_name,
            'buyer_abn' => $invoice->buyer_abn,
            'buyer_address' => $invoice->buyer_address,
            'buyer_email' => $invoice->buyer_email,
            'buyer_phone' => $invoice->buyer_phone,
            'line_items' => [
                [
                    'description' => "Credit for Invoice #{$invoice->invoice_number}",
                    'quantity' => 1,
                    'unit_price' => -$amount,
                    'total' => -$amount,
                ]
            ],
            'notes' => "Reason: {$reason}",
            'metadata' => [
                'original_invoice_id' => $invoice->id,
                'original_invoice_number' => $invoice->invoice_number,
                'reason' => $reason,
            ],
        ]);

        // Generate PDF
        $this->generatePDF($creditNote);

        return $creditNote;
    }

    /**
     * Generate unique credit note number
     */
    protected function generateCreditNoteNumber(): string
    {
        $prefix = 'CN';
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastCreditNote = Invoice::where('type', 'credit_note')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastCreditNote && preg_match('/CN-(\d{4})(\d{2})-(\d+)/', $lastCreditNote->invoice_number, $matches)) {
            $sequence = intval($matches[3]) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $sequence);
    }
}