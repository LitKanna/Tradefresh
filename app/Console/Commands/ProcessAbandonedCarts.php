<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCartAbandonmentRecovery;
use App\Models\Cart;
use App\Models\CartAbandonmentRecovery;
use Illuminate\Console\Command;

class ProcessAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cart:process-abandoned
                          {--dry-run : Run without making changes}
                          {--limit=100 : Maximum number of carts to process}';

    /**
     * The console command description.
     */
    protected $description = 'Process abandoned carts and initiate recovery campaigns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Processing abandoned carts...');

        // Find newly abandoned carts
        $newlyAbandonedCarts = $this->findNewlyAbandonedCarts($limit);
        $this->processNewlyAbandonedCarts($newlyAbandonedCarts, $dryRun);

        // Process existing recovery campaigns
        $existingRecoveries = $this->findPendingRecoveries($limit);
        $this->processExistingRecoveries($existingRecoveries, $dryRun);

        // Clean up expired recoveries
        $expiredCount = $this->cleanupExpiredRecoveries($dryRun);

        $this->info('Abandoned cart processing completed:');
        $this->line("  - New abandonments: {$newlyAbandonedCarts->count()}");
        $this->line("  - Existing recoveries: {$existingRecoveries->count()}");
        $this->line("  - Expired recoveries cleaned: {$expiredCount}");

        return self::SUCCESS;
    }

    /**
     * Find newly abandoned carts
     */
    protected function findNewlyAbandonedCarts(int $limit)
    {
        return Cart::active()
            ->whereNotNull('buyer_id')
            ->where('last_activity_at', '<', now()->subHours(2))
            ->where('items_count', '>', 0)
            ->whereDoesntHave('abandonmentRecovery')
            ->with(['buyer', 'items.product'])
            ->limit($limit)
            ->get();
    }

    /**
     * Process newly abandoned carts
     */
    protected function processNewlyAbandonedCarts($carts, bool $dryRun): void
    {
        foreach ($carts as $cart) {
            if ($dryRun) {
                $this->line("Would create abandonment recovery for cart {$cart->id} (buyer: {$cart->buyer->email})");

                continue;
            }

            try {
                // Mark cart as abandoned
                $cart->markAsAbandoned();

                // Create recovery record should be created automatically via the model
                $recovery = $cart->abandonmentRecovery;

                if ($recovery) {
                    // Schedule first recovery email (after 1 hour from now)
                    ProcessCartAbandonmentRecovery::dispatch($recovery)
                        ->delay(now()->addHour());

                    $this->line("Created recovery for cart {$cart->id} (value: {$cart->total_amount})");
                }

            } catch (\Exception $e) {
                $this->error("Failed to process cart {$cart->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Find existing recoveries that need processing
     */
    protected function findPendingRecoveries(int $limit)
    {
        $recoveries = collect();

        // First email recoveries (after 1 hour of abandonment)
        $firstEmailRecoveries = CartAbandonmentRecovery::needsFirstEmail()
            ->with(['buyer', 'cart.items'])
            ->limit($limit / 3)
            ->get();

        // Second email recoveries (24 hours after first email)
        $secondEmailRecoveries = CartAbandonmentRecovery::needsSecondEmail()
            ->with(['buyer', 'cart.items'])
            ->limit($limit / 3)
            ->get();

        // Third email recoveries (72 hours after second email)
        $thirdEmailRecoveries = CartAbandonmentRecovery::needsThirdEmail()
            ->with(['buyer', 'cart.items'])
            ->limit($limit / 3)
            ->get();

        return $recoveries
            ->concat($firstEmailRecoveries)
            ->concat($secondEmailRecoveries)
            ->concat($thirdEmailRecoveries);
    }

    /**
     * Process existing recoveries
     */
    protected function processExistingRecoveries($recoveries, bool $dryRun): void
    {
        foreach ($recoveries as $recovery) {
            if ($dryRun) {
                $emailType = $recovery->getRecoveryEmailType();
                $this->line("Would send {$emailType} recovery email to {$recovery->buyer->email}");

                continue;
            }

            try {
                ProcessCartAbandonmentRecovery::dispatch($recovery);
                $this->line("Queued recovery processing for {$recovery->buyer->email}");

            } catch (\Exception $e) {
                $this->error("Failed to queue recovery {$recovery->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Clean up expired recoveries
     */
    protected function cleanupExpiredRecoveries(bool $dryRun): int
    {
        $expiredRecoveries = CartAbandonmentRecovery::expired()->get();

        if ($dryRun) {
            $this->line("Would expire {$expiredRecoveries->count()} recovery campaigns");

            return $expiredRecoveries->count();
        }

        $count = 0;
        foreach ($expiredRecoveries as $recovery) {
            try {
                $recovery->markAsExpired();
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to expire recovery {$recovery->id}: {$e->getMessage()}");
            }
        }

        return $count;
    }
}
