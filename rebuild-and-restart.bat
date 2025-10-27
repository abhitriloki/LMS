@echo off
echo ========================================
echo  Rebuilding Assets and Restarting
echo ========================================
echo.

echo Step 1: Building assets...
call npm run build

echo.
echo Step 2: Clearing caches...
php artisan view:clear
php artisan config:clear

echo.
echo Step 3: Restarting server...
echo.
echo Close the current server window and this will start a new one.
echo.
pause

php artisan serve
