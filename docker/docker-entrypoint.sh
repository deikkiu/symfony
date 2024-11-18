#!/bin/bash

exec php-fpm

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until mysqladmin ping -h db_container -u timur -padmin --silent; do
  sleep 1
done

# Run Symfony commands
echo "Running Symfony commands..."
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

apache2-foreground &
php bin/console messenger:consume