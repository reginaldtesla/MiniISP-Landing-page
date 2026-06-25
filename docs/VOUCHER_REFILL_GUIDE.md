# TesNet — Voucher refill guide (MikroTik → CSV → hotspot-pay)

When packages sell out or stock is low, follow this guide to create more codes on the router and load them into the payment pool.

There is **no automatic sync** in v1. You always:

1. Create users on **MikroTik**
2. Export to **.rsc**
3. Convert to **CSV** (script below)
4. **Import** into `hotspot-pay`

---

## When to refill

| Signal | Action |
|--------|--------|
| Admin shows **0 available** for a package | Refill that package (or all) |
| **Available &lt; 10** | Plan a refill soon |
| Student paid but **no code** (`paid_no_stock`) | Refill immediately + manual support |

**Check stock:** `https://pay.tesnet.xyz/admin/`

---

## Part 1 — Create new voucher users on MikroTik

Codes must exist on the router **before** import. Username = password.

### Code format

`TN` + 5 letters + 2 digits + 1 letter + 1 digit + 1 letter  
Example: `TNPMZBY84G4H`

Paste **one block per profile** in MikroTik terminal (Winbox → New Terminal). Each block creates **100 codes**. Username = password.

### 1. Quick Surf — 1 GB (`Quick_Surf_1GB`)

```text
:local digits "0123456789"; :local letters "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; :local n 0; :for n from=1 to=100 do={ :local code ""; :local tries 0; :while ([:len $code] = 0) do={ :set tries ($tries + 1); :if ($tries > 100) do={ :error "duplicate" }; :local c "TN"; :local i 0; :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }; :for i from=1 to=2 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }; :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :set c ($c . [:pick $digits [:rndnum from=0 to=9]]); :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :if ([:len [/ip hotspot user find name=$c]] = 0) do={ /ip hotspot user add name=$c password=$c profile=Quick_Surf_1GB server=all limit-bytes-total=1073741824 comment=Quick_Surf_1GB disabled=no; :set code $c } } }
```

### 2. Student Choice — 3 GB (`Student_Choice_3GB`)

```text
:local digits "0123456789"; :local letters "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; :local n 0; :for n from=1 to=100 do={ :local code ""; :local tries 0; :while ([:len $code] = 0) do={ :set tries ($tries + 1); :if ($tries > 100) do={ :error "duplicate" }; :local c "TN"; :local i 0; :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }; :for i from=1 to=2 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }; :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :set c ($c . [:pick $digits [:rndnum from=0 to=9]]); :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :if ([:len [/ip hotspot user find name=$c]] = 0) do={ /ip hotspot user add name=$c password=$c profile=Student_Choice_3GB server=all limit-bytes-total=3221225472 comment=Student_Choice_3GB disabled=no; :set code $c } } }
```

### 3. Big Bundle — 7 GB (`Big_Bundle_7GB`)

```text
:local digits "0123456789"; :local letters "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; :local n 0; :for n from=1 to=100 do={ :local code ""; :local tries 0; :while ([:len $code] = 0) do={ :set tries ($tries + 1); :if ($tries > 100) do={ :error "duplicate" }; :local c "TN"; :local i 0; :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }; :for i from=1 to=2 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }; :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :set c ($c . [:pick $digits [:rndnum from=0 to=9]]); :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :if ([:len [/ip hotspot user find name=$c]] = 0) do={ /ip hotspot user add name=$c password=$c profile=Big_Bundle_7GB server=all limit-bytes-total=7516192768 comment=Big_Bundle_7GB disabled=no; :set code $c } } }
```

### 4. Heavy User — 15 GB (`Heavy_User_15GB`)

