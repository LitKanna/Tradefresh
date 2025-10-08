@echo off
echo ==========================================
echo   STARTING LARAVEL REVERB WEBSOCKET SERVER
echo ==========================================
echo.
echo This window must stay OPEN for real-time
echo quotes to work on the buyer dashboard.
echo.
echo Press Ctrl+C to stop the server.
echo.
echo ==========================================
echo.

cd /d "%~dp0"
php artisan reverb:start

pause
