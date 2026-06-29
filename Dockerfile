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

# Configuration Nginx stable pour Render
RUN cat << 'EOF' > /etc/nginx/sites-available/default
server {
    listen 10000 default_server;
    listen [::]:10000 default_server;
    server_name _;
    root /var/www/public;
    index index.php index.html;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass localhost:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
EOF

# Activer la configuration Nginx
RUN ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Configuration Supervisord
RUN cat << 'EOF' > /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

# Script de démarrage
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]