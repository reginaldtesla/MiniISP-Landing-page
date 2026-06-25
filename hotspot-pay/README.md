# TesNet hotspot-pay

Plain PHP payment service for MikroTik hotspot vouchers (Paystack + SQLite pool).

## Standalone location

Run this app from its own Apache project directory:

```text
C:\Apache24\htdocs\hotspot-pay\
```

or on the HP ProBook:

```text
/var/www/hotspot-pay/
```

Apache should point to:

```text
C:\Apache24\htdocs\hotspot-pay\public
/var/www/hotspot-pay/public
```

If this folder is still nested under the landing-page repo on Windows, move it once:

```powershell
cd C:\Apache24\htdocs
Move-Item -Path ".\MiniISP-Landing-page\hotspot-pay" -Destination ".\hotspot-pay"
```

Keep the Laravel portal separate as `C:\Apache24\htdocs\TesNet`.

## Quick setup

```bash
cd /var/www/hotspot-pay
cp config.local.php.example config.local.php
# Edit: Paystack keys, admin password, app_url
chmod 750 storage
php -m | grep -i sqlite   # needs pdo_sqlite
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

See **`../docs/VOUCHER_REFILL_GUIDE.md`** in the source repo, or copy the related docs into `hotspot-pay/docs/` after moving the app.

## Add a new package (new MikroTik profile)

See **`../docs/ADD_NEW_PACKAGE.md`** in the source repo — profile on router + `config.php` + `login.html` + import codes.

## Test buy URL

`https://pay.tesnet.xyz/buy.php?pkg=quick-surf`
