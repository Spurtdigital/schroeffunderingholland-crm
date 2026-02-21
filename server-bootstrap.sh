#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# server-bootstrap.sh — Eenmalige setup van SFH CRM op Antagonist
# Draai dit EENMALIG na de eerste deploy, via:
#   ssh -i ~/.ssh/antagonist-ssh deb122730@141.138.169.228 "bash ~/domains/crm.schroeffunderingholland.nl/app/server-bootstrap.sh"
# =============================================================================

REMOTE_ROOT="/home/deb122730/domains/crm.schroeffunderingholland.nl"
REMOTE_APP="$REMOTE_ROOT/app"
REMOTE_PUBLIC="$REMOTE_ROOT/public_html"

cd "$REMOTE_APP"

echo "==> [1/8] .env instellen..."
if [ ! -f "$REMOTE_APP/.env" ]; then
  cp "$REMOTE_APP/.env.production" "$REMOTE_APP/.env"
  echo "     .env.production gekopieerd naar .env"
else
  echo "     .env bestaat al, overgeslagen"
fi

echo "==> [2/8] APP_KEY genereren..."
php artisan key:generate --force

echo "==> [3/8] Composer install..."
if [ ! -f "$REMOTE_APP/composer.phar" ]; then
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --quiet
  rm composer-setup.php
fi
php composer.phar install --no-dev --optimize-autoloader --no-interaction

echo "==> [4/8] Storage symlink aanmaken..."
# Symlink: public_html/storage -> app/storage/app/public
if [ ! -L "$REMOTE_PUBLIC/storage" ]; then
  ln -s "$REMOTE_APP/storage/app/public" "$REMOTE_PUBLIC/storage"
  echo "     Symlink aangemaakt"
else
  echo "     Symlink bestaat al"
fi

echo "==> [5/8] Storage mappen aanmaken + permissies..."
mkdir -p "$REMOTE_APP/storage/app/public"
mkdir -p "$REMOTE_APP/storage/framework/cache/data"
mkdir -p "$REMOTE_APP/storage/framework/sessions"
mkdir -p "$REMOTE_APP/storage/framework/views"
mkdir -p "$REMOTE_APP/storage/logs"
mkdir -p "$REMOTE_APP/bootstrap/cache"
chmod -R 775 "$REMOTE_APP/storage" "$REMOTE_APP/bootstrap/cache"

echo "==> [6/8] Database migraties uitvoeren..."
php artisan migrate --force

echo "==> [7/8] Dummy data seeden (admin@sfh.nl / password)..."
php artisan db:seed --force

echo "==> [8/8] Filament Shield rollen aanmaken + cache opbouwen..."
php artisan shield:generate --all --ignore-config-exclude 2>/dev/null || php artisan shield:generate --all
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo ""
echo "Bootstrap voltooid!"
echo "Login via: https://crm.schroeffunderingholland.nl/crm"
echo "Gebruiker: admin@sfh.nl"
echo "Wachtwoord: password"
echo ""
echo "BELANGRIJK: Verander het wachtwoord direct na de eerste login!"
