FROM php:8.3-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libjpeg62-turbo-dev \
    libfreetype6-dev libonig-dev libxml2-dev libicu-dev \
    default-mysql-client supervisor \
    && rm -rf /var/lib/apt/lists/*

# PHP extension configuration
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Redis extension (phpredis — matches REDIS_CLIENT=phpredis in .env)
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP / OPcache config
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /var/www/html

# Non-root user matching host UID (avoids WSL bind-mount permission issues)
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} appgroup \
    && useradd -u ${UID} -g appgroup -m appuser
USER appuser

EXPOSE 9000
CMD ["php-fpm"]
