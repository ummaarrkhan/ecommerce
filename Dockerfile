# Stage 1: Build frontend assets
FROM node:22-alpine AS assets

WORKDIR /app

COPY package*.json ./

RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi

COPY . .

RUN npm run build


# Stage 2: Laravel Application with FrankenPHP
FROM dunglas/frankenphp:1-php8.3-bookworm

# Install required PHP extensions for both MySQL and PostgreSQL
RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    bcmath \
    intl \
    zip \
    gd \
    opcache \
    pcntl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

COPY . .

COPY --from=assets /app/public/build /app/public/build

RUN composer dump-autoload --no-dev --optimize \
    && mkdir -p storage/framework/cache/data \
       storage/framework/sessions \
       storage/framework/views \
       storage/logs \
       bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY Caddyfile /etc/frankenphp/Caddyfile

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
