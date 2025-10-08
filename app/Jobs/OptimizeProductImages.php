<?php

namespace App\Jobs;

use App\Services\ImageOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OptimizeProductImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300; // 5 minutes for image processing

    protected $imagePath;

    protected $options;

    /**
     * Create a new job instance.
     */
    public function __construct(string $imagePath, array $options = [])
    {
        $this->imagePath = $imagePath;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(ImageOptimizationService $optimizer): void
    {
        Log::info('Starting image optimization job', [
            'image' => $this->imagePath,
        ]);

        try {
            $result = $optimizer->optimize($this->imagePath, $this->options);

            if (isset($result['success'])) {
                Log::info('Image optimized successfully', [
                    'image' => $this->imagePath,
                    'original_size' => $result['original_size'],
                    'new_size' => $result['new_size'],
                    'reduction' => $result['reduction'],
                ]);

                // Generate responsive sizes if requested
                if ($this->options['generate_sizes'] ?? false) {
                    $responsiveResults = $optimizer->generateResponsiveSizes($this->imagePath);

                    Log::info('Generated responsive image sizes', [
                        'image' => $this->imagePath,
                        'sizes' => array_keys($responsiveResults),
                    ]);
                }
            } else {
                Log::warning('Image optimization failed', [
                    'image' => $this->imagePath,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Image optimization job failed', [
                'image' => $this->imagePath,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Image optimization job permanently failed', [
            'image' => $this->imagePath,
            'error' => $exception->getMessage(),
        ]);
    }
}
