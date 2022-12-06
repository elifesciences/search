FROM composer:2.4 as composer
FROM php:7.4-cli as php

RUN apt update && apt install libgearman-dev -y && pecl install gearman

RUN docker-php-ext-enable gearman \
    && docker-php-ext-configure pcntl --enable-pcntl \
    &&  docker-php-ext-install pcntl

COPY .docker/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

COPY --from=composer /usr/bin/composer /app

EXPOSE 8888
