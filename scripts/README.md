# Deployment Scripts

This directory contains scripts for deploying, backing up, and maintaining the Corporate LMS.

## Scripts Overview

### deploy.sh
Main deployment script that automates the entire deployment process.

**Usage:**
```bash
./deploy.sh [environment]
```

**Example:**
```bash
./deploy.sh production
```

**What it does:**
1. Enables maintenance mode
2. Pulls latest code from repository
3. Installs/updates dependencies
4. Builds production assets
5. Clears and caches configuration
6. Runs database migrations
7. Optimizes application
8. Restarts queue workers
9. Performs health check
10. Disables maintenance mode
11. Sends deployment notification

### backup.sh
Creates backups of database and files.

**Usage:**
```bash
./backup.sh [type]
```

**Types:**
- `full` - Backup both database and files (default)
- `database` - Backup database only
- `files` - Backup files only

**Examples:**
```bash
./backup.sh full
./backup.sh database
./backup.sh files
```

**What it does:**
1. Creates timestamped backup files
2. Compresses backups
3. Uploads to S3 (if configured)
4. Cleans old backups (older than retention period)
5. Verifies backup integrity

**Backup Location:**
- Local: `/var/backups/lms/`
- S3: `s3://your-bucket/backups/`

### restore.sh
Restores backups of database or files.

**Usage:**
```bash
./restore.sh [type] [backup_file]
```

**Types:**
- `database` - Restore database
- `files` - Restore files

**Examples:**
```bash
./restore.sh database /var/backups/lms/database_20240115_120000.sql.gz
./restore.sh files /var/backups/lms/files_20240115_120000.tar.gz
```

**What it does:**
1. Confirms restore operation
2. Enables maintenance mode
3. Restores backup
4. Runs migrations (for database restore)
5. Clears caches
6. Fixes permissions (for files restore)
7. Disables maintenance mode
8. Performs health check

**⚠️ Warning:** Restore operations will overwrite current data. Always confirm before proceeding.

### pre-deploy-checks.sh
Performs comprehensive pre-deployment checks.

**Usage:**
```bash
./pre-deploy-checks.sh
```

**What it checks:**
- PHP version and extensions
- Composer and Node.js installation
- Database connectivity
- Redis connectivity
- File permissions
- Environment configuration
- Required services status
- Disk space and memory
- SSL certificate validity
- Queue workers
- Cron jobs
- Git repository status

**Exit Codes:**
- `0` - All checks passed or warnings only
- `1` - One or more checks failed

## Setup

### 1. Make Scripts Executable

```bash
chmod +x deploy.sh
chmod +x scripts/*.sh
```

### 2. Configure Environment

Ensure your `.env` file is properly configured with:
- Database credentials
- Redis configuration
- S3 credentials (for backups)
- Notification channels (Slack, email)

### 3. Test Scripts

Run pre-deployment checks:
```bash
./scripts/pre-deploy-checks.sh
```

Create a test backup:
```bash
./scripts/backup.sh database
```

## Automated Backups

### Setup Cron Job

Add to crontab:
```bash
sudo crontab -e
```

Add daily backup at 2 AM:
```
0 2 * * * /var/www/lms/scripts/backup.sh full >> /var/log/lms-backup.log 2>&1
```

### Backup Retention

Default retention: 30 days

To change retention, edit `backup.sh`:
```bash
RETENTION_DAYS=30  # Change this value
```

## Deployment Workflow

### Standard Deployment

1. **Run pre-deployment checks:**
   ```bash
   ./scripts/pre-deploy-checks.sh
   ```

2. **Create backup:**
   ```bash
   ./scripts/backup.sh full
   ```

3. **Deploy:**
   ```bash
   ./deploy.sh production
   ```

4. **Verify deployment:**
   ```bash
   curl https://lms.yourcompany.com/health
   ```

### Emergency Rollback

1. **Enable maintenance mode:**
   ```bash
   php artisan down
   ```

2. **Restore from backup:**
   ```bash
   ./scripts/restore.sh database /var/backups/lms/database_TIMESTAMP.sql.gz
   ```

3. **Revert code:**
   ```bash
   git reset --hard <previous-commit>
   composer install --no-dev --optimize-autoloader
   npm ci --production
   npm run build
   ```

4. **Clear caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

5. **Disable maintenance mode:**
   ```bash
   php artisan up
   ```

## Monitoring

### Deployment Logs

View deployment logs:
```bash
tail -f /var/log/lms-deployment.log
```

### Backup Logs

View backup logs:
```bash
tail -f /var/log/lms-backup.log
```

### Application Logs

View application logs:
```bash
tail -f /var/www/lms/storage/logs/laravel.log
```

## Troubleshooting

### Script Fails with Permission Error

**Solution:**
```bash
# Make scripts executable
chmod +x deploy.sh scripts/*.sh

# Fix ownership
sudo chown -R $USER:www-data /var/www/lms
```

### Backup Fails to Upload to S3

**Solution:**
```bash
# Check AWS credentials
aws s3 ls s3://your-bucket/

# Verify .env configuration
grep AWS_ .env

# Test AWS CLI
aws s3 cp test.txt s3://your-bucket/test.txt
```

### Deployment Hangs

**Solution:**
```bash
# Check if maintenance mode is enabled
php artisan up

# Check queue workers
sudo supervisorctl status

# Check for stuck processes
ps aux | grep artisan
```

### Health Check Fails After Deployment

**Solution:**
```bash
# Check application logs
tail -100 storage/logs/laravel.log

# Check Nginx logs
sudo tail -100 /var/log/nginx/lms-error.log

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Test Redis connection
php artisan tinker
>>> Redis::ping();
```

## Best Practices

1. **Always run pre-deployment checks** before deploying
2. **Create backups** before every deployment
3. **Test in staging** before deploying to production
4. **Monitor logs** during and after deployment
5. **Keep backups** for at least 30 days
6. **Document changes** in deployment notes
7. **Notify team** before and after deployment
8. **Have rollback plan** ready

## Security

- Scripts should only be executable by authorized users
- Never commit `.env` files to repository
- Rotate backup encryption keys regularly
- Limit access to backup files
- Use secure channels for deployment notifications

## Support

For deployment issues:
- Email: devops@yourcompany.com
- Slack: #lms-deployments
- On-call: Check PagerDuty schedule

## Additional Resources

- [Deployment Guide](../docs/DEPLOYMENT_GUIDE.md)
- [Production Configuration Guide](../docs/PRODUCTION_CONFIGURATION_GUIDE.md)
- [Monitoring and Logging Guide](../docs/MONITORING_LOGGING_GUIDE.md)
