#!/usr/bin/env bash
#
# One-command production deploy for jamunasoft.com.
# Usage on the VPS:  bash deploy.sh
#
set -euo pipefail
cd "$(dirname "$0")"

step() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

# Keep root-run deploys from leaving Blade caches unwritable by PHP-FPM.
view_cache_owner=$(stat -c '%u:%g' storage/framework/views)
bootstrap_cache_owner=$(stat -c '%u:%g' bootstrap/cache)
finish() {
    if [ "$(id -u)" -eq 0 ]; then
        chown -R "$view_cache_owner" storage/framework/views || true
        chown -R "$bootstrap_cache_owner" bootstrap/cache || true
    fi
    php artisan up
}

step "Entering maintenance mode"
php artisan down --retry=15 || true
# Whatever happens below, never leave the site down.
trap finish EXIT

step "Pulling latest code"
git pull --ff-only

step "Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

step "Building frontend assets"
npm ci --no-audit --no-fund
npm run build

step "Running database migrations"
php artisan migrate --force

step "Refreshing caches"
php artisan storage:link || true
php artisan config:cache
php artisan view:cache
php artisan event:cache
php artisan filament:clear-cached-components

step "Restarting queue workers (picks up the new code)"
php artisan queue:restart

step "Done"
git --no-pager log --oneline -1
echo "Deployed successfully. Site is back up."
