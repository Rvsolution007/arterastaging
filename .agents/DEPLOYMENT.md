# Staging to Production Deployment Runbook

This document details the official, step-by-step deployment and synchronization process for the Artera project. Follow this guide to ensure that Staging changes are safely promoted to the Production environment without causing database regressions or losing user-uploaded media.

---

## 🔒 BEFORE STARTING (MANDATORY BACKUP)

Never deploy directly. First, create backups on the Production server.

```bash
# 1. Create backup directory
mkdir -p ~/deployment_backup
DATE=$(date +"%Y%m%d_%H%M%S")

# 2. Backup current codebase
tar -czf ~/deployment_backup/code_$DATE.tar.gz /var/www/project

# 3. Backup current user uploads
tar -czf ~/deployment_backup/uploads_$DATE.tar.gz /var/www/project/public/uploads

# 4. Backup production database
mysqldump -u DB_USER -p'DB_PASSWORD' DB_NAME > ~/deployment_backup/database_$DATE.sql
```
*Never skip this step.*

---

## 🛠️ Step-by-Step Deployment Runbook

### STEP 1: Verify Staging Status
SSH into your staging server and confirm that there are no uncommitted local edits:
```bash
ssh user@staging-server
cd /var/www/project
git status
```
*Must return: `nothing to commit, working tree clean`. If not, commit and push staging changes first:*
```bash
git add .
git commit -m "Final staging before production"
git push origin staging
```

### STEP 2: Compare Branches
Compare production and staging branches to understand the scope of changes:
```bash
git fetch origin
git log production..staging
git diff production staging
```
Review changes in: Laravel files, Docker configurations, `composer.json`, `package.json`, routes, config files, and migrations.

### STEP 3: Merge Staging → Production
Run these commands on your local machine to merge staging changes into production:
```bash
git checkout production
git pull origin production
git checkout staging
git pull origin staging
git checkout production

# Merge staging into production
git merge staging
```
*If merge conflicts occur, prefer staging changes:*
```bash
git checkout --theirs .
git add .
git commit
# Or run direct merge strategy:
# git merge -X theirs staging
```
Push the merged production branch to origin:
```bash
git push origin production
```

### STEP 4: Verify Untracked Files
Many production bugs occur because newly uploaded templates, ZIPs, or graphics were never committed or copied:
```bash
git status --untracked-files
find . -type f | grep uploads
find . -type f | grep skins
find . -type f | grep templates
find . -type f | grep zip
```
*Anything important should be moved into git or copied manually to the production server.*

### STEP 5: Compare Dependency Files
Check if configuration or dependency files were changed:
```bash
git diff production staging composer.json
git diff production staging composer.lock
git diff production staging package.json
git diff production staging package-lock.json
git diff production staging Dockerfile
git diff production staging docker-compose.yml
```
*If these files have changed, production must rebuild/install dependencies.*

### STEP 6: Upload Assets Safely
> [!CAUTION]
> **NEVER** use `rm -rf uploads` on the production server. Always use `rsync` with safety parameters.

Run `rsync` commands to copy missing assets from staging to production:
```bash
# Copies only missing user uploads
rsync -avh --progress --ignore-existing staging:/var/www/project/public/uploads/ production:/var/www/project/public/uploads/

# Sync design templates
rsync -avh --progress staging:/var/www/project/storage/templates/ production:/var/www/project/storage/templates/

# Sync frames ZIP files
rsync -avh --progress staging:/var/www/project/storage/zips/ production:/var/www/project/storage/zips/

# Sync templates graphics/skins
rsync -avh --progress staging:/var/www/project/uploads/ production:/var/www/project/uploads/
```
*If you want them to be identical, use `rsync -avh --checksum --delete-after`. But **NEVER** use `--delete` on `public/uploads` as this will delete production user files.*

### STEP 7: Pull Changes on Production
SSH into the production server and pull:
```bash
ssh user@production
cd /var/www/project
git fetch origin
git checkout production
git pull origin production
```

