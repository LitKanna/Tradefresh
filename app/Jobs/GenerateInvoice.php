<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Invoice\InvoiceGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $sendEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order, bool $sendEmail = true)
    {
        $this->order = $order;
        $this->sendEmail = $sendEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(InvoiceGenerator $invoiceGenerator): void
    {
        try {
            // Generate the invoice
            $invoice = $invoiceGenerator->generateForOrder($this->order);
            
            Log::info('Invoice generated successfully', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'order_id' => $this->order->id,
            ]);
            
            // Send invoice by email if requested
            if ($this->sendEmail) {
                dispatch(new SendInvoiceEmail($invoice));
            }
            
            // Send WhatsApp notification if buyer has WhatsApp enabled
            if ($this->order->buyer->whatsapp_notifications_enabled ?? false) {
                $invoiceGenerator->sendByWhatsApp($invoice);
            }
            
            // Notify the buyer
            $this->order->buyer->notify(new \App\Notifications\InvoiceGenerated($invoice));
            
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice', [
                'error' => $e->getMessage(),
                'order_id' => $this->order->id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Retry the job after 5 minutes
            $this->release(300);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice generation job failed', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);
        
        // Notify admin about the failure
        // Admin::notifyAll(new InvoiceGenerationFailed($this->order, $exception));
    }
}