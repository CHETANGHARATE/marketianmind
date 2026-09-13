# Marketian Mind — Backup & Disaster Recovery Runbook

## 1. Overview & Operational Principles
This runbook defines the disaster recovery architecture, recovery objectives, automated backup routines, and step-by-step restoration procedures for **Marketian Mind** on **Hostinger Premium Shared Hosting** (PHP 8.4, Laravel 13.31, MySQL).

### Recovery Objectives
- **Recovery Time Objective (RTO)**: `< 1 Hour`
  - Database restoration from standard gzip SQL dump via phpMyAdmin or SSH takes under 5–10 minutes.
  - Complete application codebase and file restoration takes under 30–45 minutes.
- **Recovery Point Objective (RPO)**: `< 24 Hours`
  - Automated database backup runs daily at `02:00 UTC`.
  - Intraday transaction loss is mitigated via authoritative server-side webhook logs in `razorpay_webhook_events` and Razorpay Merchant Dashboard reconciliation.

---

## 2. Backup Architecture & Storage Layout

### Directory Structure
All automated and manual backups are stored in the private, non-web-accessible directory:
```text
storage/
└── app/
    └── backups/
        ├── .htaccess          # Enforces "Deny from all" as an added defense-in-depth layer
        ├── db/                # Timestamped MySQL database SQL dumps (e.g. backup_db_YYYY_MM_DD_His.sql.gz)
        └── files/             # User upload archives (e.g. backup_files_YYYY_MM_DD_His.tar.gz or .zip)
```

> [!IMPORTANT]
> The `storage/app/backups/` directory is strictly **outside** the public document root (`public/` or `public_html/`). It is **never** accessible via direct HTTP URL. Download access is gated behind authenticated `role:admin` routes with strict path-traversal sanitization.

### Backup Retention Policy
The automated retention engine keeps:
- **Last 7 Daily Backups**: Daily database snapshots.
- **Last 4 Weekly Backups**: Weekly user uploads & database milestones.
- Old archives beyond this threshold are automatically pruned during the scheduled `app:backup --prune` execution to prevent Hostinger disk space exhaustion.

---

## 3. Automated Cron Scheduler on Hostinger
On Hostinger Premium Shared Hosting, no daemon (e.g. Supervisor) is running. All background tasks and backups are driven by a single cron job configured in Hostinger hPanel:

