#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

PORT="${PORT:-10000}"
sed -i "s/__PORT__/${PORT}/g" /etc/nginx/sites-available/default

# DB URL normalization for Neon
if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
  export DB_URL="$DATABASE_URL"
fi
if [ -n "${DB_URL:-}" ]; then
  export DB_URL="${DB_URL/postgres:\/\//postgresql:\/\/}"
  case "$DB_URL" in
    *sslmode=*) ;;
    *)
      if [[ "$DB_URL" == *"?"* ]]; then
        export DB_URL="${DB_URL}&sslmode=require"
      else
        export DB_URL="${DB_URL}?sslmode=require"
      fi
      ;;
  esac
fi

if [ -z "${APP_KEY:-}" ]; then
  export APP_KEY="$(php artisan key:generate --show)"
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

php artisan storage:link --force || true
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan migrate --force
if [ "${RUN_SEED:-false}" = "true" ]; then
  php artisan db:seed --force || true
fi
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

php-fpm -D
exec nginx -g 'daemon off;'
