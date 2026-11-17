@echo off
title IoT Attendance Online Launcher
cls
echo ========================================================
echo   IoT Attendance Online System - Ngrok Tunnel
echo ========================================================
echo.

echo [1/2] Checking MySQL service...
start /b "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" 2>nul

echo [2/2] Starting Ngrok Tunnel to Port 80...
echo.
echo ========================================================
echo   Permanent Domain:
echo   https://linguini-subscript-unsubtle.ngrok-free.dev
echo.
echo   API Endpoint:
echo   https://linguini-subscript-unsubtle.ngrok-free.dev/api/checkin.php
echo.
echo   Do NOT close this window while using the system!
echo ========================================================
echo.

npx --yes ngrok http 80 --url https://linguini-subscript-unsubtle.ngrok-free.dev

pause
