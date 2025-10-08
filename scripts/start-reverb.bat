@echo off
:: Sydney Markets B2B - Reverb WebSocket Server Starter
:: This ensures Reverb is always running for real-time features

echo ========================================
echo Sydney Markets B2B - WebSocket Server
echo ========================================
echo.

:: Check if Reverb is already running
netstat -an | findstr :8080 >nul
if %errorlevel% == 0 (
    echo [OK] Reverb is already running on port 8080
    echo.
    echo Press any key to exit...
    pause >nul
    exit /b 0
)

echo [STARTING] Starting Reverb WebSocket server...
echo.

:: Navigate to project directory
cd /d "C:\Users\Marut\New folder (5)"

:: Start Reverb in a new window
start "Reverb WebSocket Server" /MIN cmd /c "php artisan reverb:start --host=0.0.0.0 --port=8080 --hostname=localhost"

:: Wait a moment for it to start
timeout /t 3 /nobreak >nul

:: Check if it started successfully
netstat -an | findstr :8080 >nul
if %errorlevel% == 0 (
    echo [SUCCESS] Reverb WebSocket server is now running!
    echo.
    echo Server Details:
    echo - URL: ws://localhost:8080
    echo - Status: ACTIVE
    echo.
    echo The server is running in the background.
    echo Close the Reverb window to stop the server.
) else (
    echo [ERROR] Failed to start Reverb WebSocket server
    echo Please check the error messages and try again.
)

echo.
echo Press any key to exit...
pause >nul