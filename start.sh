#!/bin/bash
set -e

echo "🚀 Démarrage de PER ANKH..."

# === DIAGNOSTIC ===
echo "📂 VÉRIFICATION CRITIQUE :"
ls -la /var/www/public/ 2>/dev/null || echo "❌ /var/www/public n'existe pas"

if [ ! -f "/var/www/public/index.php" ]; then
    echo "❌ CRITIQUE : index.php manquant !"
    echo "Création d'un fichier index.php de secours..."
    mkdir -p /var/www/public
    cat > /var/www/public/index.php << 'EOF'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
EOF
    chmod 644 /var/www/public/index.php
    chown www-data:www-data /var/www/public/index.php
    echo "✅ index.php créé"
else
    echo "✅ index.php présent"
    head -n 3 /var/www/public/index.php
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