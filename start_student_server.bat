@echo off
title Student Leave Portal
cls
echo ========================================================
echo   Student Leave System - Quick Launcher
echo ========================================================
echo.

echo [1/3] Ensuring MySQL is running...
start /b "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" 2>nul

echo [2/3] Checking Ngrok Tunnel...
tasklist /fi "imagename eq ngrok.exe" | find /i "ngrok.exe" >nul
if errorlevel 1 (
    echo Ngrok is not running. Starting Ngrok tunnel in background...
    start "Ngrok Tunnel" /min npx --yes ngrok http 80 --url https://linguini-subscript-unsubtle.ngrok-free.dev
    timeout /t 3 /nobreak >nul
) else (
    echo Ngrok is already running! (Shared with teacher server)
)

echo [3/3] Launching Student Portal in Browser...
start "" "https://linguini-subscript-unsubtle.ngrok-free.dev/projectiot/pages/student_login.php"

echo.
echo ========================================================
echo   Opened: https://linguini-subscript-unsubtle.ngrok-free.dev/projectiot/pages/student_login.php
echo ========================================================
echo.
timeout /t 5
exit
