# ✅ Development Checklist - Corporate LMS

## 🚀 Starting Development

### Every Time You Start Working

```bash
# 1. Start Laravel Server (Terminal 1)
php artisan serve

# 2. Start Vite Dev Server (Terminal 2)
npm run dev

# 3. Open Browser
http://localhost:8000
```

**✅ Both servers MUST be running!**

---

## ⚠️ IMPORTANT RULES

### ✅ DO THIS

- ✅ Use `npm run dev` for development
- ✅ Keep both servers running while working
- ✅ Use Ctrl+F5 to hard refresh browser
- ✅ Check both terminals are still running

### ❌ DON'T DO THIS

- ❌ **NEVER** run `npm run build` during development
- ❌ Don't close terminal windows while working
- ❌ Don't use production build for development
- ❌ Don't commit `public/build/` folder to git

---

## 🔧 Common Issues & Quick Fixes

### Issue: Pages Look Broken (No Styling)

**Quick Fix:**
```bash
# 1. Delete build folder
cmd /c "rmdir /s /q public\build"

# 2. Restart Vite
# Press Ctrl+C in Vite terminal
npm run dev

# 3. Hard refresh browser
# Press Ctrl+F5
```

### Issue: Changes Not Showing

**Quick Fix:**
```bash
# 1. Check Vite is running
# Look for: "VITE v5.4.20 ready"

# 2. If not running:
npm run dev

# 3. Hard refresh browser
# Press Ctrl+F5
```

### Issue: 403 Unauthorized

**Quick Fix:**
```bash
# 1. Clear caches
php artisan optimize:clear

# 2. Check you're logged in as correct user
# Instructor: amit.kumar@gatech.com
# Admin: admin@test.com
```

### Issue: Blank Pages

**Quick Fix:**
```bash
# 1. Make sure Vite is running
npm run dev

# 2. Delete build folder
cmd /c "rmdir /s /q public\build"

# 3. Clear caches
php artisan config:clear
php artisan view:clear
```

---

## 📊 How to Check Everything is Working

### ✅ Checklist

- [ ] Laravel server running on http://localhost:8000
- [ ] Vite server running on http://localhost:5173
- [ ] Browser shows styled pages (not plain HTML)
- [ ] Sidebar is collapsible
- [ ] Dark mode toggle works
- [ ] Forms are styled
- [ ] No errors in browser console (F12)

### Terminal Output Should Show

**Terminal 1 (Laravel):**
```
Starting Laravel development server: http://127.0.0.1:8000
[Tue Oct 22 09:00:00 2025] PHP 8.2.26 Development Server
```

**Terminal 2 (Vite):**
```
VITE v5.4.20  ready in 441 ms
➜  Local:   http://localhost:5173/
LARAVEL v11.46.1  plugin v1.3.0
➜  APP_URL: http://localhost:8000
```

---

## 🎯 Quick Commands Reference

### Starting Servers
```bash
php artisan serve          # Start Laravel
npm run dev               # Start Vite (development)
```

### Stopping Servers
```bash
Ctrl+C                    # Stop server in terminal
```

### Clearing Caches
```bash
php artisan optimize:clear    # Clear all caches
php artisan config:clear      # Clear config cache
php artisan view:clear        # Clear view cache
php artisan route:clear       # Clear route cache
```

### Emergency Reset
```bash
# If everything is broken:
cmd /c "rmdir /s /q public\build"
php artisan optimize:clear
npm run dev
php artisan serve
```

---

## 📝 Daily Workflow

### Morning (Start Work)
1. Open 2 terminals
2. Terminal 1: `php artisan serve`
3. Terminal 2: `npm run dev`
4. Open http://localhost:8000
5. Start coding!

### During Work
- Keep both terminals open
- Edit files normally
- Vite auto-reloads changes
- No need to restart

### Evening (End Work)
1. Press Ctrl+C in both terminals
2. Close terminals
3. That's it!

---

## 🚨 Emergency Contacts

### If Nothing Works

1. **Stop everything** (Ctrl+C in all terminals)
2. **Delete build folder**: `cmd /c "rmdir /s /q public\build"`
3. **Clear all caches**: `php artisan optimize:clear`
4. **Restart Laravel**: `php artisan serve`
5. **Restart Vite**: `npm run dev`
6. **Hard refresh browser**: Ctrl+F5

### Still Not Working?

Check these files exist:
- `resources/css/app.css`
- `resources/js/app.js`
- `vite.config.js`
- `package.json`

Run:
```bash
npm install  # Reinstall dependencies
npm run dev  # Start Vite
```

---

## ✨ Remember

### For Development (Daily Use)
```bash
npm run dev  ← Use this! ✅
```

### For Production (Deployment Only)
```bash
npm run build  ← Only for production! ⚠️
```

**Don't mix them up!**

---

## 🎊 You're All Set!

Keep this checklist handy and refer to it whenever you start development or encounter issues.

**Happy Coding! 🚀**
