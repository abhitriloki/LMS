@echo off
echo ========================================
echo  EMERGENCY FIX - Rebuilding Everything
echo ========================================
echo.
echo This will:
echo 1. Clear all caches
echo 2. Delete build folder
echo 3. Rebuild assets
echo 4. Restart servers
echo.
echo Press any key to continue...
pause > nul

echo.
echo Step 1: Clearing Laravel caches...
php artisan optimize:clear
php artisan view:clear
php artisan cache:clear
php artisan config:clear

echo.
echo Step 2: Deleting old build folder...
if exist public\build rmdir /s /q public\build

echo.
echo Step 3: Rebuilding assets (this may take a minute)...
call npm run build

echo.
echo Step 4: Starting servers...
start "Laravel Server" cmd /k "php artisan serve"
timeout /t 2 > nul
start "Vite Dev Server" cmd /k "npm run dev"

echo.
echo ========================================
echo  Done! 
echo ========================================
echo.
echo Now:
echo 1. Wait for both servers to start
echo 2. Go to: http://127.0.0.1:8000
echo 3. Press Ctrl + Shift + F5 (super refresh)
echo.
pause
