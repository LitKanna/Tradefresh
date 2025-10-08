<?php

namespace App\Services;

use App\Events\QuoteReceived;
use App\Events\VendorTyping;
use App\Events\PriceUpdate;
use Illuminate\Support\Facades\Log;

class WebSocketService
{
    /**
     * Broadcast a new quote to buyers
     */
    public function broadcastQuote($quote, $vendor, $buyer, $attachments = [])
    {
        try {
            event(new QuoteReceived($quote, $vendor, $buyer, $attachments));

            Log::info('Quote broadcast sent', [
                'quote_id' => $quote->id,
                'vendor_id' => $vendor->id,
                'buyer_id' => $buyer->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast quote', [
                'error' => $e->getMessage(),
                'quote_id' => $quote->id ?? null,
            ]);

            return false;
        }
    }

    /**
     * Broadcast vendor typing indicator
     */
    public function broadcastTyping($rfqId, $vendorId, $vendorName, $isTyping = true)
    {
        try {
            event(new VendorTyping($rfqId, $vendorId, $vendorName, $isTyping));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast typing indicator', [
                'error' => $e->getMessage(),
                'rfq_id' => $rfqId,
            ]);

            return false;
        }
    }

    /**
     * Broadcast price update
     */
    public function broadcastPriceUpdate($product, $oldPrice, $newPrice, $vendorId, $vendorName)
    {
        try {
            // Only broadcast if price actually changed
            if ($oldPrice != $newPrice) {
                event(new PriceUpdate($product, $oldPrice, $newPrice, $vendorId, $vendorName));

                Log::info('Price update broadcast', [
                    'product' => $product,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'vendor' => $vendorName,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast price update', [
                'error' => $e->getMessage(),
                'product' => $product,
            ]);

            return false;
        }
    }

    /**
     * Get online users count for a channel
     */
    public function getOnlineUsers($channel)
    {
        // This would integrate with Reverb's presence channel
        // to get real-time user counts
        return [
            'count' => 0,
            'users' => [],
        ];
    }

    /**
     * Notify user about system events
     */
    public function notifyUser($userId, $title, $message, $type = 'info')
    {
        try {
            // Broadcast a general notification to a specific user
            broadcast(new \App\Events\UserNotification($userId, $title, $message, $type));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send user notification', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return false;
        }
    }
}