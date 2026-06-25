# TesNet — Mini ISP Landing Page & Hotspot Portal

Affordable, student-friendly internet for homes and small hostels in **Ayeduase and the KNUST area** (Kumasi, Ghana). This repository contains the public marketing site and MikroTik hotspot portal pages for [TesNet](https://github.com/reginaldtesla/MiniISP-Landing-page).

Built as a final-year student initiative to bridge the digital divide with transparent pricing, local support, and lightweight infrastructure.

## What's in this repo

| Area | Description |
|------|-------------|
| **Marketing site** | Single-page landing site with services, pricing, and contact info |
| **Hotspot portal** | Captive-portal HTML for MikroTik (login, status, logout) |
| **Connect flow** | Onboarding page with embedded hotspot preview |
| **MikroTik scripts** | RouterOS scripts to bulk-generate voucher users |
| **Installation guide** | Production setup notes for ProBook + MikroTik stack |

There is **no build step** — everything is static HTML, CSS, and JavaScript. Serve the files directly from Apache, another web server, or upload them to the MikroTik router for hotspot use.

## Project structure

```
.
├── index.html              # Main landing page
├── connect.html            # "Get Started" — join Wi‑Fi + hotspot preview
├── login.html              # Live MikroTik hotspot login (uses $(...) variables)
├── login-preview.html      # Static preview of login (for connect.html iframe)
├── status.html             # Active session status & data usage
├── logout.html             # Post-logout confirmation
├── api.json                # Captive-portal discovery metadata
├── installation            # Production deployment guide (ProBook + MikroTik)
├── mikrotik/
│   ├── tesnet-gen-vouchers-seq.rsc    # Sequential voucher codes (TNQS001, …)
│   └── tesnet-gen-vouchers-random.rsc # Random-format voucher codes
└── TesNet.png              # Brand asset
```

## Pages

### Public website

- **`index.html`** — Hero, about, services, pricing table, contact, and policies.
- **`connect.html`** — Step-by-step instructions to join the `TesNet@0207482341` Wi‑Fi network and use the hotspot portal. Embeds `login-preview.html` so visitors can see the login UI before connecting.

### MikroTik hotspot (upload to router)

These files use [MikroTik hotspot variables](https://wiki.mikrotik.com/wiki/Manual:Customizing_Hotspot_login_page) such as `$(link-login-only)`, `$(username)`, and `$(error)`:

| File | Role |
|------|------|
| `login.html` | Voucher login + package purchase (redirects to Paystack) |
| `status.html` | Session info, data usage, auto-refresh every 30s |
| `logout.html` | Logged-out confirmation with return link |
| `api.json` | Captive portal API hints (`captive`, `user-portal-url`, `venue-info-url`) |

Package purchases on the live login page redirect to **`https://pay.tesnet.xyz`** (Paystack checkout). After payment, users enter their voucher code on the same login page.

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
| 2-Week | Unlimited | 7 days | 199.00 |
| Month | Unlimited | 30 days | 299.00 |

Pricing is also shown on the landing page pricing section.

## Local development

1. Clone the repository:

   ```bash
   git clone https://github.com/reginaldtesla/MiniISP-Landing-page.git
   cd MiniISP-Landing-page
   ```

2. Serve the folder with any static file server. Examples:

   **Apache** (e.g. `htdocs/MiniISP-Landing-page`):

   ```
   http://localhost/MiniISP-Landing-page/
   ```

   **PHP built-in server:**

   ```bash
   php -S localhost:8080
   ```

   **VS Code / Cursor Live Preview** — open `index.html` (default preview path is configured in `.vscode/settings.json`).

3. Open `index.html` in a browser, or `connect.html` to preview the hotspot login UI.

> **Note:** `login.html` MikroTik placeholders (`$(error)`, `$(link-login-only)`, etc.) only resolve when the file is served by the router's hotspot. Use `login-preview.html` for local UI testing.

## MikroTik deployment

### Hotspot HTML

1. Copy `login.html`, `status.html`, `logout.html`, and `api.json` to the router (e.g. **Files** in Winbox).
2. Point the hotspot profile's HTML directory to those files (see your router's hotspot **Server Profiles** settings).
3. Adjust `api.json` URLs if your router IP or portal path differs from `http://192.168.88.1`.

### Voucher generation

Two RouterOS scripts are provided under `mikrotik/`:

- **`tesnet-gen-vouchers-seq.rsc`** — Creates 500 users (100 per profile) with predictable codes (`TNQS001`–`TNQS100`, etc.).
- **`tesnet-gen-vouchers-random.rsc`** — Creates random-format codes per profile.

Import on the router:

```text
/import file-name=tesnet-gen-vouchers-seq.rsc
```

Or add as a **System → Scripts** entry and run from Winbox.

## Production stack

The full TesNet ISP stack (billing portal, RADIUS, database, Paystack webhooks) runs on separate infrastructure — typically an **HP ProBook (Ubuntu)** behind a **MikroTik hAP²** gateway. See the **`installation`** file in this repo for network layout, server setup, and deployment steps.

Recommended layout:

```text
[ Internet ] ──► [ MikroTik hAP² 192.168.88.1 ] ──► Wi‑Fi clients
                        │
                        └──► [ ProBook server 192.168.88.2 ]
                              Laravel portal, MariaDB, FreeRADIUS
```

## Contact

- **Location:** Ayeduase, Kumasi, Ghana (near KNUST campus)
- **Phone:** [020 050 4248](tel:+233200504248)
- **Email:** [tesnet5532@gmail.com](mailto:tesnet5532@gmail.com)

## License

© 2026 TesNet. Student initiative project — all rights reserved.
