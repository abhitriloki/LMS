# ⚡ Kiro Quick Setup - Copy & Paste This!

## 🎯 Quick Start (3 Steps)

### Step 1: Open Folder in Kiro
```
File → Open Folder → C:\wamp64\www\corporate-lms
```

### Step 2: Open Kiro Chat
```
Press Ctrl+L or click the chat icon
```

### Step 3: Paste This Prompt
Copy everything below the line and paste into Kiro chat:

---

## 📋 COPY FROM HERE ⬇️

I need you to automatically set up this Laravel 11 Corporate LMS project on my Windows machine with WAMPP. Execute all commands and handle any issues that arise.

MY SYSTEM CONFIGURATION:
- OS: Windows with WAMPP
- WAMPP Location: C:\wamp64
- WAMPP runs on PORT 8080 (not default 80)
- PHP Location: C:\wamp64\bin\php\php8.2.13\php.exe
- Project Location: C:\wamp64\www\corporate-lms (CURRENT WORKSPACE)
- My Gemini API Key: AIzaSyC1iE1CwHHY0O-wt7Ah7AT26pwVoqIOUxg
- phpMyAdmin URL: http://localhost:8080/phpmyadmin

EXECUTE THESE TASKS AUTOMATICALLY:

1. PRE-SETUP VERIFICATION
   - Verify we're in the correct workspace (C:\wamp64\www\corporate-lms)
   - Check if composer.json exists
   - Check if package.json exists
   - Verify .env.example exists

2. INSTALL DEPENDENCIES
   - Run: composer install
   - If it fails, try: composer install --ignore-platform-reqs
   - Run: npm install

3. ENVIRONMENT CONFIGURATION
   - Copy .env.example to .env
   - Run: php artisan key:generate
   - Update .env file with these EXACT settings:
     * APP_NAME="Corporate LMS"
     * APP_ENV=local
     * APP_DEBUG=true
     * APP_URL=http://localhost:8000
     * DB_CONNECTION=mysql
     * DB_HOST=127.0.0.1
     * DB_PORT=3306
     * DB_DATABASE=corporate_lms
     * DB_USERNAME=root
     * DB_PASSWORD= (leave empty)
     * GEMINI_API_KEY=AIzaSyC1iE1CwHHY0O-wt7Ah7AT26pwVoqIOUxg
     * OPENAI_API_KEY= (leave empty)

4. DATABASE SETUP
   - Guide me to create the database 'corporate_lms' in phpMyAdmin at http://localhost:8080/phpmyadmin
   - Or provide MySQL command: CREATE DATABASE corporate_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   - Wait for my confirmation that database is created

5. RUN MIGRATIONS AND SEEDERS
   - Run: php artisan migrate
   - Run: php artisan db:seed
   - Run: php artisan storage:link

6. BUILD FRONTEND ASSETS
   - Run: npm run build

7. CREATE ADMIN USER
   - Run: php artisan tinker
   - Execute this code:
     $user = new App\Models\User();
     $user->name = 'Admin User';
     $user->email = 'admin@test.com';
     $user->password = Hash::make('password123');
     $user->role = 'super_admin';
     $user->email_verified_at = now();
     $user->save();
     exit

8. FINAL VERIFICATION
   - Confirm all steps completed successfully
   - Provide me with:
     * Login URL: http://localhost:8000
     * Admin Email: admin@test.com
     * Admin Password: password123
     * Gemini API Status: Configured
     * Any errors or warnings encountered

IMPORTANT REQUIREMENTS:
- Execute commands one by one and show me the output
- If any command fails, troubleshoot and try alternative solutions
- Handle WAMPP port 8080 configuration
- Ensure Gemini API key is properly configured
- Stop and ask me if you need manual intervention (like database creation)
- Provide clear status updates for each step

After completing all steps, tell me:
"✅ Setup Complete! Your Corporate LMS is ready at http://localhost:8000
   Login with: admin@test.com / password123
   Gemini AI is configured and ready to use!"

START EXECUTION NOW!

## 📋 COPY UNTIL HERE ⬆️

---

## 🎯 What Happens Next?

Kiro will:
1. ✅ Install all dependencies
2. ✅ Configure environment with your Gemini API key
3. ✅ Ask you to create database (takes 30 seconds)
4. ✅ Run migrations and seeders
5. ✅ Build frontend assets
6. ✅ Create admin user
7. ✅ Give you the login URL and credentials

**Total Time: 5-10 minutes**

---

## 🗄️ When Kiro Asks About Database:

1. Open: `http://localhost:8080/phpmyadmin`
2. Click "New" (left sidebar)
3. Name: `corporate_lms`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Tell Kiro: "Database created, continue"

---

## ✅ Success = You'll See:

```
✅ Setup Complete! Your Corporate LMS is ready at http://localhost:8000
   Login with: admin@test.com / password123
   Gemini AI is configured and ready to use!
```

---

## 🚀 After Setup:

**Start Server:**
```bash
php artisan serve
```

**Access LMS:**
```
http://localhost:8000
admin@test.com / password123
```

---

**That's it! Let Kiro do all the work!** 🎉
