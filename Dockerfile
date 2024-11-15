FROM php:8.2-apache

# PHP and required modules
RUN apt-get update && apt-get install -y \
    git zip unzip libpng-dev \
    libzip-dev default-mysql-client libmagickwand-dev librabbitmq-dev libssh-dev \
    && docker-php-ext-install pdo mysqli pdo_mysql zip sockets \
    && pecl install imagick amqp \
    && docker-php-ext-enable imagick amqp

# Apache rewrite
RUN a2enmod rewrite

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache configuration
COPY docker/apache.conf /etc/apache2/sites-enabled/000-default.conf

COPY . /var/www
WORKDIR /var/www

# Permissions
RUN chown -R www-data:www-data /var/www && chmod -R 775 /var/www

# Composer dependencies
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-scripts --no-autoloader --ignore-platform-req=ext-sockets --ignore-platform-req=ext-amqp

# Symfony commands
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["docker-entrypoint.sh"]