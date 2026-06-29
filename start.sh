#!/bin/bash
set -e

echo "🚀 Démarrage de PER ANKH..."

# === CORRECTION DES PERMISSIONS ===
echo "🔧 Application des permissions globales..."
chmod 755 /var /var/www /var/www/public 2>/dev/null || true
chown -R www-data:www-data /var/www 2>/dev/null || true

# Forcer PHP-FPM à écouter correctement et à charger l'environnement
if [ -f /usr/local/etc/php-fpm.d/www.conf ]; then
    sed -i 's/;clear_env = no/clear_env = no/g' /usr/local/etc/php-fpm.d/www.conf
    sed -i 's/listen = 127.0.0.1:9000/listen = 0.0.0.0:9000/g' /usr/local/etc/php-fpm.d/www.conf
fi

export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}

if [ -z "$PUSHER_APP_KEY" ]; then
    echo "⚠️  PUSHER_APP_KEY non défini, utilisation de valeurs par défaut"
    export PUSHER_APP_KEY=${PUSHER_APP_KEY:-dummy}
    export PUSHER_APP_SECRET=${PUSHER_APP_SECRET:-dummy}
    export PUSHER_APP_ID=${PUSHER_APP_ID:-dummy}
fi

echo "⏳ Configuration de la base de données..."
php artisan migrate --force || echo "⚠️  Migration ignorée"

echo "📦 Optimisation du cache Laravel..."
php artisan package:discover || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "🔗 Configuration du lien de stockage..."
rm -rf /var/www/public/storage
php artisan storage:link --force || true
chown -h www-data:www-data /var/www/public/storage 2>/dev/null || true

echo "✅ Application prête !"
echo "🌐 Démarrage de Nginx et PHP-FPM via Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf