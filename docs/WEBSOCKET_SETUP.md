# 🔌 WebSocket Setup with Laravel Reverb

## ✅ Installation Complete!

Laravel Reverb (WebSocket server) has been successfully installed and configured for your B2B marketplace. This enables real-time features throughout your application.

## 🚀 How to Start WebSocket Server

### IMPORTANT: Start Servers in This Order
```bash
# Terminal 1: Start Reverb WebSocket server (MUST BE RUNNING FIRST!)
php artisan reverb:start --debug

# Terminal 2: Start Laravel development server
php artisan serve

# Terminal 3: Start Vite for assets (for JavaScript compilation)
npm run dev
```

**⚠️ NOTE: The Reverb WebSocket server MUST be running before you can test any WebSocket features!**

### Production
```bash
# Use supervisor or systemd to keep Reverb running
php artisan reverb:start --host=0.0.0.0 --port=8080
```

## 📦 What's Been Added

### 1. **Events for Real-Time Broadcasting**
- `app/Events/QuoteReceived.php` - When vendor submits a quote
- `app/Events/VendorTyping.php` - Shows "vendor is typing..." indicator
- `app/Events/PriceUpdate.php` - Broadcasts price changes to all users
- `app/Events/MarketplaceEvent.php` - Base event class

### 2. **WebSocket Service**
- `app/Services/WebSocketService.php` - Centralized service for broadcasting

### 3. **Real-Time Notification Component**
- `app/Livewire/RealTimeNotifications.php` - Livewire component
- `resources/views/livewire/real-time-notifications.blade.php` - UI component

## 🎯 Features You Can Now Use

### 1. **Real-Time Quote Updates**
```php
// In your controller when vendor submits quote
use App\Services\WebSocketService;

$websocket = new WebSocketService();
$websocket->broadcastQuote($quote, $vendor, $buyer);
```

### 2. **Vendor Typing Indicator**
```php
// When vendor starts typing a quote
$websocket->broadcastTyping($rfqId, $vendorId, $vendorName, true);

// When vendor stops typing
$websocket->broadcastTyping($rfqId, $vendorId, $vendorName, false);
```

### 3. **Price Updates**
```php
// When product price changes
$websocket->broadcastPriceUpdate(
    'Tomatoes',
    3.50,  // old price
    2.99,  // new price
    $vendorId,
    'Fresh Farms'
);
```

## 🔧 How to Add to Your Pages

### Add Notification Bell to Header
```blade
<!-- In your layout file (e.g., buyer.blade.php) -->
<div class="header-right">
    @livewire('real-time-notifications')
</div>
```

### Listen for Events in Livewire Components
```php
// In any Livewire component
#[On('echo:private-buyer.{userId},quote.received')]
public function onQuoteReceived($event)
{
    // Handle the real-time quote
    $this->quotes[] = $event['quote'];
    $this->dispatch('show-toast', [
        'type' => 'success',
        'message' => 'New quote received!'
    ]);
}
```

### JavaScript Integration
```javascript
// Listen for WebSocket events in JavaScript
Echo.private(`rfq.${rfqId}`)
    .listen('.vendor.typing', (e) => {
        console.log(`${e.vendor_name} is typing...`);
        showTypingIndicator(e.vendor_name);
    });

Echo.channel('market.prices')
    .listen('.price.updated', (e) => {
        console.log(`${e.product} price changed to $${e.new_price}`);
        updatePriceDisplay(e.product, e.new_price);
    });
```

## 🔑 Environment Variables

Your `.env` file has been configured with:
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=708183
REVERB_APP_KEY=btmij6cu4kcqzm4yoxlb
REVERB_APP_SECRET=irfkd5wllnbcrmlsysqu
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

## 📊 Real-Time Features for Your Marketplace

### Immediate Impact Features
1. **Quote Notifications** - Buyers see quotes instantly
2. **Typing Indicators** - Know when vendor is responding
3. **Price Alerts** - Real-time price drop notifications
4. **Online Presence** - See who's online now

### Future Enhancements (Ready to Build)
1. **Live Auctions** - Real-time bidding
2. **Delivery Tracking** - Live driver location
3. **Stock Updates** - Instant inventory changes
4. **Chat System** - Direct buyer-vendor messaging
5. **Dashboard Metrics** - Live sales counters

## 🧪 Testing WebSocket Connection

### Using Test Command (Recommended)
```bash
# Test price update broadcast
php artisan websocket:test --type=price

# Test quote received notification
php artisan websocket:test --type=quote

# Test vendor typing indicator
php artisan websocket:test --type=typing
```

### Manual Test via Tinker
```php
// In tinker or a test route
php artisan tinker

event(new \App\Events\PriceUpdate(
    'Test Product',
    10.00,
    8.99,
    1,
    'Test Vendor'
));
```

### Check Browser Console
```javascript
// Should see in browser console when connected
Echo.channel('market.prices')
    .subscribed(() => {
        console.log('✅ Connected to WebSocket!');
    });
```

## 🐛 Troubleshooting

### If WebSocket Not Connecting
1. Check Reverb is running: `php artisan reverb:start --debug`
2. Check port 8080 is not blocked
3. Verify `.env` settings match
4. Clear config cache: `php artisan config:clear`

### If Events Not Broadcasting
1. Check queue is running: `php artisan queue:work`
2. Verify event implements `ShouldBroadcast`
3. Check channel authorization in `routes/channels.php`

## 🔒 Security

### Channel Authorization
```php
// In routes/channels.php
Broadcast::channel('buyer.{id}', function ($user, $id) {
    return $user->id === (int) $id;
});

Broadcast::channel('rfq.{rfqId}', function ($user, $rfqId) {
    // Check if user is buyer or vendor for this RFQ
    return RFQ::where('id', $rfqId)
        ->where(function($q) use ($user) {
            $q->where('buyer_id', $user->id)
              ->orWhereHas('quotes', function($q) use ($user) {
                  $q->where('vendor_id', $user->id);
              });
        })->exists();
});
```

## 🎯 Next Steps

1. **Start the WebSocket server**: `php artisan reverb:start --debug`
2. **Add notification bell** to your layouts
3. **Test with a quote submission** to see real-time updates
4. **Implement typing indicators** in quote forms
5. **Add price alerts** for buyers

## 💡 Pro Tips

- Use `ShouldBroadcastNow` for instant events (no queue needed)
- Keep payloads small for better performance
- Use presence channels for "who's online" features
- Implement reconnection logic for network issues
- Add sound notifications for important events

---

**WebSocket functionality is now ready to enhance your marketplace with real-time features!** 🚀

Start the server and watch your marketplace come alive with instant updates, just like being on the actual Sydney Markets floor!