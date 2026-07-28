@echo off
chcp 65001 >nul
title Sistema POS - La Casa del Pastel
color 0E

REM ===================================================================
REM  INICIAR POS  -  Arranca todo el sistema con un solo doble click
REM ===================================================================
REM  Este archivo debe estar en la MISMA carpeta que la carpeta "xampp".
REM  No requiere instalar nada ni permisos de administrador.
REM ===================================================================

set "RAIZ=%~dp0"
set "XAMPP=%RAIZ%xampp"
set "MYSQL=%XAMPP%\mysql\bin"
set "URL=http://localhost/Cafeteria-software/Public/Index.php?pg=login"

cls
echo.
echo   ============================================
echo      SISTEMA POS - LA CASA DEL PASTEL
echo   ============================================
echo.

if not exist "%XAMPP%\apache\bin\httpd.exe" (
    color 0C
    echo   [ERROR] No se encuentra la carpeta "xampp".
    echo.
    echo   Este archivo debe estar JUNTO a la carpeta xampp.
    echo   Carpeta actual: %RAIZ%
    echo.
    pause
    exit /b 1
)

REM ---------- 1. Arrancar MySQL (base de datos) ----------
echo   [1/4] Iniciando la base de datos...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 (
    start "" /B "%MYSQL%\mysqld.exe" --defaults-file="%XAMPP%\mysql\bin\my.ini" --standalone
    REM Esperar a que la base responda (hasta 30 s)
    set /a INTENTOS=0
    :ESPERA_MYSQL
    ping -n 2 127.0.0.1 >nul
    "%MYSQL%\mysql.exe" -u root -e "SELECT 1" >nul 2>&1
    if not errorlevel 1 goto MYSQL_LISTO
    set /a INTENTOS+=1
    if %INTENTOS% LSS 15 goto ESPERA_MYSQL
    color 0C
    echo   [ERROR] La base de datos no arranco.
    echo   Reinicia el computador e intenta de nuevo.
    pause
    exit /b 1
) else (
    echo         Ya estaba encendida.
)
:MYSQL_LISTO
echo         Base de datos lista.

REM ---------- 2. Crear la base si es la primera vez ----------
echo   [2/4] Verificando los datos...
"%MYSQL%\mysql.exe" -u root -e "USE cafeteria_software;" >nul 2>&1
if errorlevel 1 (
    echo         Primera vez: preparando el sistema...
    "%MYSQL%\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS cafeteria_software CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
    if exist "%XAMPP%\htdocs\Cafeteria-software\install\schema.sql" (
        "%MYSQL%\mysql.exe" -u root cafeteria_software < "%XAMPP%\htdocs\Cafeteria-software\install\schema.sql"
        "%MYSQL%\mysql.exe" -u root cafeteria_software < "%XAMPP%\htdocs\Cafeteria-software\install\seed.sql"
        echo         Sistema preparado. Usuario: admin  /  PIN: 1234
    )
) else (
    echo         Datos encontrados correctamente.
)

REM ---------- 3. Arrancar Apache (servidor web) ----------
echo   [3/4] Iniciando el servidor...
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 (
    start "" /B "%XAMPP%\apache\bin\httpd.exe"
    ping -n 4 127.0.0.1 >nul
) else (
    echo         Ya estaba encendido.
)
echo         Servidor listo.

REM ---------- 4. Respaldo automatico de seguridad ----------
echo   [4/4] Guardando copia de seguridad...
call "%RAIZ%RESPALDAR AHORA.bat" silencioso
echo         Copia guardada.

REM ---------- Abrir el sistema como aplicacion ----------
echo.
echo   Abriendo el sistema...
echo.

set "CHROME="
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" set "CHROME=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" set "CHROME=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set "CHROME=%LocalAppData%\Google\Chrome\Application\chrome.exe"

set "EDGE=%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe"

if defined CHROME (
    start "" "%CHROME%" --app="%URL%" --start-maximized
) else if exist "%EDGE%" (
    start "" "%EDGE%" --app="%URL%" --start-maximized
) else (
    start "" "%URL%"
)

echo   ============================================
echo      EL SISTEMA YA ESTA ABIERTO
echo   ============================================
echo.
echo   NO CIERRES esta ventana negra mientras trabajas.
echo   Al terminar, usa "APAGAR POS.bat"
echo.
echo   Esta ventana se cierra sola en 10 segundos...
ping -n 11 127.0.0.1 >nul
exit
