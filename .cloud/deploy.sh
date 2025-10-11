#!/bin/bash

# Laravel Cloud Deployment Script
# Production deployment (Octane/Reverb are dev-only packages)

echo "Starting Laravel Cloud deployment..."

# Clear all caches first
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# Run database migrations
php artisan migrate --force --no-interaction

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Build frontend assets if needed
if [ -f "package.json" ]; then
    npm ci --production 2>/dev/null || echo "Skipping npm build"
    npm run build 2>/dev/null || echo "No build script found"
fi

echo "Deployment complete!"
