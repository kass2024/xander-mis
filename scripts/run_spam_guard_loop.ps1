# Runs spam purge every 5 minutes. Stop with Ctrl+C.
$php = "C:\xampp\php\php.exe"
$script = Join-Path $PSScriptRoot "spam_guard_purge_all.php"

if (-not (Test-Path $php)) {
    Write-Error "PHP not found at $php. Update the path in run_spam_guard_loop.ps1"
    exit 1
}

Write-Host "Spam guard loop started. Purging every 5 minutes..."
while ($true) {
    $stamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$stamp] Running spam_guard_purge_all.php"
    & $php $script
    Start-Sleep -Seconds 300
}
