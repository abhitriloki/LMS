# Installation Guide

## System Requirements

### Server Requirements

- **PHP**: 8.2 or higher
- **Web Server**: Nginx or Apache
- **Database**: MySQL 8.0+ or MariaDB 10.3+
- **Cache**: Redis 7.0+
- **Node.js**: 18.x or higher
- **Composer**: 2.x

### PHP Extensions

Required PHP extensions:
- BCMath
- Ctype
- cURL
- DOM
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- PDO_MySQL
- Tokenizer
- XML
- GD or Imagick
- Redis

### Recommended Server Specifications

**Minimum (Development):**
- 2 CPU cores
- 4GB RAM
- 20GB storage

**Recommended (Production):**
- 4+ CPU cores
- 8GB+ RAM
- 100GB+ SSD storage
- CDN for static assets

## Installation Steps

### 1. Clone Repository

```bash
# Clone the repository
git clone https://github.com/yourcompany/corporate-lms.git
cd corporate-lms

# Or download and extract the release package
wget https://github.com/yourcompany/corporate-lms/archive/v1.0.0.tar.gz
tar -xzf v1.0.0.tar.gz
cd corporate-lms-1.0.0
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```


### 4. Configure Environment Variables

Edit `.env` file with your settings:

```env
# Application
APP_NAME="Corporate LMS"
APP_ENV=local
APP_KEY=base64:generated_key_here
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=corporate_lms
DB_USERNAME=root
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache & Queue
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME="${APP_NAME}"

# OpenAI
OPENAI_API_KEY=your_openai_api_key
OPENAI_ORGANIZATION=your_org_id

# AWS S3 (Optional)
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=lms-storage
AWS_USE_PATH_STYLE_ENDPOINT=false

# Meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your_master_key

# Pusher (Optional for WebSockets)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

### 5. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE corporate_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Run migrations
php artisan migrate

# Seed database with sample data (optional)
php artisan db:seed
```

### 6. Storage Setup

```bash
# Create storage link
php artisan storage:link

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 7. Build Frontend Assets

```bash
# Development build
npm run dev

# Production build
npm run build
```

### 8. Configure Search

```bash
# Install Meilisearch
curl -L https://install.meilisearch.com | sh

# Start Meilisearch
./meilisearch --master-key="your_master_key"

# Import course data to search index
php artisan scout:import "App\Models\Course"
```

### 9. Start Queue Workers

```bash
# Start queue worker
php artisan queue:work

# Or use Horizon (recommended for production)
php artisan horizon
```

### 10. Start Development Server

```bash
# Start Laravel development server
php artisan serve

# Access application at http://localhost:8000
```


## Production Installation

### 1. Server Setup (Ubuntu 22.04)

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-curl \
    php8.2-gd php8.2-zip php8.2-intl

# Install MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server

# Install Nginx
sudo apt install -y nginx
sudo systemctl enable nginx

# Install Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Configure Nginx

Create Nginx configuration:

```bash
sudo nano /etc/nginx/sites-available/lms
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name lms.yourcompany.com;
    root /var/www/lms/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

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
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/lms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 3. SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d lms.yourcompany.com

# Auto-renewal is configured automatically
```

### 4. Deploy Application

```bash
# Create application directory
sudo mkdir -p /var/www/lms
cd /var/www/lms

# Clone repository
sudo git clone https://github.com/yourcompany/corporate-lms.git .

# Set ownership
sudo chown -R www-data:www-data /var/www/lms

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci --production

# Configure environment
sudo -u www-data cp .env.production.example .env
sudo -u www-data nano .env

# Generate key
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php artisan migrate --force

# Build assets
sudo -u www-data npm run build

# Optimize application
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan optimize

# Set permissions
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### 5. Configure Supervisor for Queue Workers

Create supervisor configuration:

```bash
sudo nano /etc/supervisor/conf.d/lms-worker.conf
```

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

Start supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start lms-worker:*
```

### 6. Configure Horizon (Alternative to Supervisor)

```bash
# Create Horizon configuration
sudo nano /etc/supervisor/conf.d/lms-horizon.conf
```

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

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start lms-horizon
```

### 7. Configure Cron Jobs

```bash
sudo crontab -e -u www-data
```

Add Laravel scheduler:

```cron
* * * * * cd /var/www/lms && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Configure Backups

```bash
# Install backup package (if not already)
composer require spatie/laravel-backup

# Configure backup in config/backup.php

# Add backup cron
0 2 * * * cd /var/www/lms && php artisan backup:run >> /dev/null 2>&1
```


## Docker Installation

### Using Docker Compose

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: lms-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./storage:/var/www/storage
    networks:
      - lms-network
    depends_on:
      - mysql
      - redis

  nginx:
    image: nginx:alpine
    container_name: lms-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./deployment/nginx:/etc/nginx/conf.d
    networks:
      - lms-network
    depends_on:
      - app

  mysql:
    image: mysql:8.0
    container_name: lms-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - lms-network
    ports:
      - "3306:3306"

  redis:
    image: redis:7-alpine
    container_name: lms-redis
    restart: unless-stopped
    networks:
      - lms-network
    ports:
      - "6379:6379"

  meilisearch:
    image: getmeili/meilisearch:latest
    container_name: lms-meilisearch
    restart: unless-stopped
    environment:
      MEILI_MASTER_KEY: ${MEILISEARCH_KEY}
    volumes:
      - meilisearch-data:/meili_data
    networks:
      - lms-network
    ports:
      - "7700:7700"

  horizon:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: lms-horizon
    restart: unless-stopped
    command: php artisan horizon
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - lms-network
    depends_on:
      - mysql
      - redis

networks:
  lms-network:
    driver: bridge

volumes:
  mysql-data:
  meilisearch-data:
```

