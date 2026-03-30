#!/usr/bin/env bash

echo "=== Running composer install ==="
composer install --no-dev --working-dir=/var/www/html

echo "=== Generating application key ==="
php artisan key:generate --force

echo "=== Caching config ==="
php artisan config:cache

echo "=== Caching routes ==="
php artisan route:cache

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Done! ==="