```bash
* * * * * cd /home/uXXXXX/public_html && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Backup Schedule (in `routes/console.php`)
- **Daily Database Backup**: Every day at `02:00 UTC`
  ```bash
  php artisan app:backup --type=db --prune
  ```
- **Weekly Files Backup**: Every Sunday at `03:00 UTC`
  ```bash
  php artisan app:backup --type=files --prune
  ```

---

## 4. Disaster Recovery Scenarios & Step-by-Step Playbooks

### Scenario 1: Accidental Database Drop or Severe Database Corruption
**Symptoms**: Application returns 500 error or `/health` returns 503 `unhealthy`. Database tables missing or corrupted.

**Restoration Steps**:
1. **Enable Maintenance Mode**:
   ```bash
   php artisan down --secret="marketian-recovery-2026" --render="errors::503"
   ```
2. **Locate Latest Database Backup**:
   Check `storage/app/backups/db/` for the latest file:
   ```bash
   ls -la storage/app/backups/db/
   ```
3. **Restore via MySQL CLI or phpMyAdmin**:
   - *Via SSH CLI*:
     ```bash
     gunzip < storage/app/backups/db/backup_db_YYYY_MM_DD_His.sql.gz | mysql -u uXXXXX_dbuser -p uXXXXX_dbname
     ```
   - *Via Hostinger phpMyAdmin*:
     1. Download the latest backup via Admin Dashboard (`/admin/system/health`) or SFTP.
     2. Decompress `.sql.gz` to `.sql` locally.
     3. Open phpMyAdmin > Select Marketian Mind Database.
     4. Navigate to **Import** > Choose `.sql` file > Click **Go**.
4. **Run Migrations (if necessary)**:
   ```bash
   php artisan migrate --force
   ```
5. **Clear & Warm Cache**:
   ```bash
   php artisan optimize:clear
   php artisan optimize
   ```
6. **Disable Maintenance Mode**:
   ```bash
   php artisan up
   ```

---

### Scenario 2: Accidental User or Order Record Deletion
**Symptoms**: A critical student, enrollment, or order was inadvertently deleted.

**Restoration Steps**:
1. Do **not** overwrite the active production database.
2. Spin up a temporary database (or local SQLite/MySQL instance).
3. Import the latest database backup into the temporary database.
4. Extract the missing record(s):
   ```sql
   SELECT * FROM orders WHERE order_number = 'ORD-2026-XXXX';
   SELECT * FROM payments WHERE order_id = XXXX;
   SELECT * FROM enrollments WHERE user_id = XXXX AND course_id = XXXX;
   ```
5. Insert the recovered record(s) into the production database inside a transaction.
6. Verify access via Admin Portal (`/admin/orders` or `/admin/enrollments`).

---

### Scenario 3: Faulty Code Deployment / Production Regression
**Symptoms**: New release causes fatal exceptions, white screen, or broken checkout.

**Restoration Steps**:
1. **Rollback Git Main Branch**:
   ```bash
   git log -n 5 --oneline
   git checkout <PREVIOUS_STABLE_COMMIT_HASH>
   ```
2. **Rebuild Frontend Assets (if needed)**:
   ```bash
   npm run build
   ```
3. **Clear Application Caches**:
   ```bash
   php artisan optimize:clear
   php artisan optimize
   ```
4. **Verify Application Health**:
   ```bash
   curl -I https://marketianmind.com/health
   ```

---

### Scenario 4: Broken or Failed Database Migration
**Symptoms**: `php artisan migrate` failed mid-execution, leaving tables in an inconsistent state.

**Restoration Steps**:
1. Identify the failing migration from the console error.
2. If the migration was partially applied, inspect `database/migrations/` and determine if an additive rollback or manual schema correction is needed.
3. Check the `migrations` table:
   ```sql
   SELECT * FROM migrations ORDER BY id DESC LIMIT 5;
   ```
4. If a partial table was created:
   - Drop the partially created table/column manually.
   - Delete the entry from the `migrations` table if present.
5. Fix the migration file code (ensure idempotency with `Schema::hasTable` or `hasColumn`).
6. Re-run migration:
   ```bash
   php artisan migrate --force
   ```

---

### Scenario 5: Payment Gateway Webhook Desynchronization
**Symptoms**: Student completed payment on Razorpay, but enrollment was not triggered due to network timeout or webhook failure.

**Restoration Steps**:
1. Inspect the incoming webhook event in `razorpay_webhook_events` table:
   ```sql
   SELECT * FROM razorpay_webhook_events WHERE event_id = 'evt_XXXX' OR processed_at IS NULL;
   ```
2. Check Razorpay Merchant Dashboard for the Payment ID (`pay_XXXX`) and Order ID (`order_XXXX`).
3. Verify signature and payment status on Razorpay.
4. In Admin Orders (`/admin/orders`), find the order:
   - If payment is captured on Razorpay, mark order as `paid`, record payment transaction, and grant enrollment via Admin Enrollment panel.
   - The platform enrollment logic is strictly idempotent (`firstOrCreate` on `[user_id, course_id]`), preventing duplicate enrollments.

---

### Scenario 6: Uploaded File Storage Loss or Corruption
**Symptoms**: Student certificates, course thumbnails, or downloadable PDFs return 404.

**Restoration Steps**:
1. Locate the latest files archive in `storage/app/backups/files/`.
2. Extract the archive into `storage/app/public/`:
   ```bash
   # If .tar.gz:
   tar -xzf storage/app/backups/files/backup_files_YYYY_MM_DD_His.tar.gz -C storage/app/
   # If .zip:
   unzip storage/app/backups/files/backup_files_YYYY_MM_DD_His.zip -d storage/app/
   ```
3. Ensure the public symlink exists:
   ```bash
   php artisan storage:link
   ```
4. Verify file permissions (folders 755, files 644):
   ```bash
   chmod -R 755 storage/app/public
   ```

---

### Scenario 7: Configuration Failure / Corrupted `.env` File
**Symptoms**: Application displays `No application encryption key has been specified` or database connection fails.

**Restoration Steps**:
1. Ensure `.env.example` is present.
2. Check for backup configuration (e.g. `.env.backup` or deployment secret vault).
3. Restore required keys:
   - `APP_KEY` (MUST match previous key; otherwise encrypted student passwords/tokens will be unreadable).
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
   - `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`, `RAZORPAY_WEBHOOK_SECRET`.
   - `MAIL_*` credentials.
4. Re-cache configuration:
   ```bash
   php artisan config:cache
   ```

---

### Scenario 8: Hostinger Server Outage / Migration to New Hosting Account
**Symptoms**: Complete host infrastructure failure or migration to a new shared/VPS host.

**Restoration Steps**:
1. On the new hosting server, clone the repository:
   ```bash
   git clone git@github.com:CHETANGHARATE/marketianmind.git public_html
   cd public_html
   ```
2. Install PHP dependencies (using production flags):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. Create `.env` and configure database connection and `APP_KEY`.
4. Import latest database backup (`backup_db_YYYY_MM_DD_His.sql.gz`) into the new MySQL database.
5. Restore media uploads to `storage/app/public/`.
6. Run:
   ```bash
   php artisan storage:link
   php artisan optimize
   ```
7. Configure Hostinger Cron Job in the new control panel.
8. Update DNS / Nameservers to point domain to new server IP.
9. Verify public `/health` endpoint returns 200 `{"status": "ok"}`.

---

## 5. Post-Restoration Verification Checklist
- [ ] `/health` returns HTTP 200 with `{"status": "ok"}`
- [ ] Admin can log in at `/admin/dashboard`
- [ ] Admin System Health dashboard (`/admin/system/health`) shows database, storage, and cache operational
- [ ] Student can log in and access enrolled courses at `/student/dashboard`
- [ ] Public course catalog renders properly at `/courses`
- [ ] Test purchase checkout flow loads without JavaScript errors
- [ ] Storage symlink functions (`public/storage/` serves thumbnails/PDFs)
- [ ] Background cron execution verified in log (`storage/logs/laravel-*.log`)
