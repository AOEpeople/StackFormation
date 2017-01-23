FROM ubuntu:16.04

MAINTAINER Julian Kleinhans <julian.kleinhans@aoe.com>

ARG STACKFORMATION_VERSION

RUN locale-gen en_US.UTF-8

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

RUN apt-get update \
    && apt-get install -y wget curl zip unzip git software-properties-common \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y php7.0-fpm php7.0-cli php7.0-mcrypt php7.0-gd php7.0-mysql \
       php7.0-pgsql php7.0-imap php-memcached php7.0-mbstring php7.0-xml php7.0-curl \
       php7.0-sqlite3 php7.0-xdebug \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && mkdir /run/php \
    && apt-get remove -y --purge software-properties-common \
    && apt-get -y autoremove \
    && apt-get clean

RUN wget -q https://github.com/AOEpeople/StackFormation/releases/download/${STACKFORMATION_VERSION}/stackformation.phar \
    && mv stackformation.phar /usr/bin/stackformation \
    && chmod ugo+x /usr/bin/stackformation

RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY php-fpm.conf /etc/php/7.0/fpm/php-fpm.conf

ENTRYPOINT ["stackformation"]
