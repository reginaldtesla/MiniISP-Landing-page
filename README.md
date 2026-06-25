# MiniISP Landing Page workspace

This workspace started as the landing-page project, but it also contains two deployable TesNet services:

- **`TesNet/`** — Laravel captive portal and billing stack for student Wi-Fi.
- **`hotspot-pay/`** — plain PHP Paystack voucher-payment service.

For local Apache on Windows, keep those services as sibling folders under `htdocs` instead of running them inside the landing-page directory:

```text
C:\Apache24\htdocs\
├── MiniISP-Landing-page\   # public landing page and shared docs
├── TesNet\                 # Laravel portal, Apache DocumentRoot -> TesNet\public
└── hotspot-pay\            # voucher payment app, Apache DocumentRoot -> hotspot-pay\public
```

If the service folders are still inside `MiniISP-Landing-page`, move them once:

```powershell
cd C:\Apache24\htdocs
Move-Item -Path ".\MiniISP-Landing-page\TesNet" -Destination ".\TesNet"
Move-Item -Path ".\MiniISP-Landing-page\hotspot-pay" -Destination ".\hotspot-pay"
```

On Ubuntu / HP ProBook, use the same sibling layout:

```text
/var/www/
├── MiniISP-Landing-page/
├── TesNet/
└── hotspot-pay/
```

## TesNet portal

## Architecture

| Layer | Role |
| :--- | :--- |
| **MikroTik** (`192.168.88.1`) | Hotspot, DHCP, walled garden |
| **Ubuntu / HP ProBook** (`192.168.88.2`) | Laravel, MariaDB, FreeRADIUS |
| **Students** | Portal login → buy data → connect Wi‑Fi |

Auth is **not** MAC-based: RADIUS uses the normalized phone number as username (`233…`). Laravel syncs `radcheck` / `radreply` on user and package changes.

**Production path (HP ProBook):** `/var/www/TesNet` with Apache pointing to `/var/www/TesNet/public`.

## Quick start (development)

```bash
cd TesNet
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build:offline
php artisan serve --host=0.0.0.0 --port=8080
```

`npm run build:offline` copies fonts, Material Symbols icons, CSS, and JS into `public/assets/portal/` so the UI works without CDN or Vite (needed on the hotspot).

| URL | Purpose |
| :--- | :--- |
| `/portal/login` | Student login / register |
| `/portal/forgot-password` | Phone-based password reset |
| `/admin/login` | Admin hub (packages, students, announcements) |

Default seeded admin (change in `.env` then `php artisan db:seed`): phone `0550000001`, password from `ADMIN_PASSWORD`.

## Documentation

- **[Full setup — ProBook + MikroTik (from factory reset)](TesNet/docs/PROBOOK_MIKROTIK_FULL_SETUP.md)** — **start here for production**
- **[Installation — HP ProBook + MikroTik hAP²](installation)** — same topics, reference format
- **[Hotspot HTML & MikroTik redirect](TesNet/docs/HOTSPOT.md)** — replace legacy `login.html` with Laravel
- **[Paystack live + HTTPS webhook](TesNet/docs/PAYSTACK.md)** — production keys, `APP_URL`, webhook URL
- **[Production checklist](TesNet/docs/PRODUCTION_CHECKLIST.md)** — go-live verification (MikroTik, RADIUS, Paystack)
- **[Daily operations](TesNet/docs/DAILY_OPERATIONS.md)** — outages, manual payments, sessions, backups
- **[Laravel app README](TesNet/README.md)** — standalone TesNet setup
- **[hotspot-pay README](hotspot-pay/README.md)** — standalone voucher payment setup
- **[Voucher refill guide](docs/VOUCHER_REFILL_GUIDE.md)** — MikroTik export to hotspot-pay import
- **[Add new hotspot package](docs/ADD_NEW_PACKAGE.md)** — router profile + hotspot-pay config

## Legacy files

Root `login.html` and copies under `flash/hotspot/` are **PHPNuxBill-era** redirects. Production should point the hotspot login URL at the Laravel portal (see `TesNet/docs/HOTSPOT.md`).

## License

Student initiative project — © 2026 TesNet.
