#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# deploy.sh — SFH CRM deploy naar Antagonist
# Gebruik:
#   ./deploy.sh                     — normale deploy
#   ./deploy.sh --rotate-passwords  — eenmalig alle wachtwoorden roteren
#                                     (toont de nieuwe wachtwoorden in de terminal)
# =============================================================================

ROTATE_PASSWORDS=false
for arg in "$@"; do
  case "$arg" in
    --rotate-passwords) ROTATE_PASSWORDS=true ;;
    -h|--help)
      echo "Gebruik: ./deploy.sh [--rotate-passwords]"
      exit 0
      ;;
    *)
      echo "Onbekende optie: $arg"
      exit 1
      ;;
  esac
done

SSH_KEY="$HOME/.ssh/antagonist-ssh"
SSH_USER="deb122730"
SSH_HOST="141.138.169.228"
# Key-only: er worden geen wachtwoorden gebruikt of opgeslagen.
SSH_OPTS="-i $SSH_KEY -o IdentitiesOnly=yes -p 22 -o ServerAliveInterval=10 -o ServerAliveCountMax=30 -o ConnectTimeout=60 -o TCPKeepAlive=yes"
SSH="ssh $SSH_OPTS $SSH_USER@$SSH_HOST"

if [ ! -f "$SSH_KEY" ]; then
  echo "SSH-sleutel niet gevonden: $SSH_KEY"
  exit 1
fi
REMOTE_ROOT="/home/deb122730/domains/app.schroeffunderingholland.nl"
REMOTE_APP="$REMOTE_ROOT/app"
REMOTE_PUBLIC="$REMOTE_ROOT/public_html"

# =============================================================================
# STAP -1: Controleer of de productiegebruikers op de server zijn ingesteld
# =============================================================================
echo "==> [preflight] Controleren of PRODUCTION_USER_* op de server staat..."
if ! $SSH "test -f '$REMOTE_APP/.env' && grep -qE '^PRODUCTION_USER_[0-9]_EMAIL=.+' '$REMOTE_APP/.env'"; then
  echo ""
  echo "❌ Geen PRODUCTION_USER_*_EMAIL gevonden in $REMOTE_APP/.env."
  echo "   Zet daar minimaal PRODUCTION_USER_1_NAME / _EMAIL / _PASSWORD (min. 12 tekens)."
  echo "   De deploy stopt, zodat er geen omgeving zonder beheerder ontstaat."
  echo ""
  exit 1
fi
echo "✅ Productiegebruikers zijn geconfigureerd."
echo ""

# =============================================================================
# STAP 0: Tests draaien — deploy stopt bij falen
# =============================================================================
echo "==> [0/6] Tests draaien (blokkeert deploy bij falen)..."

# Installeer dev-dependencies tijdelijk zodat Pest beschikbaar is
ddev exec "cd /var/www/html && composer install --no-interaction --quiet 2>&1"

# Draai de volledige test suite
if ! ddev exec "cd /var/www/html && php vendor/bin/pest --no-coverage 2>&1"; then
    echo ""
    echo "❌ Tests gefaald — deploy geannuleerd."
    echo "   Fix de falende tests en probeer opnieuw."
    echo ""
    # Herstel naar no-dev vendor zodat lokale omgeving schoon blijft
    ddev exec "cd /var/www/html && composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>&1"
    exit 1
fi

echo "✅ Alle tests geslaagd — deploy gaat door."
echo ""

echo "==> [1/6] Vite build lokaal..."
npm run build

echo "==> [2/6] Composer vendor bouwen via DDEV voor productie..."
VENDOR_TMP=$(mktemp -d)
ddev exec "cd /var/www/html && composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>&1"
ddev exec "tar -cf - -C /var/www/html vendor" | tar -xf - -C "$VENDOR_TMP"

echo "==> [3/6] Bestanden uploaden via rsync..."

# Upload alles BEHALVE: .git, node_modules, vendor, .env*, storage logs, public/build (apart)
# LET OP: storage/app/public/ is uitgesloten — bevat geüploade bestanden (logo, algemene voorwaarden, etc.)
rsync -az --delete \
  -e "ssh $SSH_OPTS" \
  --exclude='.git' \
  --exclude='.env*' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='storage/app/public/' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  --exclude='public/build' \
  --exclude='public/storage' \
  --exclude='public/hot' \
  --filter='protect .env' \
  ./ "$SSH_USER@$SSH_HOST:$REMOTE_APP/"

# Upload vendor (lokaal gebouwd voor productie)
echo "==> [4/6] Vendor uploaden..."
rsync -az --delete \
  -e "ssh $SSH_OPTS" \
  "$VENDOR_TMP/vendor/" "$SSH_USER@$SSH_HOST:$REMOTE_APP/vendor/"
rm -rf "$VENDOR_TMP"

# Upload public/build apart (gegenereerde assets)
echo "==> [5/6] Assets (public/build) uploaden..."
rsync -az --delete \
  -e "ssh $SSH_OPTS" \
  public/build/ "$SSH_USER@$SSH_HOST:$REMOTE_PUBLIC/build/"

# Sync overige public bestanden (.htaccess, css/filament, js/filament, etc.)
# index.php wordt BEWUST uitgesloten — de server-versie heeft andere paden (zie hieronder)
rsync -az \
  -e "ssh $SSH_OPTS" \
  --exclude='build' \
  --exclude='storage' \
  --exclude='hot' \
  --exclude='index.php' \
  public/ "$SSH_USER@$SSH_HOST:$REMOTE_PUBLIC/"

# Schrijf server-specifieke index.php met correcte paden naar ../app/vendor en ../app/bootstrap
# (De lokale public/index.php wijst naar ../vendor — dat klopt alleen lokaal)
$SSH "cat > $REMOTE_PUBLIC/index.php" << 'INDEXPHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../app/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../app/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../app/bootstrap/app.php';

$app->handleRequest(Request::capture());
INDEXPHP

echo "==> [6/6] Laravel optimalisaties + migraties..."
$SSH "cd $REMOTE_APP && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php"
$SSH "cd $REMOTE_APP && php artisan migrate --force 2>&1"
# Gebruikers: alleen aanmaken als ze nog niet bestaan. Bestaande wachtwoorden
# worden nooit gereset. Zet PRODUCTION_USER_* in de .env op de server.
$SSH "cd $REMOTE_APP && php artisan app:create-production-users 2>&1"
if [ "$ROTATE_PASSWORDS" = true ]; then
  echo "==> Wachtwoorden roteren (eenmalig; nieuwe wachtwoorden worden hieronder getoond)..."
  $SSH "cd $REMOTE_APP && php artisan app:rotate-user-passwords --force 2>&1"
fi
$SSH "cd $REMOTE_APP && php artisan db:seed --class=EmailTemplateSeeder --force 2>&1"
# LET OP: config:cache wordt NIET gedraaid - zie server-bootstrap.sh voor uitleg
$SSH "cd $REMOTE_APP && php artisan route:cache && php artisan view:cache && php artisan event:cache 2>&1"
# Permissies fixen zodat Apache de bestanden kan lezen
$SSH "find $REMOTE_PUBLIC -type d -exec chmod 755 {} \; && find $REMOTE_PUBLIC -type f -exec chmod 644 {} \;"

echo ""
echo "Deploy voltooid! https://app.schroeffunderingholland.nl"
if [ "$ROTATE_PASSWORDS" = true ]; then
  echo ""
  echo "LET OP: de nieuwe wachtwoorden staan hierboven. Sla ze veilig op en deel ze via een veilig kanaal."
fi
