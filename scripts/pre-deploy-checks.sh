#!/bin/bash

###############################################################################
# Corporate LMS Pre-Deployment Checks
###############################################################################
# This script performs pre-deployment checks to ensure the system is ready
# Usage: ./pre-deploy-checks.sh
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
APP_DIR="/var/www/lms"
REQUIRED_PHP_VERSION="8.2"
REQUIRED_EXTENSIONS=("pdo" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "redis" "gd")

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Functions
log() {
    echo -e "${BLUE}[CHECK]${NC} $1"
}

pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASSED++))
}

fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAILED++))
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    ((WARNINGS++))
}

header() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

# Load environment
if [ -f "$APP_DIR/.env" ]; then
    export $(grep -v '^#' "$APP_DIR/.env" | xargs)
fi

header "Pre-Deployment Checks"

# Check 1: PHP Version
log "Checking PHP version..."
PHP_VERSION=$(php -r "echo PHP_VERSION;")
if php -r "exit(version_compare(PHP_VERSION, '$REQUIRED_PHP_VERSION', '>=') ? 0 : 1);"; then
    pass "PHP version $PHP_VERSION (>= $REQUIRED_PHP_VERSION required)"
else
    fail "PHP version $PHP_VERSION is too old (>= $REQUIRED_PHP_VERSION required)"
fi

# Check 2: PHP Extensions
header "PHP Extensions"
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    log "Checking extension: $ext"
    if php -m | grep -q "^$ext$"; then
        pass "Extension $ext is installed"
    else
        fail "Extension $ext is missing"
    fi
done

# Check 3: Composer
log "Checking Composer..."
if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | grep -oP '\d+\.\d+\.\d+' | head -1)
    pass "Composer $COMPOSER_VERSION is installed"
else
    fail "Composer is not installed"
fi

# Check 4: Node.js and NPM
log "Checking Node.js..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    pass "Node.js $NODE_VERSION is installed"
else
    fail "Node.js is not installed"
fi

log "Checking NPM..."
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    pass "NPM $NPM_VERSION is installed"
else
    fail "NPM is not installed"
fi

# Check 5: Database Connection
header "Database Connection"
log "Testing database connection..."
if php -r "
    require '$APP_DIR/vendor/autoload.php';
    \$app = require_once '$APP_DIR/bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    try {
        DB::connection()->getPdo();
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; then
    pass "Database connection successful"
else
    fail "Database connection failed"
fi

# Check 6: Redis Connection
header "Redis Connection"
log "Testing Redis connection..."
if php -r "
    require '$APP_DIR/vendor/autoload.php';
    \$app = require_once '$APP_DIR/bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    try {
        Redis::ping();
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; then
    pass "Redis connection successful"
else
    fail "Redis connection failed"
fi

# Check 7: Storage Permissions
header "File Permissions"
log "Checking storage directory permissions..."
if [ -w "$APP_DIR/storage" ]; then
    pass "Storage directory is writable"
else
    fail "Storage directory is not writable"
fi

log "Checking bootstrap/cache permissions..."
if [ -w "$APP_DIR/bootstrap/cache" ]; then
    pass "Bootstrap cache directory is writable"
else
    fail "Bootstrap cache directory is not writable"
fi

# Check 8: Environment File
header "Environment Configuration"
log "Checking .env file..."
if [ -f "$APP_DIR/.env" ]; then
    pass ".env file exists"
else
    fail ".env file is missing"
fi

log "Checking APP_KEY..."
if [ -n "$APP_KEY" ]; then
    pass "APP_KEY is set"
else
    fail "APP_KEY is not set"
fi

log "Checking APP_ENV..."
if [ "$APP_ENV" = "production" ]; then
    pass "APP_ENV is set to production"
elif [ "$APP_ENV" = "staging" ]; then
    warn "APP_ENV is set to staging"
else
    warn "APP_ENV is set to $APP_ENV (not production)"
fi

log "Checking APP_DEBUG..."
if [ "$APP_DEBUG" = "false" ]; then
    pass "APP_DEBUG is disabled"
else
    fail "APP_DEBUG is enabled (should be false in production)"
fi

# Check 9: Required Services
header "Required Services"

log "Checking MySQL service..."
if systemctl is-active --quiet mysql || systemctl is-active --quiet mariadb; then
    pass "MySQL service is running"
else
    fail "MySQL service is not running"
fi

log "Checking Redis service..."
if systemctl is-active --quiet redis || systemctl is-active --quiet redis-server; then
    pass "Redis service is running"
