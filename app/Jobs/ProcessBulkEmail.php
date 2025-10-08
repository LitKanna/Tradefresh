<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessBulkEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 120;

    protected $recipients;

    protected $emailData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $recipients, array $emailData)
    {
        $this->recipients = $recipients;
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing bulk email job', [
            'recipients_count' => count($this->recipients),
            'subject' => $this->emailData['subject'] ?? 'No subject',
        ]);

        foreach ($this->recipients as $recipient) {
            try {
                // Here you would send the actual email
                // For now, we'll just log it
                Log::info('Sending email', [
                    'to' => $recipient['email'],
                    'name' => $recipient['name'] ?? 'User',
                ]);

                // In production, you would use:
                // Mail::to($recipient['email'])->send(new BulkEmailNotification($this->emailData));

                // Add a small delay to avoid overwhelming the mail server
                usleep(100000); // 0.1 second delay

            } catch (\Exception $e) {
                Log::error('Failed to send email', [
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage(),
                ]);

                // Continue with other recipients even if one fails
                continue;
            }
        }

        Log::info('Bulk email job completed successfully');
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk email job failed', [
            'error' => $exception->getMessage(),
            'recipients_count' => count($this->recipients),
        ]);
    }
}
