# syntax=docker/dockerfile:1.7

FROM composer:2.8 AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-reqs

FROM node:22-bookworm-slim AS assets

WORKDIR /var/www/html

COPY package.json package-lock.json ./

RUN npm ci

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor

RUN npm run build

FROM dunglas/frankenphp:php8.3-bookworm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    imagick \
    intl \
    mbstring \
    opcache \
    pcntl \
    pdo_mysql \
    posix \
    redis \
    xml \
    zip

COPY --from=vendor /var/www/html/vendor ./vendor
COPY . .
COPY --from=assets /var/www/html/public/build ./public/build

COPY docker/production/php.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/production/entrypoint.sh /usr/local/bin/toko-entrypoint

RUN chmod +x /usr/local/bin/toko-entrypoint \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && php artisan package:discover --ansi \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache

ENV APP_ENV=production \
    APP_DEBUG=false

EXPOSE 80 443

ENTRYPOINT ["/usr/local/bin/toko-entrypoint"]
