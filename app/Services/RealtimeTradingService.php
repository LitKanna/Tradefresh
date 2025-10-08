<?php

namespace App\Services;

use App\Events\RfqBroadcast;
use App\Events\QuoteReceived;
use App\Events\VendorOnlineStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RealtimeTradingService
{
    /**
     * Track vendor online status
     */
    public function updateVendorOnlineStatus($vendorId, $isOnline, $socketId = null, $availableProducts = [])
    {
        try {
            // Update database
            DB::table('vendor_online_status')->updateOrInsert(
                ['vendor_id' => $vendorId],
                [
                    'is_online' => $isOnline,
                    'last_seen_at' => now(),
                    'socket_id' => $socketId,
                    'available_products' => json_encode($availableProducts),
                    'updated_at' => now()
                ]
            );

            // Update cache for fast access
            $cacheKey = "vendor_online_status_{$vendorId}";
            Cache::put($cacheKey, [
                'is_online' => $isOnline,
                'last_seen' => now()->toISOString(),
                'available_products' => $availableProducts
            ], 3600); // 1 hour

            // Get vendor info
            $vendor = DB::table('vendors')->where('id', $vendorId)->first();
            
            if ($vendor) {
                // Broadcast status change
                broadcast(new VendorOnlineStatusChanged($vendor, $isOnline, $availableProducts));
                
                Log::info("Vendor {$vendor->business_name} is now " . ($isOnline ? 'online' : 'offline'));
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update vendor online status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find online vendors who can fulfill RFQ
     */
    public function findMatchingOnlineVendors($rfq)
    {
        try {
            $requestedProducts = is_string($rfq->items) ? json_decode($rfq->items, true) : $rfq->items;
            
            if (!$requestedProducts) {
                return [];
            }

            // Get online vendors who have these products
            $matchingVendors = DB::table('vendor_online_status as vos')
                ->join('vendors as v', 'vos.vendor_id', '=', 'v.id')
                ->where('vos.is_online', true)
                ->where('vos.last_seen_at', '>', Carbon::now()->subMinutes(5))
                ->get()
                ->filter(function ($vendor) use ($requestedProducts) {
                    $availableProducts = json_decode($vendor->available_products ?? '[]', true);
                    
                    // Check if vendor has any of the requested products
                    foreach ($requestedProducts as $requestedProduct) {
                        $productId = $requestedProduct['product_id'] ?? null;
                        if ($productId && in_array($productId, $availableProducts)) {
                            return true;
                        }
                    }
                    return false;
                });

            // Create match records
            foreach ($matchingVendors as $vendor) {
                $matchScore = $this->calculateMatchScore($requestedProducts, json_decode($vendor->available_products ?? '[]', true));
                
                DB::table('rfq_vendor_matches')->updateOrInsert(
                    ['rfq_id' => $rfq->id, 'vendor_id' => $vendor->vendor_id],
                    [
                        'matched_products' => $vendor->available_products,
                        'match_score' => $matchScore,
                        'is_notified' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }

            return $matchingVendors->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to find matching vendors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Broadcast RFQ to matching online vendors
     */
    public function broadcastRfqToVendors($rfq, $buyer)
    {
        try {
            $matchingVendors = $this->findMatchingOnlineVendors($rfq);
            
            if (empty($matchingVendors)) {
                Log::info("No online vendors found for RFQ {$rfq->id}");
                return [];
            }

            // Convert to vendor objects for event
            $vendorObjects = collect($matchingVendors)->map(function ($vendor) {
                return (object) [
                    'id' => $vendor->vendor_id,
                    'business_name' => $vendor->business_name
                ];
            })->toArray();

            // Broadcast to matching vendors
            broadcast(new RfqBroadcast($rfq, $vendorObjects, $buyer));

            // Mark vendors as notified
            DB::table('rfq_vendor_matches')
                ->where('rfq_id', $rfq->id)
                ->whereIn('vendor_id', collect($matchingVendors)->pluck('vendor_id'))
                ->update([
                    'is_notified' => true,
                    'notified_at' => now()
                ]);

            Log::info("RFQ {$rfq->id} broadcasted to " . count($matchingVendors) . " online vendors");
            
            return $vendorObjects;
        } catch (\Exception $e) {
            Log::error("Failed to broadcast RFQ: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Handle incoming quote with attachments
     */
    public function processQuoteWithAttachments($quote, $vendor, $buyer, $attachments = [])
    {
        try {
            // Process attachments
            $processedAttachments = [];
            foreach ($attachments as $attachment) {
                $attachmentRecord = DB::table('quote_attachments')->insertGetId([
                    'quote_id' => $quote->id,
                    'type' => $attachment['type'] ?? 'image',
                    'filename' => $attachment['filename'] ?? '',
                    'original_filename' => $attachment['original_filename'] ?? '',
                    'mime_type' => $attachment['mime_type'] ?? '',
                    'file_size' => $attachment['file_size'] ?? 0,
                    'storage_path' => $attachment['storage_path'] ?? '',
                    'public_url' => $attachment['public_url'] ?? '',
                    'thumbnail_url' => $attachment['thumbnail_url'] ?? '',
                    'is_processed' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $processedAttachments[] = [
                    'id' => $attachmentRecord,
                    'type' => $attachment['type'] ?? 'image',
                    'url' => $attachment['public_url'] ?? '',
                    'thumbnail' => $attachment['thumbnail_url'] ?? '',
                    'filename' => $attachment['original_filename'] ?? ''
                ];
            }

            // Update RFQ vendor match as responded
            DB::table('rfq_vendor_matches')
                ->where('rfq_id', $quote->rfq_id)
                ->where('vendor_id', $vendor->id)
                ->update([
                    'vendor_responded' => true,
                    'responded_at' => now()
                ]);

            // Broadcast quote to buyer
            broadcast(new QuoteReceived($quote, $vendor, $buyer, $processedAttachments));

            Log::info("Quote {$quote->id} from vendor {$vendor->business_name} broadcasted to buyer {$buyer->business_name}");
            
            return $processedAttachments;
        } catch (\Exception $e) {
            Log::error("Failed to process quote: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get online vendor count
     */
    public function getOnlineVendorCount()
    {
        return DB::table('vendor_online_status')
            ->where('is_online', true)
            ->where('last_seen_at', '>', Carbon::now()->subMinutes(5))
            ->count();
    }

    /**
     * Get vendors online for specific products
     */
    public function getOnlineVendorsForProducts($productIds)
    {
        return DB::table('vendor_online_status as vos')
            ->join('vendors as v', 'vos.vendor_id', '=', 'v.id')
            ->where('vos.is_online', true)
            ->where('vos.last_seen_at', '>', Carbon::now()->subMinutes(5))
            ->get()
            ->filter(function ($vendor) use ($productIds) {
                $availableProducts = json_decode($vendor->available_products ?? '[]', true);
                return !empty(array_intersect($productIds, $availableProducts));
            });
    }

    /**
     * Calculate match score between requested and available products
     */
    private function calculateMatchScore($requestedProducts, $availableProducts)
    {
        if (empty($requestedProducts) || empty($availableProducts)) {
            return 0.0;
        }

        $requestedIds = collect($requestedProducts)->pluck('product_id')->toArray();
        $matchCount = count(array_intersect($requestedIds, $availableProducts));
        $totalRequested = count($requestedIds);

        return round($matchCount / $totalRequested, 2);
    }

    /**
     * Log WebSocket event
     */
    public function logWebSocketEvent($eventType, $userId, $userType, $payload, $recipients = null)
    {
        try {
            DB::table('websocket_events')->insert([
                'event_type' => $eventType,
                'user_type' => $userType,
                'user_id' => $userId,
                'payload' => json_encode($payload),
                'recipients' => $recipients ? json_encode($recipients) : null,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log WebSocket event: " . $e->getMessage());
        }
    }
}