# TesNet — Full setup: HP ProBook + MikroTik (factory reset)

Complete guide to deploy TesNet from **zero**: fresh Ubuntu on an HP ProBook and a **reset** MikroTik hAP². One hostel = one ProBook + one router + one database.

**Related docs (detail only where noted):**

| Doc | Use for |
|-----|---------|
| [`docs/PAYSTACK.md`](PAYSTACK.md) | Paystack keys, webhooks, HTTPS |
| [`docs/HOTSPOT.md`](HOTSPOT.md) | Captive portal behaviour |
| [`docs/PRODUCTION_CHECKLIST.md`](PRODUCTION_CHECKLIST.md) | Go-live checklist |
| [`docs/DAILY_OPERATIONS.md`](DAILY_OPERATIONS.md) | Day-to-day admin |

---

## Table of contents

1. [What you are building](#1-what-you-are-building)
2. [Before you start](#2-before-you-start)
3. [Secrets worksheet](#3-secrets-worksheet)
4. [Recommended order of work](#4-recommended-order-of-work)
5. [Part A — HP ProBook (Ubuntu server)](#5-part-a--hp-probook-ubuntu-server)
6. [Part B — MikroTik hAP² (after reset)](#6-part-b--mikrotik-hap²-after-reset)
7. [Part C — Connect ProBook and router](#7-part-c--connect-probook-and-router)
8. [End-to-end verification](#8-end-to-end-verification)
9. [Certificates and HTTPS](#9-certificates-and-https)
10. [Paystack (when ready)](#10-paystack-when-ready)
11. [Power off, restart, and recovery](#11-power-off-restart-and-recovery)
12. [Production checklist](#12-production-checklist)
13. [Troubleshooting](#13-troubleshooting)
14. [Moving to the cloud later](#14-moving-to-the-cloud-later)

---

## 1. What you are building

```text
[ Internet ]
      │
      ▼
┌─────────────────────────────────────┐
│  MikroTik hAP²  192.168.88.1        │
│  • Wi‑Fi (TesNet-Student)           │
│  • Hotspot + walled garden          │
│  • RADIUS client → ProBook          │
└──────────────┬──────────────────────┘
               │ LAN (ether2)
               ▼
┌─────────────────────────────────────┐
│  HP ProBook (Ubuntu) 192.168.88.2   │
│  • Apache → Laravel (TesNet)        │
│  • MariaDB (tesnet)                 │
│  • FreeRADIUS → same DB             │
└─────────────────────────────────────┘
               ▲
               │ Wi‑Fi
         [ Students ]
```

**Student flow:**

1. Join Wi‑Fi → phone shows **Sign in to network** (captive portal).
2. Router `login.html` → **TesNet portal** (`/portal/login`).
3. Register / log in → buy data (Paystack or manual payment).
4. Dashboard **Connect to Internet** → POST to MikroTik `http://192.168.88.1/login` (PAP) → RADIUS Accept → internet within plan.

Payment alone does **not** open the internet; **Connect** does (see [`docs/HOTSPOT.md`](HOTSPOT.md)).

---

## 2. Before you start

### Hardware

| Item | Notes |
|------|--------|
| HP ProBook | Ubuntu Server 24.04 LTS, **Ethernet to MikroTik LAN** (not only Wi‑Fi) |
| MikroTik hAP² (or similar) | Default `192.168.88.1` after reset |
| Ethernet cable | ProBook ↔ MikroTik LAN port |
| UPS (recommended) | Power loss = portal + RADIUS down; Wi‑Fi may stay up but students cannot log in (see [§11](#11-power-off-restart-and-recovery)) |

### Software on your laptop (for setup)

- [Winbox](https://mikrotik.com/download) or SSH to MikroTik
- Git, or WinSCP to copy `TesNet/` to the ProBook
- USB stick with Ubuntu Server 24.04 ISO

### IP plan (default — change only if you know why)

| Device | IP |
|--------|-----|
| MikroTik (LAN / gateway) | `192.168.88.1/24` |
| ProBook (static) | `192.168.88.2/24` |
| DHCP pool (students) | `192.168.88.10` – `192.168.88.254` |

---

## 3. Secrets worksheet

Fill this in **once** and use the same values on ProBook and MikroTik.

| Name | Example | Used on |
|------|---------|---------|
| `STRONG_DB_PASSWORD` | (long random) | MariaDB user `tesnet`, FreeRADIUS SQL, Laravel `.env` |
| `CHANGE_ME_RADIUS_SECRET` | (long random) | `/etc/freeradius/3.0/clients.conf` + MikroTik `/radius` |
| `STRONG_ROUTER_PASSWORD` | (long random) | MikroTik `admin`, optional `MIKROTIK_API_PASSWORD` |
| `WIFI_PASSPHRASE` | (WPA2 key) | Student Wi‑Fi only |
| `ADMIN_PASSWORD` | (12+ chars) | Laravel admin seed / `.env` |

Store copies offline (password manager or sealed paper). **Never commit `.env` to git.**

---

## 4. Recommended order of work

| Step | Where | What |
|------|--------|------|
| 1 | ProBook | Install Ubuntu, static IP, firewall, Apache, MariaDB, PHP, Node |
| 2 | ProBook | MariaDB + FreeRADIUS SQL + `clients.conf` (MikroTik IP) |
| 3 | ProBook | Deploy TesNet, `.env`, `migrate`, `db:seed`, `build:offline` |
| 4 | MikroTik | Reset (optional), bridge, WAN, Wi‑Fi, DHCP |
| 5 | MikroTik | Hotspot + `login.html` + RADIUS + walled garden + anti-leak |
| 6 | Both | Tests: portal, RADIUS, leak test, Connect |

You can configure the router **before** Laravel is up, but **RADIUS auth only works after** `php artisan migrate` (creates `radcheck` / `radreply` / `radacct`).

---

## 5. Part A — HP ProBook (Ubuntu server)

### A.1 Install Ubuntu Server 24.04 LTS

1. Download [Ubuntu Server 24.04 LTS](https://ubuntu.com/download/server) (64-bit).
2. Create bootable USB (Rufus on Windows).
3. Boot ProBook from USB → install:
   - Hostname: `tesnet-server`
   - User: e.g. `tesnet`
   - Enable **OpenSSH server**
4. After install:

```bash
sudo apt update && sudo apt upgrade -y
sudo reboot
```

### A.2 Static IP (Netplan)

Find interface name:

```bash
ip link
```

Edit netplan (replace `enp0s31f6` with your interface):

```bash
sudo nano /etc/netplan/00-installer-config.yaml
```

```yaml
network:
  version: 2
  ethernets:
    enp0s31f6:
      dhcp4: no
      addresses:
        - 192.168.88.2/24
      routes:
        - to: default
          via: 192.168.88.1
      nameservers:
        addresses:
          - 192.168.88.1
          - 8.8.8.8
```

Apply:

```bash
sudo netplan apply
ip a
ping -c 3 192.168.88.1
```

> **Note:** ProBook gets internet via MikroTik WAN. Until the router has NAT (Part B), `ping 8.8.8.8` may fail — that is OK during early setup.

### A.3 Firewall (UFW)

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow from 192.168.88.0/24 to any port 80,443 proto tcp
sudo ufw allow from 192.168.88.1 to any port 1812,1813 proto udp
sudo ufw enable
sudo ufw status
```

### A.4 Install software stack

```bash
sudo apt install -y software-properties-common curl git unzip \
  apache2 mariadb-server mariadb-client \
  freeradius freeradius-mysql freeradius-utils \
  php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl libapache2-mod-php8.3

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

sudo a2enmod rewrite ssl headers
sudo systemctl enable --now apache2 mariadb
```

### A.5 MariaDB — database for Laravel + FreeRADIUS

```bash
sudo mysql_secure_installation
sudo mysql
```

```sql
CREATE DATABASE tesnet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'tesnet'@'localhost' IDENTIFIED BY 'STRONG_DB_PASSWORD';
GRANT ALL PRIVILEGES ON tesnet.* TO 'tesnet'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### A.6 FreeRADIUS → SQL (same `tesnet` database)

Edit SQL module:

```bash
sudo nano /etc/freeradius/3.0/mods-available/sql
```

Inside the `sql { ... }` block:

```conf
dialect = "mysql"
driver = "rlm_sql_mysql"
server = "localhost"
port = 3306
login = "tesnet"
password = "STRONG_DB_PASSWORD"
radius_db = "tesnet"
```

Enable SQL:

```bash
sudo ln -sf /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/sql
```

Edit default site:

```bash
sudo nano /etc/freeradius/3.0/sites-available/default
```

Ensure these sections include **`sql`** (uncomment if needed):

- `authorize { ... sql ... }`
- `accounting { ... sql ... }`
- `post-auth { ... sql ... }` (recommended)

**MikroTik NAS client** (secret must match router later):

```bash
sudo nano /etc/freeradius/3.0/clients.conf
```

Add at the end:

```conf
client mikrotik-hap {
    ipaddr          = 192.168.88.1
    secret          = "CHANGE_ME_RADIUS_SECRET"
    require_message_authenticator = no
    nas_type        = other
}
```

Test and start:

```bash
sudo freeradius -CX
sudo systemctl enable --now freeradius
sudo systemctl status freeradius
```

### A.7 Deploy TesNet (Laravel)

```bash
sudo mkdir -p /var/www
sudo chown $USER:$USER /var/www
cd /var/www
git clone https://github.com/reginaldtesla/MiniISP-Landing-page.git
cd MiniISP-Landing-page/TesNet
```

Git creates `/var/www/MiniISP-Landing-page/`; the Laravel app lives in the **`TesNet`** subfolder.

Or clone on your PC and upload with WinSCP to `/var/www/MiniISP-Landing-page/TesNet` (full `TesNet` directory contents).

```bash
cd /var/www/MiniISP-Landing-page/TesNet
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

#### `.env` — ProBook / LAN first (recommended to start)

Use **HTTP on the LAN IP** so hotspot login has no certificate warnings. Add HTTPS later when you have a real domain (see [§9](#9-certificates-and-https)).

```env
APP_NAME=TesNet
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.88.2
APP_FORCE_HTTPS=false

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tesnet
DB_USERNAME=tesnet
DB_PASSWORD=STRONG_DB_PASSWORD

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=false
SESSION_ENCRYPT=false
# Do not set SESSION_DOMAIN to the IP — leave unset/empty
TRUST_PROXIES=false
PORTAL_USE_OFFLINE_ASSETS=true
LOG_CHANNEL=daily

ADMIN_NAME="TesNet Admin"
ADMIN_PHONE=0550000001
ADMIN_PASSWORD=ChangeThisAdminPassword12
ADMIN_EMAIL=admin@tesnet.local
ADMIN_DEVICE_LIMIT=5
ADMIN_ALLOWED_IPS=192.168.88.0/24,127.0.0.1
ADMIN_IDLE_LOGOUT_MINUTES=5

MIKROTIK_LOGIN_URL=http://192.168.88.1/login
MIKROTIK_API_ENABLED=true
MIKROTIK_API_HOST=192.168.88.1
MIKROTIK_API_PORT=8728
MIKROTIK_API_USER=admin
MIKROTIK_API_PASSWORD=STRONG_ROUTER_PASSWORD
MIKROTIK_API_SSL=false

SUPPORT_PHONE=0551234567
SUPPORT_EMAIL=support@yourhostel.edu.gh
SUPPORT_HOURS="Monday–Friday, 8:00 AM – 5:00 PM"

TESNET_BACKUP_ENABLED=true
TESNET_BACKUP_RETAIN_DAYS=14

# Paystack — test keys OK; webhooks need public HTTPS (see §9–§10)
PAYSTACK_PUBLIC_KEY=pk_test_...
PAYSTACK_SECRET_KEY=sk_test_...
PAYSTACK_CUSTOMER_EMAIL_DOMAIN=billing.yourhostel.edu.gh
```

Run migrations and seed:

```bash
php artisan migrate --force
php artisan db:seed --force
npm install
npm run build:offline
```

Permissions:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
# full paths: /var/www/MiniISP-Landing-page/TesNet/storage and bootstrap/cache
```

Verify offline assets exist:

```bash
ls -la public/assets/portal/portal.css public/assets/portal/fonts/
```

### A.8 Apache virtual host

```bash
sudo nano /etc/apache2/sites-available/tesnet.conf
```

```apache
<VirtualHost *:80>
    ServerName 192.168.88.2
    DocumentRoot /var/www/MiniISP-Landing-page/TesNet/public

    <Directory /var/www/MiniISP-Landing-page/TesNet/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tesnet-error.log
    CustomLog ${APACHE_LOG_DIR}/tesnet-access.log combined
</VirtualHost>
```

Enable:

```bash
sudo a2ensite tesnet.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

From the ProBook:

```bash
curl -sI http://127.0.0.1/portal/login | head -5
```

### A.9 Cron (scheduler — backups + monitoring)

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * cd /var/www/MiniISP-Landing-page/TesNet && php artisan schedule:run >> /dev/null 2>&1
```

Test manually:

```bash
cd /var/www/MiniISP-Landing-page/TesNet
php artisan tesnet:monitor
php artisan tesnet:backup-database
```

### A.10 Services start automatically after reboot

Every service TesNet needs must be **enabled** (starts on boot). You already enabled Apache and MariaDB in §A.4; confirm all of them:

```bash
sudo systemctl enable apache2 mariadb freeradius
sudo systemctl is-enabled apache2 mariadb freeradius
```

Expected: `enabled` for each. After any reboot, wait ~1–2 minutes, then run the [post-restart checklist](#114-after-a-restart-or-power-return).

Cron for Laravel scheduler is separate — it is installed under the `www-data` crontab in §A.9 and survives reboot.

### A.11 Keep the ProBook awake (no sleep on AC power)

If the laptop sleeps or hibernates, **portal and RADIUS stop** even though the MikroTik may still broadcast Wi‑Fi.

**Ubuntu Server (recommended):** no desktop power GUI — ensure no suspend:

```bash
sudo systemctl mask sleep.target suspend.target hibernate.target hybrid-sleep.target
systemctl status sleep.target
```

**If you installed desktop Ubuntu by mistake**, also set **Settings → Power** → when plugged in: **Never** suspend.

**BIOS / firmware:**

- Disable “turn off when lid closed” (or set lid close = do nothing on AC).
- Enable **wake on LAN** only if you need remote support (optional).

**Hardware:**

- Leave the ProBook **plugged into mains**.
- Use a **UPS** so a short blackout does not corrupt MariaDB mid-write (see [§11](#11-power-off-restart-and-recovery)).

---

## 6. Part B — MikroTik hAP² (after reset)

Use **Winbox** or SSH. Default after reset: `192.168.88.1`, user `admin`, empty password — **set a password immediately**.

### B.1 Factory reset (optional)

**Winbox → System → Reset Configuration**

- For a clean slate: enable **No Default Configuration** (if offered), then reset.
- Reconnect to `192.168.88.1`.

### B.2 Identity and admin password

```routeros
/system identity set name=TesNet-HAP
/user set admin password="STRONG_ROUTER_PASSWORD"
```

### B.3 Bridge, LAN ports, and Wi‑Fi

Typical hAP²: **ether1 = WAN**, **ether2–ether5 + wlan1 = LAN bridge**.

```routeros
/interface bridge add name=bridge-lan
/interface bridge port add bridge=bridge-lan interface=ether2
/interface bridge port add bridge=bridge-lan interface=ether3
/interface bridge port add bridge=bridge-lan interface=ether4
/interface bridge port add bridge=bridge-lan interface=ether5
/interface bridge port add bridge=bridge-lan interface=wlan1

/ip address add address=192.168.88.1/24 interface=bridge-lan
```

**WAN** (DHCP from ISP — adjust if you use PPPoE/static):

```routeros
/ip dhcp-client add interface=ether1 disabled=no
/ip firewall nat add chain=srcnat out-interface=ether1 action=masquerade
```

**Student Wi‑Fi** (change SSID and passphrase):

```routeros
/interface wireless security-profiles add name=tesnet-wpa mode=dynamic-keys \
    authentication-types=wpa2-psk wpa2-pre-shared-key="WIFI_PASSPHRASE"

/interface wireless set wlan1 mode=ap-bridge ssid=TesNet-Student \
    security-profile=tesnet-wpa disabled=no
```

### B.4 DHCP for hotspot clients

```routeros
/ip pool add name=hotspot-pool ranges=192.168.88.10-192.168.88.254
/ip dhcp-server add name=dhcp-hotspot interface=bridge-lan address-pool=hotspot-pool disabled=no
/ip dhcp-server network add address=192.168.88.0/24 gateway=192.168.88.1 \
    dns-server=192.168.88.1
/ip dns set allow-remote-requests=yes servers=8.8.8.8,1.1.1.1
```

### B.5 Hotspot profile and server

**Important for TesNet:** use **`http-pap`** (plain username/password POST). Do **not** use `http-chap` alone — the portal submits a simple POST form, not CHAP challenge.

```routeros
/ip hotspot profile add name=tesnet-profile \
    html-directory=hotspot \
    login-by=http-pap,cookie \
    http-cookie-lifetime=1d \
    use-radius=yes \
    ssl-certificate=none

/ip hotspot add name=tesnet-hotspot interface=bridge-lan address-pool=hotspot-pool \
    profile=tesnet-profile disabled=no
```

### B.6 Custom `login.html` (auto-open TesNet portal)

**Use the redirect file in the repo:** `TesNet/mikrotik/login.html` (points to `/portal/login`).  
**Do not** upload the old voucher UI from `TesNet/login.html` — students will not reach the Laravel portal to buy again.

**Winbox → Files → folder `hotspot`** → upload or edit **`login.html`**:

**LAN portal (ProBook at `192.168.88.2`):**

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="refresh" content="0;url=http://192.168.88.2/portal/login">
  <title>TesNet</title>
  <script>location.replace("http://192.168.88.2/portal/login");</script>
</head>
<body>
  <p>Opening TesNet…</p>
  <p><a href="http://192.168.88.2/portal/login">Continue</a></p>
</body>
</html>
```

**Later, with HTTPS domain**, replace URLs with `https://portal.yourdomain.com/portal/login` and update walled garden (§B.7).

Phones usually show **Sign in to network** — one tap is normal; this file removes extra “click login” steps on the router page.

Also upload **`TesNet/mikrotik/status.html`** to the same **hotspot** folder (redirects to `/portal/dashboard`). The large **`status.html` at the repo root** (`C:\Apache24\htdocs\TesNet\status.html`) is a legacy MikroTik template for Winbox editing only — **do not** open it in a browser on the ProBook or point `.env` at a `C:\...` path. Students should never see a `file://` or `C:\` URL; after **Connect**, they should land on `http://192.168.88.2/portal/dashboard` (portal sends MikroTik a `dst=` URL when `MIKROTIK_POST_LOGIN_URL` is set).

The portal **dashboard** shows **GB left** from the database and RADIUS accounting (fast load). Full router sync runs on **Connect**, after **payment**, and via **cron** (`php artisan schedule:run`).

### B.7 RADIUS on MikroTik (must match ProBook)

```routeros
/radius add service=hotspot address=192.168.88.2 secret="CHANGE_ME_RADIUS_SECRET" timeout=3s
/ip hotspot profile set tesnet-profile use-radius=yes radius-accounting=yes
/ip hotspot set tesnet-hotspot radius=default
```

Accounting **must** be on so `radacct` fills for data usage on the dashboard.

### B.8 Walled garden (portal + Paystack only)

Unauthenticated users must **not** get free internet. Follow **in order**.

#### Step 1 — Clear unsafe rules

```routeros
/ip hotspot walled-garden ip print
/ip hotspot walled-garden ip remove [find]
/ip hotspot walled-garden print
/ip hotspot walled-garden remove [find]
```

Never use `0.0.0.0/0`, “all HTTPS”, or public DNS IPs in the garden.

#### Step 2 — Minimum allow list

**Portal on LAN:**

```routeros
/ip hotspot walled-garden ip add dst-address=192.168.88.2 action=accept comment="TesNet portal server"
```

**Paystack** (if students pay before hotspot login):

```routeros
/ip hotspot walled-garden ip add dst-host=paystack.com action=accept comment="Paystack"
/ip hotspot walled-garden ip add dst-host=*.paystack.com action=accept comment="Paystack subdomains"
/ip hotspot walled-garden ip add dst-host=checkout.paystack.com action=accept
/ip hotspot walled-garden ip add dst-host=standard.paystack.co action=accept
```

**If `APP_URL` uses a public hostname** (HTTPS / tunnel), also add:

```routeros
/ip hotspot walled-garden ip add dst-host=portal.yourdomain.com action=accept comment="TesNet portal"
```

Verify — only a handful of rows:

```routeros
/ip hotspot walled-garden ip print
```

| Never add | Why |
|-----------|-----|
| `dst-address=0.0.0.0/0` | Free internet before login |
| Allow all port 443 | Same leak |
| `8.8.8.8` / `1.1.1.1` in garden | DNS bypass tricks |

#### Step 3 — Disable trial and local hotspot users

```routeros
/ip hotspot profile set tesnet-profile \
    trial-uptime=0s \
    trial-uptime-limit=0s \
    trial-user-profile="" \
    open-status-page=http-login \
    use-radius=yes \
    radius-accounting=yes

/ip hotspot user print
/ip hotspot user remove [find name!="admin"]
```

Students use **phone** in `radcheck` for portal registration. **Data sessions** use per-purchase users **`tn-{purchase_id}`** created by Laravel (Model A) — see below.

#### Step 3b — Hotspot profiles for Model A (per-purchase users)

Run once on the router (adjust rate limits to match your packages):

```routeros
/ip hotspot user profile add name=tesnet-pkg rate-limit=60M/60M shared-users=1
/ip hotspot user profile add name=tesnet-custom rate-limit=60M/60M shared-users=1
```

Laravel (`TESNET_PER_PURCHASE_HOTSPOT=true`) creates users like `tn-42` with `profile=tesnet-pkg`, `limit-bytes-total` from the package, and `comment=055… · 1 Cedi`. Do **not** delete `tn-*` users while they are active; weekly cleanup is done by `tesnet:cleanup-hotspot-users` on the ProBook.

Ensure `.env` on the ProBook:

```env
TESNET_PER_PURCHASE_HOTSPOT=true
MIKROTIK_API_ENABLED=true
TESNET_HOTSPOT_PROFILE_PACKAGE=tesnet-pkg
TESNET_HOTSPOT_PROFILE_CUSTOM=tesnet-custom
```

#### Step 4 — Remove bypass bindings

```routeros
/ip hotspot ip-binding print
/ip hotspot ip-binding remove [find type=bypassed]
```

#### Step 5 — No firewall shortcut around hotspot

```routeros
/ip firewall filter print where chain=forward
```

Remove rules that let `192.168.88.0/24` to WAN without hotspot auth.

#### Step 6 — Lock router management to ProBook only

```routeros
/ip service set winbox address=192.168.88.2/32 disabled=no
/ip service set api address=192.168.88.2/32 disabled=no
/ip service set www address=192.168.88.2/32 disabled=no
/ip service set ssh address=192.168.88.2/32 disabled=no
/ip service disable telnet
/ip service disable ftp
```

Students must **not** reach Winbox/API on `192.168.88.1`.

### B.9 Optional — MikroTik API (disconnect from portal/admin)

Matches `.env` `MIKROTIK_API_*` from Part A. API is already restricted to `192.168.88.2` in Step B.6.

---

## 7. Part C — Connect ProBook and router

### Physical wiring

1. ISP → MikroTik **ether1** (WAN).
2. ProBook **Ethernet** → MikroTik **ether2** (LAN bridge).
3. ProBook on AC power; disable sleep on lid close.

### Match these three everywhere

| Setting | ProBook | MikroTik |
|---------|---------|----------|
| RADIUS secret | `clients.conf` | `/radius` |
| RADIUS server IP | FreeRADIUS listens | `address=192.168.88.2` |
| Portal URL | `APP_URL` + `login.html` | Walled garden `192.168.88.2` |
| Hotspot login | `MIKROTIK_LOGIN_URL` | `http://192.168.88.1/login` |

---

## 8. End-to-end verification

### 8.1 Services on ProBook

```bash
sudo systemctl status apache2 mariadb freeradius
curl -sI http://192.168.88.2/portal/login | head -3
```

### 8.2 RADIUS test (before phone test)

Create a student on `/portal/register` or **Admin → Students**, then:

```bash
sudo mysql tesnet -e "SELECT username, attribute, value FROM radcheck LIMIT 5;"
```

Test auth (replace phone and password):

```bash
radtest 233551234567 'student-password' 127.0.0.1 0 testing123
```

Expect **Access-Accept**. Watch logs:

```bash
sudo tail -f /var/log/freeradius/radius.log
```

On MikroTik during Connect:

```routeros
/radius monitor 0
/log print where topics~"hotspot"
```

### 8.3 Portal flow (phone on TesNet-Student Wi‑Fi)

| Step | Expected |
|------|----------|
| Join Wi‑Fi | Captive portal / “Sign in to network” |
| Portal opens | `http://192.168.88.2/portal/login` |
| Register + login | Dashboard loads |
| Buy package | Paystack or manual approve → active plan |
| **Before Connect** | `google.com` **blocked** |
| **Connect to Internet** | Auto POST to MikroTik login → internet works |
| Dashboard usage | `radacct` rows increase |

### 8.4 Leak test (required before go-live)

Phone on Wi‑Fi, **not** connected to hotspot session:

| Test | Must result |
|------|-------------|
| Portal login | Works |
| Paystack checkout (if used) | Works |
| `https://google.com` | **Blocked** |
| After payment, before Connect | Google still **blocked** |
| After Connect | Browsing OK within plan |

If Google works before Connect, repeat **§B.8 Steps 1–5**.

### 8.5 Data limits in RADIUS

After purchase:

```bash
sudo mysql tesnet -e "SELECT username, attribute, value FROM radreply;"
```

| Attribute | Purpose |
|-----------|---------|
| `Mikrotik-Rate-Limit` | Speed cap |
| `Mikrotik-Total-Limit` | Data cap (bytes) |

### 8.6 Admin URLs (LAN)

| Page | URL |
|------|-----|
| Student portal | `http://192.168.88.2/portal/login` |
| Admin hub | `http://192.168.88.2/admin/login` |
| System health | `http://192.168.88.2/admin/system-health` |

---

## 9. Certificates and HTTPS

TesNet touches **three different TLS contexts**. Mixing them up causes “network has security issues” on phones or Paystack webhooks failing.

| Layer | Who connects | Recommended on ProBook (start) | Certificate needed? |
|-------|----------------|--------------------------------|---------------------|
| **A. MikroTik hotspot login** | Phone → `http://192.168.88.1/login` | **HTTP only** | **No** — `ssl-certificate=none`, `login-by=http-pap` |
| **B. Laravel portal** | Phone → `http://192.168.88.2/portal/...` | **HTTP on LAN IP** | **No** for day-one hostel LAN |
| **C. Paystack webhooks** | Paystack servers → your app | **Public HTTPS** | **Yes** — trusted CA (Let’s Encrypt), not self-signed |

### 9.1 What to do first (no certificate warnings)

This matches Part A/B of this guide:

**MikroTik hotspot profile:**

```routeros
/ip hotspot profile set tesnet-profile \
    login-by=http-pap,cookie \
    ssl-certificate=none
```

**Laravel `.env`:**

```env
APP_URL=http://192.168.88.2
APP_FORCE_HTTPS=false
MIKROTIK_LOGIN_URL=http://192.168.88.1/login
```

**`login.html`** must use `http://` URLs, not `https://192.168.88.2`, unless you install a **trusted** cert for that IP (unusual and not recommended).

**Do not:**

- Upload a **self-signed** certificate to the MikroTik hotspot profile — Android/iOS often show *“This network has security issues”* and block captive portal login.
- Redirect students to `https://192.168.88.2` without a proper public CA cert on the ProBook.
- Enable `APP_FORCE_HTTPS=true` while students still use plain `http://` on the LAN.

### 9.2 When you add a real domain (production HTTPS)

Use this when you have a hostname (e.g. `portal.yourhostel.edu.gh`) that points to the ProBook (port forward) or later to a VPS.

**1. DNS** — `A` or `CNAME` record → your public IP (or tunnel).

**2. Let’s Encrypt on the ProBook (Apache):**

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d portal.yourhostel.edu.gh
```

Certbot installs a trusted certificate and renews it automatically (cron/timer).

**3. Laravel `.env`:**

```env
APP_URL=https://portal.yourhostel.edu.gh
APP_FORCE_HTTPS=true
```

**4. MikroTik** — update together:

- `login.html` → `https://portal.yourhostel.edu.gh/portal/login`
- Walled garden: `dst-host=portal.yourhostel.edu.gh` (keep Paystack hosts)
- Hotspot login after **Connect** can stay **`http://192.168.88.1/login`** (`MIKROTIK_LOGIN_URL`) — that is router-local HTTP and does not need a public cert.

**5. Paystack** — webhook URL must use the same HTTPS origin (§10).

Test renewal:

```bash
sudo certbot renew --dry-run
```

### 9.3 Split setup (LAN portal + HTTPS only for Paystack)

Common on a ProBook before cloud migration:

| Traffic | URL | Certificate |
|---------|-----|-------------|
| Students on Wi‑Fi | `http://192.168.88.2/portal/...` | None |
| Paystack webhooks | `https://your-tunnel.ngrok-free.app/portal/payments/webhook` | Tunnel provider’s cert |

If you use a tunnel **only** for webhooks, set `APP_URL` to the tunnel HTTPS URL **only if** students also reach the portal through that same URL. If students use LAN HTTP but `APP_URL` is the tunnel, payment callbacks and links can break.

**Safer approach:** use **manual payments** until `APP_URL` and the student-facing URL are the same trusted HTTPS origin, or until the whole portal moves to HTTPS on your domain.

### 9.4 Certificate checklist (before go-live)

- [ ] Hotspot profile: `ssl-certificate=none` (or valid cert you understand)
- [ ] `login.html` scheme (`http` vs `https`) matches `APP_URL`
- [ ] No self-signed HTTPS on captive portal for students
- [ ] `APP_FORCE_HTTPS` only when Apache serves trusted TLS
- [ ] Paystack webhook URL is **https://** with a **publicly trusted** cert
- [ ] `certbot renew --dry-run` passes (if using Let’s Encrypt)

### 9.5 Symptom → fix

| Symptom | Fix |
|---------|-----|
| Android “network has security issues” | Remove hotspot SSL cert; use HTTP PAP + HTTP portal |
| Browser warns “not secure” on portal | Expected on LAN HTTP; move to Let’s Encrypt + domain when ready |
| Paystack webhook never fires | `APP_URL` must be public HTTPS; check firewall/NAT |
| Mixed content / redirect loop | `APP_URL` and `APP_FORCE_HTTPS` must match how students open the site |

---

## 10. Paystack (when ready)

On the ProBook alone:

- **Manual payments** work without public HTTPS.
- **Paystack checkout** needs students to reach Paystack (walled garden §B.8).
- **Paystack webhooks** need a **public HTTPS** URL with a **trusted** certificate: `{APP_URL}/portal/payments/webhook` (see [§9](#9-certificates-and-https)).

Options:

1. **Manual pay only** for the first weeks (simplest on ProBook).
2. **Temporary tunnel** (ngrok / Cloudflare Tunnel) — HTTPS for Paystack; understand `APP_URL` must match how students reach the app (§9.3).
3. **Real domain + Let’s Encrypt** on the ProBook (§9.2) or on a VPS later.

Full steps: [`docs/PAYSTACK.md`](PAYSTACK.md).

When you enable HTTPS, update **all** of:

- Laravel `APP_URL` and `APP_FORCE_HTTPS=true`
- MikroTik `login.html` redirect URL
- Walled garden `dst-host` for your domain
- Paystack dashboard webhook URL

---

## 11. Power off, restart, and recovery

The **HP ProBook is the brain** of TesNet. The MikroTik provides Wi‑Fi and hotspot, but **authentication, portal, payments, and usage tracking** depend on the ProBook.

### 11.1 What happens when the ProBook is off or restarting

| Component | ProBook off / rebooting | MikroTik still on |
|-----------|-------------------------|-------------------|
| Wi‑Fi SSID | — | Usually **still broadcasts** |
| Captive portal redirect | **Fails** (no `192.168.88.2`) | Hotspot may show error or blank page |
| Portal login / buy data | **Down** | — |
| FreeRADIUS auth | **Down** | Connect / new logins **fail** |
| Students already online | Sessions may **drop** when RADIUS/accounting stops | May disconnect after timeout |
| Paystack webhooks | **Down** (if portal hosted on ProBook) | Payments may not auto-activate |
| Admin hub | **Down** | — |

**MikroTik alone cannot run TesNet** — do not expect the network to work for students without the ProBook running.

### 11.2 Planned maintenance (tell students first)

1. **Admin → Settings** — enable **Outage banner**, optional **Block purchases** / **Block connect**.
2. Announce maintenance window (WhatsApp, notice board).
3. Reboot the ProBook (or apply updates).
4. Run [§11.4 post-restart checklist](#114-after-a-restart-or-power-return).
5. Disable outage banner when healthy.

See [`docs/DAILY_OPERATIONS.md`](DAILY_OPERATIONS.md).

### 11.3 Unplanned power loss

**Without UPS:** sudden power off can leave MariaDB recovering on next boot (usually automatic) or, rarely, require admin attention if disk was writing.

**With UPS:** short outages ride through; shut down cleanly if battery is low:

```bash
sudo shutdown -h now
```

After power returns, the ProBook may need the **power button** pressed if it does not auto-power-on (change in BIOS: “AC recovery = on” if available).

### 11.4 After a restart or power return

Wait **1–2 minutes** after login/boot, then on the ProBook:

```bash
# 1. Services running?
sudo systemctl status apache2 mariadb freeradius

# 2. If any failed, try start + check config
sudo systemctl start mariadb freeradius apache2
sudo freeradius -CX

# 3. Portal responds?
curl -sI http://127.0.0.1/portal/login | head -3

# 4. RADIUS test (use a real test user)
radtest 233551234567 'password' 127.0.0.1 0 testing123

# 5. App health
cd /var/www/MiniISP-Landing-page/TesNet && php artisan tesnet:monitor
```

**From Winbox (MikroTik):**

```routeros
/ping 192.168.88.2 count=3
/radius monitor 0
```

**From a student phone:** join Wi‑Fi → portal opens → test login (staff account).

If MariaDB will not start after unclean shutdown:

```bash
sudo journalctl -u mariadb -n 50
# Only if you know what you are doing and have backups:
# sudo mariadb-check --auto-repair
```

### 11.5 Keep the server reliable (summary)

| Practice | Why |
|----------|-----|
| `systemctl enable` apache2, mariadb, freeradius (§A.10) | Auto-start after reboot |
| Mains power + **UPS** | Avoid corrupt DB and long outages |
| Mask sleep/suspend (§A.11) | Laptop must not sleep while “server” |
| Lid closed = do nothing (BIOS) | Same |
| Weekly DB backup off-machine | Restore if disk fails |
| **Admin → Health** after reboot | Quick all-green check |
| BIOS **AC power recovery = On** (optional) | ProBook turns on when power returns |

### 11.6 What still works if only the ProBook is down

- MikroTik **WAN** (if configured) — upstream internet at the router may still work for wired admin, not for unpaid hotspot students.
- You can still Winbox into the router from a machine on the LAN **if** you temporarily allow your IP (normally only `192.168.88.2` — use a laptop set to `.2` only for emergency, or connect keyboard/monitor to ProBook).

Students need the ProBook **up** for normal TesNet operation.

---

## 12. Production checklist

### HP ProBook

- [ ] Static IP `192.168.88.2`, gateway `192.168.88.1`
- [ ] MariaDB `tesnet`, user `tesnet`, strong password
- [ ] FreeRADIUS running, SQL enabled, MikroTik in `clients.conf`
- [ ] `php artisan migrate --force` and `db:seed --force`
- [ ] `npm run build:offline`; `PORTAL_USE_OFFLINE_ASSETS=true`
- [ ] `SESSION_ENCRYPT=true`, `APP_DEBUG=false`
- [ ] `ADMIN_ALLOWED_IPS` set; admin password changed from seed
- [ ] Cron `schedule:run` for www-data
- [ ] `tesnet:backup-database` tested; backups copied off-server weekly
- [ ] `systemctl enable` apache2, mariadb, freeradius (§A.10)
- [ ] Sleep/suspend masked; ProBook on AC; UPS installed (§A.11, §11)
- [ ] Post-reboot checklist tested once (§11.4)
- [ ] Certificates: LAN HTTP **or** trusted HTTPS on domain (§9) — no self-signed captive portal

### MikroTik

- [ ] WAN + NAT on ether1
- [ ] Hotspot on bridge, `login-by=http-pap`, `ssl-certificate=none`
- [ ] `login.html` → Laravel `/portal/login`
- [ ] RADIUS → `192.168.88.2`, secret matches
- [ ] Walled garden: portal + Paystack only (§B.8)
- [ ] No trial, no bypass bindings, no free-internet firewall rules
- [ ] Winbox/API restricted to `192.168.88.2`

### TesNet app

- [ ] `MIKROTIK_LOGIN_URL=http://192.168.88.1/login`
- [ ] Packages configured in **Admin → Packages**
- [ ] Support phone/email in `.env`
- [ ] Leak test passed (§8.4)

See also [`docs/PRODUCTION_CHECKLIST.md`](PRODUCTION_CHECKLIST.md).

---

## 13. Troubleshooting

| Symptom | What to check |
|---------|----------------|
| “Challenge response” / CHAP error | Profile must use `login-by=http-pap`, not `http-chap` only |
| Portal not loading on phone | Walled garden `192.168.88.2`; Apache up; phone on correct Wi‑Fi |
| Free internet before Connect | Walled garden too broad; trial; `ip-binding bypassed`; firewall forward leak |
| RADIUS reject | Secret match; username = `233…` in `radcheck`; `radius.log` |
| Login OK, no internet | NAT on ether1; hotspot active; WAN up |
| Paystack paid, no data | Webhook URL + HTTPS; `transactions`; manual approve as fallback |
| Certificate warning on login | Use HTTP for hotspot login; empty `ssl-certificate` on profile |
| FreeRADIUS won’t start | `sudo freeradius -CX`; SQL password; `mods-enabled/sql` |
| `radius.log`: **Unknown column 'framedipv6address'** | `php artisan migrate` on ProBook (adds IPv6 columns to `radacct`). Then `sudo systemctl restart freeradius`. Errors should stop; `radacct` rows will update. |
| Everything dead after power cut | UPS next time; §11.4; `systemctl start mariadb freeradius apache2` |
| Works until ProBook reboots | §A.10 `systemctl enable`; §A.11 disable sleep |
| **419 Page Expired** after login | Run `php artisan tesnet:session-doctor` on the ProBook. Almost always: **`php artisan config:clear`** after `.env` changes (cached config keeps `SESSION_SECURE_COOKIE=true`). `.env`: `APP_URL=http://192.168.88.2`, `SESSION_SECURE_COOKIE=false`, `SESSION_ENCRYPT=false`, **no** `SESSION_DOMAIN` line (never `SESSION_DOMAIN=192.168.88.2`). `php artisan migrate` (sessions table). §A.8 storage permissions. Phone: open **only** `http://192.168.88.2/portal/login`, hard-refresh, register/login again. |

**Useful commands:**

```bash
# ProBook
sudo systemctl status freeradius apache2 mariadb
sudo journalctl -u freeradius -n 50
sudo tail -f /var/www/MiniISP-Landing-page/TesNet/storage/logs/laravel.log
php artisan tesnet:monitor

# MikroTik
/log print where topics~"hotspot"
/radius monitor 0
/ip hotspot active print
```

---

## 14. Moving to the cloud later

When the ProBook setup is stable:

1. Export DB: `php artisan tesnet:backup-database` or `mysqldump tesnet`.
2. Deploy same code on a VPS; import DB; new `.env` with `APP_URL=https://…`.
3. Point MikroTik RADIUS and walled garden to the cloud (or VPN from router to cloud).
4. Update `login.html` and Paystack webhook.

**One hostel = one deployment + one database.** A second hostel = duplicate stack (see prior architecture discussion), not one shared database.

---

## Quick reference

| Item | Value |
|------|--------|
| ProBook | `192.168.88.2` |
| MikroTik | `192.168.88.1` |
| Student Wi‑Fi | `TesNet-Student` (your SSID) |
| Portal | `{APP_URL}/portal/login` |
| MikroTik login (after Connect) | `http://192.168.88.1/login` |
| RADIUS | UDP 1812/1813 → `192.168.88.2` |

---

*TesNet — full ProBook + MikroTik setup guide. Update this doc when your network IPs or domain change.*
