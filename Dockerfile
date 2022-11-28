FROM php:7.4-cli

RUN apt update && apt install libgearman-dev -y && pecl install gearman

RUN docker-php-ext-enable gearman \
    && docker-php-ext-configure pcntl --enable-pcntl \
    &&  docker-php-ext-install pcntl

COPY .docker/php.ini /usr/local/etc/php/php.ini

WORKDIR /app
