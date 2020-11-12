FROM        php:7.3-cli-alpine

#
# Install system dependencies of PHP extensions.
#
RUN         apk update && \
            apk add --update wget curl libxml2-dev zip unzip libzip-dev

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

#
# Install composer
#
ENV         COMPOSER_HOME=/var/composer
COPY        ./docker/composer-install /tmp/composer-install
RUN         chmod +x /tmp/composer-install && \
            /tmp/composer-install && \
            rm /tmp/composer-install
RUN         mkdir -p /var/composer && \
            chown -R www-data:www-data /var/composer
VOLUME      /var/composer

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
