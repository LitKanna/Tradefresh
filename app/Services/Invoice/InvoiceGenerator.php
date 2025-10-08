<?php

namespace App\Services\Invoice;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\CreditNote;
use App\Services\Invoice\InvoiceNumbering;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class InvoiceGenerator
{
    protected $numberingService;
    
    public function __construct(InvoiceNumbering $numberingService)
    {
        $this->numberingService = $numberingService;
    }
    
    /**
     * Generate invoice for an order
     */
    public function generateForOrder(Order $order): Invoice
    {
        // Check if invoice already exists
        if ($order->invoice && $order->invoice->status === 'final') {
            return $order->invoice;
        }
        
        // Generate invoice number
        $invoiceNumber = $this->numberingService->generateInvoiceNumber();
        
        // Calculate totals
        $subtotal = $order->items->sum('total_price');
        $gstAmount = $subtotal * 0.10; // 10% GST
        $totalAmount = $subtotal + $gstAmount + $order->delivery_fee;
        
        // Create or update invoice
        $invoice = Invoice::updateOrCreate(
            ['order_id' => $order->id],
            [
                'invoice_number' => $invoiceNumber,
                'buyer_id' => $order->buyer_id,
                'vendor_id' => $order->vendor_id,
                'issue_date' => now(),
                'due_date' => $order->payment_due_date ?? now()->addDays(7),
                'subtotal' => $subtotal,
                'gst_amount' => $gstAmount,
                'delivery_fee' => $order->delivery_fee,
                'total_amount' => $totalAmount,
                'status' => $order->payment_status === 'paid' ? 'final' : 'draft',
                'type' => 'tax_invoice',
                'payment_terms' => $order->payment?->payment_terms ?? 7,
                'payment_method' => $order->payment_method,
                'buyer_details' => [
                    'name' => $order->buyer->business_name ?: $order->buyer->name,
                    'abn' => $order->buyer->abn,
                    'address' => $order->buyer->address,
                    'suburb' => $order->buyer->suburb,
                    'state' => $order->buyer->state,
                    'postcode' => $order->buyer->postcode,
                    'email' => $order->buyer->email,
                    'phone' => $order->buyer->phone,
                ],
                'vendor_details' => [
                    'name' => $order->vendor->business_name,
                    'abn' => $order->vendor->abn,
                    'stall_number' => $order->vendor->stall_number,
                    'address' => $order->vendor->address,
                    'suburb' => $order->vendor->suburb,
                    'state' => $order->vendor->state,
                    'postcode' => $order->vendor->postcode,
                    'email' => $order->vendor->email,
                    'phone' => $order->vendor->phone,
                ],
                'line_items' => $order->items->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'description' => $item->product->description,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'gst_amount' => $item->total_price * 0.10,
                    ];
                })->toArray(),
            ]
        );
        
        // Generate PDF
        $this->generatePDF($invoice);
        
        return $invoice;
    }
    
    /**
     * Generate proforma invoice
     */
    public function generateProforma(Order $order): Invoice
    {
        $invoiceNumber = $this->numberingService->generateProformaNumber();
        
        $subtotal = $order->items->sum('total_price');
        $gstAmount = $subtotal * 0.10;
        $totalAmount = $subtotal + $gstAmount + $order->delivery_fee;
        
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'vendor_id' => $order->vendor_id,
            'issue_date' => now(),
            'due_date' => now(),
            'subtotal' => $subtotal,
            'gst_amount' => $gstAmount,
            'delivery_fee' => $order->delivery_fee,
            'total_amount' => $totalAmount,
            'status' => 'proforma',
            'type' => 'proforma',
            'payment_method' => $order->payment_method,
            'buyer_details' => [
                'name' => $order->buyer->business_name ?: $order->buyer->name,
                'abn' => $order->buyer->abn,
                'address' => $order->buyer->address,
                'suburb' => $order->buyer->suburb,
                'state' => $order->buyer->state,
                'postcode' => $order->buyer->postcode,
            ],
            'vendor_details' => [
                'name' => $order->vendor->business_name,
                'abn' => $order->vendor->abn,
                'stall_number' => $order->vendor->stall_number,
            ],
            'line_items' => $order->items->map(function ($item) {
                return [
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ];
            })->toArray(),
        ]);
        
        $this->generatePDF($invoice);
        
        return $invoice;
    }
    
    /**
     * Generate credit note
     */
    public function generateCreditNote(Order $order, float $amount, string $reason = null): CreditNote
    {
        $creditNoteNumber = $this->numberingService->generateCreditNoteNumber();
        $originalInvoice = $order->invoice;
        
        $gstAmount = $amount * 0.10;
        $totalAmount = $amount + $gstAmount;
        
        $creditNote = CreditNote::create([
            'credit_note_number' => $creditNoteNumber,
            'invoice_id' => $originalInvoice?->id,
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'vendor_id' => $order->vendor_id,
            'issue_date' => now(),
            'amount' => $amount,
            'gst_amount' => $gstAmount,
            'total_amount' => $totalAmount,
            'reason' => $reason ?? 'Refund requested',
            'status' => 'issued',
            'original_invoice_number' => $originalInvoice?->invoice_number,
            'buyer_details' => [
                'name' => $order->buyer->business_name ?: $order->buyer->name,
                'abn' => $order->buyer->abn,
            ],
            'vendor_details' => [
                'name' => $order->vendor->business_name,
                'abn' => $order->vendor->abn,
            ],
        ]);
        
        // Generate PDF
        $this->generateCreditNotePDF($creditNote);
        
        return $creditNote;
    }
    
    /**
     * Generate RCTI (Recipient Created Tax Invoice)
     */
    public function generateRCTI(Order $order): Invoice
    {
        $invoiceNumber = $this->numberingService->generateRCTINumber();
        
        $subtotal = $order->items->sum('total_price');
        $gstAmount = $subtotal * 0.10;
        $totalAmount = $subtotal + $gstAmount + $order->delivery_fee;
        
        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'order_id' => $order->id,
            'buyer_id' => $order->buyer_id,
            'vendor_id' => $order->vendor_id,
            'issue_date' => now(),
            'due_date' => $order->payment_due_date ?? now()->addDays(7),
            'subtotal' => $subtotal,
            'gst_amount' => $gstAmount,
            'delivery_fee' => $order->delivery_fee,
            'total_amount' => $totalAmount,
            'status' => 'final',
            'type' => 'rcti',
            'payment_terms' => $order->payment?->payment_terms ?? 7,
            'payment_method' => $order->payment_method,
            'rcti_agreement' => [
                'agreed_date' => $order->vendor->rcti_agreement_date,
                'agreement_number' => $order->vendor->rcti_agreement_number,
            ],
            'buyer_details' => [
                'name' => $order->buyer->business_name ?: $order->buyer->name,
                'abn' => $order->buyer->abn,
                'address' => $order->buyer->address,
                'suburb' => $order->buyer->suburb,
                'state' => $order->buyer->state,
                'postcode' => $order->buyer->postcode,
            ],
            'vendor_details' => [
                'name' => $order->vendor->business_name,
                'abn' => $order->vendor->abn,
                'stall_number' => $order->vendor->stall_number,
            ],
            'line_items' => $order->items->map(function ($item) {
                return [
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'gst_amount' => $item->total_price * 0.10,
                ];
            })->toArray(),
        ]);
        
        $this->generatePDF($invoice);
        
        return $invoice;
    }
    
    /**
     * Generate PDF for invoice
     */
    protected function generatePDF(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('invoices.template', [
            'invoice' => $invoice,
            'order' => $invoice->order,
        ]);
        
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
        $path = storage_path('app/public/' . $filename);
        
        // Ensure directory exists
        Storage::disk('public')->makeDirectory('invoices');
        
        $pdf->save($path);
        
        // Update invoice with PDF path
        $invoice->update(['pdf_path' => $filename]);
        
        return $filename;
    }
    
    /**
     * Generate PDF for credit note
     */
    protected function generateCreditNotePDF(CreditNote $creditNote): string
    {
        $pdf = Pdf::loadView('invoices.credit-note', [
            'creditNote' => $creditNote,
            'order' => $creditNote->order,
        ]);
        
        $pdf->setPaper('A4', 'portrait');
        
        $filename = 'credit-notes/' . $creditNote->credit_note_number . '.pdf';
        $path = storage_path('app/public/' . $filename);
        
        Storage::disk('public')->makeDirectory('credit-notes');
        
        $pdf->save($path);
        
        $creditNote->update(['pdf_path' => $filename]);
        
        return $filename;
    }
    
    /**
     * Send invoice by email
     */
    public function sendByEmail(Invoice $invoice): void
    {
        if (!$invoice->pdf_path) {
            $this->generatePDF($invoice);
        }
        
        Mail::to($invoice->buyer->email)
            ->cc($invoice->vendor->email)
            ->send(new \App\Mail\InvoiceEmail($invoice));
        
        $invoice->update([
            'sent_at' => now(),
            'sent_via' => 'email',
        ]);
    }
    
    /**
     * Send invoice by WhatsApp
     */
    public function sendByWhatsApp(Invoice $invoice): void
    {
        if (!$invoice->pdf_path) {
            $this->generatePDF($invoice);
        }
        
        $whatsappService = app(\App\Services\WhatsApp\WhatsAppService::class);
        
        $pdfUrl = config('app.url') . '/storage/' . $invoice->pdf_path;
        
        $message = "Tax Invoice #{$invoice->invoice_number}\n";
        $message .= "Order: #{$invoice->order->order_number}\n";
        $message .= "Amount: AUD " . number_format($invoice->total_amount, 2) . "\n";
        $message .= "Due Date: " . $invoice->due_date->format('d/m/Y') . "\n";
        $message .= "Download: " . $pdfUrl;
        
        $whatsappService->sendMessage($invoice->buyer->phone, $message);
        
        $invoice->update([
            'sent_at' => now(),
            'sent_via' => 'whatsapp',
        ]);
    }
    
    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice): void
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        
        // Update order payment status
        if ($invoice->order) {
            $invoice->order->update([
                'payment_status' => 'paid',
                'paid_at' => now(),
            ]);
        }
    }
    
    /**
     * Void an invoice
     */
    public function voidInvoice(Invoice $invoice, string $reason): void
    {
        $invoice->update([
            'status' => 'void',
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
    }
}