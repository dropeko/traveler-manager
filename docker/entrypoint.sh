#!/usr/bin/env sh
set -e

cd /var/www/html

# Ensure Laravel writable dirs exist
mkdir -p storage bootstrap/cache

# Fix permissions (best-effort for bind mounts)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Install PHP deps if missing
if [ ! -f vendor/autoload.php ]; then
  echo "Installing composer dependencies..."
  composer install --no-interaction --prefer-dist
fi

# Generate app key if missing
if [ -f .env ]; then
  if ! grep -q '^APP_KEY=.' .env; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
  fi
fi

exec "$@"
