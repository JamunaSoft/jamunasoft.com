#!/usr/bin/env bash
#
# One-command production deploy for jamunasoft.com.
# Usage on the VPS:  bash deploy.sh
#
set -euo pipefail
cd "$(dirname "$0")"

step() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }

step "Entering maintenance mode"
php artisan down --retry=15 || true
# Whatever happens below, never leave the site down.
trap 'php artisan up' EXIT

step "Pulling latest code"
git pull --ff-only

step "Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

step "Building frontend assets"
npm install --no-audit --no-fund
npm run build

step "Running database migrations"
php artisan migrate --force

step "Refreshing caches"
php artisan storage:link || true
php artisan config:cache
php artisan view:cache
php artisan event:cache

step "Restarting queue workers (picks up the new code)"
php artisan queue:restart

step "Done"
git log --oneline -1
echo "Deployed successfully. Site is back up."
