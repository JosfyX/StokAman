FROM php:8.3-cli-alpine AS base

RUN apk add --no-cache \
    git \
    unzip \
    curl \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    mysql-client \
    && docker-php-ext-install pdo_mysql mbstring zip gd bcmath

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

FROM base AS test
COPY composer.json composer.lock ./

RUN composer install --no-interaction --prefer-dist --no-scripts
COPY . .

RUN cp .env.example .env \
    && php artisan package:discover --ansi \
    && php artisan key:generate \
    && php artisan test


FROM base AS production
COPY composer.json composer.lock ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts
COPY . .

RUN cp .env.example .env \
    && php artisan package:discover --ansi \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
