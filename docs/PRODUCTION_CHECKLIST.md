# TesNet — Production checklist

Use with **Admin → Health** in the portal and the main [installation](../installation) guide.

## Server (HP ProBook / Ubuntu)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, correct `APP_URL` (HTTPS in production)
- [ ] Apache/Nginx + PHP 8.2+ pointing to `TesNet/public`
- [ ] `php artisan migrate --force` applied
- [ ] `npm run build:offline` — `public/assets/portal/` present
- [ ] `PORTAL_USE_OFFLINE_ASSETS=true`
- [ ] Cron for Laravel scheduler:
  ```cron
  * * * * * cd /var/www/tesnet/TesNet && php artisan schedule:run >> /dev/null 2>&1
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
