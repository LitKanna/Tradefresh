<?php

namespace App\Services;

use App\Events\VendorStatusChanged;
use App\Models\Vendor;
use App\Models\VendorActivityLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class VendorTrackingService
{
    /**
     * Redis key prefixes
     */
    const REDIS_ONLINE_VENDORS = 'vendors:online';

    const REDIS_VENDOR_HEARTBEAT = 'vendor:heartbeat:';

    const REDIS_VENDOR_SESSION = 'vendor:session:';

    const REDIS_METRICS_CACHE = 'vendor:metrics:cache';

    const HEARTBEAT_TIMEOUT_SECONDS = 30;

    const ACTIVITY_THROTTLE_SECONDS = 60;

    /**
     * Mark vendor as online and broadcast the event
     */
    public function markVendorOnline(Vendor $vendor, ?string $sessionId = null): void
    {
        try {
            DB::beginTransaction();

            // Update vendor status
            $vendor->markAsOnline($sessionId);

            // Log the activity
            $vendor->logActivity('login', [
                'timestamp' => now()->toIso8601String(),
                'session_id' => $sessionId,
            ]);

            // Update Redis cache
            $this->updateRedisOnlineStatus($vendor->id, true);

            // Broadcast real-time event
            broadcast(new VendorStatusChanged($vendor, 'online'))->toOthers();

            // Update metrics
            $this->updateMetrics();

            DB::commit();

            Log::info('Vendor marked online', [
                'vendor_id' => $vendor->id,
                'session_id' => $sessionId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark vendor online', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark vendor as offline and broadcast the event
     */
    public function markVendorOffline(Vendor $vendor, string $reason = 'logout'): void
    {
        try {
            DB::beginTransaction();

            // Update vendor status
            $vendor->markAsOffline();

            // Log the activity
            $vendor->logActivity($reason, [
                'timestamp' => now()->toIso8601String(),
            ]);

            // Update Redis cache
            $this->updateRedisOnlineStatus($vendor->id, false);

            // Broadcast real-time event
            broadcast(new VendorStatusChanged($vendor, 'offline'))->toOthers();

            // Update metrics
            $this->updateMetrics();

            DB::commit();

            Log::info('Vendor marked offline', [
                'vendor_id' => $vendor->id,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark vendor offline', [
                'vendor_id' => $vendor->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process vendor heartbeat
     */
    public function processHeartbeat(Vendor $vendor): void
    {
        // Update database heartbeat
        $vendor->updateHeartbeat();

        // Update Redis heartbeat
        $this->updateRedisHeartbeat($vendor->id);

        // Ensure vendor is marked as online if not already
        if (! $vendor->is_online) {
            $this->markVendorOnline($vendor);
        }
    }

    /**
     * Track vendor activity (throttled to prevent spam)
     */
    public function trackActivity(Vendor $vendor, string $activityType = 'page_view'): void
    {
        $throttleKey = "vendor:activity:throttle:{$vendor->id}";

        // Check if we should throttle this activity
        if (! Cache::has($throttleKey)) {
            $vendor->updateActivity();

            // Set throttle for 60 seconds
            Cache::put($throttleKey, true, self::ACTIVITY_THROTTLE_SECONDS);

            // Log significant activities
            if ($activityType !== 'page_view') {
                $vendor->logActivity('activity', [
                    'type' => $activityType,
                    'timestamp' => now()->toIso8601String(),
                ]);
            }
        }
    }

    /**
     * Check for timed-out vendors and mark them offline
     */
    public function checkTimeouts(): array
    {
        $timedOutVendors = [];

        try {
            // Find vendors that should be marked offline
            $vendors = Vendor::where('is_online', true)
                ->where('last_heartbeat_at', '<', now()->subSeconds(self::HEARTBEAT_TIMEOUT_SECONDS))
                ->get();

            foreach ($vendors as $vendor) {
                $this->markVendorOffline($vendor, 'timeout');
                $timedOutVendors[] = $vendor->id;
            }

            if (count($timedOutVendors) > 0) {
                Log::info('Marked vendors offline due to timeout', [
                    'count' => count($timedOutVendors),
                    'vendor_ids' => $timedOutVendors,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error checking vendor timeouts', [
                'error' => $e->getMessage(),
            ]);
        }

        return $timedOutVendors;
    }

    /**
     * Get real-time metrics
     */
    public function getMetrics(): array
    {
        // Try to get from cache first
        $cached = Cache::get(self::REDIS_METRICS_CACHE);
        if ($cached && $cached['timestamp'] > now()->subSeconds(5)->timestamp) {
            return $cached['metrics'];
        }

        // Calculate fresh metrics
        $metrics = $this->calculateMetrics();

        // Cache for 5 seconds
        Cache::put(self::REDIS_METRICS_CACHE, [
            'metrics' => $metrics,
            'timestamp' => now()->timestamp,
        ], 5);

        return $metrics;
    }

    /**
     * Calculate real-time metrics
     */
    private function calculateMetrics(): array
    {
        $onlineVendors = Vendor::online()->get();

        $categoryBreakdown = $onlineVendors->groupBy('vendor_category')
            ->map->count()
            ->toArray();

        $locationBreakdown = $onlineVendors->groupBy('state')
            ->map->count()
            ->toArray();

        return [
            'total_online' => $onlineVendors->count(),
            'total_active' => Vendor::active()->count(),
            'category_breakdown' => $categoryBreakdown,
            'location_breakdown' => $locationBreakdown,
            'recently_active' => Vendor::recentlyActive()->count(),
            'last_updated' => now()->toIso8601String(),
        ];
    }

    /**
     * Update metrics in database
     */
    private function updateMetrics(): void
    {
        try {
            $metrics = $this->calculateMetrics();

            DB::table('realtime_vendor_metrics')
                ->updateOrInsert(
                    ['id' => 1],
                    [
                        'total_online' => $metrics['total_online'],
                        'total_active' => $metrics['total_active'],
                        'category_breakdown' => json_encode($metrics['category_breakdown']),
                        'location_breakdown' => json_encode($metrics['location_breakdown']),
                        'last_calculated_at' => now(),
                        'updated_at' => now(),
                    ]
                );
        } catch (\Exception $e) {
            Log::error('Failed to update vendor metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update Redis online status
     */
    private function updateRedisOnlineStatus(int $vendorId, bool $isOnline): void
    {
        try {
            if (config('database.redis.client') === 'predis' || config('database.redis.client') === 'phpredis') {
                $redis = Redis::connection();

                if ($isOnline) {
                    $redis->sadd(self::REDIS_ONLINE_VENDORS, $vendorId);
                    $redis->setex(self::REDIS_VENDOR_HEARTBEAT.$vendorId, self::HEARTBEAT_TIMEOUT_SECONDS * 2, time());
                } else {
                    $redis->srem(self::REDIS_ONLINE_VENDORS, $vendorId);
                    $redis->del(self::REDIS_VENDOR_HEARTBEAT.$vendorId);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            Log::warning('Redis operation failed', [
                'operation' => 'updateOnlineStatus',
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update Redis heartbeat
     */
    private function updateRedisHeartbeat(int $vendorId): void
    {
        try {
            if (config('database.redis.client') === 'predis' || config('database.redis.client') === 'phpredis') {
                $redis = Redis::connection();
                $redis->setex(self::REDIS_VENDOR_HEARTBEAT.$vendorId, self::HEARTBEAT_TIMEOUT_SECONDS * 2, time());
            }
        } catch (\Exception $e) {
            Log::warning('Redis heartbeat update failed', [
                'vendor_id' => $vendorId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get online vendors from Redis
     */
    public function getOnlineVendorsFromRedis(): array
    {
        try {
            if (config('database.redis.client') === 'predis' || config('database.redis.client') === 'phpredis') {
                $redis = Redis::connection();

                return $redis->smembers(self::REDIS_ONLINE_VENDORS);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get online vendors from Redis', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to database
        return Vendor::online()->pluck('id')->toArray();
    }

    /**
     * Cleanup old activity logs
     */
    public function cleanupOldLogs(int $daysToKeep = 30): int
    {
        return VendorActivityLog::where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }
}
