# COMPLETE END-TO-END REAL-TIME QUOTE TEST

## Prerequisites

1. **Start Reverb Server** (CRITICAL - Must be running)
   ```bash
   # Option 1: Use batch file
   start-reverb.bat

   # Option 2: Use artisan command
   php artisan reverb:start
   ```

   **Keep this terminal open!** The server must run continuously.

2. **Verify Reverb is Running**
   ```bash
   netstat -ano | findstr :9090
   ```
   You should see: `TCP    0.0.0.0:9090    0.0.0.0:0    LISTENING`

---

## Test Steps

### Step 1: Open Buyer Dashboard

1. **Open browser** (Chrome/Firefox)
2. **Navigate to:** `http://localhost:8000/buyer/dashboard`
3. **Login as buyer:**
   - Email: `john.smith@gmail.com`
   - Password: `password`

### Step 2: Open Browser Console

1. **Press F12** to open DevTools
2. **Click "Console" tab**
3. **Look for these messages:**
   ```
   ✅ Subscribed to buyers.all channel
   ✅ Subscribed to quotes.buyer.1 channel
   📡 Echo initialized and channels subscribed
   ```

**If you see these messages:** WebSocket connection is working! ✓

**If you DON'T see these messages:** Reverb server is not running. Go back to Prerequisites.

### Step 3: Open Vendor Dashboard (New Browser Window)

1. **Open NEW browser window** (or incognito mode)
2. **Navigate to:** `http://localhost:8000/vendor/dashboard`
3. **Login as vendor:**
   - Email: `fresh.produce@sydneymarkets.com`
   - Password: `password`

### Step 4: Submit Quote from Vendor Dashboard

1. **Find an RFQ** in the "Active RFQs" section
2. **Click "View Details"** button
3. **Fill in quote details:**
   - Product: Select "Pears" (or any product)
   - Quantity: 10
   - Unit: kg
   - Unit Price: $5.00
4. **Click "Submit Quote"** button

### Step 5: Watch Buyer Dashboard (Real-Time Update)

**Switch back to buyer dashboard window.**

**What you should see (within 1 second):**

1. **Browser Console Messages:**
   ```
   📥 Quote received via buyers.all: QuoteReceived
   === QUOTE RECEIVED VIA PUBLIC CHANNEL ===
   Quote ID: 60
   Vendor: Fresh Produce Sydney
   Amount: $50.00
   ```

2. **Visual Updates on Dashboard:**
   - New quote card appears in "Vendor Quotes" section (right side)
   - Quote shows vendor name, product name, price
   - Timer starts counting down from 30:00
   - Green "Accept Quote" button is active

3. **Success Notification:**
   - Toast notification appears: "New Quote Received!"
   - Message: "You have received a new quote from Fresh Produce Sydney"

**If you see all of this:** Real-time quotes are working perfectly! ✅

---

## Troubleshooting

### Issue: No messages in browser console

**Solution:**
1. Check Reverb server is running: `netstat -ano | findstr :9090`
2. Restart Reverb server: Close terminal and run `start-reverb.bat` again
3. Refresh buyer dashboard (F5)

### Issue: Quote appears after manual refresh but NOT in real-time

**Solution:**
1. Check Laravel logs: `storage/logs/laravel.log`
2. Look for broadcasting errors
3. Ensure `BROADCAST_CONNECTION=reverb` in `.env` file
4. Restart Reverb server

### Issue: 403 errors in console

**Solution:**
- This should NOT happen (we're using public channels)
- If it does, check `routes/channels.php` for channel authorization
- Ensure channels are public, not private

### Issue: Quote expired immediately

**Solution:**
- This is NORMAL for quotes older than 30 minutes
- Submit a NEW quote to test real-time functionality
- Old quotes are filtered out automatically

---

## Expected Results Summary

### Database Check (Run this anytime)
```php
php artisan tinker
Quote::orderBy('created_at', 'desc')->first();
```

You should see the most recent quote with:
- `buyer_id: 1`
- `status: 'submitted'`
- `created_at: [recent timestamp]`

### Real-Time Check (Watch browser console)
```
✅ Subscribed to buyers.all channel
✅ Subscribed to quotes.buyer.1 channel
📥 Quote received via buyers.all: QuoteReceived
=== QUOTE RECEIVED VIA PUBLIC CHANNEL ===
✅ FRONTEND RE-RENDER EVENTS DISPATCHED
```

### Visual Check (Watch buyer dashboard)
- Quote card appears within 1 second
- Timer shows 30:00 and counts down
- Vendor name and product name are correct
- Amount matches submitted quote
- "Accept Quote" button is green and active

---

## Success Criteria

✅ **All criteria must be met:**

1. [ ] Reverb server running on port 9090
2. [ ] Buyer dashboard shows "Subscribed to channels" in console
3. [ ] Vendor can submit quote without errors
4. [ ] Quote appears on buyer dashboard within 1 second
5. [ ] Quote shows correct information (vendor, product, price)
6. [ ] Quote timer counts down from 30:00
7. [ ] No errors in browser console
8. [ ] No errors in Laravel logs

---

## Performance Metrics

**Expected Performance:**
- Event broadcast latency: <100ms
- Frontend update latency: <200ms
- Total time from submit to display: <1 second

**If performance is slower:**
- Check network connection
- Ensure Reverb server is on same machine
- Check for browser extensions blocking WebSockets

---

## Next Steps After Successful Test

1. **Test Multiple Quotes:**
   - Submit 3-4 quotes in sequence
   - Verify all appear in correct order (newest first)
   - Verify timers count down independently

2. **Test Quote Expiration:**
   - Wait 30 minutes after submitting a quote
   - Refresh buyer dashboard
   - Verify expired quote does NOT appear

3. **Test Multiple Buyers:**
   - Create second buyer account
   - Submit quote for buyer #1
   - Verify buyer #2 does NOT see the quote

4. **Test Chat Messages:**
   - Open quote details modal
   - Send message from buyer
   - Verify vendor receives message in real-time

---

**Remember:** Reverb server MUST be running for real-time features to work!
