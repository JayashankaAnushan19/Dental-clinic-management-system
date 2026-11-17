@echo off
title Dental Clinic - Stop
set "XAMPP=C:\xampp"

echo Stopping Apache...
taskkill /F /IM httpd.exe >nul 2>&1 && echo [OK] Apache stopped. || echo [--] Apache was not running.

echo Stopping MySQL...
"%XAMPP%\mysql\bin\mysqladmin.exe" -u root shutdown >nul 2>&1 && echo [OK] MySQL stopped. || echo [--] MySQL was not running.

timeout /t 4 >nul
