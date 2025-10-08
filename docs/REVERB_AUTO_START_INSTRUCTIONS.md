# Reverb WebSocket Server - Auto-Start Setup

## Current Status ✅
Reverb is now running on **port 9090** (avoiding conflict with Apache on port 8080)

## How to Keep Reverb Running Automatically

### Method 1: Windows Task Scheduler (Recommended)
This will start Reverb automatically when Windows starts.

1. **Open Task Scheduler**
   - Press `Win + R`, type `taskschd.msc`, press Enter

2. **Create New Task**
   - Click "Create Basic Task..." in the right panel
   - Name: `Sydney Markets Reverb WebSocket`
   - Description: `Auto-start Reverb WebSocket server for real-time features`

3. **Set Trigger**
   - Choose "When the computer starts"
   - Click Next

4. **Set Action**
   - Choose "Start a program"
   - Program: `C:\Users\Marut\New folder (5)\auto-start-reverb.bat`
   - Click Next

5. **Finish**
   - Check "Open Properties dialog when finished"
   - Click Finish

6. **Configure Properties**
   - In General tab:
     - Check "Run whether user is logged on or not"
     - Check "Run with highest privileges"
   - In Settings tab:
     - Uncheck "Stop the task if it runs longer than"
   - Click OK

### Method 2: Manual Start
Run one of these commands:

```bash
# Quick start (port 9090)
cd "C:\Users\Marut\New folder (5)"
php artisan reverb:start --port=9090

# Or use the batch file
auto-start-reverb.bat
```

### Method 3: PowerShell Monitor
For continuous monitoring and auto-restart:

```powershell
# Run the monitor script
powershell -ExecutionPolicy Bypass -File monitor-reverb.ps1
```

## Verify Reverb is Running

### Check Status
```bash
php artisan reverb:status
```

### Check Port
```bash
netstat -an | findstr :9090
```

### Browser Test
Open: http://localhost:9090

## Important Configuration

Your application is now configured to use:
- **WebSocket Port**: 9090 (instead of 8080)
- **Host**: localhost
- **Protocol**: ws:// (not wss://)

## Update Your .env File

Make sure your `.env` file has these settings:

```env
REVERB_HOST="localhost"
REVERB_PORT=9090
REVERB_SERVER_PORT=9090
REVERB_SCHEME=http
VITE_REVERB_PORT=9090
```

## Troubleshooting

### If Reverb Won't Start
1. Check if port 9090 is free: `netstat -an | findstr :9090`
2. Kill any process using it: `taskkill /F /PID <process_id>`
3. Try starting manually with debug: `php artisan reverb:start --port=9090 --debug`

### If WebSocket Connection Fails
1. Check Windows Firewall - allow port 9090
2. Check your antivirus isn't blocking WebSocket connections
3. Make sure JavaScript uses correct port (9090)

## Current Running Instance
- **Status**: ✅ RUNNING
- **Port**: 9090
- **Started**: Just now
- **Process**: Background PHP process

## Files Created for Auto-Start
1. `start-reverb.bat` - Basic starter
2. `auto-start-reverb.bat` - Task Scheduler compatible
3. `monitor-reverb.ps1` - PowerShell monitor with auto-restart
4. `CheckReverbStatus.php` - Laravel command for status check

---

**Note**: Reverb MUST be running for real-time features (RFQ broadcasting, live quotes) to work!