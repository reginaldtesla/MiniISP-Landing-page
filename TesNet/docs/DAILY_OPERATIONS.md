# TesNet — Daily operations guide

Short reference for running the hostel hotspot day to day.

## Morning checks (5 minutes)

1. Open **Admin → Health** (`/admin/system-health`) — fix any **fail** items.
2. Open **Admin → Analytics** — confirm revenue and live sessions look normal.
3. Open **Admin → Manual Pay** — approve pending MoMo/Airtime requests.
4. Optional CLI on the server:
   ```bash
   php artisan tesnet:monitor
   ```

## Outages / maintenance

1. **Admin → Settings** (`/admin/portal-settings`)
2. Enable **Outage banner** and write a clear message.
3. Optionally enable **Block purchases** and/or **Block connect**.
4. When fixed, disable the banner and restrictions.

Students still see the portal on the walled garden; they just cannot buy or connect until you allow it.

## Approving manual payments (Paystack down)

1. Student submits **Buy Data → Manual payment** with amount, reference, and optional screenshot.
2. **Admin → Manual Pay → Pending**
3. Verify MoMo/Airtime on your phone.
4. Click **Approve** (optional admin note). This creates a transaction and activates the package + RADIUS limits like Paystack.
5. If invalid, **Reject** with a reason (required).

Audit trail: submitted time, reviewer, transaction ID, admin note, proof file.

## Creating / editing packages

1. **Admin → Packages**
2. Set data, price, speed, validity type (`days`, `until_finished`, `unlimited`).
3. Special offers: set start/end dates and promo label.

Packages update the store immediately. RADIUS limits apply **after payment** (Paystack or manual approval).

## Student cannot connect

| Symptom | Check |
|--------|--------|
| No active plan | **Admin → Users** / student dashboard — purchase or approve manual payment |
| Suspended | **Admin → Users → Edit** — uncheck **Suspend account** |
| Too many devices | **Admin → Sessions** or student **Devices** — disconnect old sessions |
| Wrong password | Student logs out and in again (refreshes hotspot password in session) |
| RADIUS reject | `SELECT * FROM radcheck WHERE username='233…';` — password + Simultaneous-Use |
| No accounting | **Admin → Health** — RADIUS accounting stale |

## Compensation (refund / free data)

There is no automatic refund to MoMo in the app. Use one of:

1. **Manual payment approve** after the student submits a request (note: “Outage compensation”).
2. **Admin edits user** — increase `device_limit` or reset password if needed.
3. For free data without payment: student submits manual payment with reference “COMP-YYYYMMDD”; admin approves after verifying.

Document large compensations in a spreadsheet for your records.

## Troubleshooting live sessions

1. **Admin → Sessions** — active `radacct` rows (phone, MAC, data, IP).
2. **Force disconnect** — marks session stopped; enables MikroTik kick if `MIKROTIK_API_ENABLED=true`.
3. Student can also disconnect from **Portal → Devices**.

## Backups

Automated (when cron is configured):

```bash
php artisan tesnet:backup-database
```

Files: `storage/backups/tesnet_*.sql.gz` (retention: `TESNET_BACKUP_RETAIN_DAYS`, default 14).

Copy backups off the server weekly (USB or cloud).

## Logs

- Laravel: `storage/logs/laravel.log` (use `LOG_CHANNEL=daily` in production)
- Backup log: `storage/logs/backup.log`
- Monitor log: `storage/logs/monitor.log`

## Security reminders

- Change default admin password from seed values.
- Set `ADMIN_ALLOWED_IPS` so `/admin` is only reachable on your LAN.
- Do not expose Winbox or MySQL to the hotspot Wi‑Fi.

See also: [PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md), [installation](../../installation), [PAYSTACK.md](PAYSTACK.md), [HOTSPOT.md](HOTSPOT.md).
