<?php

namespace App\Jobs;

use App\Models\Order;
use App\Mail\OrderConfirmationEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Order $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load necessary relationships
        $this->order->loadMissing([
            'buyer',
            'vendor',
            'items.product',
            'invoice',
            'payments'
        ]);

        try {
            // Send confirmation email to buyer
            Mail::to($this->order->buyer->email)
                ->send(new OrderConfirmationEmail($this->order, 'buyer'));

            // Send order notification to vendor
            Mail::to($this->order->vendor->email)
                ->send(new OrderConfirmationEmail($this->order, 'vendor'));

            // Log successful email sending
            \Log::info('Order confirmation emails sent', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'buyer_email' => $this->order->buyer->email,
                'vendor_email' => $this->order->vendor->email,
                'order_total' => $this->order->total_amount
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send order confirmation email', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'error' => $e->getMessage()
            ]);

            // Re-throw to trigger job retry
            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Order confirmation email job failed', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'buyer_id' => $this->order->buyer_id,
            'vendor_id' => $this->order->vendor_id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Could send admin notification about failed email
        // or implement fallback notification system
    }
}