# TesNet — Mini ISP Landing Page & Hotspot Portal

Affordable, student-friendly internet for homes and small hostels in **Ayeduase and the KNUST area** (Kumasi, Ghana). This repository contains the public marketing site and MikroTik hotspot portal pages for [TesNet](https://github.com/reginaldtesla/MiniISP-Landing-page).

Built as a final-year student initiative to bridge the digital divide with transparent pricing, local support, and lightweight infrastructure.

## What's in this repo

| Area | Description |
|------|-------------|
| **Marketing site** | Single-page landing (`index.html`) — synced from `hotspot-pay/index.html` |
| **Connect flow** | `connect.html` at repo root — join Wi‑Fi steps + embedded login preview |
| **Hotspot portal** | Captive-portal HTML for MikroTik under `Mikrotik pages/` |
| **MikroTik scripts** | RouterOS scripts to bulk-generate voucher users |
| **Installation guide** | Production setup notes for ProBook + MikroTik stack |

There is **no build step** — everything is static HTML, CSS, and JavaScript. Serve the marketing site from Apache (or any static host). Upload only the `Mikrotik pages/` portal files to the router.

**Canonical marketing URL:** keep `index.html` in sync with [`hotspot-pay/index.html`](../hotspot-pay/index.html) (or deploy one copy to your public domain).

## Project structure

```text
.
├── index.html                    # Marketing landing (canonical copy of hotspot-pay/index.html)
├── connect.html                  # Get Started — Wi‑Fi steps + iframe preview
├── assets/
│   └── tesnet-logo.png           # Logo for marketing pages
├── Mikrotik pages/               # Upload these to the router (flat, no subfolder)
│   ├── login.html                # Live hotspot login ($(…) MikroTik variables)
│   ├── login-preview.html        # Static preview for connect.html iframe
│   ├── status.html
│   ├── logout.html
│   ├── portal.css
│   ├── portal-theme.js
│   ├── portal-login.js
│   ├── portal-packages.js
│   ├── api.json
│   ├── tesnet-logo.png           # Logo on login (canonical filename)
│   └── Logo T.png                # Legacy alias (same file; optional on router)
├── mikrotik script for rsv-cvs/
│   ├── tesnet-gen-vouchers-seq.rsc
│   └── tesnet-gen-vouchers-random.rsc
├── installation                  # Production deployment guide
└── TesNet.png                    # Older brand asset (marketing archive)
```

## Pages

### Public website (repo root)

| File | Role |
|------|------|
| `index.html` | Hero, about, services, pricing, contact, policies |
| `connect.html` | Step-by-step Wi‑Fi onboarding; embeds `Mikrotik pages/login-preview.html` |
| `assets/tesnet-logo.png` | Logo used by the marketing page |

Nav links: **Get started** → `connect.html`. Payment links point to **`https://pay.tesnet.xyz`**.

### MikroTik hotspot (`Mikrotik pages/` → upload flat to router)

These files use [MikroTik hotspot variables](https://wiki.mikrotik.com/wiki/Manual:Customizing_Hotspot_login_page) such as `$(link-login-only)`, `$(username)`, and `$(error)`:

| File | Upload to router? | Role |
|------|-------------------|------|
| `login.html` | Yes | Voucher login + package purchase (Paystack) |
| `status.html` | Yes | Session info, data usage |
| `logout.html` | Yes | Logged-out confirmation |
| `portal.css` | Yes | Shared portal styles |
| `portal-theme.js` | Yes | Dark/light theme toggle |
| `portal-login.js` | Yes | Login / Buy view switcher |
| `portal-packages.js` | Yes | Data / time pricing tabs |
| `api.json` | Yes | Captive portal discovery metadata |
| `tesnet-logo.png` | Yes | Login page logo |
| `login-preview.html` | **No** | Local / marketing preview only |
| `connect.html` | **No** | Lives at repo root only |

Package purchases redirect to **`https://pay.tesnet.xyz/buy.php?pkg=…`**. After payment, users enter their voucher on `login.html`.

## Data packages (GHS)

| Plan | Data / access | Validity | Price |
|------|---------------|----------|-------|
| Quick Surf | 1 GB | Unlimited | 3.50 |
| Student Choice | 3 GB | Unlimited | 9.00 |
| Big Bundle | 7 GB | Unlimited | 18.00 |
| Heavy User | 15 GB | Unlimited | 35.00 |
| Hostel Legend | 45 GB | Unlimited | 95.00 |
| 2-Hour | Unlimited | 2 hours | 4.00 |
| 4-Hour | Unlimited | 4 hours | 8.00 |
| 8-Hour | Unlimited | 8 hours | 16.00 |
| Full Day | Unlimited | 24 hours | 25.00 |
| 2-Week | Unlimited | 14 days | 99.00 |
| Month | Unlimited | 30 days | 199.00 |

## Local development

1. Clone the repository:

   ```bash
   git clone https://github.com/reginaldtesla/MiniISP-Landing-page.git
   cd MiniISP-Landing-page
   ```

2. Serve the folder with any static file server. Examples:

   **Apache** (e.g. `htdocs/MiniISP-Landing-page`):

   ```text
   http://localhost/MiniISP-Landing-page/
   http://localhost/MiniISP-Landing-page/connect.html
   ```

   **PHP built-in server:**

   ```bash
   php -S localhost:8080
   ```

3. Open `index.html` or `connect.html` in a browser.

   > **Note:** `login.html` MikroTik placeholders only resolve on the router. Use `login-preview.html` (via `connect.html`) for local UI testing.

4. **Pay server (separate repo):** run [`hotspot-pay`](../hotspot-pay/) for checkout. Local preview uses `http://localhost/buy.php?pkg=…` when the pay vhost docroot is `hotspot-pay/public/`.

## Deploy checklist

### Marketing site (Apache / static host)

```text
[ ] index.html + assets/tesnet-logo.png at site root
[ ] connect.html at site root (paths use Mikrotik%20pages/…)
[ ] Mikrotik pages/login-preview.html present (iframe target; not uploaded to router)
[ ] Pricing matches hotspot-pay/config.php
[ ] Payment links use https://pay.tesnet.xyz
```

### MikroTik router (flat upload from `Mikrotik pages/`)

```text
[ ] login.html, status.html, logout.html
[ ] portal.css, portal-theme.js, portal-login.js, portal-packages.js
[ ] api.json, tesnet-logo.png
[ ] Hotspot profile HTML directory points at uploaded files
[ ] Walled garden: pay.tesnet.xyz, *.paystack.com, js.paystack.co, api.paystack.co
[ ] Voucher users + profiles exist; codes imported in hotspot-pay admin
[ ] Test: captive portal → buy → success code → login
```

Do **not** upload `login-preview.html`, `connect.html`, or repo-root `index.html` to the router.

## MikroTik voucher generation

Scripts under `mikrotik script for rsv-cvs/`:

- **`tesnet-gen-vouchers-seq.rsc`** — Sequential codes (`TNQS001`–`TNQS100`, …)
- **`tesnet-gen-vouchers-random.rsc`** — Random-format codes

Import on the router:

```text
/import file-name=tesnet-gen-vouchers-seq.rsc
```

## Production stack

The Paystack billing service runs in **[hotspot-pay](../hotspot-pay/)** on the ProBook (PHP + SQLite). See **`installation`** in this repo and **`docs/HOTSPOT.md`** in hotspot-pay for network layout.

```text
[ Internet ] ──► [ MikroTik hAP² 192.168.88.1 ] ──► Wi‑Fi clients
                        │
                        └──► [ ProBook — pay.tesnet.xyz (hotspot-pay) ]
```

## Contact

- **Location:** Ayeduase, Kumasi, Ghana (near KNUST campus)
- **Phone:** [020 050 4248](tel:+233200504248)
- **Email:** [tesnet5532@gmail.com](mailto:tesnet5532@gmail.com)

## License

© 2026 TesNet. Student initiative project — all rights reserved.
