FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev libonig-dev libpng-dev libjpeg-dev libfreetype6-dev libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd

RUN apt install -y libbrotli-dev

# Install Swoole extension
RUN pecl install swoole \
    && docker-php-ext-enable swoole

# RUN pecl install openswoole \
#     && docker-php-ext-enable openswoole
RUN docker-php-ext-install pcntl

COPY ./config/php/php.ini /usr/local/etc/php/php.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# DEV
# DEV => Node JS
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g chokidar-cli
# DEV => Restart Service
RUN apt-get install -y lsof procps

# Supervisor
RUN apt update && apt install -y supervisor
COPY ./config/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY ./config/scripts/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

ENTRYPOINT ["start-container"]

# Set working dir
WORKDIR /app

# Copy Laravel project
COPY public /app

# Install PHP deps
RUN composer install --no-interaction --prefer-dist

# Expose swoole port
EXPOSE 8000

# Run Laravel Swoole HTTP Server
# CMD ["php", "artisan", "swoole:http", "start"]

# php artisan octane:start --host=0.0.0.0 --port=8000 --watch