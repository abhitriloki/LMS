# Deployment Guide

This guide provides comprehensive instructions for deploying the Corporate LMS to production.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Setup](#server-setup)
3. [Initial Deployment](#initial-deployment)
4. [Deployment Process](#deployment-process)
5. [Rollback Procedures](#rollback-procedures)
6. [Post-Deployment](#post-deployment)
7. [Troubleshooting](#troubleshooting)

## Prerequisites

### Required Software

- Ubuntu 22.04 LTS or similar
- PHP 8.2 or higher
- MySQL 8.0 or higher
- Redis 6.0 or higher
- Nginx or Apache
- Node.js 18.x or higher
- Composer 2.x
- Git
- Supervisor (for queue workers)

### Required Accounts

- AWS account (for S3 storage)
- OpenAI API key
- Meilisearch instance or Algolia account
- Sentry account (for error tracking)
- Email service (SMTP or SES)

## Server Setup

### 1. Update System

```bash
sudo apt update
sudo apt upgrade -y
```

### 2. Install PHP 8.2

```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl \
    php8.2-xml php8.2-bcmath php8.2-redis php8.2-intl
```

### 3. Install MySQL

```bash
sudo apt install -y mysql-server
sudo mysql_secure_installation
```

Create database and user:

```sql
CREATE DATABASE corporate_lms_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON corporate_lms_production.* TO 'lms_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Install Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

Configure Redis:

```bash
sudo nano /etc/redis/redis.conf
```

Set:
```
maxmemory 256mb
maxmemory-policy allkeys-lru
```

Restart Redis:

```bash
sudo systemctl restart redis-server
```

### 5. Install Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 6. Install Node.js

```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### 7. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### 8. Install FFmpeg (for video processing)

```bash
sudo apt install -y ffmpeg
```

### 9. Install Supervisor

```bash
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

## Initial Deployment

### 1. Create Application Directory

```bash
sudo mkdir -p /var/www/lms
sudo chown -R $USER:www-data /var/www/lms
```

### 2. Clone Repository

```bash
cd /var/www
git clone https://github.com/yourcompany/lms.git
cd lms
```

### 3. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/lms
sudo chmod -R 775 /var/www/lms/storage
sudo chmod -R 775 /var/www/lms/bootstrap/cache
```

### 4. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci --production
```

### 5. Configure Environment

```bash
cp .env.production.example .env
nano .env
```

Update all configuration values (see [Production Configuration Guide](PRODUCTION_CONFIGURATION_GUIDE.md)).

### 6. Generate Application Key

```bash
php artisan key:generate
```

### 7. Run Migrations

```bash
php artisan migrate --force
```

### 8. Seed Database (optional)

```bash
php artisan db:seed --class=CertificateTemplateSeeder
```

### 9. Build Assets

```bash
npm run build
```

### 10. Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

### 11. Create Storage Link

```bash
php artisan storage:link
```

### 12. Index Search

```bash
php artisan scout:import "App\Models\Course"
```

## Configure Nginx

Create site configuration:

```bash
sudo nano /etc/nginx/sites-available/lms
```

Add configuration:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name lms.yourcompany.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name lms.yourcompany.com;
    root /var/www/lms/public;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/lms.yourcompany.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/lms.yourcompany.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';" always;

    # Logging
    access_log /var/log/nginx/lms-access.log;
    error_log /var/log/nginx/lms-error.log;

    index index.php;

    charset utf-8;

    # Max upload size
    client_max_body_size 512M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for long-running requests
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/lms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## Configure SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d lms.yourcompany.com
```

## Configure Queue Workers

Create Supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/lms-worker.conf
```

Add:

```ini
[program:lms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lms/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=300
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/lms/storage/logs/worker.log
stopwaitsecs=3600
```

For Horizon:

```bash
sudo nano /etc/supervisor/conf.d/lms-horizon.conf
```

Add:

```ini
[program:lms-horizon]
process_name=%(program_name)s
command=php /var/www/lms/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/lms/storage/logs/horizon.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

## Configure Scheduler

Add to crontab:

```bash
sudo crontab -e -u www-data
```

Add:

```
* * * * * cd /var/www/lms && php artisan schedule:run >> /dev/null 2>&1
```

## Deployment Process

### Automated Deployment

1. **Make scripts executable:**

```bash
chmod +x deploy.sh
chmod +x scripts/*.sh
```

2. **Run pre-deployment checks:**

```bash
./scripts/pre-deploy-checks.sh
```

3. **Create backup:**

```bash
./scripts/backup.sh full
```

4. **Deploy:**

```bash
./deploy.sh production
```

### Manual Deployment Steps

If you prefer manual deployment:

1. **Enable maintenance mode:**

```bash
php artisan down --retry=60
```

2. **Pull latest code:**

```bash
git pull origin main
```

3. **Install dependencies:**

```bash
composer install --no-dev --optimize-autoloader
npm ci --production
```

4. **Build assets:**

```bash
npm run build
```

5. **Run migrations:**

```bash
php artisan migrate --force
```

6. **Clear and cache:**

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

7. **Restart workers:**

```bash
php artisan queue:restart
php artisan horizon:terminate
```

8. **Disable maintenance mode:**

```bash
php artisan up
```

## Rollback Procedures

### Quick Rollback

1. **Enable maintenance mode:**

```bash
php artisan down
```

2. **Revert to previous commit:**

```bash
git log --oneline -10  # Find commit to revert to
git reset --hard <commit-hash>
```

3. **Restore dependencies:**

```bash
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build
```

4. **Rollback database (if needed):**

```bash
php artisan migrate:rollback --step=1
```

5. **Clear caches:**

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

6. **Disable maintenance mode:**

```bash
php artisan up
```

### Full Restore from Backup

```bash
./scripts/restore.sh database /var/backups/lms/database_TIMESTAMP.sql.gz
./scripts/restore.sh files /var/backups/lms/files_TIMESTAMP.tar.gz
```

## Post-Deployment

### 1. Verify Deployment

```bash
# Check application status
curl https://lms.yourcompany.com/health

# Check queue workers
sudo supervisorctl status

# Check logs
tail -f storage/logs/laravel.log
```

### 2. Smoke Tests

- [ ] Login functionality
- [ ] Course catalog loads
- [ ] Video playback works
- [ ] Assessment submission works
- [ ] Certificate generation works
- [ ] Email sending works
- [ ] Search functionality works

### 3. Monitor

- Check Sentry for errors
- Monitor New Relic/DataDog
- Review server logs
- Check queue size

### 4. Notify Team

Send deployment notification:

```bash
php artisan deploy:notify
```

## Zero-Downtime Deployment

For zero-downtime deployments, use a blue-green deployment strategy:

1. Set up two identical environments (blue and green)
2. Deploy to inactive environment
3. Run tests on inactive environment
4. Switch load balancer to new environment
5. Keep old environment as backup

## Troubleshooting

### Issue: 500 Internal Server Error

**Solution:**
```bash
# Check logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/lms-error.log

# Check permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Issue: Queue Jobs Not Processing

**Solution:**
```bash
# Check Supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart all

# Check Redis
redis-cli ping

# Check queue size
php artisan queue:work --once
```

### Issue: Assets Not Loading

**Solution:**
```bash
# Rebuild assets
npm run build

# Clear view cache
php artisan view:clear

# Check Nginx configuration
sudo nginx -t
sudo systemctl reload nginx
```

### Issue: Database Connection Failed

**Solution:**
```bash
# Test connection
mysql -h $DB_HOST -u $DB_USERNAME -p$DB_PASSWORD $DB_DATABASE

# Check MySQL status
sudo systemctl status mysql

# Review .env configuration
cat .env | grep DB_
```

## Maintenance

### Daily

- Monitor error rates
- Check queue size
- Review logs

### Weekly

- Review performance metrics
- Check disk space
- Update dependencies (security patches)

### Monthly

- Full backup verification
- Security audit
- Performance optimization review

## Emergency Contacts

- **DevOps Lead**: devops@yourcompany.com
- **On-Call**: Check PagerDuty
- **Slack**: #lms-production

## Additional Resources

- [Production Configuration Guide](PRODUCTION_CONFIGURATION_GUIDE.md)
- [Monitoring and Logging Guide](MONITORING_LOGGING_GUIDE.md)
- [Security Best Practices](SECURITY_QUICK_REFERENCE.md)
- [Performance Optimization](PERFORMANCE_OPTIMIZATION_GUIDE.md)
