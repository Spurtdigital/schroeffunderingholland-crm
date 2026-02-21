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

echo "==> [1/5] Vite build lokaal..."
npm run build

echo "==> [2/5] Bestanden uploaden via rsync..."

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

# Upload public/build apart (gegenereerde assets)
echo "==> [3/5] Assets (public/build) uploaden..."
rsync -az --delete \
  -e "ssh -i $SSH_KEY" \
  public/build/ "$SSH_USER@$SSH_HOST:$REMOTE_PUBLIC/build/"

# Sync overige public bestanden (index.php, .htaccess, etc.)
rsync -az \
  -e "ssh -i $SSH_KEY" \
  --exclude='build' \
  --exclude='storage' \
  --exclude='hot' \
  public/ "$SSH_USER@$SSH_HOST:$REMOTE_PUBLIC/"

echo "==> [4/5] Composer install op server..."
$SSH "cd $REMOTE_APP && php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\" && php composer-setup.php --quiet && rm composer-setup.php" 2>/dev/null || true
$SSH "cd $REMOTE_APP && php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1"

echo "==> [5/5] Laravel optimalisaties + migraties..."
$SSH "cd $REMOTE_APP && php artisan migrate --force 2>&1"
$SSH "cd $REMOTE_APP && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache 2>&1"
$SSH "cd $REMOTE_APP && php artisan queue:restart 2>/dev/null || true"

echo ""
echo "Deploy voltooid! https://crm.schroeffunderingholland.nl"
