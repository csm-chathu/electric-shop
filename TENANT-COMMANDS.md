# Tenant Management Commands

## Available Tenants

Defined in `config/tenants.php`:

| Domain | Database |
|---|---|
| `mailagas.lumac.lk` | `lmucunal_mailagas_pos` |
| `asitha-pos.lumac.lk` | `lmucunal_mailagas_pos` |
| `pos.lumac.lk` | `lmucunal_sinhala_pos` |

---

## Commands

### Fresh migrate + seed (new shop setup)

```bash
php artisan tenant:migrate mailagas.lumac.lk --fresh --seed
php artisan tenant:migrate asitha-pos.lumac.lk --fresh --seed
php artisan tenant:migrate pos.lumac.lk --fresh --seed
```

### Migrate only (run new migrations on existing DB)

```bash
php artisan tenant:migrate mailagas.lumac.lk
php artisan tenant:migrate pos.lumac.lk
```

### Seed only

```bash
php artisan tenant:seed mailagas.lumac.lk
php artisan tenant:seed pos.lumac.lk
```

### Seed a specific seeder

```bash
php artisan tenant:seed mailagas.lumac.lk --class=SettingSeeder
php artisan tenant:seed pos.lumac.lk --class=ProductSeeder
```

---

## Adding a New Tenant

1. Create database + user in cPanel → MySQL Databases
2. Add entry to `config/tenants.php`:

```php
'newshop.lumac.lk' => [
    'database' => 'lmucunal_newshop_pos',
    'username' => 'lmucunal_mysql',
    'password' => 'K!ngd0m@!t0ne',
],
```

3. Run fresh migrate + seed:

```bash
php artisan tenant:migrate newshop.lumac.lk --fresh --seed
```

---

## Notes

- In **production**, any domain not listed in `config/tenants.php` returns `403` — no fallback to default credentials
- In **local dev** (`APP_ENV=local`), unknown domains fall through to `.env` credentials
- After editing `config/tenants.php` on the server, clear the config cache:

```bash
php artisan config:clear
php artisan config:cache
```
