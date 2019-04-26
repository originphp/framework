#
# OriginPHP Framework
# Copyright 2018 - 2019 Jamiel Sharief.
#
# Licensed under The MIT License
# The above copyright notice and this permission notice shall be included in all copies or substantial
# portions of the Software.
#
# @copyright    Copyright (c) Jamiel Sharief
# @link          https://www.originphp.com
# @license      https://opensource.org/licenses/mit-license.php MIT License
#
FROM ubuntu:18.04
LABEL maintainer="Jamiel Sharief"
LABEL version="1.0.0-beta"

# Setup Enviroment

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV DATE_TIMEZONE UTC
ENV DEBIAN_FRONTEND=noninteractive

# Best Practice : Cache Busting - Prevent cache issues run as one command
# @link https://docs.docker.com/develop/develop-images/dockerfile_best-practices/

RUN apt-get update && apt-get install -y \
    curl \
    git \
    mysql-client \
    nano \
    unzip \
    wget \
    zip \
    apache2 \
    libapache2-mod-php \
    php \
    php-cli \
    php-apcu \
    php-cli \
    php-common \
    php-curl \
    php-imap \
    php-intl \
    php-json \
    php-mbstring \
    php-mysql \
    php-opcache \
    php-pear \
    php-readline \
    php-soap \
    php-xml \
    php-zip \
    php-dev \
 && rm -rf /var/lib/apt/lists/*

# Setup Web Server

RUN a2enmod rewrite
RUN a2enmod ssl
COPY . /var/www

RUN chown -R www-data:www-data /var/www
RUN chmod -R 0775 /var/www

ADD apache.conf /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-interaction

# Install PHP Unit
RUN wget https://phar.phpunit.de/phpunit-7.phar
RUN chmod +x phpunit-7.phar
RUN mv phpunit-7.phar /usr/local/bin/phpunit

# Install X-Debug for PHPUnit Code Coverage (Causes major Performance decrease when extension is enabled)
RUN pecl install xdebug
#RUN echo 'zend_extension="/usr/lib/php/20170718/xdebug.so"' >> /etc/php/7.2/cli/php.ini
#RUN echo 'xdebug.default_enable=0' >> /etc/php/7.2/cli/php.ini

# Instructions to run xdebug temporarily i.e to generate code coverage
# To enable until next restart run these commands in bash
# echo 'zend_extension="/usr/lib/php/20170718/xdebug.so"' >> /etc/php/7.2/cli/php.ini
# echo 'xdebug.default_enable=0' >> /etc/php/7.2/cli/php.ini

RUN echo 'apc.enable_cli=1' >>  /etc/php/7.2/cli/php.ini

CMD ["/usr/sbin/apache2ctl", "-DFOREGROUND"]