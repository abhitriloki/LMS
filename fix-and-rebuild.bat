@echo off
echo ========================================
echo  Fixing Alpine.js and Rebuilding Assets
echo ========================================
echo.

echo Step 1: Building JavaScript and CSS...
call npm run build
echo.

echo Step 2: Clearing Laravel caches...
php artisan view:clear
php artisan cache:clear
php artisan config:clear
echo.

echo ========================================
echo  Done! Now refresh your browser with:
echo  Ctrl + Shift + R (Windows)
echo ========================================
pause