```text
:local digits "0123456789"; :local letters "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; :local n 0; :for n from=1 to=100 do={ :local code ""; :local tries 0; :while ([:len $code] = 0) do={ :set tries ($tries + 1); :if ($tries > 100) do={ :error "duplicate" }; :local c "TN"; :local i 0; :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }; :for i from=1 to=2 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }; :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :set c ($c . [:pick $digits [:rndnum from=0 to=9]]); :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :if ([:len [/ip hotspot user find name=$c]] = 0) do={ /ip hotspot user add name=$c password=$c profile=Heavy_User_15GB server=all limit-bytes-total=16106127360 comment=Heavy_User_15GB disabled=no; :set code $c } } }
```

### 5. Hostel Legend — 45 GB (`Hostel_Legend_45GB`)

```text
:local digits "0123456789"; :local letters "ABCDEFGHIJKLMNOPQRSTUVWXYZ"; :local n 0; :for n from=1 to=100 do={ :local code ""; :local tries 0; :while ([:len $code] = 0) do={ :set tries ($tries + 1); :if ($tries > 100) do={ :error "duplicate" }; :local c "TN"; :local i 0; :for i from=1 to=5 do={ :set c ($c . [:pick $letters [:rndnum from=0 to=25]]) }; :for i from=1 to=2 do={ :set c ($c . [:pick $digits [:rndnum from=0 to=9]]) }; :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :set c ($c . [:pick $digits [:rndnum from=0 to=9]]); :set c ($c . [:pick $letters [:rndnum from=0 to=25]]); :if ([:len [/ip hotspot user find name=$c]] = 0) do={ /ip hotspot user add name=$c password=$c profile=Hostel_Legend_45GB server=all limit-bytes-total=48318382080 comment=Hostel_Legend_45GB disabled=no; :set code $c } } }
```

Run only the profile(s) you need to refill. Running all five adds **500 codes** (100 each).

### Verify on router

```text
/ip hotspot user print count-only where profile="Quick_Surf_1GB"
/ip hotspot user print count-only where profile="Student_Choice_3GB"
/ip hotspot user print count-only where profile="Big_Bundle_7GB"
/ip hotspot user print count-only where profile="Heavy_User_15GB"
/ip hotspot user print count-only where profile="Hostel_Legend_45GB"
```

---

## Part 2 — Export from MikroTik

### Export one profile

```text
/ip hotspot user export file=tesnet-refill-quick where profile="Quick_Surf_1GB"
```

### Export all new refill users (if you use profile in comment)

```text
/ip hotspot user export file=tesnet-refill-all
```

### Download the file

1. Winbox → **Files**
2. Find `tesnet-refill-quick.rsc` (or your filename)
3. **Download** to your PC (e.g. `Downloads\tesnet-refill-quick.rsc`)

---

## Part 3 — Convert `.rsc` to `.csv`

The payment app imports **CSV**, not `.rsc`. Use one of these scripts.

### CSV format required

```csv
code,profile
TNPMZBY84G4H,Quick_Surf_1GB
TNXKLMN92P4Q,Student_Choice_3GB
```

Valid `profile` values (exact Winbox names):

- `Quick_Surf_1GB`
- `Student_Choice_3GB`
- `Big_Bundle_7GB`
- `Heavy_User_15GB`
- `Hostel_Legend_45GB`

---

### Option A — On your Windows PC (Python)

From the repo folder:

```powershell
cd C:\Apache24\htdocs\hotspot-pay\scripts
python rsc-to-csv.py "C:\Users\RegiTes\Downloads\tesnet-refill-quick.rsc" -o "C:\Users\RegiTes\Downloads\tesnet-refill-quick.csv"
```

Output shows how many codes per profile were written.

---

### Option B — On ProBook (PHP)

After uploading the `.rsc` to the server:

```bash
cd /var/www/hotspot-pay

# Upload file first, e.g. to data/incoming/tesnet-refill-quick.rsc
php scripts/rsc-to-csv.php data/incoming/tesnet-refill-quick.rsc data/incoming/tesnet-refill-quick.csv
```

---

### Option C — On ProBook (Python)

