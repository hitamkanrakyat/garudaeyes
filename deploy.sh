#!/bin/bash
# deploy.sh - run locally to sync workspace to LAMPP htdocs (/opt/lampp/htdocs/garudaeyes)
# Run with: sudo bash deploy.sh
set -euo pipefail
SRC_DIR="/home/ferdinand/Downloads/garudaeyes/"
DST_DIR="/opt/lampp/htdocs/garudaeyes/"
BACKUP_DIR="${DST_DIR%/}/../garudaeyes.bak.$(date +%Y%m%d%H%M%S)"

echo "Backing up existing deployment to: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"
rsync -a --delete "$DST_DIR" "$BACKUP_DIR/"

echo "Copying workspace to deployed folder"
rsync -a --delete "$SRC_DIR" "$DST_DIR"

echo "Setting permissions"
find "$DST_DIR" -type d -exec chmod 755 {} \;
find "$DST_DIR" -type f -exec chmod 644 {} \;

echo "Restarting LAMPP"
/opt/lampp/lampp restart

echo "Deployed. Visit the application login at: http://localhost/garudaeyes/login (or your configured base path)"
