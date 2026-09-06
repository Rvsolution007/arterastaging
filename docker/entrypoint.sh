#!/bin/bash
set -e

echo "=== Artera Container Starting ==="

# Fix .env: wrap values with spaces in double quotes
if [ -f /var/www/html/.env ]; then
    sed -i 's/^\([A-Za-z_]*\)=\([^"'"'"'].*[[:space:]].*\)$/\1="\2"/' /var/www/html/.env
fi

# Ensure persistent uploads directory exists (Easypanel volume at public/uploads)
mkdir -p /var/www/html/public/uploads
chown www-data:www-data /var/www/html/public/uploads

# Create symlink: /var/www/html/uploads -> /var/www/html/public/uploads
# This makes both ./uploads and public/uploads point to the same persistent volume
# So images survive deploys and are accessible via the /uploads/ URL
if [ -L /var/www/html/uploads ]; then
    rm /var/www/html/uploads
elif [ -d /var/www/html/uploads ]; then
    # If old uploads dir exists with files, move them to persistent volume first
    if [ "$(ls -A /var/www/html/uploads 2>/dev/null)" ]; then
        cp -a /var/www/html/uploads/. /var/www/html/public/uploads/ 2>/dev/null || true
    fi
    rm -rf /var/www/html/uploads
fi
ln -s /var/www/html/public/uploads /var/www/html/uploads
echo "Uploads symlink created: /var/www/html/uploads -> /var/www/html/public/uploads"

# Wait for MySQL to be ready (retry up to 30 times)
echo "Waiting for database connection..."
MAX_RETRIES=30
RETRY_COUNT=0
until php artisan db:monitor --databases=mysql > /dev/null 2>&1 || [ $RETRY_COUNT -eq $MAX_RETRIES ]; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "Database not ready yet... retry $RETRY_COUNT/$MAX_RETRIES"
    sleep 2
done

# Run migrations
echo "Running migrations..."
php artisan migrate --force 2>&1 || echo "Migration warning (may already exist)"

# Bring already-completed Festival AI visuals into the secure usage dashboard.
# The command is idempotent and stores no prompt, credentials, or image data.
echo "Syncing Festival AI analytics..."
php artisan festival-ai:backfill-analytics --limit=500 2>&1 || echo "Festival AI analytics sync warning"

# Clear and cache config
echo "Optimizing application..."
php artisan config:clear 2>&1 || echo "Config clear warning; continuing to start Apache"
php artisan cache:clear 2>&1 || echo "Cache clear warning; continuing to start Apache"
php artisan view:clear 2>&1 || echo "View clear warning; continuing to start Apache"

# Festival AI always uses the dedicated database queue connection. Keep its
# worker inside the same container so a deploy cannot leave visuals in queued.
# The loop only restarts the worker; it never logs credentials or job payloads.
touch /var/www/html/storage/logs/festival-ai-worker.log
chown www-data:www-data /var/www/html/storage/logs/festival-ai-worker.log
echo "Starting Festival AI queue worker..."
(
    while true; do
        su -s /bin/sh www-data -c 'cd /var/www/html && php artisan queue:work --queue=festival-ai --sleep=1 --tries=1 --timeout=210 --max-time=3500' || true
        echo "Festival AI queue worker exited; restarting in 2 seconds..."
        sleep 2
    done
) >> /var/www/html/storage/logs/festival-ai-worker.log 2>&1 &

echo "=== Artera Ready! Starting Apache ==="

# Start Apache in foreground
exec apache2-foreground
