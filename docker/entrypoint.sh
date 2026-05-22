#!/bin/bash
set -e

echo "=== Artera Container Starting ==="

# Fix .env: wrap values with spaces in double quotes
if [ -f /var/www/html/.env ]; then
    sed -i 's/^\([A-Za-z_]*\)=\([^"'"'"'].*[[:space:]].*\)$/\1="\2"/' /var/www/html/.env
fi

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

# Clear and cache config
echo "Optimizing application..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "=== Artera Ready! Starting Apache ==="

# Start Apache in foreground
exec apache2-foreground
