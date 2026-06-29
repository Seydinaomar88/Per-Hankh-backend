FROM php:8.2-fpm

WORKDIR /var/www/html

# Installation des dépendances système
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

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier uniquement les fichiers nécessaires pour les dépendances
COPY composer.json composer.lock ./

# Installer les dépendances SANS scripts
RUN composer install --optimize-autoloader --no-interaction --no-dev --no-scripts

# Copier le reste de l'application
COPY . .

# Création des dossiers nécessaires
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/app/public \
    && mkdir -p bootstrap/cache

# Suppression du lien symbolique problématique
RUN rm -rf public/storage || true

# Création du lien symbolique pour storage
RUN ln -s /var/www/html/storage/app/public public/storage

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/storage \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Optimisation finale (sans scripts)
RUN composer dump-autoload --optimize --no-scripts

# Script de démarrage qui gérera tout
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8000

CMD ["/usr/local/bin/start.sh"]