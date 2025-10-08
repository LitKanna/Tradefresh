<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Buyer;
use App\Models\Vendor;
use App\Models\RFQ;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for buyers to receive quotes
Broadcast::channel('buyer.{id}', function ($user, $id) {
    if ($user instanceof Buyer) {
        return (int) $user->id === (int) $id;
    }
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
    if (!$rfq) {
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

// Presence channel for tracking online users
Broadcast::channel('online-users', function ($user) {
    if ($user instanceof Buyer) {
        return [
            'id' => $user->id,
            'name' => $user->business_name,
            'type' => 'buyer'
        ];
    }

    if ($user instanceof Vendor) {
        return [
            'id' => $user->id,
            'name' => $user->business_name,
            'type' => 'vendor'
        ];
    }

    return false;
});

// Private channel for quote messages
Broadcast::channel('quote.{quoteId}.messages', function ($user, $quoteId) {
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