else
    fail "Redis service is not running"
fi

log "Checking PHP-FPM service..."
if systemctl is-active --quiet php8.2-fpm || systemctl is-active --quiet php-fpm; then
    pass "PHP-FPM service is running"
else
    warn "PHP-FPM service status unknown"
fi

log "Checking Nginx/Apache service..."
if systemctl is-active --quiet nginx; then
    pass "Nginx service is running"
elif systemctl is-active --quiet apache2; then
    pass "Apache service is running"
else
    warn "Web server service status unknown"
fi

# Check 10: Disk Space
header "System Resources"
log "Checking disk space..."
DISK_USAGE=$(df -h "$APP_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -lt 80 ]; then
    pass "Disk usage is ${DISK_USAGE}% (< 80%)"
elif [ "$DISK_USAGE" -lt 90 ]; then
    warn "Disk usage is ${DISK_USAGE}% (approaching limit)"
else
    fail "Disk usage is ${DISK_USAGE}% (critical)"
fi

# Check 11: Memory
log "Checking available memory..."
AVAILABLE_MEM=$(free -m | awk 'NR==2 {print $7}')
if [ "$AVAILABLE_MEM" -gt 500 ]; then
    pass "Available memory: ${AVAILABLE_MEM}MB"
elif [ "$AVAILABLE_MEM" -gt 200 ]; then
    warn "Available memory: ${AVAILABLE_MEM}MB (low)"
else
    fail "Available memory: ${AVAILABLE_MEM}MB (critical)"
fi

# Check 12: SSL Certificate
header "SSL Certificate"
log "Checking SSL certificate..."
if [ -n "$APP_URL" ] && [[ "$APP_URL" == https://* ]]; then
    DOMAIN=$(echo "$APP_URL" | sed -e 's|^https://||' -e 's|/.*||')
    if command -v openssl &> /dev/null; then
        EXPIRY=$(echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)
        if [ -n "$EXPIRY" ]; then
            EXPIRY_EPOCH=$(date -d "$EXPIRY" +%s)
            NOW_EPOCH=$(date +%s)
            DAYS_LEFT=$(( ($EXPIRY_EPOCH - $NOW_EPOCH) / 86400 ))
            
            if [ "$DAYS_LEFT" -gt 30 ]; then
                pass "SSL certificate valid for $DAYS_LEFT days"
            elif [ "$DAYS_LEFT" -gt 7 ]; then
                warn "SSL certificate expires in $DAYS_LEFT days"
            else
                fail "SSL certificate expires in $DAYS_LEFT days (renew soon!)"
            fi
        else
            warn "Could not check SSL certificate expiry"
        fi
    else
        warn "OpenSSL not available to check certificate"
    fi
else
    warn "APP_URL is not HTTPS"
fi

# Check 13: Queue Workers
header "Queue Workers"
log "Checking queue workers..."
if pgrep -f "queue:work" > /dev/null; then
    WORKER_COUNT=$(pgrep -f "queue:work" | wc -l)
    pass "$WORKER_COUNT queue worker(s) running"
else
    warn "No queue workers running"
fi

# Check 14: Cron Jobs
header "Scheduled Tasks"
log "Checking Laravel scheduler..."
if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
    pass "Laravel scheduler is configured in crontab"
else
    warn "Laravel scheduler not found in crontab"
fi

# Check 15: Git Status
header "Git Repository"
log "Checking git status..."
cd "$APP_DIR"
if [ -d ".git" ]; then
    if git diff-index --quiet HEAD --; then
        pass "No uncommitted changes"
    else
        warn "There are uncommitted changes"
    fi
    
    BRANCH=$(git rev-parse --abbrev-ref HEAD)
    if [ "$BRANCH" = "main" ] || [ "$BRANCH" = "master" ]; then
        pass "On branch: $BRANCH"
    else
        warn "On branch: $BRANCH (not main/master)"
    fi
else
    warn "Not a git repository"
fi

# Summary
header "Summary"
echo ""
echo -e "${GREEN}Passed:${NC} $PASSED"
echo -e "${YELLOW}Warnings:${NC} $WARNINGS"
echo -e "${RED}Failed:${NC} $FAILED"
echo ""

if [ $FAILED -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "${GREEN}✓ All checks passed! Ready for deployment.${NC}"
        exit 0
    else
        echo -e "${YELLOW}⚠ Some warnings detected. Review before deployment.${NC}"
        exit 0
    fi
else
    echo -e "${RED}✗ Some checks failed. Fix issues before deployment.${NC}"
    exit 1
fi
