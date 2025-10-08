@echo off
echo Installing MCP Servers (Project-Wide)...
echo.

echo Step 1: Installing Node packages...
call npm install
echo.

echo Step 2: MCP servers installed:
echo   - Laravel Boost MCP: node_modules/@laravel-boost/mcp-server
echo   - Playwright MCP: node_modules/@playwright/mcp
echo.

echo Step 3: Configuration updated:
echo   - Config: %APPDATA%\Claude\claude_desktop_config.json
echo.

echo ========================================
echo NEXT STEPS:
echo ========================================
echo 1. Close Claude Code completely
echo 2. Exit from system tray if running
echo 3. Reopen Claude Code
echo 4. Type /mcp to verify servers connected
echo ========================================

pause
