#!/bin/bash
set -e

echo "🚀 Démarrage de PER ANKH..."

# === DIAGNOSTIC COMPLET ===
echo "📂 DIAGNOSTIC DE LA STRUCTURE :"
echo "Contenu de /var/www/public :"
ls -la /var/www/public/ 2>/dev/null || echo "❌ /var/www/public n'existe pas"

echo "Contenu de /etc/nginx/sites-enabled :"
ls -la /etc/nginx/sites-enabled/

echo "Configuration Nginx :"
cat /etc/nginx/sites-enabled/default | head -20

echo "Test de l'index.php :"
if [ -f "/var/www/public/index.php" ]; then
    echo "✅ index.php existe"
    head -n 5 /var/www/public/index.php
else
    echo "❌ index.php manquant !"
    exit 1
fi
# === FIN DIAGNOSTIC ===

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

# Démarrer Supervisord (qui gère Nginx et PHP-FPM)
echo "🌐 Démarrage de Nginx sur le port 10000 et PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf