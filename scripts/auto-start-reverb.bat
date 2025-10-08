@echo off
:: Sydney Markets B2B - Auto-Start Reverb on System Boot
:: This file should be added to Windows Task Scheduler

cd /d "C:\Users\Marut\New folder (5)"

:: Check if Reverb is already running on port 9090
netstat -an | findstr :9090 >nul
if %errorlevel% == 0 (
    echo Reverb is already running on port 9090
    exit /b 0
)

:: Start Reverb on port 9090 (avoiding conflict with Apache on 8080)
echo Starting Reverb WebSocket Server on port 9090...
start /B php artisan reverb:start --port=9090 --host=0.0.0.0 --hostname=localhost

:: Log the start time
echo %date% %time% - Reverb started on port 9090 >> reverb-startup.log

exit /b 0