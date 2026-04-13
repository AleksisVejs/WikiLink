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

# cPanel / shared hosting: Node via NVM, or EasyApache "ea-nodejs" packages
if [[ -z "${NODE_SKIP_NVM:-}" ]]; then
  export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
  if [[ -s "$NVM_DIR/nvm.sh" ]]; then
    # shellcheck source=/dev/null
    . "$NVM_DIR/nvm.sh"
    nvm use default >/dev/null 2>&1 || true
  fi
fi
if ! command -v npm >/dev/null 2>&1; then
  for _nodebin in /opt/cpanel/ea-nodejs22/bin /opt/cpanel/ea-nodejs20/bin /opt/cpanel/ea-nodejs18/bin; do
    if [[ -x "$_nodebin/npm" ]]; then
      export PATH="$_nodebin:$PATH"
      break
    fi
  done
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
  # Vite empties dist/ before build, so backup the live SQLite DB first (see build.emptyOutDir).
  preserve_db=""
  if [[ -f "dist/api/wikilink.db" ]]; then
    preserve_db="$(mktemp)"
    cp dist/api/wikilink.db "$preserve_db"
    echo "[deploy] Preserving dist/api/wikilink.db across vite build ($(wc -c <"$preserve_db") bytes)"
  fi

  npm run build

  # Copy PHP API into dist so Apache can serve it
  if [[ -d api ]]; then
    cp -r api dist/api
    rm -f dist/api/wikilink.db 2>/dev/null || true
    if [[ -n "$preserve_db" && -f "$preserve_db" ]]; then
      mv "$preserve_db" dist/api/wikilink.db
      echo "[deploy] Restored dist/api/wikilink.db"
    fi
  fi
else
  cat >&2 <<'EOF'
[deploy] ERROR: npm not found.

Option A — NVM (typical on shared hosting SSH):
  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash
  source ~/.bashrc
  nvm install 20
  nvm alias default 20
  ./deploy.sh

Option B — cPanel: open "Setup Node.js App" / "Node.js Selector" (name varies), install a version,
  then note the path to node/npm and prepend it to PATH in deploy.sh or export PATH before ./deploy.sh.

Option C — If your host installed EasyApache Node, ensure ea-nodejs18 (or 20/22) is installed in WHM;
  deploy.sh already looks under /opt/cpanel/ea-nodejs*/bin.
EOF
  exit 1
fi

echo "[deploy] OK. Serving directory should be: $REPO_ROOT/dist"
