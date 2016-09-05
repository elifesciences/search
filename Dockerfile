FROM php:7.0-fpm

RUN apt-get update && \
    apt-get upgrade -y

RUN apt-get install -y git curl wget zlibc zlib1g zlib1g-dev

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer
#RUN curl -sS https://github.com/puli/cli/releases/download/1.0.0-beta10/puli.phar && \
#    mv puli.phar /usr/local/bin/puli && \
#    chmod 755  /usr/local/bin/puli


RUN echo 'date.timezone = "Europe/London"' >> /usr/local/etc/php/conf.d/php.ini
RUN docker-php-ext-install zip

COPY composer.json /searchapi/composer.json
COPY composer.lock /searchapi/composer.lock
RUN cd /searchapi && composer install --no-scripts --no-autoloader --no-interaction --no-dev

COPY . /searchapi

RUN mkdir -p /searchapi/cache && \
    chmod 777 /searchapi/cache -R

WORKDIR /searchapi
