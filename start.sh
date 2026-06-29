#!/bin/bash
set -e

echo "🚀 Démarrage de PER ANKH..."

# === DIAGNOSTIC COMPLET ===
echo "📂 DIAGNOSTIC DE LA STRUCTURE :"
echo "1. Contenu de /var/www/public :"
ls -la /var/www/public/ 2>/dev/null || echo "❌ /var/www/public n'existe pas"

echo "2. Recherche de index.php :"
find /var/www -name "index.php" -type f 2>/dev/null

echo "3. Permissions de index.php :"
ls -la /var/www/public/index.php 2>/dev/null || echo "❌ index.php non trouvé"

echo "4. Contenu de /etc/nginx/sites-enabled :"
ls -la /etc/nginx/sites-enabled/

echo "5. Configuration Nginx chargée :"
cat /etc/nginx/sites-enabled/default | head -30

echo "6. Test de configuration Nginx :"
nginx -t 2>&1 || echo "❌ Erreur de configuration Nginx"

echo "7. Vérification de l'utilisateur Nginx :"
ps aux | grep nginx

echo "8. Vérification de PHP-FPM :"
ps aux | grep php-fpm

echo "9. Test de connexion PHP-FPM :"
echo "<?php echo 'PHP-FPM is working'; ?>" > /tmp/test.php
SCRIPT_FILENAME=/tmp/test.php REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 /tmp/test.php 2>/dev/null || echo "❌ PHP-FPM ne répond pas"

echo "10. Test de l'index.php :"
if [ -f "/var/www/public/index.php" ]; then
    echo "✅ index.php existe"
    head -n 5 /var/www/public/index.php
    # Vérifier si le fichier est lisible par www-data
    sudo -u www-data cat /var/www/public/index.php > /dev/null 2>&1 && echo "✅ index.php lisible par www-data" || echo "❌ index.php non lisible par www-data"
else
    echo "❌ index.php manquant !"
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