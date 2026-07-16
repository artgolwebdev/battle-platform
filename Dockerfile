FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    && docker-php-ext-configure gd \
    && docker-php-ext-install \
        exif \
        pdo \
        pdo_mysql \
        mbstring \
        gd \
        zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

CMD php artisan serve --host=0.0.0.0 --port=$PORT