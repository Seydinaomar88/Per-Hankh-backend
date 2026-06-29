FROM php:8.2-fpm

# Utiliser /var/www comme WORKDIR
WORKDIR /var/www

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
    nginx \
    supervisor \
    sudo \
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

# Création des structures de dossiers requises
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/app/public \
    && mkdir -p bootstrap/cache

# Nettoyage et application stricte des permissions lors du build
RUN rm -rf public/storage || true \
    && chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

# Optimisation finale (sans scripts)
RUN composer dump-autoload --optimize --no-scripts

# Supprimer la configuration Nginx par défaut
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default

# Création directe de la configuration Nginx propre pour Render
RUN printf 'server {\n\
    listen 10000;\n\
    server_name localhost;\n\
    root /var/www/public;\n\
    index index.php index.html;\n\
    location / {\n\
        try_files $uri $uri/ /index.php?$query_string;\n\
    }\n\
    location ~ \\.php$ {\n\
        include fastcgi_params;\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_index index.php;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
    }\n\
}\n' > /etc/nginx/sites-available/default

# Activer la configuration Nginx
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Configuration Supervisord
RUN printf '[supervisord]\n\
nodaemon=true\n\
user=root\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
\n\
[program:php-fpm]\n\
command=php-fpm -F\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n' > /etc/supervisor/conf.d/supervisord.conf

# Script de démarrage
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]