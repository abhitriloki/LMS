# Project Setup Guide

## Quick Start

This Laravel 11 project has been initialized with all necessary configuration files.

### Required Software

1. **PHP 8.2+** - Download from https://www.php.net/downloads
2. **Composer** - Download from https://getcomposer.org/download/
3. **Node.js & npm** - Download from https://nodejs.org/
4. **MySQL 8.0+** - Download from https://dev.mysql.com/downloads/
5. **Redis** - Download from https://redis.io/download

### Installation Steps

```bash
# 1. Install PHP dependencies
composer install

# 2. Install Node.js dependencies
npm install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure database in .env file
# Edit DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 6. Run migrations
php artisan migrate

# 7. Seed database (optional)
php artisan db:seed

# 8. Create storage link
php artisan storage:link

# 9. Build frontend assets
npm run build

# 10. Start development server
php artisan serve
```

### Default Users (after seeding)

- Admin: admin@example.com / password
- Instructor: instructor@example.com / password
- Employee: employee@example.com / password

## Next Steps

Proceed to Task 2 in the implementation plan to create database schema and migrations.
