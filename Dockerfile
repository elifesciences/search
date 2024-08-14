##
## base image setup
#
FROM php:7.4-cli as base

USER root

RUN apt update && apt install libgearman-dev zip unzip gearman-tools bc procps jq apache2-utils retry -y && rm -rf /var/lib/apt/lists/*
RUN pecl install gearman && docker-php-ext-enable gearman
RUN docker-php-ext-configure pcntl --enable-pcntl &&  docker-php-ext-install pcntl

COPY .docker/php.ini /usr/local/etc/php/php.ini
WORKDIR /app


##
## Composer Dependency builder
#
FROM base as deps
COPY composer.json composer.json
COPY composer.lock composer.lock
COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer
RUN composer install

##
## Application builder
#
FROM base as app
COPY . /app/
COPY --from=deps /app/vendor /app/vendor

##
## Dev environment
#
FROM app as dev

COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer

# Install additional tools needed for tests
RUN apt update && apt install apache2-utils retry -y

# Use the PHP dev server to run the app
CMD ["php", "-S", "0.0.0.0:80", "-t", "./web", "./web/app_dev.php"]
EXPOSE 80

##
## Prod environment
#
FROM app as prod

# TODO: Replace with a more production ready webserver
CMD ["php", "-S", "0.0.0.0:80", "-t", "./web", "./web/app_dev.php"]
EXPOSE 80
