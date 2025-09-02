#!/usr/bin/env bash
# Reliable deploy script for GarudaEyes using LAMPP binaries
# Usage: sudo bash scripts/deploy_final.sh

set -euo pipefail

SRC="/home/ferdinand/Downloads/garudaeyes"
DST="/opt/lampp/htdocs/garudaeyes"
PHP_BIN="/opt/lampp/bin/php"
MYSQL_BIN="/opt/lampp/bin/mysql"

if [ "$(id -u)" -ne 0 ]; then
  echo "Run with sudo" >&2
  exit 2
fi

ORIG_USER="${SUDO_USER:-$(logname 2>/dev/null || echo root)}"
echo "Deploying as $ORIG_USER"

mkdir -p "$DST"
rsync -a --delete --exclude='.git' --exclude='node_modules' "$SRC/" "$DST/"
chown -R "$ORIG_USER":"$ORIG_USER" "$DST"
find "$DST" -type f -exec chmod 0644 {} +
find "$DST" -type d -exec chmod 0755 {} +
chmod +x "$DST"/scripts/*.sh 2>/dev/null || true

echo "Restarting LAMPP"
/opt/lampp/lampp restart || { echo "Failed to restart LAMPP" >&2; exit 3; }

echo "Applying DB schema via PHP binary"
if [ -x "$PHP_BIN" ] && [ -f "$DST/scripts/create_missing_tables.php" ]; then
  "$PHP_BIN" "$DST/scripts/create_missing_tables.php" || echo "create_missing_tables returned non-zero"
fi

echo "Applying init.sql via apply_init_cli.php if present"
if [ -f "$DST/scripts/apply_init_cli.php" ] && [ -x "$PHP_BIN" ]; then
  "$PHP_BIN" "$DST/scripts/apply_init_cli.php" || echo "apply_init_cli.php returned non-zero"
fi

echo "Cleaning debug logs"
rm -f /tmp/chart_error.log || true

echo "Running smoke test"
if [ -x "$DST/tests/smoke.sh" ]; then
  sudo -u "$ORIG_USER" bash "$DST/tests/smoke.sh" || echo "smoke tests failed"
fi

echo "Deploy finished. Visit http://localhost/garudaeyes"
