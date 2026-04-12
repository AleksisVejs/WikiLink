#!/usr/bin/env bash
# WikiLink / WikiGame — deploy on server (e.g. cPanel subdomain alflix.wikilink.com)
#
# Setup once (SSH):
#   cd ~
#   git clone https://github.com/AleksisVejs/WikiLink.git alflix.wikilink.com
#   cd alflix.wikilink.com
#   chmod +x deploy.sh
#
# In cPanel → Domains → alflix.wikilink.com → set Document Root to:
#   /home/YOUR_CPANEL_USER/alflix.wikilink.com/dist
#
# Then deploy:
#   ./deploy.sh
#
# Optional: cron every 5 minutes (replace USER and path):
#   */5 * * * * /home/USER/alflix.wikilink.com/deploy.sh >> /home/USER/alflix.wikilink.com/deploy.log 2>&1

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
git pull origin "$BRANCH"

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
