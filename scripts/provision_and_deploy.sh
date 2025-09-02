#!/usr/bin/env bash
# scripts/provision_and_deploy.sh
# One-shot script to provision a fresh VPS (Ubuntu/Debian), install LAMP (or use XAMPP), deploy GarudaEyes,
# create DB/user, run migrations, import seeds, and create an admin user.
# Usage (example):
#   sudo bash scripts/provision_and_deploy.sh --domain example.tld --admin-email admin@example.tld --admin-pass 'Secret' --repo 'https://.../garudaeyes.git'

set -euo pipefail

if [ "${EUID:-$(id -u)}" -ne 0 ]; then
  echo "Please run as root (sudo bash $0 ...)"
  exit 2
fi

# Defaults
DOMAIN=""
ADMIN_EMAIL="admin@example.local"
ADMIN_PASS="Admin@Garuda2025!"
REPO_URL=""
SRC_TAR=""
USE_LAMPP=false
DB_ROOT_PASS=""
NONINTERACTIVE=false

# Parse args
while [ "$#" -gt 0 ]; do
  case "$1" in
    --domain) DOMAIN="$2"; shift 2;;
    --admin-email) ADMIN_EMAIL="$2"; shift 2;;
    --admin-pass) ADMIN_PASS="$2"; shift 2;;
    --repo) REPO_URL="$2"; shift 2;;
    --src-tar) SRC_TAR="$2"; shift 2;;
    --use-lampp) USE_LAMPP=true; shift 1;;
    --db-root-pass) DB_ROOT_PASS="$2"; shift 2;;
    --noninteractive) NONINTERACTIVE=true; shift 1;;
    --help) echo "Usage: sudo bash $0 --domain <domain> [--repo <git-url> | --src-tar <tar>] [--admin-email <email>] [--admin-pass <pass>] [--use-lampp] [--db-root-pass <pass>]"; exit 0;;
    *) echo "Unknown arg: $1"; exit 1;;
  esac
done

if [ -z "$DOMAIN" ]; then
  echo "--domain is required"
  exit 1
fi

SRC_DIR="$(cd "$(dirname "$0")/.." && pwd)"
WEB_ROOT="/var/www/$DOMAIN"
DB_NAME="garudaeyes"
DB_USER="garudaeyes"
DB_PASS="$(openssl rand -base64 16 | tr -dc 'A-Za-z0-9' | head -c 16)"

# Detect PHP and mysql binaries
PHP_BIN="$(command -v php || true)"
MYSQL_BIN="$(command -v mysql || true)"
if [ "$USE_LAMPP" = true ] || [ -x "/opt/lampp/bin/php" ]; then
  if [ -x "/opt/lampp/bin/php" ]; then
    PHP_BIN="/opt/lampp/bin/php"
  fi
  if [ -x "/opt/lampp/bin/mysql" ]; then
    MYSQL_BIN="/opt/lampp/bin/mysql"
  fi
fi

echo "Using PHP: ${PHP_BIN:-(none)}"
echo "Using mysql client: ${MYSQL_BIN:-(none)}"

# Install system packages if not using LAMPP
if [ "$USE_LAMPP" = false ]; then
  apt update && apt upgrade -y
  apt install -y apache2 mariadb-server php php-mysql php-xml php-mbstring php-curl php-zip unzip curl rsync openssl
  for mod in rewrite headers; do a2enmod "$mod" || true; done
  systemctl restart apache2 || true
fi

# Prepare web root and source
mkdir -p "$WEB_ROOT"
if [ -n "$REPO_URL" ]; then
  # clone shallow into /tmp and rsync
  TMP_CLONE="/tmp/garudaeyes_clone_$$"
  rm -rf "$TMP_CLONE"
  git clone --depth 1 "$REPO_URL" "$TMP_CLONE"
  rsync -a --delete --exclude='.git' --exclude='node_modules' "$TMP_CLONE/" "$WEB_ROOT/"
  rm -rf "$TMP_CLONE"
