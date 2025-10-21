# Testing Corporate LMS on Localhost with WAMPP

This guide will help you set up and test the Corporate LMS project on your local machine using WAMPP.

## Prerequisites

### What You Need

1. **WAMPP** (already installed)
2. **Composer** - PHP dependency manager
3. **Node.js & NPM** - For frontend assets
4. **Git** (optional) - If cloning from repository

## Step-by-Step Setup

### Step 1: Check WAMPP Requirements

1. **Start WAMPP** and ensure these services are running:
   - Apache (Web Server)
   - MySQL (Database)

2. **Verify PHP Version**:
   - Open WAMPP control panel
   - Click on "PHP" → "Version"
   - Ensure you have **PHP 8.2 or higher**
   - If not, update PHP in WAMPP settings

3. **Check PHP Extensions**:
   - Go to WAMPP PHP folder: `C:\wamp64\bin\php\php8.x.x\`
   - Open `php.ini` file
   - Ensure these extensions are enabled (remove `;` if present):
     ```ini
     extension=curl
     extension=fileinfo
     extension=gd
     extension=mbstring
     extension=openssl
     extension=pdo_mysql
     extension=zip
     ```
   - Save and restart Apache

### Step 2: Install Composer

**If you get "composer is not recognized" error, follow this guide first:**
👉 **[Composer Installation Guide for Windows](COMPOSER_INSTALLATION_WINDOWS.md)**

Quick steps:
1. Download Composer from: https://getcomposer.org/Composer-Setup.exe
2. Run the installer
3. Point it to your WAMPP PHP folder: `C:\wamp64\bin\php\php8.2.x\php.exe`
4. Complete installation
5. **Close and reopen Command Prompt**
6. Verify installation:
   ```cmd
   composer --version
   ```

**Important**: You MUST open a NEW Command Prompt after installing Composer!

### Step 3: Install Node.js

1. Download Node.js from: https://nodejs.org/ (LTS version)
2. Run the installer
3. Verify installation:
   ```cmd
   node --version
   npm --version
   ```

### Step 4: Place Project in WAMPP Directory

1. Copy your project folder to WAMPP's www directory:
   ```
   C:\wamp64\www\corporate-lms\
   ```

2. Open Command Prompt as Administrator

3. Navigate to project directory:
   ```cmd
   cd C:\wamp64\www\corporate-lms
   ```

### Step 5: Install Project Dependencies

1. **Install PHP dependencies**:
   ```cmd
   composer install
   ```
   
   This will take a few minutes. Wait for it to complete.

2. **Install Node.js dependencies**:
   ```cmd
   npm install
   ```

### Step 6: Configure Environment

1. **Copy environment file**:
   ```cmd
   copy .env.example .env
   ```

2. **Generate application key**:
   ```cmd
   php artisan key:generate
   ```

3. **Edit .env file**:
   - Open `.env` file with Notepad or any text editor
   - Update these settings:

   ```env
   APP_NAME="Corporate LMS"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost/corporate-lms/public

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=corporate_lms
   DB_USERNAME=root
   DB_PASSWORD=

   CACHE_DRIVER=file
   QUEUE_CONNECTION=sync
   SESSION_DRIVER=file

   # For now, leave AI features disabled or use dummy values
   OPENAI_API_KEY=sk-dummy-key-for-testing
   ```

   **Note**: Leave `DB_PASSWORD` empty if you haven't set a MySQL password in WAMPP.

### Step 7: Create Database

1. **Open phpMyAdmin**:
   - Go to: http://localhost/phpmyadmin
   - Or click "phpMyAdmin" in WAMPP control panel

2. **Create new database**:
   - Click "New" in the left sidebar
   - Database name: `corporate_lms`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

### Step 8: Run Database Migrations

1. **Run migrations** (creates all tables):
   ```cmd
   php artisan migrate
   ```

2. **Seed database with sample data** (optional but recommended):
   ```cmd
   php artisan db:seed
   ```

   This creates:
   - Sample admin user
   - Categories
   - Departments
   - Sample courses (if available)

### Step 9: Create Storage Link

```cmd
php artisan storage:link
```

This creates a symbolic link for file uploads.

### Step 10: Build Frontend Assets

```cmd
npm run dev
```

Or for production build:
```cmd
npm run build
```

### Step 11: Start the Application

You have two options:

#### Option A: Use Laravel's Built-in Server (Recommended for Testing)

```cmd
php artisan serve
```

Then open your browser and go to:
```
http://localhost:8000
```

#### Option B: Use WAMPP Apache

1. Configure Apache virtual host (optional):
   - Open: `C:\wamp64\bin\apache\apache2.x.x\conf\extra\httpd-vhosts.conf`
   - Add:
     ```apache
     <VirtualHost *:80>
         DocumentRoot "C:/wamp64/www/corporate-lms/public"
         ServerName lms.local
         <Directory "C:/wamp64/www/corporate-lms/public">
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```

2. Edit hosts file:
   - Open: `C:\Windows\System32\drivers\etc\hosts` (as Administrator)
   - Add: `127.0.0.1 lms.local`

3. Restart Apache in WAMPP

4. Access: http://lms.local

**OR** simply access via:
```
http://localhost/corporate-lms/public
```

### Step 12: Create Admin User

If seeding didn't create an admin user, create one manually:

```cmd
php artisan tinker
```

Then type:
```php
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@test.com';
$user->password = Hash::make('password123');
$user->role = 'super_admin';
$user->email_verified_at = now();
$user->save();
exit
```

### Step 13: Login and Test

1. **Open the application** in your browser

2. **Login with**:
   - Email: `admin@test.com`
   - Password: `password123`

3. **Test basic features**:
   - Browse dashboard
   - Create a category
   - Create a course
   - Create a user
   - Test enrollment

## Common Issues and Solutions

### Issue 1: "Class not found" errors

**Solution**:
```cmd
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Issue 2: Permission errors on storage folder

