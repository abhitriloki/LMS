# Complete Beginner's Guide - Corporate LMS Setup

## 🎯 Goal
Get your Corporate LMS running on your computer, then deploy it to DigitalOcean so anyone can access it online.

---

## Part 1: Get It Running on Your Computer (Localhost)

### Step 1: Install Required Software

#### 1.1 Install Composer (PHP Package Manager)

1. **Download**: Go to https://getcomposer.org/Composer-Setup.exe
2. **Run the installer**
3. **When asked for PHP location**, browse to:
   ```
   C:\wamp64\bin\php\php8.2.13\php.exe
   ```
   (Your version number might be slightly different)
4. **Click through** until installation completes
5. **Close and reopen** Command Prompt

#### 1.2 Install Node.js (JavaScript Package Manager)

1. **Download**: Go to https://nodejs.org/
2. **Click** "Download LTS" (the green button)
3. **Run the installer**
4. **Click "Next"** through all steps
5. **Restart your computer** after installation

### Step 2: Prepare Your Project

1. **Make sure WAMPP is running**:
   - Open WAMPP
   - Start Apache (click Start)
   - Start MySQL (click Start)
   - Both should show green

2. **Open Command Prompt as Administrator**:
   - Press Windows key
   - Type "cmd"
   - Right-click "Command Prompt"
   - Click "Run as administrator"

3. **Navigate to your project**:
   ```cmd
   cd C:\wamp64\www\corporate-lms
   ```

### Step 3: Install Dependencies

Run these commands one by one:

```cmd
composer install
```
Wait for it to finish (may take 5-10 minutes)

```cmd
npm install
```
Wait for it to finish (may take 5-10 minutes)

### Step 4: Configure Environment

```cmd
copy .env.example .env
php artisan key:generate
```

### Step 5: Edit Configuration File

1. **Open the .env file**:
   - Go to `C:\wamp64\www\corporate-lms`
   - Find `.env` file
   - Right-click → Open with Notepad

2. **Find and update these lines**:

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

# Your Gemini API Key (Already configured!)
GEMINI_API_KEY=AIzaSyC1iE1CwHHY0O-wt7Ah7AT26pwVoqIOUxg

# Leave this empty for now
OPENAI_API_KEY=
```

3. **Save and close** the file

### Step 6: Create Database

1. **Open your browser**
2. **Go to**: http://localhost/phpmyadmin
3. **Click "New"** on the left sidebar
4. **Database name**: `corporate_lms`
5. **Collation**: Select `utf8mb4_unicode_ci`
6. **Click "Create"**

### Step 7: Setup Database Tables

Back in Command Prompt, run:

```cmd
php artisan migrate
```

If it asks "Do you want to create the database?", type `yes` and press Enter.

Then run:

```cmd
php artisan db:seed
```

This creates sample data and an admin user.

### Step 8: Build Frontend

```cmd
npm run build
```

Wait for it to complete (2-3 minutes).

### Step 9: Create Admin User

```cmd
php artisan tinker
```

Then copy and paste this (all at once):

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

Press Enter after pasting.

### Step 10: Start the Application

```cmd
php artisan serve
```

**Don't close this window!** Keep it running.

### Step 11: Open Your LMS

1. **Open your browser**
2. **Go to**: http://localhost:8000
3. **You should see the login page!**

### Step 12: Login

- **Email**: `admin@test.com`
- **Password**: `password123`

**🎉 Congratulations! Your LMS is running locally!**

---

## Part 2: Understanding What You Have

### What Can You Do Now?

1. **Admin Panel**: Create courses, users, categories
2. **Course Management**: Upload content, create lessons
3. **Assessment Creation**: Create quizzes and tests
4. **User Management**: Add employees, instructors
5. **AI Features**: (Will work with your Gemini API)
   - Course recommendations
   - Question generation
   - Content analysis
   - Chatbot assistant

### Important URLs

- **Homepage**: http://localhost:8000
- **Login**: http://localhost:8000/login
- **Admin Dashboard**: http://localhost:8000/admin
- **Database**: http://localhost/phpmyadmin

---

## Part 3: Deploy to DigitalOcean (Make It Live Online)

### Prerequisites

- ✅ Your LMS working on localhost (Part 1 complete)
- ✅ DigitalOcean account (you have this via GitHub Education)
- ✅ GitHub account
- ✅ Your project code

### Step 1: Push Code to GitHub

1. **Create a new repository on GitHub**:
   - Go to https://github.com/new
   - Name: `corporate-lms`
   - Make it **Private**
   - Click "Create repository"

2. **Push your code** (in Command Prompt):

```cmd
cd C:\wamp64\www\corporate-lms
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR-USERNAME/corporate-lms.git
git push -u origin main
```

Replace `YOUR-USERNAME` with your GitHub username.

### Step 2: Create DigitalOcean App

1. **Login to DigitalOcean**: https://cloud.digitalocean.com/

2. **Click "Create"** → **"Apps"**

3. **Connect GitHub**:
   - Click "GitHub"
   - Authorize DigitalOcean
   - Select your `corporate-lms` repository
   - Click "Next"

4. **Configure App**:
   - **Name**: `corporate-lms`
   - **Branch**: `main`
   - **Autodeploy**: ✅ (checked)
   - Click "Next"

5. **Edit Plan**:
   - Select **"Basic"** plan
   - Choose **$5/month** option (or free if available)
   - Click "Back"

6. **Add Database**:
   - Click "Add Resource"
   - Select "Database"
   - Choose "MySQL"
   - Name: `lms-db`
   - Plan: **$15/month** (cheapest MySQL option)
   - Click "Add Database"

7. **Environment Variables**:
   - Click on your app component
   - Scroll to "Environment Variables"
   - Click "Edit"
   - Add these variables:

```
APP_NAME=Corporate LMS
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_THIS_LATER

