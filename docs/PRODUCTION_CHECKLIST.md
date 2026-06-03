# TesNet — Production checklist

Use with **Admin → Health** in the portal and the main [installation](../installation) guide.

Full step-by-step (factory reset → go-live): **[PROBOOK_MIKROTIK_FULL_SETUP.md](PROBOOK_MIKROTIK_FULL_SETUP.md)**.

## ProBook — everything to install

### 1. Operating system

| Item | Notes |
|------|--------|
| **Ubuntu Server 24.04 LTS** (64-bit) | Fresh install on HP ProBook |
| **OpenSSH server** | Enable during Ubuntu install (remote admin) |

After install: `sudo apt update && sudo apt upgrade -y`

### 2. System packages (one command)

```bash
sudo apt install -y software-properties-common curl git unzip \
  apache2 mariadb-server mariadb-client \
  freeradius freeradius-mysql freeradius-utils \
  php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl libapache2-mod-php8.3
```

| Package | Role |
|---------|------|
| `apache2` | Web server → Laravel `public/` |
| `mariadb-server` | Database `tesnet` (app + RADIUS) |
| `freeradius` + `freeradius-mysql` | Wi‑Fi authentication / accounting |
| `freeradius-utils` | `radtest` for debugging |
| `php8.3*` + `libapache2-mod-php8.3` | Laravel 13 (requires PHP 8.3+) |
| `git`, `curl`, `unzip` | Deploy and downloads |

Enable Apache modules and services:

```bash
sudo a2enmod rewrite ssl headers
sudo systemctl enable --now apache2 mariadb
```

### 3. Composer (PHP dependencies)

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 4. Node.js 20 (build portal offline assets)

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

Used once per deploy: `npm install` and `npm run build:offline` (not needed on every reboot).

### 5. Optional (when you need them)

| Install | When |
|---------|------|
| `certbot` + `python3-certbot-apache` | Public domain + HTTPS / Paystack live webhooks |
| **UFW** | Built into Ubuntu — configure firewall (not a separate app) |

```bash
sudo apt install -y certbot python3-certbot-apache
```

### 6. Application (not apt — deploy TesNet code)

On the ProBook after packages above (path after default `git clone`):

```bash
cd /var/www
git clone https://github.com/reginaldtesla/MiniISP-Landing-page.git
cd /var/www/MiniISP-Landing-page/TesNet
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
npm install && npm run build:offline
```

### 7. Configure & enable (not installers)

| Task | Purpose |
|------|---------|
| Netplan static IP `192.168.88.2` | Stable portal + RADIUS address |
| UFW rules | SSH, HTTP/S from LAN, RADIUS from MikroTik |
| FreeRADIUS `mods-enabled/sql` + `clients.conf` | RADIUS → MariaDB + MikroTik NAS |
| Apache site `tesnet.conf` | DocumentRoot → `TesNet/public` |
| `systemctl enable freeradius` | RADIUS after reboot |
| `www-data` crontab | `php artisan schedule:run` (backups, monitor) |
| Mask sleep targets | ProBook must not suspend (see setup guide §A.11) |

### 8. Not installed on the ProBook

| Item | Where it runs |
|------|----------------|
| MikroTik RouterOS | On the hAP² router only |
| Winbox | Your Windows laptop (optional) |
| Paystack | Cloud (keys in `.env` only) |

---

## Server (HP ProBook / Ubuntu)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL` (HTTPS in production)
- [ ] Apache + PHP 8.3+ → `DocumentRoot` `/var/www/MiniISP-Landing-page/TesNet/public`
- [ ] `php artisan migrate --force` applied
- [ ] `npm run build:offline` — `public/assets/portal/` present
- [ ] `PORTAL_USE_OFFLINE_ASSETS=true`
- [ ] Cron for Laravel scheduler:
  ```cron
  * * * * * cd /var/www/MiniISP-Landing-page/TesNet && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] `LOG_CHANNEL=daily` (log rotation via daily files)
- [ ] `TESNET_BACKUP_ENABLED=true` — verify `php artisan tesnet:backup-database` works
- [ ] `ADMIN_ALLOWED_IPS` set (e.g. `192.168.88.0/24,127.0.0.1`) — optional but recommended
- [ ] Strong `ADMIN_PASSWORD` (min length `ADMIN_MIN_PASSWORD_LENGTH`, default 12)

## MariaDB + FreeRADIUS

- [ ] Database `tesnet` created; app and RADIUS use same DB
- [ ] Tables `radcheck`, `radreply`, `radacct` exist (migrations)
- [ ] FreeRADIUS SQL module enabled; test: `radtest USER PASS 127.0.0.1 0 testing123`
- [ ] MikroTik RADIUS client secret matches `clients.conf`
- [ ] Accounting enabled on hotspot — `radacct` rows update while users are online

## MikroTik hAP²

- [ ] Hotspot on LAN bridge; `use-radius=yes`
- [ ] **Walled garden** — portal host + Paystack hosts only (see installation §5.7)
- [ ] No trial / bypass users
- [ ] `login.html` redirects to Laravel `/portal/login`
- [ ] `MIKROTIK_LOGIN_URL` matches router login URL
- [ ] Optional: `MIKROTIK_API_ENABLED=true` for force-disconnect from admin/student devices

### Walled garden test (anti-leak)

| Step | Expected |
|------|----------|
| Connected, not logged in | Portal opens; `google.com` blocked |
| Logged in, no package | Portal works; general internet blocked |
| Paid, not connected | Still blocked until **Connect** |
| Connected with active plan | Browsing works within quota |

## Paystack

- [ ] Live or test keys in `.env`
- [ ] Webhook URL: `{APP_URL}/portal/payments/webhook`
- [ ] HTTPS on `APP_URL` for production webhooks

## App smoke test

- [ ] Student register + login
- [ ] Buy package (Paystack) OR manual payment approve
- [ ] Dashboard **Connect to Internet** → MikroTik login → internet works
- [ ] Usage increases in `radacct` / dashboard data remaining decreases
- [ ] Admin: Analytics, Sessions, Manual Pay, Health pages load

## CLI health commands

```bash
php artisan tesnet:monitor
php artisan tesnet:backup-database
php artisan route:list --name=admin.system-health
```

## After go-live

- [ ] Read [DAILY_OPERATIONS.md](DAILY_OPERATIONS.md)
- [ ] Weekly: download DB backup off-server
- [ ] Monthly: review Analytics + top packages; adjust pricing
