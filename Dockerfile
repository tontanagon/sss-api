FROM php:8.2-cli

WORKDIR /app

# System deps
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader

# Laravel optimize
RUN php artisan key:generate || true
RUN php artisan storage:link || true
RUN php artisan config:clear || true
RUN php artisan route:clear || true

# Railway port
CMD php -S 0.0.0.0:$PORT -t public
