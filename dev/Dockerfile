#FROM composer:2.4 as composer
FROM php:7.4-cli as php

USER root

RUN apt update && apt install libgearman-dev zip unzip -y && pecl install gearman

RUN docker-php-ext-enable gearman \
    && docker-php-ext-configure pcntl --enable-pcntl \
    &&  docker-php-ext-install pcntl

COPY .docker/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer

ENTRYPOINT ["php", "-S", "0.0.0.0:8888", "-t", "./web", "./web/app_dev.php"]

EXPOSE 8888
