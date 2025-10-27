@echo off
echo ========================================
echo  Complete Fix - Clearing Everything
echo ========================================
echo.

echo Step 1: Clearing all Laravel caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
echo.

echo Step 2: Rebuilding assets...
call npm run build
echo.

echo Step 3: Creating favicon (fixing 404)...
echo ^<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"^>^<rect fill="#3b82f6" width="100" height="100"/^>^<text x="50" y="70" font-size="60" fill="white" text-anchor="middle" font-family="Arial"^>L^</text^>^</svg^> > public\favicon.svg
echo.

echo ========================================
echo  Done! 
echo  1. Restart your PHP server (php artisan serve)
echo  2. Refresh browser with Ctrl + Shift + R
echo ========================================
pause
