#!/bin/bash

set -e

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_info "🚀 Déploiement de Per-Ankh Backend"

# Vérifier Docker
if ! command -v docker &> /dev/null; then
    log_error "Docker n'est pas installé"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    log_error "Docker Compose n'est pas installé"
    exit 1
fi

# Backup des données existantes
log_info "📦 Sauvegarde des données existantes..."
if [ -d "storage/app/public" ]; then
    mkdir -p storage_backup
    cp -r storage/app/public/* storage_backup/ 2>/dev/null || true
    log_info "✅ Sauvegarde terminée"
fi

# Supprimer le lien symbolique problématique
log_info "🧹 Nettoyage du lien symbolique..."
rm -rf public/storage 2>/dev/null || true

# Arrêter les conteneurs
log_info "🛑 Arrêt des conteneurs..."
docker-compose down

# Nettoyer les images obsolètes
log_info "🧹 Nettoyage des images..."
docker rmi per-ankh-backend-app per-ankh-backend-reverb 2>/dev/null || true

# Build
log_info "🏗️  Construction des images..."
docker-compose build --no-cache

# Démarrer
log_info "🚀 Démarrage des services..."
docker-compose up -d

# Attendre
log_info "⏳ Attente du démarrage des services..."
sleep 15

# Vérifier que les services sont en ligne
if ! docker-compose ps | grep -q "Up"; then
    log_error "❌ Les services ne sont pas démarrés correctement"
    docker-compose logs
    exit 1
fi

# Exécuter les migrations
log_info "🗄️  Exécution des migrations..."
docker-compose exec -T app php artisan migrate --force || {
    log_error "❌ Échec des migrations"
    exit 1
}

# Créer le lien storage dans le conteneur
log_info "🔗 Configuration du storage..."
docker-compose exec -T app bash -c "
    php artisan storage:link || true
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache
"

# Optimiser
log_info "⚡ Optimisation du cache..."
docker-compose exec -T app php artisan optimize:clear

# Restaurer les données de backup
if [ -d "storage_backup" ] && [ "$(ls -A storage_backup)" ]; then
    log_info "📂 Restauration des données..."
    docker-compose exec -T app bash -c "
        cp -r /var/www/storage_backup/* /var/www/storage/app/public/ 2>/dev/null || true
        chown -R www-data:www-data /var/www/storage/app/public
        chmod -R 775 /var/www/storage/app/public
    "
fi

# Afficher les status
log_info "✅ Déploiement terminé avec succès !"
echo ""
echo "📊 Résumé des services :"
echo "   🌐 Application      : http://localhost:8000"
echo "   📊 phpMyAdmin      : http://localhost:8082"
echo "   📧 MailHog         : http://localhost:8025"
echo "   📡 Reverb          : http://localhost:8085"
echo "   🔌 SocketIO        : http://localhost:3001"
echo ""
echo "📋 Commandes utiles :"
echo "   docker-compose logs -f     # Voir les logs"
echo "   docker-compose ps          # Voir les services"
echo "   docker-compose exec app bash   # Accéder au conteneur"
echo "   docker-compose down        # Arrêter les services"
echo ""

# Afficher les logs en arrière-plan
log_info "Affichage des logs (Ctrl+C pour quitter)..."
docker-compose logs -f --tail=20