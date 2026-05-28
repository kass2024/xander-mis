#!/bin/bash
# Xander spam guard — run from cPanel Cron Jobs every 5 minutes:
#   */5 * * * * /bin/bash /home/USERNAME/public_html/scripts/cron_spam_guard.sh
#
# Replace USERNAME and public_html path if your site lives in a subfolder.

set -u

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="$APP_ROOT/logs"
mkdir -p "$LOG_DIR"

PHP_CANDIDATES=(
  "/usr/local/bin/php"
  "/usr/bin/php"
  "/opt/cpanel/ea-php84/root/usr/bin/php"
  "/opt/cpanel/ea-php83/root/usr/bin/php"
  "/opt/cpanel/ea-php82/root/usr/bin/php"
  "/opt/cpanel/ea-php81/root/usr/bin/php"
)

for PHP_BIN in "${PHP_CANDIDATES[@]}"; do
  if [ -x "$PHP_BIN" ]; then
    cd "$APP_ROOT" || exit 1
    "$PHP_BIN" "$SCRIPT_DIR/spam_guard_purge_all.php" --limit=200 >> "$LOG_DIR/spam_cron_stdout.log" 2>&1
    exit $?
  fi
done

echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: PHP CLI not found. Set PHP path in cron_spam_guard.sh" >> "$LOG_DIR/spam_cron_stdout.log"
exit 1
