#!/usr/bin/env bash
set -e

cd /var/www/html

# Render assigns PORT (often 10000). richarvey defaults to 80.
PORT="${PORT:-80}"
echo "Configuring nginx listen port: ${PORT}"
for f in /etc/nginx/sites-available/* /etc/nginx/sites-enabled/* /etc/nginx/conf.d/*; do
  [ -f "$f" ] || continue
  sed -i -E "s/listen[[:space:]]+\[::\]:80;/listen [::]:${PORT};/g" "$f" || true
  sed -i -E "s/listen[[:space:]]+80;/listen ${PORT};/g" "$f" || true
  sed -i -E "s/listen[[:space:]]+80 /listen ${PORT} /g" "$f" || true
done

# Render/Neon often expose DATABASE_URL; Laravel reads DB_URL
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
  echo "APP_KEY missing — generating temporary key"
  export APP_KEY="$(php artisan key:generate --show)"
fi

echo "Ensuring storage dirs..."
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache || true

echo "Linking public storage..."
php artisan storage:link --force || true

echo "Caching config..."
php artisan config:cache || true

echo "Caching routes..."
php artisan route:cache || true

echo "Caching views..."
php artisan view:cache || true

echo "Running migrations..."
php artisan migrate --force

if [ "${RUN_SEED:-false}" = "true" ]; then
  echo "Seeding database..."
  php artisan db:seed --force || true
fi

echo "Deploy script finished."