### STEP 8: Install Composer Dependencies
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### STEP 9: Build Frontend Assets
If frontend compilation runs on the server:
```bash
npm ci
# Or: npm install
npm run build
```

### STEP 10: Run Database Migrations
Run schema updates safely. Existing production database rows and data are preserved:
```bash
php artisan migrate --force
```

### STEP 11: Clear & Rebuild Framework Caches
```bash
# Clear old caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan event:clear

# Rebuild optimized production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### STEP 12: Set Permissions
Ensure the web server has read/write permissions to storage and cache:
```bash
sudo chown -R www-data:www-data storage
sudo chown -R www-data:www-data bootstrap/cache
sudo chmod -R 775 storage
sudo chmod -R 775 bootstrap/cache
```

### STEP 13: Rebuild Docker Containers (If Dockerized)
If `Dockerfile` or configuration changed:
```bash
docker compose pull
docker compose build --no-cache
docker compose up -d
```
If only PHP code changed:
```bash
docker compose up -d
```

### STEP 14: Clear OPcache (Restart PHP-FPM)
If Dockerized:
```bash
docker compose restart php
```
If native server (reload clears OPcache bytecode cache without dropping connections):
```bash
sudo systemctl reload php8.3-fpm
# Or: sudo service php8.3-fpm reload
```

### STEP 15: Verify PHP Configuration & Extensions
Verify active memory limits and file upload configurations:
```bash
php -v
php -m
php -i | grep memory_limit
php -i | grep upload_max_filesize
php -i | grep post_max_size
```
*Verify required extensions are loaded: `imagick`, `gd`, `redis`, `zip`, `fileinfo`, `mbstring`, `openssl`, `exif`.*

### STEP 16: System Health Check
Verify Laravel framework environment config:
```bash
php artisan about
php artisan optimize
```
*Perform manual smoke-tests on Homepage, Admin login, Image upload, Template upload, ZIP upload, APIs, queues, and cron.*

### STEP 17: Rollback Protocol (Emergency Only)
If production encounters a breaking issue, revert changes immediately:
```bash
# 1. Hard reset to previous git commit
git reset --hard HEAD~1

# 2. Or restore from physical code and DB backups
tar -xzf ~/deployment_backup/code_backup_date.tar.gz
mysql -u DB_USER -p'DB_PASSWORD' DB_NAME < ~/deployment_backup/database_backup_date.sql
```

---

## 🎨 Asset Sync Strategy

To prevent deleting user-generated uploads, use specific `rsync` rules for each directory:

| Folder Path | Sync Command Strategy | Delete Allowed? | Description |
| :--- | :--- | :--- | :--- |
| `/public/uploads/templates` | `rsync -av --checksum` | **Yes** | Managed templates assets |
| `/public/uploads/skins` | `rsync -av --checksum` | **Yes** | Frame frame backgrounds & layers |
| `/public/uploads/icons` | `rsync -av --checksum` | **Yes** | Canvas template icon vectors |
| `/public/uploads/zips` | `rsync -av --checksum` | **Yes** | Frame source ZIP archives |
| `/public/uploads/user_uploads` | `rsync --ignore-existing` | **NO** | Production user-generated logs/images |
| `/storage/app/public` | `rsync --ignore-existing` | **NO** | Active storage files |

---

## 📝 Deployment Checklist

- [ ] Create database, uploads, and codebase backups.
- [ ] Verify clean git status on staging.
- [ ] Diff staging and production branches.
- [ ] Merge staging to production and push.
- [ ] Check for untracked template/skin assets.
- [ ] Sync assets using rsync (ensuring user uploads are untouched).
- [ ] Pull latest branch on Production server.
- [ ] Run composer install optimized.
- [ ] Compile NPM assets.
- [ ] Run database migrations.
- [ ] Rebuild Laravel caches.
- [ ] Set storage permissions.
- [ ] Restart PHP-FPM / Docker container to clear OPcache.
- [ ] Verify required PHP extensions are active.
- [ ] Smoke-test critical user flows.
