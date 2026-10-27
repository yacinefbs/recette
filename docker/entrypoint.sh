#!/bin/sh
set -e
cd /var/www/html

[ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist
[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan migrate --force || echo "Migration ignorée (DB pas prête ?)"

exec docker-php-entrypoint "$@"
