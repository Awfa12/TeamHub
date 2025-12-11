#!/usr/bin/env bash
set -euo pipefail

echo "Building frontend assets..."
npm run build

echo "Caching config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Pruning caches..."
php artisan cache:clear
php artisan event:cache

echo "Done. Ready for deploy."


