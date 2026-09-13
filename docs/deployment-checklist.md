# Marketian Mind — Hostinger Deployment Checklist

## Pre-Deployment Prerequisites
- [ ] Ensure all local changes are tested and clean (`git status`).
- [ ] Production database created in Hostinger hPanel with utf8mb4 collation.
- [ ] Domain points to Hostinger nameservers / DNS with active SSL certificate (Let's Encrypt).
- [ ] Razorpay API keys (Live mode) and Webhook secret obtained from Razorpay dashboard.

---

## Zero-Downtime Deployment Sequence

### Step 1: Maintenance Mode (Optional for major schema changes)
```bash
php artisan down --secret="mm-deploy-2026" --render="errors::503"
```
*(When accessed via `https://marketianmind.com/mm-deploy-2026`, admin can bypass the maintenance screen).*

### Step 2: Pull Latest Code
```bash
git pull origin main
```

### Step 3: Install Production Dependencies
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### Step 4: Run Additive Database Migrations
```bash
php artisan migrate --force
```

### Step 5: Build Frontend Assets
If building directly on the server (or uploading pre-compiled `public/build`):
```bash
npm ci
npm run build
```

### Step 6: Create Storage Symlink (First-time deployment only)
```bash
php artisan storage:link
```

### Step 7: Clear & Warm Application Caches
```bash
php artisan optimize:clear
php artisan optimize
```

### Step 8: Disable Maintenance Mode
```bash
php artisan up
```

---

## Hostinger Post-Deployment Verification
- [ ] Visit `https://marketianmind.com/health` and verify HTTP 200 `{"status": "ok"}`.
- [ ] Visit `https://marketianmind.com/admin/system/health` as admin and verify:
  - Database: Connected (latency displayed)
  - Storage: Writable
  - Cache: Operational
  - Trigger a manual database backup via the dashboard button to verify backup engine.
- [ ] Check Hostinger Cron configuration:
  ```bash
  * * * * * cd /home/uXXXXX/public_html && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] Test student login, course catalog, and Razorpay checkout modal loading.
