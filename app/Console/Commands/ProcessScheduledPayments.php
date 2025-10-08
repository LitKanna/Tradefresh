<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScheduledPayments as ProcessScheduledPaymentsJob;
use Illuminate\Console\Command;

class ProcessScheduledPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:process-scheduled {--dry-run : Show what would be processed without actually processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due scheduled payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled payments...');

        if ($this->option('dry-run')) {
            $this->dryRun();
        } else {
            ProcessScheduledPaymentsJob::dispatch();
            $this->info('Scheduled payments job dispatched successfully.');
        }

        return 0;
    }

    /**
     * Show what would be processed without actually processing
     */
    private function dryRun()
    {
        $duePayments = \App\Models\ScheduledPayment::with(['user', 'paymentMethod', 'invoice'])
            ->due()
            ->get();

        $this->info("Found {$duePayments->count()} scheduled payments due for processing:");

        if ($duePayments->count() > 0) {
            $this->table(
                ['ID', 'User', 'Amount', 'Payment Method', 'Next Payment Date', 'Description'],
                $duePayments->map(function ($payment) {
                    return [
                        $payment->id,
                        $payment->user->name ?? 'N/A',
                        '$' . number_format($payment->amount, 2),
                        $payment->paymentMethod->display_name ?? 'N/A',
                        $payment->next_payment_date->format('Y-m-d H:i:s'),
                        $payment->description ?? 'N/A',
                    ];
                })->toArray()
            );
        }
    }
}