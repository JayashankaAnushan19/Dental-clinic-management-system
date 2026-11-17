@echo off
setlocal
title SmileCare Dental - Launcher

rem ===== Settings =====
set "XAMPP=C:\xampp"
set "PROJECT=%~dp0"
set "WEBROOT=%PROJECT%public"
set "LINK=%XAMPP%\htdocs\dentalclinic"
set "PHP=%XAMPP%\php\php.exe"
set "MYSQLADMIN=%XAMPP%\mysql\bin\mysqladmin.exe"
set "URL=http://localhost/dentalclinic/"

echo ============================================
echo   SmileCare Dental - Start
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

rem ===== 2. Database + demo data (first run only) =====
"%PHP%" "%PROJECT%database\install.php"
if errorlevel 1 (
    echo [ERROR] Database setup failed. See the message above.
    pause & exit /b 1
)

rem ===== 3. Link the public folder into htdocs =====
if not exist "%LINK%" (
    mklink /J "%LINK%" "%WEBROOT%" >nul
    if errorlevel 1 (
        echo [ERROR] Could not create link %LINK%
        pause & exit /b 1
    )
    echo [OK] Linked %LINK% -^> %WEBROOT%
) else (
    echo [OK] %LINK% already exists.
)

rem ===== 4. Apache =====
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

rem ===== 5. Open browser =====
echo.
echo Opening %URL%
echo.
echo   Admin   : admin@smilecare.test   / Admin@123
echo   Dentist : dentist@smilecare.test / Dentist@123
echo   Patient : patient@smilecare.test / Patient@123
echo.
echo Keep the minimized MySQL and Apache windows open. Run stop.bat to shut down.
start "" "%URL%"
timeout /t 10 >nul
