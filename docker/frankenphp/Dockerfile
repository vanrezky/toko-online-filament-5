FROM dunglas/frankenphp:php8.3-bookworm

ARG WWWUSER=1000
ARG WWWGROUP=1000

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/frankenphp/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY docker/frankenphp/start.sh /usr/local/bin/start-frankenphp

RUN install-php-extensions \
        bcmath \
        exif \
        gd \
        imagick \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        posix \
        xml \
        zip \
    && groupmod --gid "${WWWGROUP}" www-data \
    && usermod --uid "${WWWUSER}" --gid "${WWWGROUP}" www-data \
    && chmod +x /usr/local/bin/start-frankenphp

USER www-data

EXPOSE 8080
