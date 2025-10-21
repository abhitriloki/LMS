#!/bin/bash

###############################################################################
# Corporate LMS Deployment Script
###############################################################################
# This script automates the deployment process for the Corporate LMS
# Usage: ./deploy.sh [environment]
# Example: ./deploy.sh production
###############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
ENVIRONMENT=${1:-production}
APP_DIR="/var/www/lms"
BACKUP_DIR="/var/backups/lms"
LOG_FILE="/var/log/lms-deployment.log"

# Functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" | tee -a "$LOG_FILE"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1" | tee -a "$LOG_FILE"
}

# Check if running as correct user
if [ "$EUID" -eq 0 ]; then 
    error "Please do not run this script as root"
fi

log "Starting deployment for environment: $ENVIRONMENT"

# Step 1: Enable maintenance mode
log "Enabling maintenance mode..."
cd "$APP_DIR"
php artisan down --retry=60 --secret="$(openssl rand -hex 16)" || warning "Failed to enable maintenance mode"

# Step 2: Pull latest code
log "Pulling latest code from repository..."
git fetch origin
git reset --hard origin/main || error "Failed to pull latest code"

# Step 3: Install/Update dependencies
log "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction || error "Composer install failed"

log "Installing NPM dependencies..."
npm ci --production || error "NPM install failed"

# Step 4: Build assets
log "Building production assets..."
npm run build || error "Asset build failed"

# Step 5: Clear and cache configuration
log "Clearing configuration cache..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

log "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Step 6: Run database migrations
log "Running database migrations..."
php artisan migrate --force || error "Database migration failed"

# Step 7: Optimize application
log "Optimizing application..."
php artisan optimize

# Step 8: Restart queue workers
log "Restarting queue workers..."
php artisan queue:restart

# Step 9: Restart Horizon (if using)
if [ -f "artisan" ] && php artisan list | grep -q "horizon:terminate"; then
    log "Restarting Horizon..."
    php artisan horizon:terminate
fi

# Step 10: Clear OPcache
log "Clearing OPcache..."
if command -v cachetool &> /dev/null; then
    cachetool opcache:reset --fcgi=/var/run/php/php8.2-fpm.sock
else
    warning "cachetool not found, skipping OPcache reset"
fi

# Step 11: Warm up cache
log "Warming up cache..."
php artisan cache:warmup 2>/dev/null || warning "Cache warmup not available"

# Step 12: Index search
log "Updating search indexes..."
php artisan scout:import "App\Models\Course" || warning "Search indexing failed"

# Step 13: Disable maintenance mode
log "Disabling maintenance mode..."
php artisan up

# Step 14: Health check
log "Performing health check..."
sleep 5
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
if [ "$HEALTH_CHECK" -eq 200 ]; then
    log "Health check passed!"
else
    error "Health check failed with status code: $HEALTH_CHECK"
fi

# Step 15: Notify deployment
log "Sending deployment notification..."
php artisan deploy:notify || warning "Failed to send deployment notification"

log "Deployment completed successfully!"
log "Application is now live at: $(php artisan tinker --execute='echo config(\"app.url\");')"

exit 0
