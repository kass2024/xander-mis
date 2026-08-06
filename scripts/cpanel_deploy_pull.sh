#!/bin/bash
# Run on cPanel via SSH after git pull (repo: xander-mis)
# Usage: bash scripts/cpanel_deploy_pull.sh
set -euo pipefail
cd "$(dirname "$0")/.."
git pull origin main
echo "Deployed $(git rev-parse --short HEAD) at $(date -u)"
