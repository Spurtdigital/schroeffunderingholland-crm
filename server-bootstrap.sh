#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# server-bootstrap.sh — Eenmalige setup van SFH CRM op Antagonist
# Draai dit EENMALIG na de eerste deploy, via:
#   ssh -i ~/.ssh/antagonist-ssh deb122730@141.138.169.228 "bash ~/domains/app.schroeffunderingholland.nl/app/server-bootstrap.sh"
# =============================================================================

REMOTE_ROOT="/home/deb122730/domains/app.schroeffunderingholland.nl"
REMOTE_APP="$REMOTE_ROOT/app"
REMOTE_PUBLIC="$REMOTE_ROOT/public_html"

cd "$REMOTE_APP"

echo "==> [1/10] .env instellen..."
if [ ! -f "$REMOTE_APP/.env" ]; then
  if [ ! -f "$REMOTE_APP/.env.production" ]; then
    echo "     FOUT: $REMOTE_APP/.env ontbreekt. Maak deze eerst aan (met PRODUCTION_USER_*)."
    exit 1
  fi
  cp "$REMOTE_APP/.env.production" "$REMOTE_APP/.env"
  echo "     .env.production gekopieerd naar .env"
else
  echo "     .env bestaat al, overgeslagen"
fi

echo "==> [2/10] APP_KEY genereren..."
php artisan key:generate --force

echo "==> [3/10] Composer install..."
# Antagonist heeft proc_* functies uitgeschakeld waardoor composer.phar niet werkt.
# De vendor map wordt lokaal gebouwd en geüpload via deploy.sh.
if [ -d "$REMOTE_APP/vendor" ]; then
  echo "     vendor map aanwezig (geüpload via deploy), stap overgeslagen"
else
  echo "     WAARSCHUWING: vendor map ontbreekt! Draai deploy.sh opnieuw."
  exit 1
fi

echo "==> [4/10] Storage symlink aanmaken..."
# Symlink: public_html/storage -> app/storage/app/public
if [ ! -L "$REMOTE_PUBLIC/storage" ]; then
  ln -s "$REMOTE_APP/storage/app/public" "$REMOTE_PUBLIC/storage"
  echo "     Symlink aangemaakt"
else
  echo "     Symlink bestaat al"
fi

echo "==> [5/10] Storage mappen aanmaken + permissies..."
mkdir -p "$REMOTE_APP/storage/app/public"
mkdir -p "$REMOTE_APP/storage/framework/cache/data"
mkdir -p "$REMOTE_APP/storage/framework/sessions"
mkdir -p "$REMOTE_APP/storage/framework/views"
mkdir -p "$REMOTE_APP/storage/logs"
mkdir -p "$REMOTE_APP/bootstrap/cache"
chmod -R 775 "$REMOTE_APP/storage" "$REMOTE_APP/bootstrap/cache"

echo "==> [6/10] Database migraties uitvoeren..."
php artisan migrate --force

echo "==> [7/10] Productie-seeders draaien (geen demo-data, geen standaardaccount)..."
php artisan db:seed --class=PaymentTermsSeeder --force
php artisan db:seed --class=QuoteAssumptionsSeeder --force
php artisan db:seed --class=EmailTemplateSeeder --force

echo "==> [8/10] Productiegebruikers aanmaken (alleen nieuwe gebruikers)..."
php artisan app:create-production-users

echo "==> [9/10] Filament Shield rollen aanmaken + cache opbouwen..."
php artisan shield:generate --all --panel=admin --no-interaction
# LET OP: config:cache wordt NIET gedraaid omdat Antagonist CLI php een andere
# variables_order heeft dan web php, waardoor APP_KEY leeg in de cache terecht komt.
# Laravel leest .env direct via de web request - dat werkt correct.
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> [10/10] Laravel scheduler instellen in crontab..."
# Voeg scheduler toe als deze nog niet bestaat
CRON_LINE="* * * * * /usr/local/bin/php $REMOTE_APP/artisan schedule:run >> /dev/null 2>&1"
if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
  echo "     Scheduler cron bestaat al, overgeslagen"
else
  (crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -
  echo "     Scheduler cron toegevoegd"
fi

echo ""
echo "Bootstrap voltooid!"
echo "Login via: https://app.schroeffunderingholland.nl/admin"
echo ""
echo "Er is GEEN standaardaccount meer. Zet in $REMOTE_APP/.env:"
echo "  PRODUCTION_USER_1_NAME / PRODUCTION_USER_1_EMAIL / PRODUCTION_USER_1_PASSWORD (min. 12 tekens)"
echo "en draai daarna:"
echo "  php artisan app:create-production-users"
