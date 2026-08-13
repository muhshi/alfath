# =========================================
# Stage 1: Base PHP + dependency
# =========================================
FROM dunglas/frankenphp:php8.4 AS base

ENV SERVER_NAME=":80"
WORKDIR /app

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip curl unzip git \
    python3 python3-venv python3-pip make g++ \
    openfortivpn tmux \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl gd zip pdo_mysql \
    && docker-php-ext-enable intl gd zip pdo_mysql \
    && pip3 install --no-cache-dir pandas openpyxl pymysql --break-system-packages \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo "upload_max_filesize = 100M\npost_max_size = 100M\nmemory_limit = 512M\nmax_execution_time = 600" > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js & pnpm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g pnpm

# =========================================
# Stage 2: Build dependencies
# =========================================
FROM base AS build

WORKDIR /app

# Copy file yang dibutuhkan untuk install dependencies
COPY composer.json composer.lock package.json pnpm-lock.yaml vite.config.* ./

# Copy seluruh source code
COPY . .

# Install Laravel dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Install frontend dependencies
RUN CI=true pnpm install --frozen-lockfile

# Build frontend
RUN pnpm run build

# =========================================
# Stage 3: Final image
# =========================================
FROM base AS final

WORKDIR /app

# Copy hasil build dari stage build
COPY --from=build /app /app

# Set permission Laravel
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache /app/database

EXPOSE 80 443 443/udp

COPY Caddyfile /etc/caddy/Caddyfile
