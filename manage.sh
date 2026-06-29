#!/bin/bash

case "$1" in
  backup)
    echo "📦 Sauvegarde des données..."
    docker run --rm -v storage_data:/data -v $(pwd):/backup alpine cp -r /data/* /backup/storage_backup/ 2>/dev/null || true
    echo "✅ Sauvegarde terminée"
    ;;
    
  restore)
    echo "📂 Restauration des données..."
    if [ -d "storage_backup" ]; then
      docker run --rm -v storage_data:/data -v $(pwd):/backup alpine cp -r /backup/storage_backup/* /data/
      echo "✅ Restauration terminée"
    else
      echo "❌ Aucune sauvegarde trouvée"
    fi
    ;;
    
  shell)
    docker-compose exec app bash
    ;;
    
  logs)
    docker-compose logs -f "${@:2}"
    ;;
    
  restart)
    docker-compose restart "${@:2}"
    ;;
    
  down)
    docker-compose down
    ;;
    
  ps)
    docker-compose ps
    ;;
    
  *)
    echo "Usage: ./manage.sh {backup|restore|shell|logs|restart|down|ps}"
    exit 1
    ;;
esac