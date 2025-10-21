#!/bin/bash

###############################################################################
# Corporate LMS Backup Script
###############################################################################
# This script creates backups of the database and important files
# Usage: ./backup.sh [type]
# Types: full, database, files
# Example: ./backup.sh full
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
BACKUP_TYPE=${1:-full}
APP_DIR="/var/www/lms"
BACKUP_DIR="/var/backups/lms"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

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

# Create backup directory
mkdir -p "$BACKUP_DIR"

log "Starting $BACKUP_TYPE backup..."

# Database backup
backup_database() {
    log "Backing up database..."
    
    BACKUP_FILE="$BACKUP_DIR/database_${TIMESTAMP}.sql"
    
    if [ "$DB_CONNECTION" = "mysql" ]; then
        mysqldump \
            --host="$DB_HOST" \
            --port="$DB_PORT" \
            --user="$DB_USERNAME" \
            --password="$DB_PASSWORD" \
            --single-transaction \
            --routines \
            --triggers \
            --events \
            "$DB_DATABASE" > "$BACKUP_FILE"
        
        # Compress backup
        gzip "$BACKUP_FILE"
        log "Database backup created: ${BACKUP_FILE}.gz"
        
        # Upload to S3 if configured
        if [ -n "$AWS_BUCKET" ] && [ "$BACKUP_ENABLED" = "true" ]; then
            log "Uploading database backup to S3..."
            aws s3 cp "${BACKUP_FILE}.gz" "s3://${AWS_BUCKET}/backups/database/database_${TIMESTAMP}.sql.gz"
            log "Database backup uploaded to S3"
        fi
    else
        warning "Database backup only supports MySQL"
    fi
}

# Files backup
backup_files() {
    log "Backing up files..."
    
    BACKUP_FILE="$BACKUP_DIR/files_${TIMESTAMP}.tar.gz"
    
    # Backup important directories
    tar -czf "$BACKUP_FILE" \
        -C "$APP_DIR" \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='storage/logs' \
        --exclude='storage/framework/cache' \
        --exclude='storage/framework/sessions' \
        --exclude='storage/framework/views' \
        storage/app \
        .env \
        || warning "Some files could not be backed up"
    
    log "Files backup created: $BACKUP_FILE"
    
    # Upload to S3 if configured
    if [ -n "$AWS_BUCKET" ] && [ "$BACKUP_ENABLED" = "true" ]; then
        log "Uploading files backup to S3..."
        aws s3 cp "$BACKUP_FILE" "s3://${AWS_BUCKET}/backups/files/files_${TIMESTAMP}.tar.gz"
        log "Files backup uploaded to S3"
    fi
}

# Perform backup based on type
case $BACKUP_TYPE in
    full)
        backup_database
        backup_files
        ;;
    database)
        backup_database
        ;;
    files)
        backup_files
        ;;
    *)
        error "Invalid backup type. Use: full, database, or files"
        ;;
esac

# Clean old backups
log "Cleaning old backups (older than $RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete
log "Old backups cleaned"

# Verify backup
if [ "$BACKUP_TYPE" = "database" ] || [ "$BACKUP_TYPE" = "full" ]; then
    LATEST_DB_BACKUP=$(ls -t "$BACKUP_DIR"/database_*.sql.gz 2>/dev/null | head -1)
    if [ -n "$LATEST_DB_BACKUP" ]; then
        SIZE=$(du -h "$LATEST_DB_BACKUP" | cut -f1)
        log "Latest database backup size: $SIZE"
    fi
fi

if [ "$BACKUP_TYPE" = "files" ] || [ "$BACKUP_TYPE" = "full" ]; then
    LATEST_FILES_BACKUP=$(ls -t "$BACKUP_DIR"/files_*.tar.gz 2>/dev/null | head -1)
    if [ -n "$LATEST_FILES_BACKUP" ]; then
        SIZE=$(du -h "$LATEST_FILES_BACKUP" | cut -f1)
        log "Latest files backup size: $SIZE"
    fi
fi

log "Backup completed successfully!"

exit 0