Create `Dockerfile`:

```dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
```

### Start Docker Containers

```bash
# Build and start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force

# Build assets (if needed)
docker-compose exec app npm run build

# Create storage link
docker-compose exec app php artisan storage:link
```


## Post-Installation

### 1. Create Admin User

```bash
# Using tinker
php artisan tinker

# Create admin user
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@yourcompany.com';
$user->password = Hash::make('secure_password');
$user->role = 'super_admin';
$user->email_verified_at = now();
$user->save();
```

Or use seeder:

```bash
php artisan db:seed --class=AdminUserSeeder
```

### 2. Configure AI Services

```bash
# Test OpenAI connection
php artisan tinker

$service = app(App\Services\AI\OpenAIService::class);
$response = $service->generateText('Hello, this is a test');
echo $response;
```

### 3. Import Initial Data

```bash
# Seed categories
php artisan db:seed --class=CategorySeeder

# Seed departments
php artisan db:seed --class=DepartmentSeeder

# Seed certificate templates
php artisan db:seed --class=CertificateTemplateSeeder
```

### 4. Configure File Storage

For S3 storage:

```bash
# Test S3 connection
php artisan tinker

Storage::disk('s3')->put('test.txt', 'Hello World');
Storage::disk('s3')->exists('test.txt'); // Should return true
Storage::disk('s3')->delete('test.txt');
```

### 5. Test Email Configuration

```bash
php artisan tinker

Mail::raw('Test email', function($message) {
    $message->to('test@example.com')
            ->subject('Test Email from LMS');
});
```

### 6. Verify Installation

Access the following URLs to verify:

- **Homepage**: `http://your-domain.com`
- **Login**: `http://your-domain.com/login`
- **Admin Panel**: `http://your-domain.com/admin`
- **API Health**: `http://your-domain.com/health`
- **Horizon Dashboard**: `http://your-domain.com/horizon`

### 7. Security Checklist

- [ ] Change default admin password
- [ ] Configure firewall rules
- [ ] Enable SSL/TLS
- [ ] Set up regular backups
- [ ] Configure monitoring
- [ ] Review file permissions
- [ ] Enable rate limiting
- [ ] Configure CORS properly
- [ ] Set up intrusion detection
- [ ] Enable audit logging

## Troubleshooting

### Common Installation Issues

**Issue: Permission denied errors**

```bash
sudo chown -R www-data:www-data /var/www/lms
sudo chmod -R 775 storage bootstrap/cache
```

**Issue: Database connection failed**

```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u username -p -h localhost database_name

# Verify .env database credentials
```

**Issue: Redis connection failed**

```bash
# Check Redis is running
sudo systemctl status redis

# Test connection
redis-cli ping

# Should return PONG
```

**Issue: Queue not processing**

```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart lms-worker:*

# Check logs
tail -f storage/logs/worker.log
```

**Issue: Assets not loading**

```bash
# Rebuild assets
npm run build

# Clear cache
php artisan cache:clear
php artisan view:clear

# Check storage link
php artisan storage:link
```

**Issue: 500 Internal Server Error**

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Check PHP-FPM logs
sudo tail -f /var/log/php8.2-fpm.log
```

## Updating the Application

### Update Process

```bash
# 1. Backup database and files
php artisan backup:run

# 2. Enable maintenance mode
php artisan down

# 3. Pull latest code
git pull origin main

# 4. Update dependencies
composer install --no-dev --optimize-autoloader
npm ci --production

# 5. Run migrations
php artisan migrate --force

# 6. Build assets
npm run build

# 7. Clear and cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 8. Restart services
php artisan queue:restart
sudo systemctl reload php8.2-fpm

# 9. Disable maintenance mode
php artisan up
```

## Uninstallation

### Complete Removal

```bash
# 1. Stop services
sudo supervisorctl stop lms-worker:*
sudo systemctl stop nginx

# 2. Remove application files
sudo rm -rf /var/www/lms

# 3. Remove database
mysql -u root -p
DROP DATABASE corporate_lms;
EXIT;

# 4. Remove Nginx configuration
sudo rm /etc/nginx/sites-enabled/lms
sudo rm /etc/nginx/sites-available/lms
sudo systemctl reload nginx

# 5. Remove supervisor configuration
sudo rm /etc/supervisor/conf.d/lms-worker.conf
sudo supervisorctl reread
sudo supervisorctl update

# 6. Remove cron jobs
sudo crontab -e -u www-data
# Remove LMS-related entries
```

## Support

For installation support:

- **Documentation**: https://docs.yourcompany.com/lms
- **Email**: support@yourcompany.com
- **Issue Tracker**: https://github.com/yourcompany/corporate-lms/issues

## License

This software is proprietary and confidential. Unauthorized copying, distribution, or use is strictly prohibited.

---

**Installation Guide Version**: 1.0.0  
**Last Updated**: 2024-01-17
