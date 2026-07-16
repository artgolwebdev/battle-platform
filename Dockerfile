FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install \
        exif \
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

RUN npm install
RUN npm run build

CMD php artisan serve --host=0.0.0.0 --port=$PORT