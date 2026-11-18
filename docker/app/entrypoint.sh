#!/bin/sh
set -e

php bin/console cache:clear --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
chown -R www-data:www-data var

exec docker-php-entrypoint "$@"
