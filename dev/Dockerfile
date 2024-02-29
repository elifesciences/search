FROM php:7.4-cli

USER root

RUN apt update && apt install zip unzip bc procps jq apache2-utils -y

RUN docker-php-ext-configure pcntl --enable-pcntl \
    &&  docker-php-ext-install pcntl

RUN pecl install xdebug-3.1.5 \
      && docker-php-ext-enable xdebug

COPY .docker/php.ini /usr/local/etc/php/php.ini
COPY .docker/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /app

COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer

ENTRYPOINT ["php", "-S", "0.0.0.0:80", "-t", "./web", "./web/app_dev.php"]

EXPOSE 80
