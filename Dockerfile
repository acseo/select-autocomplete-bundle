FROM        php:7.3-cli-alpine

#
# Install system dependencies of PHP extensions.
#
RUN         apk update && \
            apk add --update wget curl libxml2-dev zip unzip libzip-dev autoconf build-base openssl-dev pkgconfig libressl-dev

#
# XDebug for code analyzing
#
RUN         apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
            && pecl install xdebug \
            && docker-php-ext-enable xdebug \
            && apk del -f .build-deps

#
# Install PHP extensions
#
RUN         docker-php-ext-install \
              iconv \
              mysqli \
              opcache \
              pdo \
              pdo_mysql \
              xml

RUN         pecl install \
                apcu \
                mongodb \
            && docker-php-ext-enable \
                apcu \
                opcache

RUN          echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongo.ini

#
# Install composer
#
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer --version=1.10.16

#
# Set working dir
#
VOLUME      /app
WORKDIR     /app

#
# Copy project files
#
COPY        . /app

#
# Keep container alive
#
CMD         tail -f /dev/null
