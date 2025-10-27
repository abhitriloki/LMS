@echo off
echo ========================================
echo  Using Production Build (Simpler)
echo ========================================
echo.
echo This will:
echo 1. Build assets for production
echo 2. Start only Laravel server
echo 3. No need for Vite dev server
echo.
pause

echo.
echo Step 1: Building production assets...
call npm run build

echo.
echo Step 2: Clearing caches...
php artisan view:clear
php artisan config:clear

echo.
echo Step 3: Starting Laravel server...
echo.
echo ========================================
echo  Server starting...
echo ========================================
echo.
echo Go to: http://127.0.0.1:8000
echo Press Ctrl+Shift+F5 to hard refresh
echo.
php artisan serve
