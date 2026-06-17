#!/bin/bash
set -e

echo "==> Generating APP_KEY if missing..."
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

echo "==> Ensuring storage directories exist..."
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/framework/cache
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/logs

echo "==> Ensuring resources/views directory exists..."
if [ ! -d "/var/www/resources/views" ]; then
    echo "Warning: resources/views directory not found, creating it..."
    mkdir -p /var/www/resources/views
    echo "<!-- placeholder -->" > /var/www/resources/views/placeholder.blade.php
fi

echo "==> Caching Laravel config..."
php artisan config:cache
php artisan route:cache

echo "==> Caching views (non-blocking)..."
php artisan view:cache || echo "Warning: view:cache failed, continuing..."

echo "==> Running migrations (with retry)..."
for i in {1..30}; do
    if php artisan migrate --force; then
        echo "Migrations completed successfully"
        break
    else
        echo "Migration attempt $i failed, retrying in 2 seconds..."
        sleep 2
    fi
done

echo "==> Seeding Admin User..."
for i in {1..5}; do
    if php artisan db:seed --class=AdminUserSeeder --force; then
        echo "Seeding completed successfully"
        break
    else
        echo "Seeding attempt $i failed, retrying in 2 seconds..."
        sleep 2
    fi
done

echo "==> Starting PHP-FPM..."
php-fpm &

echo "==> Starting Nginx..."
nginx -g "daemon off;"