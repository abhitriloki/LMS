# 🚀 Corporate LMS - Automated Setup with Kiro

**Get your LMS running in 8-15 minutes with Kiro automation!**

---

## ⚡ Quick Start (3 Steps)

### 1️⃣ Open Project in Kiro
- Launch Kiro IDE
- File → Open Folder
- Select: `C:\wamp64\www\corporate-lms`

### 2️⃣ Open Kiro Chat
- Press `Ctrl+L`
- Or click the chat icon

### 3️⃣ Paste This Prompt

Copy everything below and paste into Kiro chat:

```
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
```

---

## 🎯 What Happens Next?

Kiro will automatically:
1. ✅ Install all dependencies (2-5 min)
2. ✅ Configure environment with your Gemini API key (30 sec)
3. ⚠️ Ask you to create database (30 sec - **only manual step**)
4. ✅ Run migrations and seeders (1-2 min)
5. ✅ Build frontend assets (1-3 min)
6. ✅ Create admin user (30 sec)
7. ✅ Verify everything works (10 sec)

**Total Time:** 8-15 minutes
**Your Time:** 3 minutes

---

## 🗄️ When Kiro Asks About Database

1. Open: `http://localhost:8080/phpmyadmin`
2. Click "New" (left sidebar)
3. Database name: `corporate_lms`
4. Collation: `utf8mb4_unicode_ci`
5. Click "Create"
6. Tell Kiro: "Database created, continue"

---

## ✅ Success!

When setup is complete, you'll see:

```
✅ Setup Complete! Your Corporate LMS is ready at http://localhost:8000
   Login with: admin@test.com / password123
   Gemini AI is configured and ready to use!
```

---

## 🚀 Start Using Your LMS

### Start the Server:
```bash
php artisan serve
```

### Access Your LMS:
- URL: `http://localhost:8000`
- Email: `admin@test.com`
- Password: `password123`

---

## 📚 More Detailed Guides

Want more information? Check these files:

- **START_HERE.md** - Main entry point with all options
- **KIRO_QUICK_SETUP.md** - This guide in more detail
- **KIRO_AUTOMATED_SETUP_GUIDE.md** - Comprehensive guide
- **KIRO_SETUP_FLOWCHART.md** - Visual flowchart
- **SETUP_OPTIONS_SUMMARY.md** - Compare all setup methods

---

## 🆘 Need Help?

- **During setup:** Ask Kiro "What went wrong?"
- **After setup:** Check `docs/USER_GUIDE_*.md`
- **For development:** Check `docs/DEVELOPER_GUIDE.md`

---

**Ready? Copy the prompt above and paste it into Kiro chat!** 🚀
