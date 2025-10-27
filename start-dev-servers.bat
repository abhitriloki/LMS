@echo off
echo ========================================
echo  Starting Development Servers
echo ========================================
echo.
echo This will open TWO terminal windows:
echo 1. PHP Server (Laravel)
echo 2. Vite Dev Server (Assets)
echo.
echo Keep BOTH windows open while developing!
echo.
echo Press any key to start...
pause > nul

echo Starting PHP Server...
start "Laravel Server" cmd /k "php artisan serve"

timeout /t 2 > nul

echo Starting Vite Dev Server...
start "Vite Dev Server" cmd /k "npm run dev"

echo.
echo ========================================
echo  Both servers are starting!
echo ========================================
echo.
echo Wait for both windows to show "ready"
echo Then go to: http://127.0.0.1:8000
echo.
echo Press any key to close this window...
pause > nul
