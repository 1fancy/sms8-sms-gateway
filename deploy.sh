#!/usr/bin/env bash
# SMS8 MCP — simple pull-deploy script.
#
# Run on the server inside /www/wwwroot/mcp.sms8.io/ to pull the latest
# code from GitHub and fix permissions. No restart needed — PHP-FPM picks
# up changed files immediately.
#
#   ./deploy.sh
#
# Trigger this from a GitHub webhook later if you want push-to-deploy.
set -euo pipefail

cd "$(dirname "$0")"

echo "→ git pull"
git pull --ff-only

echo "→ fix ownership"
chown -R www:www . 2>/dev/null || true
chmod -R u=rwX,go=rX . 2>/dev/null || true
chmod 600 deploy.sh 2>/dev/null || true

echo "✓ deploy complete at $(date -u +%FT%TZ)"
