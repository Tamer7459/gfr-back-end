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
MIGRATION_SUCCESS=false
for i in {1..30}; do
    php artisan db:wipe --force 2>/dev/null || true
    PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "ROLLBACK" 2>/dev/null || true

    if [ $i -eq 1 ]; then
        echo "First attempt: running migrate:fresh to ensure clean state..."
        if php artisan migrate:fresh --force -v; then
            echo "Migrations completed successfully"
            MIGRATION_SUCCESS=true
            break
        else
            echo "=== MIGRATION ERROR OUTPUT ABOVE ==="
        fi
    else
        if php artisan migrate --force -v; then
            echo "Migrations completed successfully"
            MIGRATION_SUCCESS=true
            break
        else
            echo "=== MIGRATION ERROR OUTPUT ABOVE ==="
        fi
    fi
    echo "Migration attempt $i failed, retrying in 2 seconds..."
    sleep 2
done

if [ "$MIGRATION_SUCCESS" = false ]; then
    echo "ERROR: Migrations failed after 30 attempts. Exiting."
    exit 1
fi

echo "==> Seeding Admin User..."
SEED_SUCCESS=false
for i in {1..5}; do
    if php artisan db:seed --class=AdminUserSeeder --force; then
        echo "Seeding completed successfully"
        SEED_SUCCESS=true
        break
    else
        echo "Seeding attempt $i failed, retrying in 2 seconds..."
        sleep 2
    fi
done

if [ "$SEED_SUCCESS" = false ]; then
    echo "WARNING: Seeding failed after 5 attempts. Continuing anyway..."
fi

echo "==> Starting PHP-FPM..."
php-fpm &

echo "==> Starting Nginx..."
nginx -g "daemon off;"