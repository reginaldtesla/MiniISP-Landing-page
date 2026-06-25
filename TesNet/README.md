# TesNet portal

Laravel captive-portal and billing app for the TesNet student Wi-Fi network.

## Standalone location

Run this app from its own Apache project directory:

```text
C:\Apache24\htdocs\TesNet\
```

or on the HP ProBook:

```text
/var/www/TesNet/
```

Apache should point to the Laravel public directory:

```text
C:\Apache24\htdocs\TesNet\public
/var/www/TesNet/public
```

If this folder is still nested under the landing-page repo on Windows, move it once:

```powershell
cd C:\Apache24\htdocs
Move-Item -Path ".\MiniISP-Landing-page\TesNet" -Destination ".\TesNet"
```

Keep `hotspot-pay` separate as `C:\Apache24\htdocs\hotspot-pay`.

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build:offline
php artisan serve --host=0.0.0.0 --port=8080
```

`npm run build:offline` copies local fonts, Material Symbols, CSS, and JS into `public/assets/portal/` so the captive portal works without external CDNs.

## Main URLs

| URL | Purpose |
| --- | --- |
| `/portal/login` | Student login and registration |
| `/portal/forgot-password` | Phone-based password reset |
| `/portal/dashboard` | Student dashboard |
| `/admin/login` | Admin hub |

## Production notes

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Set `APP_URL` to the public portal URL.
- Configure MariaDB and FreeRADIUS before enabling MikroTik RADIUS login.
- Set Apache `DocumentRoot` to the `public/` directory.
- Add the Laravel scheduler:

```cron
* * * * * cd /var/www/TesNet && php artisan schedule:run >> /dev/null 2>&1
```

## Documentation

The TesNet deployment docs live inside this app folder:

- [Docs index](docs/README.md)
- [Full setup — ProBook + MikroTik](docs/PROBOOK_MIKROTIK_FULL_SETUP.md)
- [Production checklist](docs/PRODUCTION_CHECKLIST.md)
- [Cloudflare Tunnel](docs/CLOUDFLARE_TUNNEL.md)
- [Hotspot HTML & MikroTik redirect](docs/HOTSPOT.md)
- [Paystack live + HTTPS webhook](docs/PAYSTACK.md)
