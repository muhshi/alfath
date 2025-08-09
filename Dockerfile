# Gunakan image FrankenPHP resmi
FROM dunglas/frankenphp:php8.3

ENV SERVER_NAME=":80"

# Atur working directory di dalam container
WORKDIR /app

# Install dependensi sistem dan ekstensi PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) intl gd zip pdo_mysql \
    && docker-php-ext-enable intl gd zip pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy seluruh file project ke container
COPY . /app

# Copy composer dari image resminya
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Install dependencies Laravel
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Set permission untuk Laravel
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Expose port untuk FrankenPHP
EXPOSE 80
EXPOSE 443
EXPOSE 443/udp

# Copy konfigurasi Caddy (FrankenPHP)
COPY Caddyfile /etc/caddy/Caddyfile
