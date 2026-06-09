FROM php:8.0-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql

RUN pecl channel-update pecl.php.net \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    || echo "pcov not installed — coverage unavailable but tests will run"

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
