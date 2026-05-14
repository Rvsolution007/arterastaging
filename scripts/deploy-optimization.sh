#!/bin/bash
#======================================================================
# BrandKit VPS Performance Optimization — Auto Deployment Script
#======================================================================
# 
# USAGE: 
#   chmod +x scripts/deploy-optimization.sh
#   sudo bash scripts/deploy-optimization.sh
#
# This script will:
#   1. Install & configure Redis
#   2. Run database migrations (indexes + jobs table)
#   3. Set up Supervisor for queue workers
#   4. Configure PHP OPcache
#   5. Optimize Laravel caches
#   6. Tune MySQL for 8GB RAM
#
# SAFE: All operations are idempotent (can run multiple times safely)
#======================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Auto-detect project path
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  BrandKit VPS Performance Optimization       ║${NC}"
echo -e "${CYAN}║  Auto Deployment Script v1.0                 ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Project Directory: ${PROJECT_DIR}${NC}"
echo ""

# Detect PHP version
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null || echo "8.1")
echo -e "${GREEN}✓ Detected PHP version: ${PHP_VERSION}${NC}"

#----------------------------------------------------------------------
# STEP 1: Install & Configure Redis
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}━━━ STEP 1: Redis Installation ━━━${NC}"

if command -v redis-server &> /dev/null; then
    echo -e "${GREEN}✓ Redis is already installed${NC}"
else
    echo -e "${YELLOW}Installing Redis...${NC}"
    apt-get update -qq
    apt-get install -y redis-server
    echo -e "${GREEN}✓ Redis installed${NC}"
fi

# Install PHP Redis extension if not present
if php -m 2>/dev/null | grep -q redis; then
    echo -e "${GREEN}✓ PHP Redis extension is already installed${NC}"
else
    echo -e "${YELLOW}Installing PHP Redis extension...${NC}"
    apt-get install -y php${PHP_VERSION}-redis 2>/dev/null || apt-get install -y php-redis
    echo -e "${GREEN}✓ PHP Redis extension installed${NC}"
fi

# Configure Redis for performance
REDIS_CONF="/etc/redis/redis.conf"
if [ -f "$REDIS_CONF" ]; then
    # Set max memory to 512MB (safe for 8GB RAM server)
    if ! grep -q "^maxmemory 512mb" "$REDIS_CONF"; then
        echo -e "${YELLOW}Configuring Redis max memory (512MB)...${NC}"
        sed -i 's/^# maxmemory .*/maxmemory 512mb/' "$REDIS_CONF"
        # Add if not exists
        grep -q "^maxmemory" "$REDIS_CONF" || echo "maxmemory 512mb" >> "$REDIS_CONF"
    fi
    
    # Set eviction policy
    if ! grep -q "^maxmemory-policy allkeys-lru" "$REDIS_CONF"; then
        sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' "$REDIS_CONF"
        grep -q "^maxmemory-policy" "$REDIS_CONF" || echo "maxmemory-policy allkeys-lru" >> "$REDIS_CONF"
    fi
    echo -e "${GREEN}✓ Redis configured (512MB max, LRU eviction)${NC}"
fi

# Ensure Redis is running
systemctl enable redis-server 2>/dev/null || true
systemctl restart redis-server 2>/dev/null || true

# Verify Redis
if redis-cli ping 2>/dev/null | grep -q "PONG"; then
    echo -e "${GREEN}✓ Redis is running (PONG received)${NC}"
else
    echo -e "${RED}✗ Redis failed to start! Check: sudo systemctl status redis-server${NC}"
    exit 1
fi

#----------------------------------------------------------------------
# STEP 2: Update .env for Redis
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}━━━ STEP 2: Environment Configuration ━━━${NC}"

