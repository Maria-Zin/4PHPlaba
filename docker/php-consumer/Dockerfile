FROM php:8.4-cli-alpine

RUN apk add --no-cache git unzip postgresql-libs icu-data-full libzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev icu-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql intl zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
