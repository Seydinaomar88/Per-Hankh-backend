#!/bin/bash
set -e

echo "🚀 Démarrage de PER ANKH..."

# Variables d'environnement par défaut si non définies
export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}

# Configuration des broadcast si nécessaire
if [ -z "$PUSHER_APP_KEY" ]; then
    echo "⚠️  PUSHER_APP_KEY non défini, utilisation de valeurs par défaut"
    export PUSHER_APP_KEY=${PUSHER_APP_KEY:-dummy}
    export PUSHER_APP_SECRET=${PUSHER_APP_SECRET:-dummy}
    export PUSHER_APP_ID=${PUSHER_APP_ID:-dummy}
fi

# Attente de la base de données
echo "⏳ Vérification de la base de données..."
php artisan migrate --force || echo "⚠️  Migration ignorée"

# Exécution des scripts Composer
echo "📦 Exécution des scripts post-installation..."
php artisan package:discover || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Création du lien storage (ignore si déjà existant)
php artisan storage:link 2>/dev/null || true

# Permissions finales
chown -R www-data:www-data storage bootstrap/cache public/storage 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "✅ Application prête !"

# Le port 10000 est le port par défaut de Render
echo "🌐 Démarrage sur le port 10000..."

# Démarrer PHP-FPM sur le port 10000
/usr/sbin/php-fpm --nodaemonize --port 10000