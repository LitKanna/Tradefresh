<?php

use App\Models\Buyer;
use App\Models\RFQ;
use App\Models\Vendor;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for buyers to receive quotes
Broadcast::channel('buyer.{id}', function ($user, $id) {
    // IMPORTANT: For multi-guard authentication, we need to check the buyer guard first
    // because $user might be null when accessed via WebSocket auth

    // Primary check: Use the buyer guard directly
    $buyer = auth()->guard('buyer')->user();
    if ($buyer && (int) $buyer->id === (int) $id) {
        \Log::info('Buyer channel authorized via guard', [
            'buyer_id' => $buyer->id,
            'channel' => "buyer.{$id}",
        ]);

        return true;
    }

    // Fallback: Check if $user is a Buyer instance
    if ($user instanceof Buyer && (int) $user->id === (int) $id) {
        \Log::info('Buyer channel authorized via instance', [
            'buyer_id' => $user->id,
            'channel' => "buyer.{$id}",
        ]);

        return true;
    }

    \Log::warning('Buyer channel authorization failed', [
        'requested_id' => $id,
        'user_type' => $user ? get_class($user) : 'null',
        'buyer_guard' => $buyer ? $buyer->id : 'null',
    ]);

    return false;
});

// Private channel for buyers to receive quotes for specific RFQs
Broadcast::channel('buyer-quotes.{rfqId}', function ($user, $rfqId) {
    if ($user instanceof Buyer) {
        $rfq = RFQ::find($rfqId);

        return $rfq && $rfq->buyer_id === $user->id;
    }

    return false;
});

// Private channel for vendors to receive notifications
Broadcast::channel('vendor.{id}', function ($user, $id) {
    if ($user instanceof Vendor) {
        return (int) $user->id === (int) $id;
    }

    return false;
});

// Private channel for RFQ participants (buyer and vendors who quoted)
Broadcast::channel('rfq.{rfqId}', function ($user, $rfqId) {
    $rfq = RFQ::find($rfqId);
    if (! $rfq) {
        return false;
    }

    // Allow buyer who created the RFQ
    if ($user instanceof Buyer && $rfq->buyer_id === $user->id) {
        return true;
    }

    // Allow vendors who have submitted quotes for this RFQ
    if ($user instanceof Vendor) {
        return $rfq->quotes()->where('vendor_id', $user->id)->exists();
    }

    return false;
});

// Private channel for quotes - MATCHES GIST PATTERN
Broadcast::channel('quotes.buyer.{id}', function ($user, $id) {
    // Primary check: Use the buyer guard directly
    $buyer = auth()->guard('buyer')->user();
    if ($buyer && (int) $buyer->id === (int) $id) {
        \Log::info('Quotes channel authorized via guard', [
            'buyer_id' => $buyer->id,
            'channel' => "quotes.buyer.{$id}",
        ]);

        return true;
    }

    // Fallback: Check if $user is a Buyer instance
    if ($user instanceof Buyer && (int) $user->id === (int) $id) {
        \Log::info('Quotes channel authorized via instance', [
            'buyer_id' => $user->id,
            'channel' => "quotes.buyer.{$id}",
        ]);

        return true;
    }

    \Log::warning('Quotes channel authorization failed', [
        'requested_id' => $id,
        'user_type' => $user ? get_class($user) : 'null',
        'buyer_guard' => $buyer ? $buyer->id : 'null',
    ]);

    return false;
});

// Presence channel for tracking online users
Broadcast::channel('online-users', function ($user) {
    if ($user instanceof Buyer) {
        return [
            'id' => $user->id,
            'name' => $user->business_name,
            'type' => 'buyer',
        ];
    }

    if ($user instanceof Vendor) {
        return [
            'id' => $user->id,
            'name' => $user->business_name,
            'type' => 'vendor',
        ];
    }

    return false;
});

// Private channel for quote messages
Broadcast::channel('quote.{quoteId}.messages', function ($user, $quoteId) {
    // Check if user is authenticated
    if (!$user) {
        return false;
    }

    $quote = \App\Models\Quote::find($quoteId);
    if (!$quote) {
        return false;
    }

    // Allow buyer who owns the quote
    if ($user instanceof Buyer && $quote->buyer_id === $user->id) {
        return true;
    }

    // Allow vendor who submitted the quote
    if ($user instanceof Vendor && $quote->vendor_id === $user->id) {
        return true;
    }

    return false;
});

// Private channel for direct buyer messages
Broadcast::channel('messages.buyer.{id}', function ($user, $id) {
    // Primary check: Use the buyer guard directly
    $buyer = auth()->guard('buyer')->user();
    if ($buyer && (int) $buyer->id === (int) $id) {
        return true;
    }

    // Fallback: Check if $user is a Buyer instance
    if ($user instanceof Buyer && (int) $user->id === (int) $id) {
        return true;
    }

    return false;
});

// Private channel for direct vendor messages
Broadcast::channel('messages.vendor.{id}', function ($user, $id) {
    // Primary check: Use the vendor guard directly
    $vendor = auth()->guard('vendor')->user();
    if ($vendor && (int) $vendor->id === (int) $id) {
        return true;
    }

    // Fallback: Check if $user is a Vendor instance
    if ($user instanceof Vendor && (int) $user->id === (int) $id) {
        return true;
    }

    return false;
});
