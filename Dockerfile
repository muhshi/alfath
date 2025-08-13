# =========================================
# Stage 1: Build front-end assets dengan Node
# =========================================
FROM node:22-alpine AS assets
WORKDIR /app

# Aktifkan pnpm via corepack
RUN corepack enable && corepack prepare pnpm@9 --activate

# Salin file lock & manifest dulu agar cache install efektif
COPY package.json pnpm-lock.yaml ./
RUN pnpm fetch

# Salin seluruh source lalu install offline + build
COPY . .
RUN pnpm install --offline
RUN pnpm run build

# =========================================
# Stage 2: Install composer dependencies
# =========================================
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
# Salin sisa kode agar dump-autoload bisa finalize (opsional)
COPY . .
RUN composer dump-autoload --optimize --no-dev

# =========================================
# Stage 3: FrankenPHP final image
# =========================================
FROM dunglas/frankenphp:php8.3 AS final

ENV SERVER_NAME=":80"
WORKDIR /app

# OS deps & ekstensi PHP yang umum dipakai Laravel + GD + intl + zip + mysql + sqlite
RUN apt-get update && apt-get install -y \
    libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    zip curl unzip git sqlite3 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl gd zip pdo_mysql pdo_sqlite \
    && docker-php-ext-enable intl gd zip pdo_mysql pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy source code
COPY . .

# Copy vendor & build assets hasil stage sebelumnya
COPY --from=vendor /app/vendor /app/vendor
COPY --from=assets /app/public/build /app/public/build

# Optimasi Laravel (tanpa memaksa jika APP_KEY belum ada)
RUN php artisan key:generate --force || true \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Permission untuk storage & cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Caddyfile untuk FrankenPHP
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80 443 443/udp
