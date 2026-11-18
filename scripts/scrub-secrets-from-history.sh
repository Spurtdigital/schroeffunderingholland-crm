#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# scrub-secrets-from-history.sh — eenmalige git-history-scrub
#
# Dit hoort NIET in deploy.sh: het herschrijft alle commit-hashes en vereist
# daarna een force-push. Draai dit bewust, eenmalig, vanuit een schone clone.
#
# Gebruik:
#   1. Maak BUITEN de repo een bestand met daarin per regel:
#        <letterlijke-secret>==>***REMOVED***
#      Bijvoorbeeld: /tmp/sfh-secrets.txt
#   2. ./scripts/scrub-secrets-from-history.sh /tmp/sfh-secrets.txt
#   3. Zet de origin remote terug en force-push (zie de output).
#
# Let op: rotate de betrokken wachtwoorden/tokens ALTIJD, ook na de scrub.
# GitHub kan oude commits via hun SHA nog enige tijd bereikbaar houden.
# =============================================================================

if ! command -v git-filter-repo >/dev/null 2>&1; then
  echo "FOUT: git-filter-repo ontbreekt. Installeer het met: brew install git-filter-repo"
  exit 1
fi

if [ $# -lt 1 ]; then
  echo "Gebruik: $0 <bestand-met-secrets>"
  exit 1
fi

SECRETS_FILE="$1"
[ -f "$SECRETS_FILE" ] || { echo "FOUT: bestand niet gevonden: $SECRETS_FILE"; exit 1; }

REPO_ROOT="$(git rev-parse --show-toplevel)"
SECRETS_DIR="$(cd "$(dirname "$SECRETS_FILE")" && pwd)"
case "$SECRETS_DIR" in
  "$REPO_ROOT"|"$REPO_ROOT"/*)
    echo "FOUT: zet het secrets-bestand buiten de repo (bijv. in /tmp)."
    exit 1
    ;;
esac

if [ -n "$(git status --porcelain)" ]; then
  echo "FOUT: de working tree is niet schoon. Commit of stash eerst je wijzigingen."
  exit 1
fi

BACKUP="backup/pre-scrub-$(date +%Y%m%d-%H%M%S)"
git branch "$BACKUP"
echo "Backup-branch aangemaakt: $BACKUP"

git filter-repo --replace-text "$SECRETS_FILE" --force

cat <<'EOF'

Klaar. git-filter-repo heeft om veiligheidsredenen de origin remote verwijderd.
Zet hem terug en push bewust:
  git remote add origin git@github.com:Spurtdigital/schroeffunderingholland-crm.git
  git push --force origin master

Daarna:
  - laat collaborators opnieuw clonen (oude clones bevatten de secrets nog);
  - rotate alle betrokken wachtwoorden/tokens als dat nog niet is gedaan.
EOF
