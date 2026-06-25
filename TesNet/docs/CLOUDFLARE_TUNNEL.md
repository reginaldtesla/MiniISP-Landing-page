# TesNet — Cloudflare Tunnel (replace ngrok)

Use **Cloudflare Tunnel** (`cloudflared`) on the HP ProBook when you need a **stable public HTTPS URL** for Paystack webhooks and (optionally) the student portal. It replaces **ngrok**, which changes URLs on the free tier and is meant for short tests only.

**You need:** a domain on Cloudflare (e.g. `yourhostel.edu.gh` or a subdomain you control).

---

## What changes when you move off ngrok

| Item | ngrok (old) | Cloudflare Tunnel (new) |
|------|-------------|-------------------------|
| URL | Changes unless paid static domain | Fixed: `https://portal.yourdomain.com` |
| Certificate | ngrok-managed | Cloudflare edge (trusted) |
| Port forward on MikroTik | Not required | Not required |
| Paystack webhook | `{ngrok-url}/portal/payments/webhook` | `https://portal.yourdomain.com/portal/payments/webhook` |
| Cost | Free tier limits | Free on Cloudflare |

---

## Choose a setup mode

### Mode A — Recommended: portal + Paystack on Cloudflare HTTPS

Students open **`https://portal.yourdomain.com`** (from Wi‑Fi or anywhere). One `APP_URL` for portal, Paystack callback, and webhooks.

**Pros:** No split-URL bugs; Paystack “contact admin after charge” issues from mismatched `APP_URL` go away.  
**Cons:** Update MikroTik `login.html` and walled garden for your domain.

### Mode B — LAN portal + Cloudflare only for Paystack

Students stay on **`http://192.168.88.2`**, tunnel used only so Paystack can POST webhooks.

**Not recommended:** Laravel builds payment callback URLs from `APP_URL`. If `APP_URL` is the tunnel but students use LAN HTTP, redirects after Paystack checkout can break. Prefer **Mode A** or **manual payments** until the whole portal shares one URL.

---

## 1. Cloudflare dashboard

1. Add your domain to Cloudflare (DNS managed by Cloudflare).
2. You will create a **Tunnel** — no `A` record to your home IP is required.

---

## 2. Install `cloudflared` on the ProBook

```bash
curl -L https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb -o cloudflared.deb
sudo dpkg -i cloudflared.deb
cloudflared --version
```

---

## 3. Create the tunnel

```bash
cloudflared tunnel login
cloudflared tunnel create tesnet
```

Save the **tunnel ID** (UUID) and credentials path (e.g. `/root/.cloudflared/<TUNNEL_ID>.json`).

---

## 4. Tunnel config

```bash
sudo mkdir -p /etc/cloudflared
sudo nano /etc/cloudflared/config.yml
```

**TesNet only** — replace `TUNNEL_ID` and `portal.yourdomain.com`:

```yaml
tunnel: TUNNEL_ID
credentials-file: /root/.cloudflared/TUNNEL_ID.json

ingress:
  - hostname: portal.yourdomain.com
    service: http://127.0.0.1:80
    originRequest:
      httpHostHeader: portal.yourdomain.com
  - service: http_status:404
```

**Same ProBook, TesNet + AkwaabaFit** — one tunnel, two hostnames (Apache routes by `ServerName`):

```yaml
tunnel: TUNNEL_ID
credentials-file: /root/.cloudflared/TUNNEL_ID.json

ingress:
  - hostname: portal.yourdomain.com
    service: http://127.0.0.1:80
    originRequest:
      httpHostHeader: portal.yourdomain.com
  - hostname: api.yourdomain.com
    service: http://127.0.0.1:80
    originRequest:
      httpHostHeader: api.yourdomain.com
  - service: http_status:404
```

Create DNS routes (CNAME to the tunnel):

```bash
cloudflared tunnel route dns tesnet portal.yourdomain.com
# If AkwaabaFit on same tunnel:
cloudflared tunnel route dns tesnet api.yourdomain.com
```

---

## 5. Run as a system service

```bash
sudo cloudflared service install
sudo systemctl enable --now cloudflared
sudo systemctl status cloudflared
```

Logs:

```bash
sudo journalctl -u cloudflared -n 50 --no-pager
```

---

## 6. Apache virtual host (TesNet)

Edit `/etc/apache2/sites-available/tesnet.conf` so Apache accepts the Cloudflare hostname:

```apache
<VirtualHost *:80>
    ServerName portal.yourdomain.com
    ServerAlias 192.168.88.2
    DocumentRoot /var/www/TesNet/public

    <Directory /var/www/TesNet/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/tesnet-error.log
    CustomLog ${APACHE_LOG_DIR}/tesnet-access.log combined
</VirtualHost>
```

```bash
sudo apache2ctl configtest
sudo systemctl reload apache2
```

