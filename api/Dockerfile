# ---- Production Laravel Backend ----
FROM php:8.4-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files and install dependencies
COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Copy backend source code
COPY . .

# Prepare directories and permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# Run Laravel artisan serve
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
