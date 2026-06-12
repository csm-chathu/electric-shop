# Lumac POS — License Key Generation Guide

## Step 1 — Open Terminal in the Project Folder

```
E:\LMUC\POS\lmuc-pos
```

---

## Step 2 — Generate a Trial Key (14 days)

```bash
node scripts/keygen.cjs trial "Shop Name Here"
```

**Example:**
```bash
node scripts/keygen.cjs trial "Perera Grocery"
```

**Output:**
```
─────────────────────────────────────
  Key      : T6BJ-PLJH-WEAD-153F
  Type     : Trial (14 days)
  Customer : Perera Grocery
  Valid on : 2026-06-13
─────────────────────────────────────
```

Send `T6BJ-PLJH-WEAD-153F` to the customer.

---

## Step 3 — Generate a Paid Key (after payment received)

```bash
node scripts/keygen.cjs paid "Shop Name Here"
```

**Example:**
```bash
node scripts/keygen.cjs paid "Perera Grocery"
```

**Output:**
```
─────────────────────────────────────
  Key      : PAAS-RJ79-4ACA-8E54
  Type     : Paid — valid TODAY only to activate (then works forever)
  Customer : Perera Grocery
  Valid on : 2026-06-13
─────────────────────────────────────
```

Send the key to the customer **same day** — it expires at midnight.

---

## Step 4 — Customer Enters the Key

| Situation | Where to enter |
|---|---|
| First install (trial) | Activation window appears automatically on launch |
| Upgrading trial → paid | App → **Settings** → **Enter New Key** → type paid key |
| Trial expired | Activation window appears automatically on launch |

---

## Key Rules

| | Trial Key | Paid Key |
|---|---|---|
| Starts with | `T` | `P` |
| Must be used today | No | **Yes** |
| Expires after activation | 14 days | Never |
| Works offline | Yes | Yes |
| One machine only | Yes | Yes |

---

## Important Notes

- **Paid key expires tonight** — if customer does not enter it today, generate a new one tomorrow
- **One key = one machine** — the key locks to the customer's MAC address on first use
- **No internet needed** — everything is verified locally on the customer's machine
- **Keep `scripts/keygen.cjs` private** — anyone with it can generate unlimited keys
