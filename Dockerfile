##
## base image setup
#
FROM php:8.3-apache@sha256:d9bfe53cbe358ab5c43144991fb7b95ffb84180ae947a5e4dc3e7a095605742d AS base

USER root

RUN apt-get update && apt-get install zip unzip bc procps jq -y && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-configure pcntl --enable-pcntl &&  docker-php-ext-install pcntl

COPY .docker/php.ini /usr/local/etc/php/php.ini
WORKDIR /app


##
## Dev environment
#
FROM base AS dev

COPY --from=composer:2.9@sha256:8b4d59fde3bd505c5fef70c9f8d5c05e92af811fed037dad12869b373925ed31 /usr/bin/composer /usr/bin/composer

# Install additional tools needed for tests
RUN apt-get update && apt-get install retry -y

# Use the PHP dev server to run the app
CMD ["php", "-S", "0.0.0.0:80", "-t", "./web", "./web/app_dev.php"]
EXPOSE 80

##
## Composer Dependency builder for prod
#
FROM base AS deps
COPY composer.json composer.json
COPY composer.lock composer.lock
COPY --from=composer:2.9@sha256:8b4d59fde3bd505c5fef70c9f8d5c05e92af811fed037dad12869b373925ed31 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev

##
## Prod environment
#
FROM base AS prod

COPY bin /app/bin
COPY src /app/src
COPY web /app/web
COPY smoke_test* /app/

COPY --from=deps /app/vendor /app/vendor

ENV APACHE_DOCUMENT_ROOT=/app/web
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -ri -e 's!</VirtualHost>!\tFallbackResource app_prod.php\n</VirtualHost>!g' /etc/apache2/sites-available//000-default.conf
EXPOSE 80

RUN mkdir /app/var
RUN chown -R www-data:www-data /app/var
