# PayU Payment Log Viewer
# Run this script to watch PayU-related logs in real-time

Write-Host "=== PayU Payment Log Viewer ===" -ForegroundColor Green
Write-Host "Press Ctrl+C to stop" -ForegroundColor Yellow
Write-Host ""

$logFile = "storage\logs\laravel.log"

if (-not (Test-Path $logFile)) {
    Write-Host "Log file not found: $logFile" -ForegroundColor Red
    exit
}

Write-Host "Watching for PayU-related logs..." -ForegroundColor Cyan
Write-Host ""

# Tail the log file and filter for PayU entries
Get-Content $logFile -Wait -Tail 50 | ForEach-Object {
    if ($_ -match "PayU|payment|Payment|PAYU") {
        if ($_ -match "ERROR|Exception|Failed|failed") {
            Write-Host $_ -ForegroundColor Red
        } elseif ($_ -match "Success|success|SUCCESS") {
            Write-Host $_ -ForegroundColor Green
        } elseif ($_ -match "Warning|WARNING") {
            Write-Host $_ -ForegroundColor Yellow
        } else {
            Write-Host $_ -ForegroundColor White
        }
    }
}

