#!/bin/bash
set -e
cd "$(dirname "$0")"
echo "==> Création des répertoires Symfony..."
mkdir -p symfony/var/cache symfony/var/log symfony/var/uploads/avatars
chmod -R 777 symfony/var 2>/dev/null || true
echo "==> Terminé. Lancez: docker compose up -d && docker compose exec symfony composer install"
echo "    Puis: docker compose exec symfony php bin/console doctrine:migrations:migrate --no-interaction"
echo "    Puis: docker compose exec symfony php bin/console doctrine:fixtures:load --no-interaction"
