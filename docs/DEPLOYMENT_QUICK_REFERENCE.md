# Deployment Quick Reference

Quick reference guide for common deployment tasks.

## Quick Commands

### Pre-Deployment
```bash
# Run all pre-deployment checks
./scripts/pre-deploy-checks.sh

# Create full backup
./scripts/backup.sh full

# Create database backup only
./scripts/backup.sh database
```

### Deployment
```bash
# Deploy to production
./deploy.sh production

# Check application health
curl https://lms.yourcompany.com/health
```

### Rollback
```bash
# Enable maintenance mode
php artisan down

# Restore database
./scripts/restore.sh database /var/backups/lms/database_TIMESTAMP.sql.gz

# Revert code
git reset --hard <commit-hash>
composer install --no-dev --optimize-autoloader
npm ci --production && npm run build

# Clear caches
php artisan config:clear && php artisan cache:clear

# Disable maintenance mode
php artisan up
```

### Monitoring
```bash
# View deployment logs
tail -f /var/log/lms-deployment.log

# View application logs
tail -f /var/www/lms/storage/logs/laravel.log

# Check queue workers
sudo supervisorctl status

# Check health
curl http://localhost/health | jq
```

## Configuration Files

| File | Purpose |
|------|---------|
| `.env` | Environment configuration |
| `config/monitoring.php` | Monitoring settings |
| `config/database.php` | Database configuration |
| `deployment/nginx/lms.conf` | Nginx configuration |
| `deployment/supervisor/*.conf` | Queue worker configuration |

## Important Paths

| Path | Description |
|------|-------------|
| `/var/www/lms` | Application directory |
| `/var/backups/lms` | Local backups |
| `/var/log/nginx` | Nginx logs |
| `/var/www/lms/storage/logs` | Application logs |

## Service Management

```bash
# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo systemctl restart redis-server
sudo systemctl restart mysql

# Check service status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status redis-server
sudo systemctl status mysql

# Restart queue workers
sudo supervisorctl restart all
php artisan queue:restart
php artisan horizon:terminate
```

## Cache Management

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

## Database Operations

```bash
# Run migrations
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback --step=1

# Check migration status
php artisan migrate:status

# Seed database
php artisan db:seed
```

## Queue Management

```bash
# Check queue size
php artisan queue:work --once

# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all

# Monitor Horizon
# Visit: https://lms.yourcompany.com/admin/horizon
```

## Backup Management

```bash
# List backups
ls -lh /var/backups/lms/

# List S3 backups
aws s3 ls s3://your-bucket/backups/

# Download backup from S3
aws s3 cp s3://your-bucket/backups/database_TIMESTAMP.sql.gz .

# Upload backup to S3
aws s3 cp backup.sql.gz s3://your-bucket/backups/
```

## Troubleshooting

### Application Not Loading
```bash
# Check Nginx
sudo nginx -t
sudo systemctl status nginx

# Check PHP-FPM
sudo systemctl status php8.2-fpm

# Check logs
tail -100 /var/log/nginx/lms-error.log
tail -100 storage/logs/laravel.log
```

### Database Connection Failed
```bash
# Test connection
mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE

# Check MySQL status
sudo systemctl status mysql

# Check .env
grep DB_ .env
```

### Queue Not Processing
```bash
# Check workers
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart all

# Check Redis
redis-cli ping

# Manual queue work
php artisan queue:work --once
```

### High Memory Usage
```bash
# Check memory
free -h

# Check PHP processes
ps aux | grep php | wc -l

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Clear OPcache
cachetool opcache:reset --fcgi=/var/run/php/php8.2-fpm.sock
```

## Performance Optimization

```bash
# Enable OPcache
# Edit /etc/php/8.2/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.validate_timestamps=0

# Optimize Composer autoloader
composer dump-autoload --optimize --no-dev

# Build production assets
npm run build

# Warm up cache
php artisan cache:warmup
```

## Security Checks

```bash
# Check SSL certificate
openssl s_client -connect lms.yourcompany.com:443 -servername lms.yourcompany.com

# Check file permissions
ls -la storage/
ls -la bootstrap/cache/

# Check for updates
composer outdated
npm outdated

# Run security audit
composer audit
npm audit
```

## Monitoring URLs

- **Application**: https://lms.yourcompany.com
- **Health Check**: https://lms.yourcompany.com/health
- **Horizon**: https://lms.yourcompany.com/admin/horizon
- **Sentry**: https://sentry.io/organizations/your-org/projects/lms/
- **New Relic**: https://one.newrelic.com/

## Emergency Contacts

- **DevOps**: devops@yourcompany.com
- **Tech Lead**: tech-lead@yourcompany.com
- **On-Call**: Check PagerDuty
- **Slack**: #lms-production

## Useful Artisan Commands

```bash
# Application info
php artisan about

# List routes
php artisan route:list

# List scheduled tasks
php artisan schedule:list

# Run scheduler manually
php artisan schedule:run

# Clear everything
php artisan optimize:clear

# Send test notification
php artisan deploy:notify --message="Test deployment"
```

## Log Locations

| Log | Location |
|-----|----------|
| Application | `/var/www/lms/storage/logs/laravel.log` |
| Nginx Access | `/var/log/nginx/lms-access.log` |
| Nginx Error | `/var/log/nginx/lms-error.log` |
| PHP-FPM | `/var/log/php8.2-fpm.log` |
| MySQL | `/var/log/mysql/error.log` |
| Queue Workers | `/var/www/lms/storage/logs/worker.log` |
| Horizon | `/var/www/lms/storage/logs/horizon.log` |

## Environment Variables

### Critical Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://lms.yourcompany.com

DB_HOST=...
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

REDIS_HOST=...
REDIS_PASSWORD=...

AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=...

OPENAI_API_KEY=...
```

## Health Check Response

```json
{
  "status": "healthy",
  "timestamp": "2024-01-15T10:30:00Z",
  "checks": {
    "database": {"status": "healthy", "response_time_ms": 5.23},
    "redis": {"status": "healthy", "response_time_ms": 2.15},
    "storage": {"status": "healthy", "response_time_ms": 45.67},
    "queue": {"status": "healthy", "size": 12},
    "cache": {"status": "healthy", "response_time_ms": 1.89}
  },
  "version": "1.0.0",
  "environment": "production"
}
```

## Deployment Checklist

- [ ] Run pre-deployment checks
- [ ] Create backup
- [ ] Deploy application
- [ ] Verify health check
- [ ] Test critical functionality
- [ ] Monitor for errors
- [ ] Notify team

## Additional Resources

- [Full Deployment Guide](DEPLOYMENT_GUIDE.md)
- [Configuration Guide](PRODUCTION_CONFIGURATION_GUIDE.md)
- [Monitoring Guide](MONITORING_LOGGING_GUIDE.md)
- [Scripts Documentation](../scripts/README.md)
