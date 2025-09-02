#!/usr/bin/env bash
# Minimal smoke tests for GarudaEyes
set -euo pipefail

BASE_PATH="/garudaeyes"
URL="http://localhost${BASE_PATH}/login"

echo "Fetching: $URL"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$URL")
echo "HTTP status: $STATUS"
if [ "$STATUS" -ne 200 ]; then
  echo "Expected HTTP 200" >&2
  exit 2
fi

BODY=$(curl -s "$URL")
echo "$BODY" | grep -q 'name="csrf_token"' && echo "csrf token: OK" || (echo "csrf token: MISSING" && exit 3)
echo "$BODY" | grep -E "action=\"${BASE_PATH}/login\"" >/dev/null && echo "form action: OK" || (echo "form action: MISSING/INCORRECT" && exit 4)

echo "Smoke tests passed"
