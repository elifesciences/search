FROM debian:stable-slim

USER root

RUN  apt-get update \
    && echo 'debconf debconf/frontend select Noninteractive' | debconf-set-selections \
    && apt-get install -y apt-utils apt-transport-https lsb-release ca-certificates wget \
    && wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
    && echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-get -y install php7.2 \
    && apt-get -y install php7.2 php7.2-cli php7.2-common php7.2-json php7.2-opcache php7.2-mysql php7.2-zip php7.2-fpm php7.2-mbstring php7.2-dev php7.2-xml\
    && apt-get -y install php-pear \
    && apt-get update \
    && apt-get clean all \
    && apt-get install -y libgearman-dev \
    && pecl install gearman

EXPOSE 4730