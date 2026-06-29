#!/bin/bash
set -e

echo "🚀 Démarrage de PER ANKH..."

# === CORRECTION DES PERMISSIONS ===
echo "🔧 Correction des permissions des dossiers parents et fichiers..."

# S'assurer que TOUS les dossiers parents sont traversables par tout le monde (+x)
chmod 755 /var /var/www /var/www/public 2>/dev/null || true

# Appliquer récursivement le propriétaire et les droits sur les dossiers de l'application
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/public 2>/dev/null || true

# Forcer les droits de lecture sur index.php
chmod 644 /var/www/public/index.php 2>/dev/null || true

# Vérification et Debug du dossier public
if [ -f "/var/www/public/index.php" ]; then
    echo "✅ index.php trouvé"
    echo "Permissions : $(ls -la /var/www/public/index.php)"
    
    # Tester si www-data peut VRAIMENT lire le fichier maintenant
    if sudo -u www-data cat /var/www/public/index.php > /dev/null 2>&1; then
        echo "✅ index.php est enfin lisible par www-data !"
    else
        echo "❌ index.php TOUJOYRS non lisible par www-data. Tentative de correction agressive..."
        chmod 777 /var/www /var/www/public /var/www/public/index.php 2>/dev/null || true
    fi
else
    echo "❌ index.php manquant au démarrage !"
    echo "Contenu de /var/www/public :"
    ls -la /var/www/public || true
    exit 1
fi
# === FIN CORRECTION ===

# Variables d'environnement par défaut si non définies
export APP_ENV=${APP_ENV:-production}
export APP_DEBUG=${APP_DEBUG:-false}

if [ -z "$PUSHER_APP_KEY" ]; then
    echo "⚠️  PUSHER_APP_KEY non défini, utilisation de valeurs par défaut"
    export PUSHER_APP_KEY=${PUSHER_APP_KEY:-dummy}
    export PUSHER_APP_SECRET=${PUSHER_APP_SECRET:-dummy}
    export PUSHER_APP_ID=${PUSHER_APP_ID:-dummy}
fi

echo "⏳ Vérification de la base de données..."
php artisan migrate --force || echo "⚠️  Migration ignorée"

echo "📦 Exécution des scripts post-installation..."
php artisan package:discover || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Nettoyage et recréation propre du lien symbolique
echo "🔗 Configuration du lien de stockage..."
rm -rf /var/www/public/storage
php artisan storage:link --force || true

# Permissions finales après génération des caches et du lien
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public/storage 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/public/storage 2>/dev/null || true

echo "✅ Application prête !"

echo "🌐 Démarrage de Nginx sur le port 10000 et PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf