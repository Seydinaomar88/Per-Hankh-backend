FROM php:8.2-fpm

# Utiliser /var/www comme WORKDIR (comme dans votre docker-compose.yml)
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

# === VÉRIFICATION ET CORRECTION ===
RUN echo "📂 Vérification de la structure..." && \
    ls -la /var/www/ && \
    ls -la /var/www/public/ && \
    if [ ! -f /var/www/public/index.php ]; then \
        echo "❌ index.php manquant ! Création d'un fichier de secours..."; \
        echo '<?php' > /var/www/public/index.php && \
        echo '/**' >> /var/www/public/index.php && \
        echo ' * Laravel - A PHP Framework For Web Artisans' >> /var/www/public/index.php && \
        echo ' *' >> /var/www/public/index.php && \
        echo ' * @package  Laravel' >> /var/www/public/index.php && \
        echo ' * @author   Taylor Otwell <taylor@laravel.com>' >> /var/www/public/index.php && \
        echo ' */' >> /var/www/public/index.php && \
        echo '' >> /var/www/public/index.php && \
        echo 'use Illuminate\Foundation\Application;' >> /var/www/public/index.php && \
        echo 'use Illuminate\Http\Request;' >> /var/www/public/index.php && \
        echo '' >> /var/www/public/index.php && \
        echo 'define("LARAVEL_START", microtime(true));' >> /var/www/public/index.php && \
        echo '' >> /var/www/public/index.php && \
        echo '// Determine if the application is in maintenance mode...' >> /var/www/public/index.php && \
        echo 'if (file_exists($maintenance = __DIR__."/../storage/framework/maintenance.php")) {' >> /var/www/public/index.php && \
        echo '    require $maintenance;' >> /var/www/public/index.php && \
        echo '}' >> /var/www/public/index.php && \
        echo '' >> /var/www/public/index.php && \
        echo '// Register the Composer autoloader...' >> /var/www/public/index.php && \
        echo 'require __DIR__."/../vendor/autoload.php";' >> /var/www/public/index.php && \
        echo '' >> /var/www/public/index.php && \
        echo '// Bootstrap Laravel and handle the request...' >> /var/www/public/index.php && \
        echo '/** @var Application $app */' >> /var/www/public/index.php && \
        echo '$app = require_once __DIR__."/../bootstrap/app.php";' >> /var/www/public/index.php && \
        echo '' >> /var/www/public/index.php && \
        echo '$app->handleRequest(Request::capture());' >> /var/www/public/index.php; \
    else \
        echo "✅ index.php présent"; \
        cat /var/www/public/index.php | head -n 5; \
    fi
# === FIN VÉRIFICATION ===

# Création des dossiers nécessaires
RUN mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && mkdir -p storage/app/public \
    && mkdir -p bootstrap/cache

# Suppression du lien symbolique problématique
RUN rm -rf public/storage || true

# Création du lien symbolique pour storage
RUN ln -s /var/www/storage/app/public public/storage

# Permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public/storage \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Optimisation finale (sans scripts)
RUN composer dump-autoload --optimize --no-scripts

# Configuration Nginx pour Render (avec root /var/www/public)
RUN echo 'server { \
    listen 10000; \
    server_name localhost; \
    root /var/www/public; \
    index index.php; \
    \
    error_log /var/log/nginx/error.log debug; \
    access_log /var/log/nginx/access.log; \
    \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        fastcgi_param PATH_INFO $fastcgi_path_info; \
        include fastcgi_params; \
        fastcgi_read_timeout 300; \
        fastcgi_connect_timeout 300; \
        fastcgi_send_timeout 300; \
    } \
    \
    location /api { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location /broadcasting/auth { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
}' > /etc/nginx/sites-enabled/default

# Configuration Supervisord (inchangée)
RUN echo '[supervisord]' > /etc/supervisor/conf.d/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'user=root' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:nginx]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/dev/stdout' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile_maxbytes=0' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/dev/stderr' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile_maxbytes=0' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:php-fpm]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=php-fpm -F' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/dev/stdout' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile_maxbytes=0' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/dev/stderr' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile_maxbytes=0' >> /etc/supervisor/conf.d/supervisord.conf

# Script de démarrage
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]