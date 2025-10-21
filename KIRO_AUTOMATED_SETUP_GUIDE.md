# 🤖 Kiro Automated Setup Guide for Corporate LMS

This guide will help you use Kiro to automatically set up your Corporate LMS project in your WAMPP server folder.

---

## 📋 Prerequisites

Before starting, make sure you have:

1. ✅ WAMPP installed at `C:\wamp64`
2. ✅ WAMPP running on port 8080
3. ✅ Apache and MySQL services running in WAMPP
4. ✅ Your project files copied to `C:\wamp64\www\corporate-lms`
5. ✅ Composer installed on your system
6. ✅ Node.js and npm installed on your system

---

## 🚀 Step-by-Step Instructions

### Step 1: Open the Project Folder in Kiro

1. **Launch Kiro IDE**
2. **Click on "File" → "Open Folder"** (or press `Ctrl+K Ctrl+O`)
3. **Navigate to:** `C:\wamp64\www\corporate-lms`
4. **Click "Select Folder"**
5. **Wait for Kiro to index the project** (you'll see a progress indicator)

### Step 2: Open Kiro Chat

1. **Open the Kiro chat panel** (usually on the right side or press `Ctrl+L`)
2. **Make sure you're in a new chat session**

### Step 3: Copy and Paste the Automated Setup Prompt

Copy the entire prompt below and paste it into Kiro chat:

---

## 🤖 KIRO AUTOMATED SETUP PROMPT

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

## 📝 What Kiro Will Do

Kiro will automatically:

1. ✅ Verify your project structure
2. ✅ Install all PHP dependencies (Composer)
3. ✅ Install all JavaScript dependencies (npm)
4. ✅ Create and configure your .env file
5. ✅ Generate application key
6. ✅ Configure your Gemini API key
7. ✅ Guide you through database creation
8. ✅ Run database migrations
9. ✅ Seed the database with initial data
10. ✅ Create storage symlink
11. ✅ Build frontend assets
12. ✅ Create an admin user account
13. ✅ Verify everything is working

---

## 🎯 Expected Timeline

- **Total Time:** 5-10 minutes (depending on your internet speed)
- **Your Involvement:** Minimal (just create the database when prompted)

---

## 🔧 Troubleshooting

### If Kiro asks about database creation:

1. Open your browser
2. Go to: `http://localhost:8080/phpmyadmin`
3. Click "New" in the left sidebar
4. Database name: `corporate_lms`
5. Collation: `utf8mb4_unicode_ci`
6. Click "Create"
7. Tell Kiro: "Database created, please continue"

### If Composer fails:

Kiro will automatically try: `composer install --ignore-platform-reqs`

### If npm fails:

Kiro will suggest: `npm install --legacy-peer-deps`

### If migrations fail:

Kiro will check:
- Database connection
- Database exists
- Credentials are correct

---

## ✅ Success Indicators

You'll know setup is complete when you see:

```
✅ Setup Complete! Your Corporate LMS is ready at http://localhost:8000
   Login with: admin@test.com / password123
   Gemini AI is configured and ready to use!
```

---

## 🚀 After Setup

1. **Start the development server:**
   - Kiro will guide you to run: `php artisan serve`
   - Or you can run it manually in your terminal

2. **Access your LMS:**
   - Open browser: `http://localhost:8000`
   - Login with: `admin@test.com` / `password123`

3. **Test AI Features:**
   - Navigate to AI features in the admin panel
   - Verify Gemini API is working

---

## 💡 Pro Tips

1. **Keep WAMPP Running:** Make sure Apache and MySQL are always running
2. **Port 8080:** Remember your WAMPP uses port 8080, not 80
3. **Terminal Access:** Keep a terminal open to see command outputs
4. **Kiro Chat:** Keep the chat open to see progress updates
5. **Save Credentials:** Save your admin credentials somewhere safe

---

## 🆘 Need Help?

If something goes wrong:

1. **Check WAMPP Status:** Make sure Apache and MySQL are running
2. **Check Kiro Output:** Read the error messages carefully
3. **Ask Kiro:** Type "What went wrong?" in the chat
4. **Manual Fallback:** Use the `GEMINI_CLI_SETUP_PROMPT.md` for manual setup

---

## 📚 Additional Resources

- **Full Manual Setup:** See `COMPLETE_BEGINNER_SETUP_GUIDE.md`
- **Gemini CLI Setup:** See `GEMINI_CLI_SETUP_PROMPT.md`
- **Developer Guide:** See `docs/DEVELOPER_GUIDE.md`
- **Installation Guide:** See `docs/INSTALLATION_GUIDE.md`

---

**Ready to start? Follow Step 1 above and let Kiro do the work!** 🚀
