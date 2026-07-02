FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
    nginx \
    supervisor \
    nodejs \
    npm \
    git \
    curl \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    imagemagick-dev \
    imagemagick \
    $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        xml \
        zip \
        gd \
        intl \
        bcmath \
        opcache \
        pcntl

RUN pecl install redis imagick \
    && docker-php-ext-enable redis imagick \
    && apk del $PHPIZE_DEPS

# PHP production config
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "upload_max_filesize=50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=50M" >> /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first (layer cache)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Install Node deps and build assets
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY . .

RUN npm run build \
    && composer run-script post-autoload-dump \
    && rm -rf node_modules

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy Docker configs
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
