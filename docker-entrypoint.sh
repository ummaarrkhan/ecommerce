#!/bin/sh
set -e

# Ensure storage symbolic link exists
if [ ! -L /app/public/storage ]; then
    php artisan storage:link --force || true
fi

# Run migrations automatically if RUN_MIGRATIONS is set to true
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || true
fi

# Cache config and routes if APP_KEY is set
if [ -n "$APP_KEY" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

exec "$@"
