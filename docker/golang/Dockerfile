FROM ubuntu:16.04

MAINTAINER Julian Kleinhans <julian.kleinhans@aoe.com>

ARG STACKFORMATION_VERSION

RUN locale-gen en_US.UTF-8
ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

## Common
RUN apt-get update \
    && apt-get install -y sudo wget curl zip unzip git software-properties-common


## AWS cli
RUN apt-get install -y python3
RUN curl "https://s3.amazonaws.com/aws-cli/awscli-bundle.zip" -o "awscli-bundle.zip" \
    && unzip awscli-bundle.zip \
    && /usr/bin/python3 awscli-bundle/install -i /usr/local/aws -b /usr/bin/aws


## PHP
RUN add-apt-repository -y ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y php7.0-fpm php7.0-cli php7.0-mcrypt php7.0-gd php7.0-mysql \
       php7.0-pgsql php7.0-imap php-memcached php7.0-mbstring php7.0-xml php7.0-curl \
       php7.0-sqlite3 php7.0-xdebug \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && mkdir /run/php
COPY php-fpm.conf /etc/php/7.0/fpm/php-fpm.conf


## Stackformation
RUN wget -q https://github.com/AOEpeople/StackFormation/releases/download/${STACKFORMATION_VERSION}/stackformation.phar \
    && mv stackformation.phar /usr/bin/stackformation \
    && chmod ugo+x /usr/bin/stackformation


## Golang
RUN apt-get update \
    && apt-get install -y git software-properties-common \
    && add-apt-repository -y ppa:ubuntu-lxc/lxd-stable \
    && apt-get update \
    && apt-get install -y golang


## Cleanup
RUN apt-get remove -y --purge software-properties-common \
    && apt-get clean
RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ENTRYPOINT ["stackformation"]
