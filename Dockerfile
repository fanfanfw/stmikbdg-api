FROM node:22-bookworm AS assets
WORKDIR /app
COPY package*.json ./
RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
COPY . .
RUN npm run build

FROM php:8.3-cli-bookworm
ENV PHP_UPLOAD_MAX_FILESIZE=20M PHP_POST_MAX_SIZE=25M PHP_MEMORY_LIMIT=512M PHP_MAX_EXECUTION_TIME=180
RUN apt-get update && apt-get install -y git unzip curl libpq-dev libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libcurl4-openssl-dev && docker-php-ext-configure gd --with-freetype --with-jpeg && docker-php-ext-install pdo_pgsql pgsql zip intl bcmath gd mbstring curl exif pcntl && pecl install redis && docker-php-ext-enable redis && printf 'upload_max_filesize=${PHP_UPLOAD_MAX_FILESIZE}\npost_max_size=${PHP_POST_MAX_SIZE}\nmemory_limit=${PHP_MEMORY_LIMIT}\nmax_execution_time=${PHP_MAX_EXECUTION_TIME}\n' > /usr/local/etc/php/conf.d/runtime-limits.ini && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
