# Emergency Fix - No CSS Loading

## Issue
Pages load but have NO styling - looks like plain HTML

## Quick Fix Steps

### Step 1: Stop Both Servers
Close both terminal windows (or press Ctrl+C in each)

### Step 2: Clear Everything
Run these commands:
```bash
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
php artisan config:clear
```

### Step 3: Delete Build Folder
```bash
rmdir /s /q public\build
```

### Step 4: Rebuild Assets
```bash
npm run build
```

Wait for it to complete.

### Step 5: Start Servers Again
```bash
start-dev-servers.bat
```

### Step 6: Hard Refresh Browser
Press `Ctrl + Shift + F5` (super hard refresh)

## If Still Not Working

Check Vite terminal for errors. Common issues:

1. **Port 5173 already in use**
   - Close other Vite instances
   - Restart computer

2. **Node modules issue**
   ```bash
   rmdir /s /q node_modules
   npm install
   npm run build
   ```

3. **Vite config issue**
   - Check if Vite terminal shows errors
   - Share the error message

