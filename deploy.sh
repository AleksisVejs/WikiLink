#!/usr/bin/env bash
# WikiLink — deploy on server (cPanel subdomain wikilink.fraksis.com, repo folder ~/wikilink)
#
# Setup once (SSH):
#   cd ~
#   git clone https://github.com/AleksisVejs/WikiLink.git wikilink
#   cd wikilink
#   chmod +x deploy.sh
#
# In cPanel → Domains → wikilink.fraksis.com → set Document Root to:
#   /home/YOUR_CPANEL_USER/wikilink/dist
#
# Then deploy:
#   ./deploy.sh
#
# Optional: cron every 5 minutes (replace USER):
#   */5 * * * * /home/USER/wikilink/deploy.sh >> /home/USER/wikilink/deploy.log 2>&1
#
# By default the repo is hard-reset to origin (good for deploy-only clones). To keep local
# commits/edits instead: DEPLOY_NO_HARD_RESET=1 ./deploy.sh

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$REPO_ROOT"

BRANCH="${DEPLOY_BRANCH:-main}"

# cPanel / shared hosting: Node often via NVM
if [[ -z "${NODE_SKIP_NVM:-}" ]]; then
  export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
  if [[ -s "$NVM_DIR/nvm.sh" ]]; then
    # shellcheck source=/dev/null
    . "$NVM_DIR/nvm.sh"
  fi
fi

echo "[deploy] $(date -u +%Y-%m-%dT%H:%M:%SZ) in $REPO_ROOT"

git fetch origin "$BRANCH"
git checkout "$BRANCH"
if [[ -z "${DEPLOY_NO_HARD_RESET:-}" ]]; then
  git reset --hard "origin/$BRANCH"
else
  git pull origin "$BRANCH"
fi

if command -v npm >/dev/null 2>&1; then
  if [[ -f package-lock.json ]]; then
    npm ci
  else
    npm install
  fi
  npm run build
else
  echo "[deploy] ERROR: npm not found. Install Node (e.g. NVM in cPanel) or set PATH." >&2
  exit 1
fi

echo "[deploy] OK. Serving directory should be: $REPO_ROOT/dist"
