# TesNet hotspot-pay

Plain PHP payment service for MikroTik hotspot vouchers (Paystack + SQLite pool).

## Quick setup (ProBook)

```bash
cd /var/www/MiniISP-Landing-page/TesNet/hotspot-pay
cp config.local.php.example config.local.php
# Edit: Paystack keys, admin password, app_url
chmod 750 storage
php -m | grep -i sqlite   # need pdo_sqlite
```

Import vouchers:

1. Open `https://pay.tesnet.xyz/admin/` (after Apache + tunnel)
2. Login → **Import CSV** → upload `data/vouchers-import.csv`

Or one-shot CLI:

```bash
php -r "
require 'lib/bootstrap.php';
\$r = hp_import_csv(hp_db(), 'data/vouchers-import.csv');
print_r(\$r);
"
```

## Apache

See `deploy/apache-pay.tesnet.xyz.conf.example`.

## Paystack

- Webhook URL: `https://pay.tesnet.xyz/webhook.php`
- Event: `charge.success`

## MikroTik

- Upload updated `login.html` from repo root
- Walled garden: `pay.tesnet.xyz`, `*.paystack.com`, `js.paystack.co`, `api.paystack.co`

## Refill voucher stock

See **`docs/VOUCHER_REFILL_GUIDE.md`** — MikroTik generate → export `.rsc` → `scripts/rsc-to-csv.py` → admin import.

## Add a new package (new MikroTik profile)

See **`docs/ADD_NEW_PACKAGE.md`** — profile on router + `config.php` + `login.html` + import codes.

## Test buy URL

`https://pay.tesnet.xyz/buy.php?pkg=quick-surf`
