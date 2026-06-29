#!/bin/bash
set -e

echo "🚀 Démarrage de PER ANKH..."

# === CORRECTION DES PERMISSIONS ===
echo "🔧 Correction des permissions..."
# Changer le propriétaire de tous les fichiers pour www-data
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public 2>/dev/null || true
# Donner les droits de lecture/écriture
chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/public 2>/dev/null || true
# S'assurer que index.php est lisible
chmod 644 /var/www/public/index.php 2>/dev/null || true

# Vérification finale
if [ -f "/var/www/public/index.php" ]; then
    echo "✅ index.php trouvé"
    echo "Permissions : $(ls -la /var/www/public/index.php)"
    # Tester si www-data peut lire le fichier
    sudo -u www-data cat /var/www/public/index.php > /dev/null 2>&1 && echo "✅ index.php lisible par www-data" || echo "❌ index.php non lisible par www-data"
else
    echo "❌ index.php manquant !"
    exit 1
fi
# === FIN CORRECTION ===

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

# Permissions finales après les commandes artisan
chown -R www-data:www-data storage bootstrap/cache public/storage 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "✅ Application prête !"

# Démarrer Supervisord (qui gère Nginx et PHP-FPM)
echo "🌐 Démarrage de Nginx sur le port 10000 et PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf