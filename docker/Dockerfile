FROM php:8.4-fpm-alpine

ARG WWWUSER=1000
ARG WWWGROUP=1000

RUN apk add --no-cache \
        bash \
        git \
        curl \
        unzip \
        nodejs \
        npm \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN addgroup -g "${WWWGROUP}" vellum 2>/dev/null \
    || addgroup vellum \
    ; adduser -D -u "${WWWUSER}" -G vellum vellum 2>/dev/null \
    || true

RUN sed -i "s/^user = .*/user = vellum/; s/^group = .*/group = vellum/" \
        /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html
