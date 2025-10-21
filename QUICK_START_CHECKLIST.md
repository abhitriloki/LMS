# Quick Start Checklist ✅

## Before You Start
- [ ] WAMPP installed and running (Apache + MySQL green)
- [ ] Internet connection working

## Installation (30 minutes)

### 1. Install Tools
- [ ] Install Composer from https://getcomposer.org/Composer-Setup.exe
- [ ] Install Node.js from https://nodejs.org/
- [ ] Restart computer

### 2. Setup Project
```cmd
cd C:\wamp64\www\corporate-lms
composer install
npm install
copy .env.example .env
php artisan key:generate
```

### 3. Create Database
- [ ] Go to http://localhost/phpmyadmin
- [ ] Create database: `corporate_lms`
- [ ] Collation: `utf8mb4_unicode_ci`

### 4. Setup Database
```cmd
php artisan migrate
php artisan db:seed
npm run build
```

### 5. Create Admin
```cmd
php artisan tinker
```
Then paste:
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

### 6. Start Server
```cmd
php artisan serve
```

### 7. Login
- [ ] Open: http://localhost:8000
- [ ] Email: `admin@test.com`
- [ ] Password: `password123`

## ✅ Success!
Your LMS is running!

---

## Your Credentials

**Localhost:**
- URL: http://localhost:8000
- Email: admin@test.com
- Password: password123

**Database:**
- URL: http://localhost/phpmyadmin
- Username: root
- Password: (empty)
- Database: corporate_lms

**Gemini API:**
- Key: AIzaSyC1iE1CwHHY0O-wt7Ah7AT26pwVoqIOUxg
- Already configured in .env file

---

## Common Commands

**Start server:**
```cmd
cd C:\wamp64\www\corporate-lms
php artisan serve
```

**Stop server:**
Press `Ctrl + C` in Command Prompt

**Clear cache:**
```cmd
php artisan cache:clear
php artisan config:clear
```

**Reset database:**
```cmd
php artisan migrate:fresh --seed
```

---

## Need Help?

1. Check `COMPLETE_BEGINNER_SETUP_GUIDE.md` for detailed steps
2. Check `storage/logs/laravel.log` for errors
3. Make sure WAMPP is running (green lights)
4. Try restarting Command Prompt

---

## Next Steps

After it's working locally:

1. ✅ Test all features
2. ✅ Create sample courses
3. ✅ Add test users
4. ✅ Deploy to DigitalOcean (see guide)

---

**Quick tip**: Keep Command Prompt open while using the app!
