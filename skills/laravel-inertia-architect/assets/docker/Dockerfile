# syntax=docker/dockerfile:1
# Multi-stage production image for Laravel 13 + Inertia (React) with SSR.
# One image runs every role (web, worker, scheduler, ssr); docker-compose picks the command.
# Verify current tags at https://serversideup.net/open-source/docker-php/ before using.

############################
# 1. Composer dependencies
############################
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

############################
# 2. Frontend build (client + SSR bundle)
############################
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# Wayfinder generates TS from PHP routes during the Vite build and needs vendor/ + php.
# If your build calls `php artisan wayfinder:generate`, either commit the generated files
# or build this stage FROM an image that has PHP + Node.
COPY --from=vendor /app/vendor ./vendor
RUN npm run build:ssr

############################
# 3. Runtime
############################
FROM serversideup/php:8.4-fpm-nginx AS runtime

USER root
RUN install-php-extensions intl gd exif bcmath pcntl redis pdo_pgsql \
    && apt-get update \
    && apt-get install -y --no-install-recommends jpegoptim optipng pngquant gifsicle webp \
    && rm -rf /var/lib/apt/lists/*
# Node runtime for the SSR role (Debian's apt nodejs is too old for Inertia v3); drop if SSR is disabled.
COPY --from=node:24-slim /usr/local/bin/node /usr/local/bin/node

ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=false \
    LOG_CHANNEL=stderr

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
COPY --chown=www-data:www-data --from=assets /app/bootstrap/ssr ./bootstrap/ssr

USER www-data
EXPOSE 8080
