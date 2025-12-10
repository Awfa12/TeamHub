FROM php:8.4-fpm-bookworm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libssl-dev \
    default-mysql-client \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl pdo_mysql bcmath pcntl opcache zip mbstring xml gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP config overrides
COPY php.ini /usr/local/etc/php/conf.d/99-teamhub.ini

# Permissions for www-data
RUN chown -R www-data:www-data /var/www/html

USER www-data

CMD ["php-fpm"]