**Solution**:
```cmd
# In Command Prompt (as Administrator)
icacls "C:\wamp64\www\corporate-lms\storage" /grant Everyone:(OI)(CI)F /T
icacls "C:\wamp64\www\corporate-lms\bootstrap\cache" /grant Everyone:(OI)(CI)F /T
```

### Issue 3: Database connection error

**Solution**:
- Verify MySQL is running in WAMPP
- Check database name in `.env` matches phpMyAdmin
- Verify username is `root` and password is empty (or your WAMPP MySQL password)

### Issue 4: "Mix manifest not found"

**Solution**:
```cmd
npm run dev
```

### Issue 5: OpenAI API errors

**Solution**:
For testing without OpenAI, you can disable AI features:
- In `.env`, set: `OPENAI_API_KEY=sk-dummy-key`
- AI features will show errors, but core LMS features will work

### Issue 6: Port 80 already in use

**Solution**:
- Use Laravel's built-in server instead: `php artisan serve`
- Or change WAMPP Apache port in WAMPP settings

### Issue 7: Composer install fails

**Solution**:
```cmd
# Update Composer
composer self-update

# Clear Composer cache
composer clear-cache

# Try again
composer install --no-scripts
composer install
```

## Testing Checklist

Once everything is set up, test these features:

- [ ] Login page loads
- [ ] Can login with admin credentials
- [ ] Dashboard displays
- [ ] Can create a category
- [ ] Can create a course
- [ ] Can create a user
- [ ] Can enroll user in course
- [ ] Can view course catalog
- [ ] Can upload files (if storage is configured)

## Simplified Testing (Without AI Features)

If you want to test without setting up AI services:

1. In `.env`, set:
   ```env
   OPENAI_API_KEY=sk-dummy-key-for-testing
   ```

2. Avoid using these features during testing:
   - AI Course Recommendations
   - AI Question Generator
   - AI Content Analyzer
   - AI Chatbot
   - AI Auto-Grading

3. Focus on testing:
   - User management
   - Course creation
   - Manual enrollment
   - Content upload
   - Manual assessments
   - Basic reporting

## Quick Start Commands Summary

```cmd
# Navigate to project
cd C:\wamp64\www\corporate-lms

# Install dependencies
composer install
npm install

# Setup environment
copy .env.example .env
php artisan key:generate

# Setup database (after creating it in phpMyAdmin)
php artisan migrate
php artisan db:seed

# Build assets
npm run dev

# Start server
php artisan serve
```

Then open: http://localhost:8000

## Getting Help

If you encounter issues:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Apache error logs in WAMPP
3. Enable debug mode in `.env`: `APP_DEBUG=true`
4. Clear all caches:
   ```cmd
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   php artisan view:clear
   ```

## Next Steps

Once the basic system is working:

1. Explore the admin panel
2. Create sample courses
3. Test enrollment workflows
4. Review the user guides in `docs/` folder
5. Set up OpenAI API key for AI features (optional)

---

**Happy Testing!** 🚀

For detailed documentation, see:
- [Installation Guide](INSTALLATION_GUIDE.md)
- [Developer Guide](DEVELOPER_GUIDE.md)
- [User Guides](README.md)
