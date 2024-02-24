ARG PHP_VERSION

FROM php:${PHP_VERSION}-fpm

# System dependencies
RUN apt update && apt install -y \
    curl

#> PHP extensions

# XDebug
RUN pecl install \
    xdebug \
&& docker-php-ext-enable \
    xdebug

# Opcache
RUN docker-php-ext-install \
    opcache

# APCu
RUN pecl install \
    apcu \
&& docker-php-ext-enable \
    apcu

# PDO
RUN docker-php-ext-install \
    pdo pdo_mysql

# Zip
RUN apt install -y \
    libzip-dev zip unzip \
&& docker-php-ext-install \
    zip

# Exif
RUN docker-php-ext-install \
    exif

# Imagick
RUN apt update && apt install -y \
    libmagickwand-dev \
&& pecl install \
    imagick \
&& docker-php-ext-enable \
    imagick

#< PHP extensions

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
