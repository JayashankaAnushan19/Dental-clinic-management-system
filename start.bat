@echo off
setlocal
title Dental Clinic - Launcher

rem ===== Settings =====
set "XAMPP=C:\xampp"
set "PROJECT=%~dp0"
set "APP=%PROJECT%dental"
set "LINK=%XAMPP%\htdocs\dental"
set "SQLFILE=%PROJECT%dentalclinic.sql"
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "MYSQLADMIN=%XAMPP%\mysql\bin\mysqladmin.exe"
set "URL=http://localhost/dental/index.html"

echo ============================================
echo   Dental Clinic Management System - Start
echo ============================================
echo.

if not exist "%XAMPP%\apache\bin\httpd.exe" (
    echo [ERROR] XAMPP not found at %XAMPP%. Edit XAMPP= at the top of this file.
    pause & exit /b 1
)

rem ===== 1. MySQL =====
tasklist /FI "IMAGENAME eq mysqld.exe" | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo [..] Starting MySQL...
    start "XAMPP MySQL - do not close" /MIN "%XAMPP%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
) else (
    echo [OK] MySQL already running.
)

set /a tries=0
:waitmysql
"%MYSQLADMIN%" -u root ping >nul 2>&1 && goto mysqlok
set /a tries+=1
if %tries% geq 30 (
    echo [ERROR] MySQL did not respond. Try starting it from the XAMPP Control Panel.
    pause & exit /b 1
)
timeout /t 1 /nobreak >nul
goto waitmysql
:mysqlok
echo [OK] MySQL is ready.

rem ===== 2. Database (import only the first time) =====
"%MYSQL%" -u root -e "USE dentalclinic" >nul 2>&1
if errorlevel 1 (
    echo [..] Creating database 'dentalclinic' and importing data...
    "%MYSQL%" -u root -e "CREATE DATABASE dentalclinic CHARACTER SET utf8mb4"
    "%MYSQL%" -u root dentalclinic < "%SQLFILE%"
    if errorlevel 1 (
        echo [ERROR] Import failed.
        pause & exit /b 1
    )
    echo [OK] Database imported.
) else (
    echo [OK] Database 'dentalclinic' already exists - keeping your data.
)

rem ===== 3. MySQL user admin/admin used by connect-mysql.php =====
"%MYSQL%" -u root -e "CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'admin'; GRANT ALL PRIVILEGES ON dentalclinic.* TO 'admin'@'localhost'; FLUSH PRIVILEGES;"
if errorlevel 1 (
    echo [ERROR] Could not create MySQL user 'admin'.
    pause & exit /b 1
)
echo [OK] MySQL user 'admin' ready.

rem ===== 4. Link the project into htdocs (no copying, edits show up live) =====
if not exist "%LINK%" (
    mklink /J "%LINK%" "%APP%" >nul
    if errorlevel 1 (
        echo [ERROR] Could not create link %LINK%
        pause & exit /b 1
    )
    echo [OK] Linked %LINK% -^> %APP%
) else (
    echo [OK] %LINK% already exists.
)

rem ===== 5. Apache =====
tasklist /FI "IMAGENAME eq httpd.exe" | find /I "httpd.exe" >nul
if errorlevel 1 (
    echo [..] Starting Apache...
    start "XAMPP Apache - do not close" /MIN "%XAMPP%\apache\bin\httpd.exe"
) else (
    echo [OK] Apache already running.
)

set /a tries=0
:waitapache
curl -s -o nul http://localhost/ >nul 2>&1 && goto apacheok
set /a tries+=1
if %tries% geq 20 (
    echo [ERROR] Apache did not respond on port 80. Another program ^(Skype, IIS, ...^) may be using it.
    pause & exit /b 1
)
timeout /t 1 /nobreak >nul
goto waitapache
:apacheok
echo [OK] Apache is ready.

rem ===== 6. Open browser =====
echo.
echo Opening %URL%
echo.
echo   Admin login  : sumitnarang76@gmail.com / sumit
echo   Patient login: dileep@gmail.com / dileep
echo.
echo Run stop.bat to shut down Apache and MySQL.
start "" "%URL%"
timeout /t 8 >nul
