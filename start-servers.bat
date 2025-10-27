@echo off
echo ============================================
echo   Corporate LMS - Starting Servers
echo ============================================
echo.

echo Starting Laravel server...
start "Laravel Server" cmd /k "cd /d %~dp0 && php artisan serve"
timeout /t 2 /nobreak >nul

echo Starting Vite dev server...
start "Vite Dev Server" cmd /k "cd /d %~dp0 && npm run dev"
timeout /t 2 /nobreak >nul

echo.
echo ============================================
echo   Both servers are starting!
echo ============================================
echo.
echo Laravel: http://localhost:8000
echo Vite:    http://localhost:5173
echo.
echo Press any key to open the application...
pause >nul

start http://localhost:8000

echo.
echo Servers are running in separate windows.
echo Close those windows to stop the servers.
echo.
pause