DB_CONNECTION=mysql
DB_HOST=${lms-db.HOSTNAME}
DB_PORT=${lms-db.PORT}
DB_DATABASE=${lms-db.DATABASE}
DB_USERNAME=${lms-db.USERNAME}
DB_PASSWORD=${lms-db.PASSWORD}

GEMINI_API_KEY=AIzaSyC1iE1CwHHY0O-wt7Ah7AT26pwVoqIOUxg

SESSION_DRIVER=database
CACHE_DRIVER=database
QUEUE_CONNECTION=database
```

8. **Click "Save"**

9. **Review and Create**:
   - Review all settings
   - Click "Create Resources"

### Step 3: Configure Build Settings

1. **After app is created**, click on it

2. **Go to "Settings"** → **"App-Level"**

3. **Build Command**:
```bash
composer install --no-dev --optimize-autoloader && npm install && npm run build && php artisan key:generate && php artisan migrate --force && php artisan db:seed --force
```

4. **Run Command**:
```bash
heroku-php-apache2 public/
```

5. **Click "Save"**

### Step 4: Add Buildpacks

1. **Create a file** in your project root: `Procfile`

```
web: vendor/bin/heroku-php-apache2 public/
```

2. **Create another file**: `composer.json` (if not exists, add this section)

```json
{
  "require": {
    "php": "^8.2"
  },
  "post-install-cmd": [
    "php artisan key:generate --ansi",
    "php artisan migrate --force",
    "php artisan db:seed --force",
    "php artisan storage:link",
    "php artisan config:cache",
    "php artisan route:cache",
    "php artisan view:cache"
  ]
}
```

3. **Push changes**:

```cmd
git add .
git commit -m "Add deployment configuration"
git push
```

### Step 5: Wait for Deployment

- DigitalOcean will automatically deploy
- This takes 5-10 minutes
- Watch the "Activity" tab for progress

### Step 6: Access Your Live App

1. **Find your URL**:
   - In DigitalOcean dashboard
   - Look for: `https://your-app-name.ondigitalocean.app`

2. **Open it in browser**

3. **Login**:
   - Email: `admin@test.com`
   - Password: `password123`

**🚀 Your LMS is now LIVE on the internet!**

---

## Troubleshooting

### Problem: "composer: command not found"

**Solution**:
1. Close Command Prompt
2. Open a NEW Command Prompt
3. Try again

### Problem: "npm: command not found"

**Solution**:
1. Restart your computer
2. Open Command Prompt
3. Try again

### Problem: Database connection error

**Solution**:
1. Make sure WAMPP MySQL is running (green light)
2. Check database name is `corporate_lms`
3. Check `.env` file has correct settings

### Problem: "Class not found" errors

**Solution**:
```cmd
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

### Problem: DigitalOcean deployment fails

**Solution**:
1. Check the "Activity" log for errors
2. Make sure all environment variables are set
3. Try redeploying: Click "Actions" → "Force Rebuild"

---

## What's Next?

### On Localhost:
1. **Create courses**: Go to Admin → Courses → Create
2. **Add users**: Go to Admin → Users → Create
3. **Test features**: Try creating assessments, enrolling users
4. **Explore AI features**: Use the chatbot, generate questions

### On DigitalOcean:
1. **Custom domain**: Add your own domain name
2. **SSL Certificate**: Enable HTTPS (automatic)
3. **Backups**: Enable automatic backups
4. **Scaling**: Upgrade plan if needed

---

## Cost Breakdown (DigitalOcean)

- **App**: $5/month (Basic plan)
- **Database**: $15/month (MySQL)
- **Total**: $20/month

**With GitHub Education**, you get $200 credit = **10 months FREE!**

---

## Support

If you get stuck:

1. **Check the error message** carefully
2. **Google the error** - usually someone else had the same issue
3. **Check Laravel logs**: `storage/logs/laravel.log`
4. **Ask for help**: Include the error message and what you were doing

---

## Summary

✅ **Part 1**: LMS running on your computer (localhost)
✅ **Part 2**: Understanding what you built
✅ **Part 3**: LMS live on the internet (DigitalOcean)

**You now have a fully functional Learning Management System with AI features!**

---

**Created**: 2024-01-17
**Version**: 1.0
**For**: Non-technical users
