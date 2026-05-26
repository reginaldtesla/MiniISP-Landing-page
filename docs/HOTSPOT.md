# MikroTik hotspot → Laravel portal

TesNet no longer relies on PHPNuxBill or voucher HTML for day-to-day login. The **authoritative** login and payment UI is the Laravel app on the billing server.

## Target flow

1. Student associates to Wi‑Fi and gets a hotspot login page from MikroTik.
2. MikroTik redirects (or links) to **`https://<your-server>/portal/login`** (walled garden must allow this host).
3. Student signs in with **phone + password** (same credentials synced to FreeRADIUS).
4. After purchase, **Connect Wi‑Fi** uses `MIKROTIK_LOGIN_URL` with username/password query params (see `AuthController::connectToWifi`).

## Walled garden

Add your Laravel host to MikroTik **IP → Hotspot → Walled Garden** (and DNS if you use a hostname), for example:

- Billing server IP: `192.168.88.2`
- Or FQDN used in `APP_URL`

Students must reach `/portal/login`, `/portal/register`, and Paystack-related paths **before** they have an active session.

## Retiring `login.html`

Legacy files in the repo (`login.html`, `TesNet/login.html`, `flash/hotspot/login.html`) redirected users to PHPNuxBill. For production:

1. **Do not** upload a custom `login.html` that points to the old billing UI unless you still need a transitional redirect.
2. Prefer MikroTik **Hotspot Server Profile** → **HTML** settings, or a minimal `login.html` that only redirects:

   ```html
   <meta http-equiv="refresh" content="0;url=https://YOUR_DOMAIN/portal/login?$(link-login-only)">
   ```

   Adjust for your captive portal variables (`$(link-login-only)`, `$(link-orig)`, etc.) per RouterOS docs.

3. Set **`MIKROTIK_LOGIN_URL`** in `.env` to the router’s HTTP login endpoint (e.g. `http://192.168.88.1/login`) used after the student is authorized in RADIUS.

## FreeRADIUS

- Laravel writes `radcheck` (Cleartext-Password, Simultaneous-Use) and `radreply` (rate limits, `Mikrotik-Total-Limit`) via `RadiusSyncService`.
- MikroTik hotspot uses RADIUS for authentication; usernames are **normalized phone numbers** (`233551234567`).

## Announcements

Admins post global notices under **Admin → Notifications**. Students see the latest active notice as a **modal** on the dashboard (“Got it” stores dismissal in `localStorage`).

## Checklist

- [ ] Laravel reachable from hotspot clients (walled garden)
- [ ] `APP_URL` matches the URL students use (HTTPS in production)
- [ ] FreeRADIUS + MikroTik RADIUS client configured
- [ ] Legacy PHPNuxBill `login.html` removed or replaced with Laravel redirect