Test locally:

```bash
curl -H "Host: portal.yourdomain.com" http://127.0.0.1/portal/login -I
```

Test public HTTPS:

```bash
curl -I https://portal.yourdomain.com/portal/login
```

---

## 7. Laravel `.env` (TesNet)

```env
APP_URL=https://portal.yourdomain.com
APP_FORCE_HTTPS=true
TRUST_PROXIES=true
SESSION_SECURE_COOKIE=true

# After Connect, MikroTik still uses router-local HTTP for hotspot login:
MIKROTIK_LOGIN_URL=http://192.168.88.1/login
# Dashboard after hotspot login — use the same URL students use for the portal:
MIKROTIK_POST_LOGIN_URL=https://portal.yourdomain.com/portal/dashboard
```

Apply:

```bash
cd /var/www/TesNet
php artisan config:clear
php artisan config:cache
```

---

## 8. MikroTik walled garden

Add your portal hostname (keep Paystack hosts). Example:

```routeros
/ip hotspot walled-garden ip add dst-host=portal.yourdomain.com action=accept comment="TesNet portal HTTPS"
/ip hotspot walled-garden ip add dst-host=paystack.com action=accept comment="Paystack"
/ip hotspot walled-garden ip add dst-host=*.paystack.com action=accept comment="Paystack subdomains"
/ip hotspot walled-garden ip add dst-host=checkout.paystack.com action=accept
/ip hotspot walled-garden ip add dst-host=standard.paystack.co action=accept
```

If you fully move to Mode A, you can **remove** the LAN `dst-address=192.168.88.2` rule (optional — keeping it does not hurt).

Update **`login.html`** on the router so the captive portal opens your HTTPS URL:

```html
<!-- Redirect to Laravel login -->
<meta http-equiv="refresh" content="0; url=https://portal.yourdomain.com/portal/login">
```

Upload via Winbox → Files → hotspot folder, or fetch from the ProBook if you host a copy there.

---

## 9. Paystack dashboard

1. **Webhook URL** (Settings → API & Webhooks):

   ```text
   https://portal.yourdomain.com/portal/payments/webhook
   ```

2. Enable **`charge.success`**.
3. Use **live** keys in `.env` when ready (`PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`).
4. Remove the old **ngrok** webhook URL from Paystack.

See also [`PAYSTACK.md`](PAYSTACK.md).

---

## 10. Stop ngrok

On the ProBook (or wherever ngrok was running):

```bash
# If ngrok was a manual process — stop it (Ctrl+C) and remove any startup script.

# If installed as a service:
sudo systemctl stop ngrok 2>/dev/null
sudo systemctl disable ngrok 2>/dev/null
```

Do **not** leave ngrok and Cloudflare both registered as Paystack webhooks — use one HTTPS origin only.

---

## 11. Migration checklist (ngrok → Cloudflare)

- [ ] Domain on Cloudflare; tunnel created and `cloudflared` service **active**
- [ ] `curl -I https://portal.yourdomain.com/portal/login` returns **200** or **302**
- [ ] Apache `ServerName` matches tunnel `httpHostHeader`
- [ ] TesNet `.env`: `APP_URL`, `APP_FORCE_HTTPS=true`, `TRUST_PROXIES=true`
- [ ] `php artisan config:cache`
- [ ] MikroTik `login.html` → HTTPS portal URL
- [ ] Walled garden includes `portal.yourdomain.com` + Paystack hosts
- [ ] Paystack webhook URL updated; ngrok URL removed
- [ ] Test payment end-to-end (checkout → dashboard success message)
- [ ] Check `storage/logs/laravel.log` — no `Paystack verify failed` / webhook 500

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| Cloudflare **502** | Apache down; wrong `httpHostHeader`; check `journalctl -u cloudflared` |
| **419** / session lost | `TRUST_PROXIES=true`; do not set `SESSION_DOMAIN` to an IP |
| Paystack paid, plan not active | Webhook URL must match `APP_URL`; check logs; retry callback (see manual pay docs) |
| Portal works on LAN IP but not domain | Apache `ServerName`; tunnel DNS route; walled garden hostname |
| Mixed content | `APP_FORCE_HTTPS=true`; all links should use `https://` |

**Useful commands:**

```bash
sudo systemctl status cloudflared apache2
sudo journalctl -u cloudflared -f
sudo tail -f /var/www/TesNet/storage/logs/laravel.log
```

---

## Related docs

- [`PROBOOK_MIKROTIK_FULL_SETUP.md`](PROBOOK_MIKROTIK_FULL_SETUP.md) — §9 HTTPS, walled garden, certificates  
- [`PAYSTACK.md`](PAYSTACK.md) — keys and webhook paths  
- [`PRODUCTION_CHECKLIST.md`](PRODUCTION_CHECKLIST.md) — go-live checklist  
