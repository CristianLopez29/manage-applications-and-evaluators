#!/bin/sh
set -e

# Every role (web, queue, scheduler) shares this image, so waiting for the
# database here means no container starts doing work against a cold MySQL.
if [ -n "$DB_HOST" ]; then
    echo "Waiting for ${DB_HOST}:${DB_PORT:-3306} ..."
    timeout="${DB_WAIT_TIMEOUT:-60}"
    elapsed=0
    until php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT:-3306}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
        elapsed=$((elapsed + 2))
        if [ "$elapsed" -ge "$timeout" ]; then
            echo "Database not reachable after ${timeout}s; giving up." >&2
            exit 1
        fi
        sleep 2
    done
fi

# Only the web role owns schema and cache state. Running these from the queue
# and scheduler containers too would race three migrators against one database.
if [ "${CONTAINER_ROLE:-web}" = "web" ]; then
    php artisan migrate --force --no-interaction

    # Rebuilt on every boot rather than baked into the image: the caches embed
    # environment values, and the image is built once but runs with whatever
    # .env the server provides.
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan l5-swagger:generate || echo "Swagger generation failed; continuing." >&2

    # storage:link is idempotent but fails loudly if the link already exists.
    php artisan storage:link 2>/dev/null || true
fi

# The artisan commands above run as root, so the caches and any log they touched
# are root-owned; php-fpm workers run as www-data and could not then write.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

exec "$@"
