#!/bin/bash
set -e

DEPLOY_PATH="${DEPLOY_PATH:-/var/www/html}"
echo "Deploying to: $DEPLOY_PATH"

cd "$DEPLOY_PATH"

echo "Running post-deployment tasks..."

php artisan config:cache --force 2>/dev/null || true
php artisan route:cache --force 2>/dev/null || true
php artisan view:cache --force 2>/dev/null || true
php artisan event:cache --force 2>/dev/null || true

chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chmod -R 775 vendor 2>/dev/null || true

php artisan queue:restart 2>/dev/null || true

php artisan cache:clear 2>/dev/null || true

echo "Deployment complete!"