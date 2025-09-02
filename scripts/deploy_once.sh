#!/usr/bin/env bash

set -euo pipefail

# deploy_once.sh - reliable LAMPP-aware deploy for GarudaEyes
# Usage: sudo bash scripts/deploy_once.sh [--src SRC] [--dst DST] [--admin-user u --admin-pass p] [--no-restart]

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SRC_DEFAULT="$SCRIPT_DIR/.."
DST_DEFAULT="/opt/lampp/htdocs/garudaeyes"

SRC="$SRC_DEFAULT"
DST="$DST_DEFAULT"
PHP_BIN="/opt/lampp/bin/php"
MYSQL_BIN="/opt/lampp/bin/mysql"
LAMPP_CTL="/opt/lampp/lampp"
RESTART_LAMPP=true

ADMIN_USER=""
ADMIN_PASS=""
ADMIN_NAME="Administrator"
ADMIN_EMAIL="admin@example.local"

show_usage() {
  cat <<'USAGE'
Usage: sudo bash scripts/deploy_once.sh [options]

Options:
  --src PATH           Source directory (default: workspace parent)
  --dst PATH           Destination (default: /opt/lampp/htdocs/garudaeyes)
  --admin-user USER    Create or update admin user with provided username
  --admin-pass PASS    Password for admin user (used with --admin-user)
  --admin-name NAME    Admin full name (optional)
  --admin-email EMAIL  Admin email (optional)
  --no-restart         Do not restart LAMPP
  -h, --help           Show this help
  --db-user USER       MySQL user for automatic garuda.sql import
  --db-pass PASS       MySQL password for automatic garuda.sql import
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --src) SRC="$2"; shift 2;;
    --dst) DST="$2"; shift 2;;
    --admin-user) ADMIN_USER="$2"; shift 2;;
    --admin-pass) ADMIN_PASS="$2"; shift 2;;
    --admin-name) ADMIN_NAME="$2"; shift 2;;
  --admin-email) ADMIN_EMAIL="$2"; shift 2;;
  --db-user) DB_USER="$2"; shift 2;;
  --db-pass) DB_PASS="$2"; shift 2;;
    --no-restart) RESTART_LAMPP=false; shift 1;;
    -h|--help) show_usage; exit 0;;
    *) echo "Unknown option: $1"; show_usage; exit 2;;
  esac
done

DB_HOST="127.0.0.1"
DB_PORT="3306"

if [ "$(id -u)" -ne 0 ]; then
  echo "This deploy script requires root (sudo). Run with: sudo bash $0" >&2
  exit 2
fi

ORIG_USER="${SUDO_USER:-$(logname 2>/dev/null || echo root)}"
echo "Deploying from: $SRC"
echo "Deploying to:   $DST"
echo "Running as:     $ORIG_USER"

# Resolve PHP binary
if [ -x "$PHP_BIN" ]; then
  echo "Using LAMPP PHP: $PHP_BIN"
else
  PHP_BIN="$(command -v php || true)"
  if [ -z "$PHP_BIN" ]; then
    echo "No PHP CLI found (neither /opt/lampp/bin/php nor system php). Aborting." >&2
    exit 3
  fi
  echo "Using system PHP: $PHP_BIN"
fi

# Resolve mysql client
if [ -x "$MYSQL_BIN" ]; then
  echo "Using LAMPP mysql client: $MYSQL_BIN"
else
  MYSQL_BIN="$(command -v mysql || true)"
  if [ -z "$MYSQL_BIN" ]; then
    echo "No mysql client found; some DB tasks may be skipped." >&2
  else
    echo "Using system mysql client: $MYSQL_BIN"
  fi
fi

# Sync files
mkdir -p "$DST"
rsync -a --delete --exclude='.git' --exclude='node_modules' "$SRC/" "$DST/"
echo "Files synced."

