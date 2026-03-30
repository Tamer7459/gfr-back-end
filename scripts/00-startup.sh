#!/usr/bin/env bash

# أنشئ الـ .env من متغيرات Render
cat > /var/www/html/.env << EOF
APP_NAME=GFR
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_URL=https://gfr-back-end.onrender.com
LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_SSLMODE=require

SANCTUM_STATEFUL_DOMAINS=grf-project.netlify.app
SESSION_DOMAIN=.netlify.app
FRONTEND_URL=https://grf-project.netlify.app
CORS_ALLOWED_ORIGINS=https://grf-project.netlify.app
SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
EOF

php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan migrate --force