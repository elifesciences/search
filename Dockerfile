FROM php:7.4-cli

USER root

RUN apt update && apt install libgearman-dev zip unzip gearman-tools bc procps jq apache2-utils retry -y && pecl install gearman

RUN docker-php-ext-enable gearman \
    && docker-php-ext-configure pcntl --enable-pcntl \
    &&  docker-php-ext-install pcntl

COPY .docker/php.ini /usr/local/etc/php/php.ini

WORKDIR /app

COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer
COPY . /app/
RUN composer install

CMD ["php", "-S", "0.0.0.0:80", "-t", "./web", "./web/app_dev.php"]

EXPOSE 80