# Ownership & perms
chown -R "$ORIG_USER":"$ORIG_USER" "$DST"
find "$DST" -type f -exec chmod 0644 {} +
find "$DST" -type d -exec chmod 0755 {} +
chmod +x "$DST"/scripts/*.sh 2>/dev/null || true
echo "Ownership and permissions set."

# Restart LAMPP
if $RESTART_LAMPP && [ -x "$LAMPP_CTL" ]; then
  echo "Restarting LAMPP..."
  "$LAMPP_CTL" restart || echo "Warning: LAMPP restart failed" >&2
  sleep 2
else
  echo "Skipping LAMPP restart (no $LAMPP_CTL or --no-restart)."
fi

# Run project's PHP helper to apply initial schema
if [ -x "$PHP_BIN" ]; then
  if [ -f "$DST/scripts/apply_init_cli.php" ]; then
    echo "Applying DB schema via apply_init_cli.php"
    sudo -u "$ORIG_USER" "$PHP_BIN" "$DST/scripts/apply_init_cli.php" || echo "apply_init_cli.php returned non-zero, continuing"
  fi
else
  echo "PHP CLI not available; skipping PHP-based schema apply." >&2
fi

# Run create_missing_tables.php if present, otherwise try idempotent SQL via mysql client
if [ -f "$DST/scripts/create_missing_tables.php" ]; then
  echo "Running create_missing_tables.php"
  sudo -u "$ORIG_USER" "$PHP_BIN" "$DST/scripts/create_missing_tables.php" || echo "create_missing_tables.php returned non-zero, continuing"
elif [ -x "$MYSQL_BIN" ]; then
  echo "Attempting fallback DB fixes via mysql client (no password prompts will be provided)"
  cat <<'SQL' | "$MYSQL_BIN" -uroot 2>/dev/null || true
CREATE DATABASE IF NOT EXISTS garudaeyes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE garudaeyes;
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  username VARCHAR(191) DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE users ADD COLUMN IF NOT EXISTS current_session VARCHAR(128) DEFAULT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL;
SQL
  echo "Fallback DB fixes attempted (errors suppressed)."
else
  echo "No DB helper script and no mysql client; skipping DB fix attempts." >&2
fi

# If garuda.sql exists, offer import: automatic when DB user/pass given; otherwise interactive prompt
if [ -f "$DST/garuda.sql" ] && [ -x "$MYSQL_BIN" ]; then
  if [ -n "${DB_USER:-}" ] && [ -n "${DB_PASS:-}" ]; then
    echo "Importing garuda.sql using provided DB credentials (user: $DB_USER@${DB_HOST}:${DB_PORT})"
    "$MYSQL_BIN" -h"${DB_HOST}" -P"${DB_PORT}" -u"$DB_USER" -p"$DB_PASS" < "$DST/garuda.sql" || echo "garuda.sql import returned non-zero; inspect file and DB credentials"
  else
    read -p "Detected $DST/garuda.sql. Import to MySQL now? [y/N]: " REPLY_IMP
    if [[ "$REPLY_IMP" =~ ^[Yy]$ ]]; then
      read -p "MySQL host (default ${DB_HOST}): " DB_HOST_TMP
      DB_HOST=${DB_HOST_TMP:-$DB_HOST}
      read -p "MySQL port (default ${DB_PORT}): " DB_PORT_TMP
      DB_PORT=${DB_PORT_TMP:-$DB_PORT}
      read -p "MySQL user (default root): " DB_USER_TMP
      DB_USER=${DB_USER_TMP:-root}
      read -s -p "MySQL password (leave empty for no password): " DB_PASS_TMP
      echo
      DB_PASS=${DB_PASS_TMP:-}
      echo "Importing garuda.sql as user '$DB_USER'@'$DB_HOST:$DB_PORT' (password hidden)"
      "$MYSQL_BIN" -h"${DB_HOST}" -P"${DB_PORT}" -u"$DB_USER" -p"$DB_PASS" < "$DST/garuda.sql" || echo "garuda.sql import returned non-zero; inspect file and DB credentials"
    else
      echo "Skipping automatic garuda.sql import."
    fi
  fi
fi

# Optional: create admin user when credentials provided
if [ -n "$ADMIN_USER" ]; then
  if [ -z "$ADMIN_PASS" ]; then
    echo "--admin-user provided but --admin-pass missing; skipping admin creation." >&2
  else
    if [ -x "$PHP_BIN" ] && [ -f "$DST/scripts/create_admin.php" ]; then
      echo "Creating/updating admin user via scripts/create_admin.php"
      sudo -u "$ORIG_USER" "$PHP_BIN" "$DST/scripts/create_admin.php" -u "$ADMIN_USER" -p "$ADMIN_PASS" -n "$ADMIN_NAME" -e "$ADMIN_EMAIL" || echo "create_admin.php failed"
    else
      echo "Cannot run create_admin.php (missing PHP or script)." >&2
    fi
  fi
fi

# Basic endpoint verification
JQ_BIN="$(command -v jq || true)"
BASE_URL="http://localhost/garudaeyes"
ERRMSG=""

echo; echo "=== Verifying $BASE_URL/login ==="
LOGIN_CODE=$(curl -s -o /tmp/deploy_login.html -w "%{http_code}" "$BASE_URL/login" || echo "000")
echo "HTTP: $LOGIN_CODE"
if [ "$LOGIN_CODE" != "200" ]; then
  ERRMSG+="Login page HTTP $LOGIN_CODE; "
else
  if grep -q 'name="csrf_token"' /tmp/deploy_login.html; then
    echo "CSRF token: OK"
  else
    ERRMSG+="CSRF token missing; "
  fi
fi

echo; echo "=== Verifying $BASE_URL/api/chart.php ==="
CH_CODE=$(curl -s -o /tmp/deploy_chart.json -w "%{http_code}" "$BASE_URL/api/chart.php" || echo "000")
echo "HTTP: $CH_CODE"
if [ "$CH_CODE" != "200" ]; then
  ERRMSG+="API chart HTTP $CH_CODE; "
else
  if [ -n "$JQ_BIN" ]; then
    if "$JQ_BIN" . /tmp/deploy_chart.json >/dev/null 2>&1; then
      echo "API chart: valid JSON (jq ok)"
    else
      ERRMSG+="API chart JSON invalid; "
    fi
  else
    if grep -q '"labels"' /tmp/deploy_chart.json || grep -q '"data"' /tmp/deploy_chart.json; then
      echo "API chart: JSON seems OK (heuristic)"
    else
      ERRMSG+="API chart JSON missing expected keys; "
    fi
  fi
fi

echo; echo "=== FINAL REPORT ==="
if [ -z "$ERRMSG" ]; then
  echo "PASS: Basic checks OK."
  exit 0
else
  echo "FAIL: $ERRMSG"
  exit 1
fi