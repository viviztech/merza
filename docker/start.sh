#!/bin/sh
set -e

echo "==> Running migrations..."
php /var/www/html/artisan migrate --force

echo "==> Seeding roles and admin user..."
php /var/www/html/artisan db:seed --class=RolesAndAdminSeeder --force

echo "==> Seeding roles and permissions..."
php /var/www/html/artisan db:seed --class=RolesAndPermissionsSeeder --force

echo "==> Syncing bot settings from environment..."
php /var/www/html/artisan db:seed --class=BotSettingsSeeder --force

echo "==> Seeding products..."
php /var/www/html/artisan db:seed --class=ProductSeeder --force

echo "==> Linking storage..."
php /var/www/html/artisan storage:link --force 2>/dev/null || true

echo "==> Caching config, routes, views..."
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:cache

echo "==> Starting services (nginx + php-fpm + queue + scheduler)..."
exec /usr/bin/supervisord -n -c /etc/supervisord.conf