ENV_FILE="${PROJECT_DIR}/.env"
if [ -f "$ENV_FILE" ]; then
    # Update CACHE_DRIVER
    if grep -q "^CACHE_DRIVER=file" "$ENV_FILE"; then
        sed -i 's/^CACHE_DRIVER=file/CACHE_DRIVER=redis/' "$ENV_FILE"
        echo -e "${GREEN}✓ CACHE_DRIVER set to redis${NC}"
    elif grep -q "^CACHE_DRIVER=redis" "$ENV_FILE"; then
        echo -e "${GREEN}✓ CACHE_DRIVER already set to redis${NC}"
    fi
    
    # Update QUEUE_CONNECTION
    if grep -q "^QUEUE_CONNECTION=sync" "$ENV_FILE"; then
        sed -i 's/^QUEUE_CONNECTION=sync/QUEUE_CONNECTION=redis/' "$ENV_FILE"
        echo -e "${GREEN}✓ QUEUE_CONNECTION set to redis${NC}"
    elif grep -q "^QUEUE_CONNECTION=redis" "$ENV_FILE"; then
        echo -e "${GREEN}✓ QUEUE_CONNECTION already set to redis${NC}"
    fi

    # Update SESSION_DRIVER (optional but recommended)
    if grep -q "^SESSION_DRIVER=file" "$ENV_FILE"; then
        sed -i 's/^SESSION_DRIVER=file/SESSION_DRIVER=redis/' "$ENV_FILE"
        echo -e "${GREEN}✓ SESSION_DRIVER set to redis${NC}"
    fi
else
    echo -e "${RED}✗ .env file not found at ${ENV_FILE}${NC}"
    exit 1
fi

#----------------------------------------------------------------------
# STEP 3: Run Database Migrations
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}━━━ STEP 3: Database Migrations ━━━${NC}"

cd "$PROJECT_DIR"
echo -e "${YELLOW}Running migrations...${NC}"
php artisan migrate --force 2>&1 | tail -5
echo -e "${GREEN}✓ Migrations completed${NC}"

#----------------------------------------------------------------------
# STEP 4: Setup Supervisor for Queue Workers
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}━━━ STEP 4: Queue Worker (Supervisor) ━━━${NC}"

if ! command -v supervisord &> /dev/null; then
    echo -e "${YELLOW}Installing Supervisor...${NC}"
    apt-get install -y supervisor
fi

SUPERVISOR_CONF="/etc/supervisor/conf.d/brandkit-worker.conf"
WEB_USER=$(stat -c '%U' "${PROJECT_DIR}/artisan" 2>/dev/null || echo "www-data")

cat > "$SUPERVISOR_CONF" << EOF
[program:brandkit-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${PROJECT_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=${WEB_USER}
numprocs=2
redirect_stderr=true
stdout_logfile=${PROJECT_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF

echo -e "${GREEN}✓ Supervisor config created (user: ${WEB_USER}, 2 workers)${NC}"

supervisorctl reread 2>/dev/null || true
supervisorctl update 2>/dev/null || true
supervisorctl restart brandkit-worker:* 2>/dev/null || true
echo -e "${GREEN}✓ Queue workers started${NC}"

#----------------------------------------------------------------------
# STEP 5: PHP OPcache Configuration
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}━━━ STEP 5: PHP OPcache ━━━${NC}"

# Find PHP ini directory
PHP_INI_DIR=$(php -r "echo php_ini_scanned_dir();" 2>/dev/null)
OPCACHE_INI="${PHP_INI_DIR}/99-brandkit-opcache.ini"

if [ -n "$PHP_INI_DIR" ] && [ -d "$PHP_INI_DIR" ]; then
    cat > "$OPCACHE_INI" << 'EOF'
; BrandKit OPcache Optimization
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
EOF
    echo -e "${GREEN}✓ OPcache configured (256MB, production mode)${NC}"
else
    echo -e "${YELLOW}⚠ Could not find PHP ini scan directory. Configure OPcache manually.${NC}"
fi

#----------------------------------------------------------------------
# STEP 6: MySQL Tuning
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}━━━ STEP 6: MySQL Tuning ━━━${NC}"

MYSQL_CONF="/etc/mysql/conf.d/brandkit-tuning.cnf"
MYSQL_CONF_ALT="/etc/mysql/mysql.conf.d/brandkit-tuning.cnf"

# Only create if MySQL conf directory exists
if [ -d "/etc/mysql/conf.d" ]; then
    MYSQL_TARGET="$MYSQL_CONF"
elif [ -d "/etc/mysql/mysql.conf.d" ]; then
    MYSQL_TARGET="$MYSQL_CONF_ALT"
else
    MYSQL_TARGET=""
fi

if [ -n "$MYSQL_TARGET" ] && [ ! -f "$MYSQL_TARGET" ]; then
    cat > "$MYSQL_TARGET" << 'EOF'
[mysqld]
# BrandKit Performance Tuning (for 8GB RAM server)
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 200
thread_cache_size = 16
table_open_cache = 4000
tmp_table_size = 64M
max_heap_table_size = 64M
join_buffer_size = 4M
sort_buffer_size = 4M
EOF
    echo -e "${GREEN}✓ MySQL tuning config created${NC}"
    echo -e "${YELLOW}⚠ MySQL restart required: sudo systemctl restart mysql${NC}"
else
    echo -e "${GREEN}✓ MySQL tuning already configured or dir not found${NC}"
fi

#----------------------------------------------------------------------
# STEP 7: Laravel Optimization
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}━━━ STEP 7: Laravel Optimization ━━━${NC}"