elif [ -n "$SRC_TAR" ]; then
  tar -xvf "$SRC_TAR" -C /tmp
  rsync -a --delete --exclude='.git' --exclude='node_modules' /tmp/garudaeyes/ "$WEB_ROOT/" || rsync -a --delete --exclude='.git' --exclude='node_modules' "$SRC_DIR/" "$WEB_ROOT/"
else
  rsync -a --delete --exclude='.git' --exclude='node_modules' "$SRC_DIR/" "$WEB_ROOT/"
fi

chown -R www-data:www-data "$WEB_ROOT"
find "$WEB_ROOT" -type f -exec chmod 0644 {} +
find "$WEB_ROOT" -type d -exec chmod 0755 {} +

# Create DB and user (use root client if available)
echo "Creating database and user..."
MYSQL_CMD="$MYSQL_BIN"
if [ -n "$DB_ROOT_PASS" ]; then
  MYSQL_CMD="$MYSQL_CMD -u root -p$DB_ROOT_PASS"
else
  MYSQL_CMD="$MYSQL_CMD -u root"
fi

# Create DB
echo "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" | $MYSQL_CMD || true
# Create user
$MYSQL_BIN -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';" || true
$MYSQL_BIN -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;" || true

# Run migrations if migration runner exists, otherwise import init.sql
if [ -x "$PHP_BIN" ] && [ -f "$WEB_ROOT/scripts/create_missing_tables.php" ]; then
  echo "Running PHP migration runner..."
  "$PHP_BIN" "$WEB_ROOT/scripts/create_missing_tables.php" || echo "Migration runner exited with non-zero status (continuing)"
else
  if [ -f "$WEB_ROOT/db/init.sql" ]; then
    echo "Importing db/init.sql..."
    $MYSQL_BIN -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$WEB_ROOT/db/init.sql" || true
  fi
fi

# Import seed if present (ask unless noninteractive)
if [ -f "$WEB_ROOT/garuda.sql" ]; then
  do_import=true
  if [ "$NONINTERACTIVE" = false ]; then
    read -r -p "Found garuda.sql seed file. Import into $DB_NAME? [y/N] " ans || true
    case "${ans:-n}" in
      [Yy]*) do_import=true;;
      *) do_import=false;;
    esac
  fi
  if [ "$do_import" = true ]; then
    echo "Importing garuda.sql..."
    $MYSQL_BIN -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$WEB_ROOT/garuda.sql" || echo "Import finished with errors (see output)"
  else
    echo "Skipping garuda.sql import"
  fi
fi

# Apache vhost
VHOST_FILE="/etc/apache2/sites-available/$DOMAIN.conf"
cat > "$VHOST_FILE" <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot $WEB_ROOT
    <Directory $WEB_ROOT>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog "/var/log/apache2/${DOMAIN}_error.log"
    CustomLog "/var/log/apache2/${DOMAIN}_access.log" combined
</VirtualHost>
EOF

a2ensite "$DOMAIN" || true
systemctl reload apache2 || true

# Try to create admin user via provided script
if [ -x "$PHP_BIN" ] && [ -f "$WEB_ROOT/scripts/create_admin.php" ]; then
  echo "Creating admin user..."
  "$PHP_BIN" "$WEB_ROOT/scripts/create_admin.php" -u "$ADMIN_EMAIL" -p "$ADMIN_PASS" -n "Administrator" -e "$ADMIN_EMAIL" || true
fi

echo -e "\n=== GarudaEyes deployed! ==="
echo "Domain: http://$DOMAIN/"
echo "Web root: $WEB_ROOT"
echo "DB name: $DB_NAME"
echo "DB user: $DB_USER"
echo "DB pass: $DB_PASS"
echo "Admin email: $ADMIN_EMAIL"
echo "Admin password: $ADMIN_PASS"
echo "You can now login at http://$DOMAIN/login"
