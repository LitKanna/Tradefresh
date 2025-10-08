@echo off
REM Sydney Markets B2B - Windows Backup Script
REM Runs daily to backup code and database

set TIMESTAMP=%date:~-4%%date:~3,2%%date:~0,2%-%time:~0,2%%time:~3,2%%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_DIR=backups

echo === Starting Sydney Markets Backup ===
echo Timestamp: %TIMESTAMP%

REM Create backup directory if it doesn't exist
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

REM 1. Backup Database
echo Backing up database...
if exist "database\database.sqlite" (
    copy "database\database.sqlite" "%BACKUP_DIR%\db-%TIMESTAMP%.sqlite" >nul
    echo Database backed up successfully
) else (
    echo ERROR: Database file not found!
)

REM 2. Backup .env file
if exist ".env" (
    copy ".env" "%BACKUP_DIR%\.env-%TIMESTAMP%" >nul
    echo Environment file backed up
)

REM 3. Create code snapshot
echo Creating code snapshot...
git add . 2>nul
git commit -m "Automated backup - %TIMESTAMP%" --quiet 2>nul
echo Code snapshot created

REM 4. Clean old backups (older than 30 days)
echo Cleaning old backups...
forfiles /P "%BACKUP_DIR%" /M "db-*.sqlite" /D -30 /C "cmd /c del @path" 2>nul
forfiles /P "%BACKUP_DIR%" /M ".env-*" /D -30 /C "cmd /c del @path" 2>nul

REM 5. Log backup
echo %TIMESTAMP% - Backup completed >> "%BACKUP_DIR%\backup.log"

echo.
echo === Backup Complete ===
echo Check %BACKUP_DIR% folder for backups
echo.