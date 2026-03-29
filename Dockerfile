# Base image
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# تثبيت الإضافات المطلوبة
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# نسخ ملفات المشروع
COPY . .

# تثبيت الاعتماديات
RUN composer install --no-dev --optimize-autoloader

# Expose port
EXPOSE 8000

# Command to run Laravel
CMD php artisan serve --host=0.0.0.0 --port=8000