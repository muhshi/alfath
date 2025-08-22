# =========================================
# Stage 1: Build front-end assets (Node)
# =========================================
FROM node:22-bullseye AS assets
WORKDIR /app

RUN corepack enable && corepack prepare pnpm@9 --activate

# pakai cache yang efisien
COPY package.json pnpm-lock.yaml vite.config.js ./
RUN pnpm fetch

COPY resources/ resources/
RUN pnpm install --offline
RUN pnpm run build

# =========================================
# Stage 2: Install composer dependencies (tanpa scripts)
# =========================================
FROM composer:2 AS vendor
WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY composer.json composer.lock ./
# skip scripts (no artisan here) + abaikan ext-intl (akan dipasang di final)
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    --no-scripts --ignore-platform-req=ext-intl

# =========================================
# Stage 3: FrankenPHP final image
# =========================================
FROM dunglas/frankenphp:php8.3 AS final

ENV SERVER_NAME=":80"
WORKDIR /app

# Ekstensi yang dibutuhkan Laravel + Filament
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    zip curl unzip git sqlite3 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl gd zip pdo_mysql pdo_sqlite \
    && docker-php-ext-enable intl gd zip pdo_mysql pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Salin source code dahulu
COPY artisan ./
COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/ database/
COPY public/ public/
COPY resources/ resources/
COPY routes/ routes/
COPY composer.json composer.lock ./

# Buat direktori yang dibutuhkan Laravel
RUN mkdir -p storage bootstrap/cache

# Salin vendor & aset yang sudah terbangun
COPY --from=vendor /app/vendor /app/vendor
COPY --from=assets /app/public/build /app/public/build

# Laravel optimize (jalankan artisan scripts di sini)
# package:discover dulu agar packages.php terbentuk
RUN php artisan key:generate --force || true \
    && php artisan package:discover --ansi \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Permission
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# FrankenPHP (Caddy) config
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80 443 443/udp
