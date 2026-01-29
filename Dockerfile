FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    libicu-dev \
    zlib1g-dev \
    libzip-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure intl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
        zip

# Enable extensions
RUN docker-php-ext-enable intl zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --prefer-dist

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

EXPOSE 8000

# Startup script with error handling
CMD sh -c "set -e && echo '🚀 Starting Zyg application...' && php artisan package:discover --ansi && echo '📦 Running migrations...' && php artisan migrate --force || (echo '❌ Migration failed! Check database connection.' && exit 1) && echo '🌱 Seeding database...' && php artisan db:seed --class=DemoSeeder --force || echo '⚠️  Seeding failed or skipped' && echo '✅ Starting web server...' && php -S 0.0.0.0:\$PORT -t public"
