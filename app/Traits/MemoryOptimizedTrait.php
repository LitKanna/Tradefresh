<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait MemoryOptimizedTrait
{
    /**
     * Process large collections in chunks to avoid memory issues
     */
    public function chunkProcess(Builder $query, int $chunkSize, callable $callback): void
    {
        $query->chunk($chunkSize, function (Collection $items) use ($callback) {
            $callback($items);
            
            // Force garbage collection after each chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });
    }

    /**
     * Lazy load large datasets with cursor for memory efficiency
     */
    public function lazyProcess(Builder $query, callable $callback): void
    {
        $query->lazyById($this->getLazyChunkSize())->each(function ($item) use ($callback) {
            $callback($item);
            
            // Unset processed items from memory
            unset($item);
        });
    }

    /**
     * Stream large CSV exports without loading all data into memory
     */
    public function streamCsv(Builder $query, array $headers, callable $rowTransformer = null): \Generator
    {
        yield $headers;
        
        $query->lazyById($this->getLazyChunkSize())->each(function ($item) use ($rowTransformer) {
            $row = $rowTransformer ? $rowTransformer($item) : $item->toArray();
            yield $row;
            
            // Clear memory
            unset($item, $row);
        });
    }

    /**
     * Process batch operations with memory management
     */
    public function batchProcess(array $data, int $batchSize, callable $processor): array
    {
        $results = [];
        $chunks = array_chunk($data, $batchSize);
        
        foreach ($chunks as $chunk) {
            $chunkResults = $processor($chunk);
            $results = array_merge($results, $chunkResults);
            
            // Clear processed chunk from memory
            unset($chunk, $chunkResults);
            
            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        return $results;
    }

    /**
     * Optimize query by selecting only needed columns
     */
    public function optimizeSelect(Builder $query, array $columns): Builder
    {
        // Only select specified columns to reduce memory usage
        return $query->select($columns);
    }

    /**
     * Cache frequently accessed computed properties to avoid recalculation
     */
    public function cacheComputed(string $key, callable $computation, int $ttl = 3600)
    {
        return cache()->remember($key, $ttl, $computation);
    }

    /**
     * Get optimal chunk size based on available memory
     */
    protected function getLazyChunkSize(): int
    {
        $memoryLimit = $this->getMemoryLimitInBytes();
        $currentMemory = memory_get_usage(true);
        $availableMemory = $memoryLimit - $currentMemory;
        
        // Use 10% of available memory, with min 100 and max 5000
        $chunkSize = min(5000, max(100, intval($availableMemory * 0.1 / 1024 / 10)));
        
        return $chunkSize;
    }

    /**
     * Get memory limit in bytes
     */
    protected function getMemoryLimitInBytes(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === -1) {
            return PHP_INT_MAX;
        }
        
        $unit = strtolower(substr($memoryLimit, -1));
        $value = intval($memoryLimit);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return $value;
        }
    }

    /**
     * Monitor memory usage during operation
     */
    protected function checkMemoryUsage(string $operation = 'Unknown'): void
    {
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->getMemoryLimitInBytes();
        $percentage = ($current / $limit) * 100;
        
        if ($percentage > 80) {
            \Log::warning("High memory usage during {$operation}", [
                'current' => $this->formatBytes($current),
                'peak' => $this->formatBytes($peak),
                'limit' => $this->formatBytes($limit),
                'percentage' => round($percentage, 2)
            ]);
            
            // Force garbage collection
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
    }

    /**
     * Format bytes for human readability
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Clean up resources and trigger garbage collection
     */
    protected function cleanup(array $variables = []): void
    {
        foreach ($variables as $var) {
            unset($var);
        }
        
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Process large imports with memory optimization
     */
    public function processLargeImport(\SplFileObject $file, callable $processor, int $batchSize = 1000): array
    {
        $results = [
            'processed' => 0,
            'errors' => 0,
            'memory_peak' => 0
        ];
        
        $batch = [];
        $lineNumber = 0;
        
        while (!$file->eof()) {
            $line = $file->fgets();
            if (empty(trim($line))) continue;
            
            $batch[] = $line;
            $lineNumber++;
            
            if (count($batch) >= $batchSize) {
                try {
                    $processor($batch);
                    $results['processed'] += count($batch);
                } catch (\Exception $e) {
                    $results['errors']++;
                    \Log::error("Import error at line {$lineNumber}: " . $e->getMessage());
                }
                
                // Clear batch and check memory
                $batch = [];
                $this->checkMemoryUsage('Large Import');
                $results['memory_peak'] = max($results['memory_peak'], memory_get_peak_usage(true));
            }
        }
        
        // Process remaining items
        if (!empty($batch)) {
            try {
                $processor($batch);
                $results['processed'] += count($batch);
            } catch (\Exception $e) {
                $results['errors']++;
                \Log::error("Import error in final batch: " . $e->getMessage());
            }
        }
        
        return $results;
    }

    /**
     * Optimize large aggregation queries
     */
    public function optimizeAggregation(Builder $query, string $column, string $aggregation = 'sum'): mixed
    {
        // Use database-level aggregation instead of loading all records
        switch (strtolower($aggregation)) {
            case 'sum':
                return $query->sum($column);
            case 'avg':
                return $query->avg($column);
            case 'count':
                return $query->count($column);
            case 'max':
                return $query->max($column);
            case 'min':
                return $query->min($column);
            default:
                return $query->get()->{$aggregation}($column);
        }
    }
}