FROM php:8.2-fpm

# Install packages
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev libmagickwand-dev \
    unzip git librabbitmq-dev \
    && docker-php-ext-install intl pdo_mysql zip opcache \
    && pecl install imagick amqp \
    && docker-php-ext-enable imagick \amqp

# Source code
COPY . /var/www
WORKDIR /var/www

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Permissions
RUN chown -R www-data:www-data /var/www && chmod -R 775 /var/www

# Composer dependencies
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --no-autoloader --ignore-platform-req=ext-sockets --ignore-platform-req=ext-amqp

# Commands
COPY ./docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]
EXPOSE 9000