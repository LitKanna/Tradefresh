<?php

namespace App\Console\Commands;

use App\Services\VendorTrackingService;
use Illuminate\Console\Command;

class CheckVendorTimeouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vendors:check-timeouts 
                            {--cleanup : Also cleanup old activity logs}
                            {--days=30 : Days to keep activity logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for timed-out vendors and mark them offline';

    /**
     * The vendor tracking service
     */
    protected VendorTrackingService $trackingService;

    /**
     * Create a new command instance.
     */
    public function __construct(VendorTrackingService $trackingService)
    {
        parent::__construct();
        $this->trackingService = $trackingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for timed-out vendors...');
        
        // Check for timeouts
        $timedOutVendors = $this->trackingService->checkTimeouts();
        
        if (count($timedOutVendors) > 0) {
            $this->info(sprintf('Marked %d vendors as offline due to timeout', count($timedOutVendors)));
            
            if ($this->output->isVerbose()) {
                $this->table(['Vendor ID'], array_map(fn($id) => [$id], $timedOutVendors));
            }
        } else {
            $this->info('No timed-out vendors found');
        }
        
        // Cleanup old logs if requested
        if ($this->option('cleanup')) {
            $this->info('Cleaning up old activity logs...');
            $days = (int) $this->option('days');
            $deleted = $this->trackingService->cleanupOldLogs($days);
            $this->info(sprintf('Deleted %d old activity logs (older than %d days)', $deleted, $days));
        }
        
        // Display current metrics
        if ($this->output->isVerbose()) {
            $metrics = $this->trackingService->getMetrics();
            $this->newLine();
            $this->info('Current Vendor Metrics:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Online', $metrics['total_online']],
                    ['Total Active', $metrics['total_active']],
                    ['Recently Active (5 min)', $metrics['recently_active']],
                ]
            );
        }
        
        return Command::SUCCESS;
    }
}