```bash
cd /var/www/hotspot-pay
python3 scripts/rsc-to-csv.py data/incoming/tesnet-refill-quick.rsc -o data/incoming/tesnet-refill-quick.csv
```

---

### Script behaviour

- Reads MikroTik `add name=... profile=... limit-bytes-total=...` lines
- Handles line breaks after `name=\`
- Outputs `code,profile` (uppercase codes)
- **Does not** talk to MikroTik or Paystack — file conversion only
- Safe to run on exports that include old codes (import step skips duplicates)

---

## Part 4 — Import CSV into hotspot-pay

### Option A — Admin UI (recommended)

1. Open `https://pay.tesnet.xyz/admin/`
2. Log in
3. **Import CSV**
4. Choose your `.csv` file
5. Read the result message:
   - **imported** — new codes added
   - **skipped** — already in database (OK)
   - **invalid** — wrong profile or bad row

### Option B — CLI on ProBook

```bash
cd /var/www/hotspot-pay
php -r 'require "lib/bootstrap.php"; print_r(hp_import_csv(hp_db(), "data/incoming/tesnet-refill-quick.csv"));'
```

Example output:

```text
Array
(
    [imported] => 100
    [skipped] => 0
    [invalid] => 0
)
```

---

## Part 5 — Verify stock

### Admin

`https://pay.tesnet.xyz/admin/` — **Available** count should increase.

### CLI

```bash
cd /var/www/hotspot-pay
php -r '
require "lib/bootstrap.php";
foreach (hp_stock_summary(hp_db()) as $r) {
  echo $r["name"].": ".$r["available"]." available, ".$r["assigned"]." sold\n";
}'
```

### Test payment

```text
https://pay.tesnet.xyz/buy.php?pkg=quick-surf
```

Complete a test payment → code on success page → login on hotspot.

---

## Full refill checklist (printable)

```
[ ] 1. Check admin stock — which package is low?
[ ] 2. MikroTik: run generate command(s) for that profile
[ ] 3. MikroTik: export → Files → download .rsc
[ ] 4. Convert: python rsc-to-csv.py export.rsc -o refill.csv
[ ] 5. Import: pay.tesnet.xyz/admin → Upload refill.csv
[ ] 6. Confirm: Available count increased
[ ] 7. Test: buy.php?pkg=... → code appears
```

---

## FAQ

### Do I need to delete old sold codes?

**No.** Sold codes stay in the database as `assigned`. Import only adds **new** codes.

### Can I re-import the same CSV?

**Yes.** Duplicates are **skipped**, not duplicated.

### What if `invalid` rows appear on import?

- Profile name must match Winbox exactly (`Quick_Surf_1GB`, not `Quick Surf`)
- Every row needs a `code` column
- Use the script output — don’t hand-edit unless necessary

### Does import create MikroTik users?

**No.** Users must already exist on the router. Import only fills the **payment pool**.

### Student used all data — can the same code work again?

The code stays on MikroTik until data is used up. The pool marks it **assigned** after sale. For a **new** customer, use a **new** code from the pool.

### Where are the scripts?

| Script | Path |
|--------|------|
| Python | `hotspot-pay/scripts/rsc-to-csv.py` |
| PHP | `hotspot-pay/scripts/rsc-to-csv.php` |

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Script finds 0 codes | Export must include `limit-bytes-total` lines; re-export hotspot **users**, not full config only |
| Import 0, skipped all | Codes already imported — OK if refill was run before |
| Payment works, login fails | Code not on MikroTik, or wrong password — must equal username |
| `unable to open database file` on import via web | Fix storage permissions: `sudo chown -R www-data:www-data storage` |

---

## Related docs

- **`docs/ADD_NEW_PACKAGE.md`** — brand-new profile (not just refilling stock)
- `docs/HOTSPOT_VOUCHER_PAY.md` — payment system design
- `hotspot-pay/README.md` — deploy notes
- `mikrotik/` — optional RouterOS helper files
