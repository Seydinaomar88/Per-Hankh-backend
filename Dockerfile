FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    bcmath \
    gd \
    zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer les dossiers nécessaires
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/app/public \
    && mkdir -p bootstrap/cache

COPY . .

# Supprimer le lien symbolique s'il existe
RUN rm -rf public/storage || true

# Créer un lien symbolique qui pointe vers le volume
RUN ln -s /var/www/storage/app/public public/storage

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public/storage \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Optimisation
RUN composer install --optimize-autoloader --no-interaction --no-dev \
    && composer dump-autoload --optimize

EXPOSE 9000

CMD ["php-fpm"]