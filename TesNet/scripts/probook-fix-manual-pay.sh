#!/usr/bin/env bash
set -euo pipefail

if [ -d /var/www/MiniISP-Landing-page/TesNet ]; then
    APP=/var/www/MiniISP-Landing-page/TesNet
elif [ -d "$HOME/TesNet" ]; then
    APP="$HOME/TesNet"
else
    APP="$(find /var/www -maxdepth 5 -name artisan -print -quit 2>/dev/null | xargs dirname 2>/dev/null || true)"
fi

if [ -z "${APP:-}" ] || [ ! -f "$APP/artisan" ]; then
    echo "ERROR: Could not find TesNet (artisan)."
    exit 1
fi

echo "Using TesNet at: $APP"
cd "$APP"

echo "=== Migration status (pending) ==="
php artisan migrate:status | grep -i pending || echo "(none pending)"

echo "=== Running migrations ==="
php artisan migrate --force

echo "=== Clearing config/cache ==="
php artisan config:clear
php artisan cache:clear

echo "=== Pending manual payment requests ==="
php artisan tinker --execute="echo App\Models\ManualPaymentRequest::query()->where('status','pending')->get(['id','type','package_slug','amount','metadata'])->toJson(JSON_PRETTY_PRINT);"

echo "=== Recent manual payment approval errors ==="
grep -h "Manual payment approval failed" storage/logs/laravel*.log 2>/dev/null | tail -5 || echo "(no log entries)"

echo "=== Done. Try Admin -> Manual Pay -> Approve again. ==="
