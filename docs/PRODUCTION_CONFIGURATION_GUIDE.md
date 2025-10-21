# Production Configuration Guide

This guide provides detailed instructions for configuring the Corporate LMS for production deployment.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Configuration](#environment-configuration)
3. [Database Setup](#database-setup)
4. [Redis Configuration](#redis-configuration)
5. [S3 Storage Setup](#s3-storage-setup)
6. [Security Configuration](#security-configuration)
7. [Performance Optimization](#performance-optimization)
8. [Monitoring Setup](#monitoring-setup)

## Prerequisites

Before deploying to production, ensure you have:

- PHP 8.2 or higher
- MySQL 8.0 or higher
- Redis 6.0 or higher
- Node.js 18.x or higher
- Composer 2.x
- FFmpeg (for video processing)
- SSL certificate for HTTPS

## Environment Configuration

### 1. Copy Production Environment File

```bash
cp .env.production.example .env
```

### 2. Generate Application Key

```bash
php artisan key:generate
```

### 3. Configure Core Settings

Update the following in your `.env` file:

```env
APP_NAME="Your Company LMS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lms.yourcompany.com
```

**Important:** Never set `APP_DEBUG=true` in production as it exposes sensitive information.

## Database Setup

### 1. Configure Database Connection

```env
DB_CONNECTION=mysql
DB_HOST=your-production-db-host.rds.amazonaws.com
DB_PORT=3306
DB_DATABASE=corporate_lms_production
DB_USERNAME=lms_user
DB_PASSWORD=your-secure-password
```

### 2. Database Best Practices

- Use a dedicated database user with minimal required permissions
- Enable SSL/TLS for database connections
- Use connection pooling for better performance
- Configure read replicas for high-traffic scenarios

### 3. Database Optimization

```sql
-- Create indexes (already in migrations)
-- Configure MySQL for production
SET GLOBAL max_connections = 200;
SET GLOBAL innodb_buffer_pool_size = 2G;
SET GLOBAL query_cache_size = 64M;
```

### 4. Run Migrations

```bash
php artisan migrate --force
```

## Redis Configuration

### 1. Configure Redis Connection

```env
REDIS_HOST=your-redis-cluster.cache.amazonaws.com
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379
```

### 2. Redis Database Separation

Use separate Redis databases for different purposes:

```env
REDIS_DB=0              # Default
REDIS_CACHE_DB=1        # Cache
REDIS_QUEUE_DB=2        # Queues
REDIS_SESSION_DB=3      # Sessions
```

### 3. Redis Cluster Setup (Optional)

For high availability:

```env
REDIS_CLUSTER=true
REDIS_SENTINELS=sentinel1:26379,sentinel2:26379,sentinel3:26379
REDIS_SENTINEL_SERVICE=mymaster
```

### 4. Test Redis Connection

```bash
php artisan tinker
>>> Redis::ping()
```

## S3 Storage Setup

### 1. Create S3 Buckets

Create separate buckets for different content types:

- `corporate-lms-production` (main bucket)
- `corporate-lms-videos` (video content)
- `corporate-lms-documents` (PDFs, presentations)
- `corporate-lms-certificates` (certificates)
- `corporate-lms-avatars` (user avatars)

### 2. Configure S3 Credentials

```env
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=corporate-lms-production
```

### 3. Set Bucket Policies

Example bucket policy for public read access to avatars:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::corporate-lms-avatars/*"
    }
  ]
}
```

### 4. Configure CORS

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
    "AllowedOrigins": ["https://lms.yourcompany.com"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

### 5. Enable Versioning and Lifecycle Rules

- Enable versioning for critical buckets
- Set lifecycle rules to archive old content to Glacier
- Configure automatic deletion of incomplete multipart uploads

### 6. CloudFront CDN (Optional)

For better performance:

```env
AWS_CLOUDFRONT_URL=https://d1234567890.cloudfront.net
```

## Security Configuration

### 1. SSL/TLS Configuration

Ensure your web server is configured with a valid SSL certificate:

```nginx
server {
    listen 443 ssl http2;
    server_name lms.yourcompany.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
}
```

### 2. Session Security

```env
SESSION_DRIVER=redis
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=.yourcompany.com
```

### 3. CSRF Protection

```env
SANCTUM_STATEFUL_DOMAINS=lms.yourcompany.com,www.yourcompany.com
```

### 4. Rate Limiting

```env
RATE_LIMIT_API=60
RATE_LIMIT_AUTH=5
RATE_LIMIT_GLOBAL=1000
```

### 5. Content Security Policy

Enable CSP headers in `config/security.php`:

```php
'csp' => [
    'enabled' => true,
    'report_only' => false,
    'report_uri' => '/csp-report',
]
```

### 6. Password Policy

```env
PASSWORD_MIN_LENGTH=12
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SPECIAL=true
```

## Performance Optimization

### 1. Enable Caching

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache events
php artisan event:cache
```

### 2. Optimize Autoloader

```bash
composer install --optimize-autoloader --no-dev
```

### 3. Enable OPcache

In `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
```

### 4. Queue Workers

Configure queue workers with Supervisor:

```ini
[program:lms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lms/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
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

### 5. Horizon Configuration

```bash
# Start Horizon
php artisan horizon

# Terminate Horizon gracefully
php artisan horizon:terminate
```

Configure Horizon in `config/horizon.php` for production environment.

### 6. Asset Optimization

```bash
# Build production assets
npm run build

# Optimize images
php artisan optimize:assets
```

## Monitoring Setup

### 1. Error Tracking with Sentry

```env
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_ENVIRONMENT=production
```

Install Sentry:

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=your-dsn
```

### 2. Application Monitoring

#### New Relic

```env
NEW_RELIC_ENABLED=true
NEW_RELIC_APP_NAME="Corporate LMS"
NEW_RELIC_LICENSE_KEY=your-license-key
```

#### DataDog

```env
DATADOG_ENABLED=true
DATADOG_API_KEY=your-api-key
DATADOG_APP_KEY=your-app-key
```

### 3. Log Management

Configure log channels in `config/logging.php`:

```php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['daily', 'slack'],
        'ignore_exceptions' => false,
    ],
    
    'daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'warning'),
        'days' => 14,
    ],
    
    'slack' => [
        'driver' => 'slack',
        'url' => env('LOG_SLACK_WEBHOOK_URL'),
        'username' => 'LMS Error Bot',
        'emoji' => ':boom:',
        'level' => 'error',
    ],
],
```

### 4. Health Checks

Create a health check endpoint:

```bash
php artisan make:controller HealthCheckController
```

Configure monitoring tools to ping `/health` endpoint regularly.

### 5. Performance Monitoring

Enable query logging for slow queries:

```env
DB_LOG_SLOW_QUERIES=true
DB_SLOW_QUERY_TIME=1000
```

## Email Configuration

### 1. SMTP Configuration

```env
MAIL_MAILER=smtp
MAIL_HOST=email-smtp.us-east-1.amazonaws.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourcompany.com"
MAIL_FROM_NAME="Corporate LMS"
```

### 2. Amazon SES (Alternative)

```env
MAIL_MAILER=ses
AWS_SES_REGION=us-east-1
```

### 3. Test Email Configuration

```bash
php artisan tinker
>>> Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

## AI Services Configuration

### 1. OpenAI Setup

```env
OPENAI_API_KEY=sk-your-api-key
OPENAI_ORGANIZATION=org-your-org-id
OPENAI_MODEL=gpt-4
OPENAI_MAX_TOKENS=2000
OPENAI_TIMEOUT=60
```

### 2. Rate Limiting

```env
OPENAI_RATE_LIMIT_PER_MINUTE=60
OPENAI_RATE_LIMIT_PER_DAY=10000
```

### 3. Fallback Service

```env
FALLBACK_AI_SERVICE=gemini
GEMINI_API_KEY=your-gemini-api-key
```

## Search Configuration

### 1. Meilisearch Setup

```env
SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=true
MEILISEARCH_HOST=https://your-meilisearch-instance.com
MEILISEARCH_KEY=your-master-key
```

### 2. Index Courses

```bash
php artisan scout:import "App\Models\Course"
```

## Backup Configuration

### 1. Configure Backups

```env
BACKUP_ENABLED=true
BACKUP_DISK=s3
BACKUP_SCHEDULE="0 2 * * *"
BACKUP_RETENTION_DAYS=30
```

### 2. Schedule Backups

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:run')->daily()->at('02:00');
    $schedule->command('backup:clean')->daily()->at('03:00');
}
```

## Final Checklist

Before going live:

- [ ] All environment variables configured
- [ ] Database migrations run successfully
- [ ] Redis connection tested
- [ ] S3 buckets created and configured
- [ ] SSL certificate installed and verified
- [ ] Caching enabled (config, routes, views)
- [ ] Queue workers running with Supervisor
- [ ] Horizon configured and running
- [ ] Error tracking (Sentry) configured
- [ ] Log aggregation configured
- [ ] Backup system tested
- [ ] Email sending tested
- [ ] AI services tested
- [ ] Search indexing completed
- [ ] Performance testing completed
- [ ] Security audit completed
- [ ] Load testing completed
- [ ] Monitoring dashboards configured
- [ ] Documentation updated
- [ ] Team trained on production procedures

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check storage permissions: `chmod -R 775 storage bootstrap/cache`
   - Clear cache: `php artisan cache:clear`
   - Check logs: `tail -f storage/logs/laravel.log`

2. **Queue Jobs Not Processing**
   - Restart queue workers: `php artisan queue:restart`
   - Check Supervisor status: `supervisorctl status`
   - Verify Redis connection

3. **Slow Performance**
   - Enable OPcache
   - Check database indexes
   - Review slow query log
   - Increase Redis memory

4. **File Upload Issues**
   - Verify S3 credentials
   - Check bucket permissions
   - Verify CORS configuration
   - Check PHP upload limits

## Support

For production support:
- Email: devops@yourcompany.com
- Slack: #lms-production
- On-call: +1-XXX-XXX-XXXX
