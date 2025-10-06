#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="/var/www/html"

echo "$TIMEZONE" > /etc/timezone || true
cp "/usr/share/zoneinfo/$TIMEZONE" /etc/localtime || true

if [[ ! -f "$APP_ROOT/config.php" ]]; then
	cp "$APP_ROOT/config.example.php" "$APP_ROOT/config.php"
fi

sed -i \
	-e "s/%DB_HOST%/${DB_HOST}/g" \
	-e "s/%DB_NAME%/${DB_NAME}/g" \
	-e "s/%DB_USER%/${DB_USER}/g" \
	-e "s/%DB_PASSWORD%/${DB_PASS}/g" \
	"$APP_ROOT/config.php"

exec "$@"