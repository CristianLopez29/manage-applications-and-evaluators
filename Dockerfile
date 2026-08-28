# Production image: nginx + php-fpm under supervisord, one container per app.
#
# Deliberately not built from vendor/laravel/sail: that runtime is a development
# image (node, xdebug hooks, a uid-mapped user) and expects the project to be
# bind-mounted with vendor/ already installed on the host. Here the code is
# baked in and dependencies are installed at build time with --no-dev.

# --- Stage 1: PHP with every extension the app needs ------------------------
# The dependency install and the runtime share this stage on purpose, so
# Composer resolves platform requirements (ext-gd, ext-intl, ext-zip) against
# exactly the PHP that will run them. Installing from the stock composer image
# instead would fail the platform check, and --ignore-platform-req would only
# hide a real mismatch until runtime.
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
      nginx \
      supervisor \
      icu-libs \
      libzip \
      libpng \
      freetype \
      libjpeg-turbo \
      oniguruma \
 && apk add --no-cache --virtual .build-deps \
      $PHPIZE_DEPS \
      icu-dev \
      libzip-dev \
      libpng-dev \
      freetype-dev \
      libjpeg-turbo-dev \
      oniguruma-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
      pdo_mysql \
      bcmath \
      intl \
      zip \
      gd \
      opcache \
      pcntl \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del .build-deps \
 && rm -rf /tmp/pear

# --- Stage 2: dependencies --------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy only what affects resolution first, so the install layer is reused
# whenever application code changes but dependencies do not.
COPY composer.json composer.lock ./

# --no-scripts: artisan is not present yet, and package:discover would fail.
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --prefer-dist \
      --no-interaction \
      --no-progress

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# --- Stage 3: runtime -------------------------------------------------------
FROM base AS runtime

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app .

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# storage/ and bootstrap/cache are the only writable paths; everything else
# stays read-only to the runtime user.
RUN mkdir -p storage/framework/cache storage/framework/sessions \
             storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 8080

# Traefik health-checks this; it must not require the health token, so it hits
# the framework's own /up route rather than /api/health.
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
  CMD wget -qO- http://127.0.0.1:8080/up >/dev/null 2>&1 || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
