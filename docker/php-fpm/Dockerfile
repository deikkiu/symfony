FROM php:8.2.0-fpm-alpine

# Install packages
RUN apk add --no-cache bash curl git libpng-dev libzip-dev \
    imagemagick-dev mysql-client zlib-dev \
    libjpeg-turbo-dev libwebp-dev libx11-dev libxml2-dev \
    && docker-php-ext-install pdo mysqli pdo_mysql zip sockets \
    && pecl install imagick amqp \
    && docker-php-ext-enable imagick amqp

# Configure non-root user.
ARG PUID=1000
ARG PGID=1000

RUN apk update && apk --no-cache add shadow && \
    groupmod -g ${PGID} www-data && \
    usermod -u ${PUID} -g www-data www-data

# Source code
COPY . /var/www
WORKDIR /var/www

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Permissions
RUN chown -R www-data:www-data /var/www && chmod -R 775 /var/www

# Composer dependencies
#RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --no-autoloader --ignore-platform-req=ext-sockets --ignore-platform-req=ext-amqp

# Commands
COPY ./docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]
EXPOSE 9000