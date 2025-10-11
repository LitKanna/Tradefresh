#!/bin/bash

# Laravel Cloud Deployment Script
# This script runs during deployment on Laravel Cloud

echo "Starting Laravel Cloud deployment..."

# Disable Octane for Laravel Cloud (use FPM instead)
export OCTANE_SERVER=

# Run database migrations
php artisan migrate --force --no-interaction

# Clear and optimize caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets if needed
if [ -f "package.json" ]; then
    npm run build 2>/dev/null || echo "No build script found"
fi

echo "Deployment complete!"
