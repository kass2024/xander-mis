#!/bin/bash
# Xander spam guard — cPanel account xandhqav
# Cron: */5 * * * * /bin/bash /home/xandhqav/public_html/scripts/cron_spam_guard.sh

set -u

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="$APP_ROOT/logs"
mkdir -p "$LOG_DIR"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] cron start app=$APP_ROOT" >> "$LOG_DIR/spam_cron_stdout.log"

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
    code=$?
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] cron ok php=$PHP_BIN exit=$code" >> "$LOG_DIR/spam_cron_stdout.log"
    exit $code
  fi
done

echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: PHP CLI not found. Set PHP path in cron_spam_guard.sh" >> "$LOG_DIR/spam_cron_stdout.log"
exit 1
