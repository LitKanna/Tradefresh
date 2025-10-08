<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Mail\InvoiceEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendInvoiceEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoice;
    protected $recipients;

    /**
     * Create a new job instance.
     */
    public function __construct(Invoice $invoice, array $recipients = [])
    {
        $this->invoice = $invoice;
        $this->recipients = $recipients;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // If no recipients specified, use default
            if (empty($this->recipients)) {
                $this->recipients = [
                    'to' => $this->invoice->buyer->email,
                    'cc' => $this->invoice->buyer->accounts_email ?? null,
                    'bcc' => config('mail.admin_email'),
                ];
            }
            
            // Build the mail
            $mail = Mail::to($this->recipients['to']);
            
            if (!empty($this->recipients['cc'])) {
                $mail->cc($this->recipients['cc']);
            }
            
            if (!empty($this->recipients['bcc'])) {
                $mail->bcc($this->recipients['bcc']);
            }
            
            // Send the email
            $mail->send(new InvoiceEmail($this->invoice));
            
            // Update invoice status
            $this->invoice->update([
                'sent_at' => now(),
                'sent_via' => 'email',
                'status' => $this->invoice->status === 'draft' ? 'sent' : $this->invoice->status,
            ]);
            
            Log::info('Invoice email sent successfully', [
                'invoice_id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
                'recipient' => $this->recipients['to'],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send invoice email', [
                'error' => $e->getMessage(),
                'invoice_id' => $this->invoice->id,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Retry the job after 10 minutes
            $this->release(600);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice email job failed', [
            'invoice_id' => $this->invoice->id,
            'error' => $exception->getMessage(),
        ]);
    }
}