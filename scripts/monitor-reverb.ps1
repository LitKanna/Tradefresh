# Sydney Markets B2B - Reverb Monitor
# This script ensures Reverb WebSocket server is always running

$projectPath = "C:\Users\Marut\New folder (5)"
$checkInterval = 30  # Check every 30 seconds
$reverbPort = 8080

Write-Host "=======================================" -ForegroundColor Green
Write-Host "Sydney Markets B2B - Reverb Monitor" -ForegroundColor Green
Write-Host "=======================================" -ForegroundColor Green
Write-Host ""
Write-Host "Monitoring Reverb WebSocket Server..." -ForegroundColor Yellow
Write-Host "Check interval: $checkInterval seconds" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop monitoring" -ForegroundColor Yellow
Write-Host ""

function Test-ReverbRunning {
    $connection = Test-NetConnection -ComputerName localhost -Port $reverbPort -WarningAction SilentlyContinue -InformationLevel Quiet
    return $connection
}

function Start-ReverbServer {
    Write-Host "[$(Get-Date -Format 'HH:mm:ss')] Starting Reverb..." -ForegroundColor Yellow

    Set-Location $projectPath

    # Start Reverb in a hidden window
    $reverbProcess = Start-Process php -ArgumentList "artisan reverb:start --host=0.0.0.0 --port=$reverbPort --hostname=localhost" -WindowStyle Hidden -PassThru

    # Wait for startup
    Start-Sleep -Seconds 5

    if (Test-ReverbRunning) {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ✓ Reverb started successfully!" -ForegroundColor Green
        return $true
    } else {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ✗ Failed to start Reverb" -ForegroundColor Red
        return $false
    }
}

# Main monitoring loop
while ($true) {
    if (Test-ReverbRunning) {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ✓ Reverb is running" -ForegroundColor Green
    } else {
        Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ✗ Reverb is not running!" -ForegroundColor Red

        # Try to start it
        if (Start-ReverbServer) {
            Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ✓ Reverb restarted successfully" -ForegroundColor Green
        } else {
            Write-Host "[$(Get-Date -Format 'HH:mm:ss')] ✗ Failed to restart Reverb. Will retry in $checkInterval seconds..." -ForegroundColor Red
        }
    }

    # Wait before next check
    Start-Sleep -Seconds $checkInterval
}