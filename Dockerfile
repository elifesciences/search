##
## base image setup
#
FROM php:7.4-apache AS base

USER root

RUN apt-get update && apt-get install libgearman-dev zip unzip gearman-tools bc procps jq -y && rm -rf /var/lib/apt/lists/*
RUN pecl install gearman && docker-php-ext-enable gearman
RUN docker-php-ext-configure pcntl --enable-pcntl &&  docker-php-ext-install pcntl

COPY .docker/php.ini /usr/local/etc/php/php.ini
WORKDIR /app


##
## Composer Dependency builder
#
FROM base AS deps
COPY composer.json composer.json
COPY composer.lock composer.lock
COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer
RUN composer install

##
## Application builder
#
FROM base AS app
COPY . /app/
COPY --from=deps /app/vendor /app/vendor
EXPOSE 80

##
## Dev environment
#
FROM app AS dev

COPY --from=composer:2.4 /usr/bin/composer /usr/bin/composer

# Install additional tools needed for tests
RUN apt-get update && apt-get install retry -y

# Use the PHP dev server to run the app
CMD ["php", "-S", "0.0.0.0:80", "-t", "./web", "./web/app_dev.php"]


##
## Prod environment
#
FROM app AS prod

ENV APACHE_DOCUMENT_ROOT /app/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -ri -e 's!</VirtualHost>!\tFallbackResource app_prod.php\n</VirtualHost>!g' /etc/apache2/sites-available//000-default.conf
RUN chown -R www-data:www-data /app/var
