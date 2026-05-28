@echo off
set PHP=C:\xampp\php\php.exe
set SCRIPT=%~dp0spam_guard_purge_all.php

if not exist "%PHP%" (
  echo Update PHP path in scripts\run_spam_guard_loop.bat
  exit /b 1
)

echo Spam guard loop started. Purging every 5 minutes. Press Ctrl+C to stop.
:loop
echo [%date% %time%] Running spam_guard_purge_all.php
"%PHP%" "%SCRIPT%"
timeout /t 300 /nobreak >nul
goto loop
