# Laravel Cloud Deployment Guide

## Critical Fixes Applied ✅

### Problem: Octane/FrankenPHP Write Permission Errors
**Root Cause:** Laravel Octane and Reverb were auto-discovered and installed in production, causing permission errors when trying to write process ID files.

### Solution Implemented:

#### 1. **Moved Octane/Reverb to Dev Dependencies** ✅
```json
// composer.json
"require-dev": {
    "laravel/octane": "^2.12",
    "laravel/reverb": "^1.6",
    "laravel/boost": "^1.1",
    "spatie/laravel-ignition": "^2.4"
}
```

**Why:** These packages are only needed for local development:
- **Octane**: Development server optimization
- **Reverb**: WebSocket server (dev testing only)
- **Boost**: Laravel development tools
- **Ignition**: Error debugging (dev only)

#### 2. **Disabled Auto-Discovery** ✅
```json
// composer.json
"extra": {
    "laravel": {
        "dont-discover": [
            "laravel/octane",
            "laravel/reverb"
        ]
    }
}
```

**Why:** Prevents Laravel from auto-registering these packages even if they're installed.

#### 3. **Cleaned Laravel Cloud Configuration** ✅
```yaml
# laravel-cloud.yaml
deployment:
  build:
    - composer install --no-dev --optimize-autoloader
    - php artisan config:clear
    - php artisan migrate --force --no-interaction
    - php artisan config:cache
    - php artisan route:cache
    - php artisan view:cache
    - php artisan event:cache
```

**Why:** `--no-dev` ensures dev packages are never installed in production.

## Deployment Steps

### Step 1: Update Composer Dependencies
```bash
composer update --no-dev
```

### Step 2: Clear Cached Discovery
```bash
php artisan package:discover --ansi
php artisan config:clear
```

### Step 3: Commit Changes
```bash
git add .
git commit -m "fix: Move Octane/Reverb to dev dependencies for Laravel Cloud deployment"
git push origin main
```

### Step 4: Laravel Cloud Environment Variables

**Remove these variables from Laravel Cloud dashboard:**
- ❌ `OCTANE_SERVER` (no longer needed)

**Ensure these are set:**
- ✅ `APP_KEY` (your app key)
- ✅ `APP_ENV=production`
- ✅ `APP_DEBUG=false`
- ✅ `DB_CONNECTION=mysql` (or your database)
- ✅ Any API keys (Stripe, Gemini, etc.)

### Step 5: Deploy on Laravel Cloud

1. **Push to GitHub** (Laravel Cloud auto-deploys from GitHub)
2. **Monitor deployment logs** for any errors
3. **Verify deployment** by visiting your app URL

## Expected Result

✅ **No Octane errors** - Octane won't be installed in production
✅ **No permission errors** - FrankenPHP won't try to write files
✅ **Normal PHP-FPM deployment** - Standard Laravel deployment
✅ **Clean production build** - Only production packages installed

## Production Architecture

### What's Used in Production:
- **Web Server:** PHP-FPM (standard)
- **Broadcasting:** Log driver (Reverb disabled)
- **Queue:** Database driver
- **Cache:** Database/Redis (as configured)
- **Session:** Database driver

### What's Available in Development:
- **Web Server:** Laravel Octane (RoadRunner/FrankenPHP)
- **Broadcasting:** Laravel Reverb (WebSockets)
- **Debugging:** Laravel Ignition, Boost

## Troubleshooting

### If deployment still fails:

1. **Check composer install logs:**
   ```bash
   composer install --no-dev -vvv
   ```

2. **Verify package discovery:**
   ```bash
   php artisan package:discover --ansi
   ```

3. **Check registered providers:**
   ```bash
   php artisan about
   ```

### Common Issues:

**Issue:** "Class 'Laravel\Octane\OctaneServiceProvider' not found"
**Solution:** Run `composer install --no-dev` to remove Octane from production

**Issue:** "Unable to write to process ID file"
**Solution:** Octane is still installed - verify `--no-dev` flag is used

**Issue:** Missing service providers
**Solution:** Check `bootstrap/cache/packages.php` doesn't include Octane/Reverb

## Verification Commands

After deployment, verify the setup:

```bash
# Check installed packages (Octane should NOT be listed)
composer show --installed | grep octane

# Check registered providers (Octane should NOT be registered)
php artisan about

# Check application status
php artisan config:show app
```

## Success Criteria

✅ Application loads without errors
✅ No Octane-related error messages
✅ Database connections work
✅ Routes are accessible
✅ Production caches are optimized

## Notes

- **Octane is only for development** - It provides performance benefits locally but isn't required for production
- **Laravel Cloud uses PHP-FPM** - Standard PHP deployment, not application servers
- **Reverb is optional** - WebSockets can be added later when needed
- **Dev packages stay in require-dev** - Keeps production lean and secure
