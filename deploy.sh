#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# deploy.sh — SFH CRM deploy naar Antagonist
# Gebruik: ./deploy.sh
# =============================================================================

SSH_KEY="$HOME/.ssh/antagonist-ssh"
SSH_USER="deb122730"
SSH_HOST="141.138.169.228"
SSH="ssh -i $SSH_KEY $SSH_USER@$SSH_HOST"
REMOTE_ROOT="/home/deb122730/domains/crm.schroeffunderingholland.nl"
REMOTE_APP="$REMOTE_ROOT/app"
REMOTE_PUBLIC="$REMOTE_ROOT/public_html"

echo "==> [1/6] Vite build lokaal..."
npm run build

echo "==> [2/6] Composer vendor lokaal bouwen voor productie..."
VENDOR_TMP=$(mktemp -d)
cp composer.json composer.lock "$VENDOR_TMP/"
(cd "$VENDOR_TMP" && composer install --no-dev --optimize-autoloader --no-interaction --quiet)

echo "==> [3/6] Bestanden uploaden via rsync..."

# Upload alles BEHALVE: .git, node_modules, vendor, .env*, storage logs, public/build (apart)
rsync -az --delete \
  -e "ssh -i $SSH_KEY" \
  --exclude='.git' \
  --exclude='.env*' \
  --exclude='node_modules' \
  --exclude='vendor' \
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
  -e "ssh -i $SSH_KEY" \
  "$VENDOR_TMP/vendor/" "$SSH_USER@$SSH_HOST:$REMOTE_APP/vendor/"
rm -rf "$VENDOR_TMP"

# Upload public/build apart (gegenereerde assets)
echo "==> [5/6] Assets (public/build) uploaden..."
rsync -az --delete \
  -e "ssh -i $SSH_KEY" \
  public/build/ "$SSH_USER@$SSH_HOST:$REMOTE_PUBLIC/build/"

# Sync overige public bestanden (.htaccess, css/filament, js/filament, etc.)
# index.php wordt BEWUST uitgesloten — de server-versie heeft andere paden (zie hieronder)
rsync -az \
  -e "ssh -i $SSH_KEY" \
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
# LET OP: config:cache wordt NIET gedraaid - zie server-bootstrap.sh voor uitleg
$SSH "cd $REMOTE_APP && php artisan route:cache && php artisan view:cache && php artisan event:cache 2>&1"
# Permissies fixen zodat Apache de bestanden kan lezen
$SSH "find $REMOTE_PUBLIC -type d -exec chmod 755 {} \; && find $REMOTE_PUBLIC -type f -exec chmod 644 {} \;"

echo ""
echo "Deploy voltooid! https://crm.schroeffunderingholland.nl"
