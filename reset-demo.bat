@echo off
title SmileCare Dental - Reset demo data
echo This will DELETE all data in the dentalclinic_v2 database and reload fresh demo data.
choice /M "Continue"
if errorlevel 2 exit /b 0
"C:\xampp\php\php.exe" "%~dp0database\install.php" --fresh
pause