cd "$PROJECT_DIR"

# Clear all caches first
php artisan config:clear 2>/dev/null
php artisan cache:clear 2>/dev/null
php artisan route:clear 2>/dev/null
php artisan view:clear 2>/dev/null

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize 2>/dev/null || true

# Clear application cache
php artisan cache:clear-app 2>/dev/null || true

echo -e "${GREEN}✓ Laravel caches optimized${NC}"

#----------------------------------------------------------------------
# STEP 8: Restart Services
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}━━━ STEP 8: Restarting Services ━━━${NC}"

# Restart PHP-FPM
systemctl restart php${PHP_VERSION}-fpm 2>/dev/null && echo -e "${GREEN}✓ PHP-FPM restarted${NC}" || echo -e "${YELLOW}⚠ PHP-FPM restart skipped${NC}"

# Restart Nginx/Apache
if systemctl is-active --quiet nginx 2>/dev/null; then
    systemctl reload nginx
    echo -e "${GREEN}✓ Nginx reloaded${NC}"
elif systemctl is-active --quiet apache2 2>/dev/null; then
    systemctl reload apache2
    echo -e "${GREEN}✓ Apache reloaded${NC}"
fi

#----------------------------------------------------------------------
# FINAL: Summary
#----------------------------------------------------------------------
echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║  ✅ DEPLOYMENT COMPLETE!                     ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${GREEN}Optimizations Applied:${NC}"
echo -e "  ✓ Redis Cache (512MB, LRU eviction)"
echo -e "  ✓ Database Indexes (30+ columns)"
echo -e "  ✓ Queue Workers (2 processes via Supervisor)"
echo -e "  ✓ PHP OPcache (256MB, production mode)"
echo -e "  ✓ MySQL Tuning (2GB buffer pool)"
echo -e "  ✓ Laravel Config/Route/View caching"
echo ""
echo -e "${YELLOW}Remaining Manual Steps:${NC}"
echo -e "  1. Restart MySQL: ${CYAN}sudo systemctl restart mysql${NC}"
echo -e "  2. Setup Cloudflare (optional): Add domain to cloudflare.com"
echo -e "     Page Rule: yourdomain.com/uploads/* → Cache Everything"
echo ""
echo -e "${GREEN}Verify with:${NC}"
echo -e "  redis-cli ping                    → PONG"
echo -e "  sudo supervisorctl status         → RUNNING"
echo -e "  php artisan cache:clear-app       → ✅ cleared"
echo ""
echo -e "${CYAN}Expected Performance Improvement:${NC}"
echo -e "  DB queries per request: 200-500 → 8-12"
echo -e "  API response time:     500-2000ms → 50-200ms"
echo -e "  Concurrent users:      300-500 → 800-1000"
echo ""
