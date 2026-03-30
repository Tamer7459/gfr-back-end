#!/bin/bash
set -e

echo "==> Generating APP_KEY if missing..."
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

echo "==> Caching Laravel config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding Admin User..."
php artisan db:seed --class=AdminUserSeeder --force

echo "==> Starting PHP-FPM..."
php-fpm &

echo "==> Starting Nginx..."
nginx -g "daemon off;"