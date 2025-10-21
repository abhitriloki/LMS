#!/bin/bash

###############################################################################
# Corporate LMS Restore Script
###############################################################################
# This script restores backups of the database and files
# Usage: ./restore.sh [type] [backup_file]
# Types: database, files
# Example: ./restore.sh database /var/backups/lms/database_20240115_120000.sql.gz
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
RESTORE_TYPE=$1
BACKUP_FILE=$2
APP_DIR="/var/www/lms"

# Load environment variables
if [ -f "$APP_DIR/.env" ]; then
    export $(grep -v '^#' "$APP_DIR/.env" | xargs)
fi

# Functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1"
    exit 1
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1"
}

confirm() {
    read -p "$1 (yes/no): " response
    case "$response" in
        [yY][eE][sS]|[yY]) 
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

# Validate inputs
if [ -z "$RESTORE_TYPE" ] || [ -z "$BACKUP_FILE" ]; then
    error "Usage: ./restore.sh [type] [backup_file]"
fi

if [ ! -f "$BACKUP_FILE" ]; then
    error "Backup file not found: $BACKUP_FILE"
fi

# Restore database
restore_database() {
    log "Restoring database from: $BACKUP_FILE"
    
    if ! confirm "This will overwrite the current database. Continue?"; then
        log "Restore cancelled"
        exit 0
    fi
    
    # Enable maintenance mode
    log "Enabling maintenance mode..."
    cd "$APP_DIR"
    php artisan down || warning "Failed to enable maintenance mode"
    
    # Decompress if needed
    TEMP_FILE="$BACKUP_FILE"
    if [[ "$BACKUP_FILE" == *.gz ]]; then
        log "Decompressing backup..."
        TEMP_FILE="${BACKUP_FILE%.gz}"
        gunzip -c "$BACKUP_FILE" > "$TEMP_FILE"
    fi
    
    # Restore database
    log "Restoring database..."
    mysql \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        "$DB_DATABASE" < "$TEMP_FILE" || error "Database restore failed"
    
    # Clean up temp file
    if [ "$TEMP_FILE" != "$BACKUP_FILE" ]; then
        rm "$TEMP_FILE"
    fi
    
    # Run migrations to ensure schema is up to date
    log "Running migrations..."
    php artisan migrate --force
    
    # Clear caches
    log "Clearing caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    
    # Disable maintenance mode
    log "Disabling maintenance mode..."
    php artisan up
    
    log "Database restored successfully!"
}

# Restore files
restore_files() {
    log "Restoring files from: $BACKUP_FILE"
    
    if ! confirm "This will overwrite current files. Continue?"; then
        log "Restore cancelled"
        exit 0
    fi
    
    # Enable maintenance mode
    log "Enabling maintenance mode..."
    cd "$APP_DIR"
    php artisan down || warning "Failed to enable maintenance mode"
    
    # Extract backup
    log "Extracting files..."
    tar -xzf "$BACKUP_FILE" -C "$APP_DIR" || error "File extraction failed"
    
    # Fix permissions
    log "Fixing permissions..."
    chown -R www-data:www-data "$APP_DIR/storage"
    chmod -R 775 "$APP_DIR/storage"
    
    # Clear caches
    log "Clearing caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan view:clear
    
    # Disable maintenance mode
    log "Disabling maintenance mode..."
    php artisan up
    
    log "Files restored successfully!"
}

# Perform restore based on type
case $RESTORE_TYPE in
    database)
        restore_database
        ;;
    files)
        restore_files
        ;;
    *)
        error "Invalid restore type. Use: database or files"
        ;;
esac

# Health check
log "Performing health check..."
sleep 5
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)
if [ "$HEALTH_CHECK" -eq 200 ]; then
    log "Health check passed!"
else
    warning "Health check failed with status code: $HEALTH_CHECK"
fi

log "Restore completed!"

exit 0
