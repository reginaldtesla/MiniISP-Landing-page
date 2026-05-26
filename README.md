# TesNet — Hostel Mini-ISP (Ayeduase / KNUST)

TesNet is a Laravel captive-portal and billing stack for student Wi‑Fi: **phone + password** login, **FreeRADIUS**, **MikroTik** hotspot, **Paystack** payments, and separate **student** vs **admin** hubs.

**Application code:** [`TesNet/`](TesNet/) (Laravel 13)

## Architecture

| Layer | Role |
| :--- | :--- |
| **MikroTik** (`192.168.88.1`) | Hotspot, DHCP, walled garden |
| **Ubuntu / HP ProBook** (`192.168.88.2`) | Laravel, MariaDB, FreeRADIUS |
| **Students** | Portal login → buy data → connect Wi‑Fi |

Auth is **not** MAC-based: RADIUS uses the normalized phone number as username (`233…`). Laravel syncs `radcheck` / `radreply` on user and package changes.

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

| URL | Purpose |
| :--- | :--- |
| `/portal/login` | Student login / register |
| `/portal/forgot-password` | Phone-based password reset |
| `/admin/login` | Admin hub (packages, students, announcements) |

Default seeded admin (change in `.env` then `php artisan db:seed`): phone `0550000001`, password from `ADMIN_PASSWORD`.

## Documentation

- **[Hotspot HTML & MikroTik redirect](docs/HOTSPOT.md)** — replace legacy `login.html` with Laravel
- **[Paystack live + HTTPS webhook](docs/PAYSTACK.md)** — production keys, `APP_URL`, webhook URL
- **[Laravel app README](TesNet/README.md)** — env vars, RADIUS, offline assets (if present)

## Legacy files

Root `login.html` and copies under `flash/hotspot/` are **PHPNuxBill-era** redirects. Production should point the hotspot login URL at the Laravel portal (see `docs/HOTSPOT.md`).

## License

Student initiative project — © 2026 TesNet.
