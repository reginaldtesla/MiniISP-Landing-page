# MiniISP Landing Page

Static landing page for TesNet internet services in Ayeduase / KNUST.

This directory is only for the public MiniISP marketing pages. The TesNet Laravel portal and hotspot-pay voucher app should live in their own sibling folders under Apache `htdocs`.

## Folder location

Expected Windows Apache layout:

```text
C:\Apache24\htdocs\
├── MiniISP-Landing-page\
├── TesNet\
└── hotspot-pay\
```

This README covers only:

```text
C:\Apache24\htdocs\MiniISP-Landing-page\
```

## Main files

| File | Purpose |
| --- | --- |
| `index.html` | Main landing page: hero, services, pricing, contact, footer |
| `connect.html` | "Get Started" / connection entry page |
| `login.html` | Legacy hotspot login page template |
| `logout.html` | Legacy hotspot logout page template |
| `status.html` | Legacy hotspot status page template |
| `login-preview.html` | Browser preview for the hotspot login page |

## Open locally

With Apache running, open:

```text
http://localhost/MiniISP-Landing-page/
```

Or open `index.html` directly in a browser for a quick static preview.

## Editing the landing page

Most public content is in `index.html`:

- Navigation links
- Hero copy
- Services section
- Pricing table
- Contact details
- Footer policy text

The page uses Tailwind from the CDN, Google Fonts, and Material Symbols, so no build step is required.

## Related projects

These are separate projects and should not be documented as part of this directory:

```text
C:\Apache24\htdocs\TesNet
C:\Apache24\htdocs\hotspot-pay
```

Each project should keep its own README and docs inside its own folder.
