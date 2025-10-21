# 🚀 Final Gemini CLI Setup Prompt for Corporate LMS

**COPY AND PASTE THIS ENTIRE PROMPT TO GEMINI CLI:**

---
cdcd
## 🚀 AUTOMATED SETUP PROMPT START

I need you to automatically set up a Laravel 11 Corporate LMS project on my Windows machine with WAMPP. Execute all commands and handle any issues that arise. Here are my specific requirements:

**My System Configuration:**
- OS: Windows with WAMPP
- WAMPP Location: C:\wamp64
- **WAMPP runs on PORT 8080** (not default 80)
- PHP Location: C:\wamp64\bin\php\php8.2.13\php.exe (adjust version if different)
- Project Location: C:\wamp64\www\corporate-lms
- **My Gemini API Key:** AIzaSyC1iE1CwHHY0O-wt7Ah7AT26pwVoqIOUxg

**⚠️ IMPORTANT: My WAMPP runs on port 8080, so phpMyAdmin is at http://localhost:8080/phpmyadmin**

**Execute These Tasks Automatically:**

### 1. Pre-Setup Checks
- Check if WAMPP Apache and MySQL are running
- Verify PHP is accessible at C:\wamp64\bin\php\php8.2.13\php.exe
- Check if Composer is installed (`composer --version`)
- Check if Node.js is installed (`node --version` and `npm --version`)

### 2. Install Missing Tools (if needed)
- If Composer missing: Download from https://getcomposer.org/Composer-Setup.exe and install
- If Node.js missing: Download from https://nodejs.org/ and install
- Guide me through installations if needed

### 3. Project Setup
```cmd
cd C:\wamp64\www\corporate-lms
composer install
npm install
copy .env.example .env
php artisan key:generate
```

### 4. Configure Environment File
Update the .env file with these EXACT settings:
```env
APP_NAME="Corporate LMS"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=corporate_lms
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

# My Gemini API Key - MUST BE INCLUDED
GEMINI_API_KEY=AIzaSyC1iE1CwHHY0O-wt7Ah7AT26pwVoqIOUxg

# Leave OpenAI empty for now
OPENAI_API_KEY=
```

### 5. Database Setup
- Access phpMyAdmin at **http://localhost:8080/phpmyadmin** (NOTE: PORT 8080)
- Create database: `corporate_lms`
- Collation: `utf8mb4_unicode_ci`
- Or use MySQL command line if phpMyAdmin doesn't work

### 6. Database Migration and Seeding
```cmd
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 7. Build Frontend Assets
```cmd
npm run build
```

### 8. Create Admin User
```cmd
php artisan tinker
```
Then execute:
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

### 9. Start Development Server
```cmd
php artisan serve
```

### 10. Test AI Integration
After the server starts, test that Gemini API is working by:
- Accessing the admin panel
- Testing AI features (if available)
- Confirming API key is properly configured

**CRITICAL REQUIREMENTS:**

1. **Handle WAMPP Port 8080**: Remember my WAMPP runs on port 8080, not 80
2. **Include Gemini API**: MUST configure my API key: AIzaSyC1iE1CwHHY0O-wt7Ah7AT26pwVoqIOUxg
3. **Auto-Fix Issues**: If any command fails, troubleshoot and fix automatically
4. **Show Progress**: Display output of each command
5. **Final Verification**: Confirm everything is working

**Expected Final Result:**
- ✅ LMS running at http://localhost:8000
- ✅ Database created and populated
- ✅ Admin login: admin@test.com / password123
- ✅ Gemini API integrated and working
- ✅ All dependencies installed
- ✅ No errors in console
- ✅ AI features functional

**If You Encounter Issues:**
- Try alternative solutions
- Check WAMPP is running on port 8080
- Verify file permissions
- Clear caches if needed
- Restart services if required

**Final Deliverables:**
Provide me with:
1. ✅ Success confirmation
2. 🌐 Login URL: http://localhost:8000
3. 👤 Admin credentials: admin@test.com / password123
4. 🤖 Confirmation that Gemini API is working
5. 📋 Summary of what was installed/configured
6. ⚠️ Any issues encountered and how they were resolved

**START EXECUTION NOW - Handle everything automatically and show me the progress!**

## 🚀 AUTOMATED SETUP PROMPT END

---

# Alternative: Simplified Prompt (If Above is Too Long)

If the above prompt is too long, use this shorter version:

---

## SIMPLIFIED PROMPT START

Set up this Laravel project on Windows with WAMPP:

**Project Path:** C:\wamp64\www\corporate-lms
**PHP Path:** C:\wamp64\bin\php\php8.2.13\php.exe

**Execute these commands in order:**

```cmd
cd C:\wamp64\www\corporate-lms
composer install
npm install
copy .env.example .env
php artisan key:generate
```

**Then create database:**
```sql
CREATE DATABASE corporate_lms;
```

**Then continue:**
```cmd
php artisan migrate
php artisan db:seed
php artisan storage:link
npm run build
php artisan serve
```

**Create admin user:**
```php
php artisan tinker
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@test.com';
$user->password = Hash::make('password123');
$user->role = 'super_admin';
$user->email_verified_at = now();
$user->save();
exit
```

Show me any errors and help fix them. Final goal: working app at http://localhost:8000

## SIMPLIFIED PROMPT END

---

# For Claude Desktop or Other AI with MCP

If using Claude Desktop with MCP (Model Context Protocol) for file system access:

---

## CLAUDE DESKTOP PROMPT START

I have a Laravel project at C:\wamp64\www\corporate-lms that needs to be set up. Please:

1. Check if composer.json exists in the project
2. Read the .env.example file
3. Create a .env file with appropriate local settings
4. Execute setup commands using the terminal
5. Create the database and run migrations
6. Set up an admin user
7. Start the development server

Use your file system and terminal access to complete this setup automatically. The project should run on http://localhost:8000 when complete.

## CLAUDE DESKTOP PROMPT END

---

# What to Expect

When you give this prompt to Gemini CLI or another AI assistant with code execution:

1. **It will check** if Composer and Node.js are installed
2. **It will install** all PHP and JavaScript dependencies
3. **It will configure** the environment file
4. **It will create** the database
5. **It will run** migrations and seeders
6. **It will create** an admin user
7. **It will start** the development server
8. **It will give you** the login URL and credentials

## If Something Goes Wrong

If the AI encounters errors, it should:
- Show you the error message
- Attempt to fix it automatically
- Ask for your input if manual intervention is needed
- Provide alternative solutions

## After Setup is Complete

You should receive:
- ✅ URL: http://localhost:8000
- ✅ Email: admin@test.com
- ✅ Password: password123
- ✅ Confirmation that all services are running

---

**Note:** Make sure WAMPP's Apache and MySQL services are running before giving this prompt to the AI!
