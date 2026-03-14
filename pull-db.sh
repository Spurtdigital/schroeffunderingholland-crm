#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# pull-db.sh — Haal live database op en importeer in DDEV
# Gebruik: ./pull-db.sh
# =============================================================================

SSH_KEY="$HOME/.ssh/antagonist-ssh"
SSH_USER="deb122730"
SSH_HOST="141.138.169.228"
SSH="ssh -i $SSH_KEY $SSH_USER@$SSH_HOST"
REMOTE_APP="/home/deb122730/domains/crm.schroeffunderingholland.nl/app"

echo "==> [1/4] Live database dumpen op server..."
$SSH "cd $REMOTE_APP && php artisan db:show --json 2>/dev/null | true"

# Haal DB-credentials op uit de live .env
DB_DATABASE=$($SSH "grep '^DB_DATABASE=' $REMOTE_APP/.env | cut -d'=' -f2 | tr -d '\r'")
DB_USERNAME=$($SSH "grep '^DB_USERNAME=' $REMOTE_APP/.env | cut -d'=' -f2 | tr -d '\r'")
DB_PASSWORD=$($SSH "grep '^DB_PASSWORD=' $REMOTE_APP/.env | cut -d'=' -f2 | tr -d '\r'")

echo "    Database: $DB_DATABASE"

# Dump direct via SSH naar lokaal bestand
echo "==> [2/4] Database streamen naar lokaal..."
$SSH "mysqldump -u'$DB_USERNAME' -p'$DB_PASSWORD' --single-transaction --no-tablespaces '$DB_DATABASE'" \
    > /tmp/sfh_live_dump.sql

echo "    Dump opgeslagen in /tmp/sfh_live_dump.sql ($(du -sh /tmp/sfh_live_dump.sql | cut -f1))"

echo "==> [3/4] Importeren in DDEV..."
ddev import-db --file=/tmp/sfh_live_dump.sql

echo "==> [4/4] Nieuwe migrations draaien..."
ddev artisan migrate --force

# Opruimen
rm -f /tmp/sfh_live_dump.sql

echo ""
echo "Klaar! Live data staat nu in https://sfh.ddev.site/"
