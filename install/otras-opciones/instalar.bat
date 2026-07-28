@echo off
REM ===========================================================================
REM  INSTALADOR AUTOMATICO - Sistema POS
REM  ---------------------------------------------------------------------------
REM  Crea la base de datos vacia y carga la estructura + los datos iniciales.
REM  Requiere que XAMPP este instalado y MySQL encendido.
REM
REM  Uso: doble click en este archivo.
REM ===========================================================================
setlocal

set MYSQL=C:\xampp\mysql\bin\mysql.exe
set DBNAME=cafeteria_software
set CARPETA=%~dp0

echo.
echo ==========================================================
echo   INSTALADOR DEL SISTEMA POS
echo ==========================================================
echo.

REM --- Verificar que MySQL exista ---
if not exist "%MYSQL%" (
    echo [ERROR] No se encontro MySQL en: %MYSQL%
    echo         Instala XAMPP o corrige la ruta en este archivo.
    echo.
    pause
    exit /b 1
)

REM --- Verificar que MySQL este encendido ---
"%MYSQL%" -u root -e "SELECT 1;" >nul 2>&1
if errorlevel 1 (
    echo [ERROR] No se pudo conectar a MySQL.
    echo         Abre el Panel de XAMPP y presiona Start en MySQL.
    echo.
    pause
    exit /b 1
)

REM --- Avisar si la base de datos ya existe (no borrar sin permiso) ---
for /f %%A in ('"%MYSQL%" -u root -N -e "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name='%DBNAME%';" 2^>nul') do set EXISTE=%%A

if "%EXISTE%"=="1" (
    echo [AVISO] La base de datos "%DBNAME%" YA EXISTE.
    echo.
    echo         Si continuas, se conservan los datos actuales y solo se
    echo         agregara lo que falte. NO se borra nada.
    echo.
    echo         Si quieres una instalacion totalmente limpia, primero borra
    echo         la base de datos desde phpMyAdmin.
    echo.
    set /p SEGUIR="Continuar? (S/N): "
    if /i not "%SEGUIR%"=="S" (
        echo Cancelado por el usuario.
        pause
        exit /b 0
    )
)

echo.
echo [1/3] Creando la base de datos...
"%MYSQL%" -u root -e "CREATE DATABASE IF NOT EXISTS %DBNAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
if errorlevel 1 goto error

echo [2/3] Cargando la estructura (tablas y vistas)...
"%MYSQL%" -u root %DBNAME% < "%CARPETA%schema.sql"
if errorlevel 1 goto error

echo [3/3] Cargando los datos iniciales...
"%MYSQL%" -u root %DBNAME% < "%CARPETA%seed.sql"
if errorlevel 1 goto error

echo.
echo ==========================================================
echo   INSTALACION COMPLETA
echo ==========================================================
echo.
echo   Abre en el navegador:
echo   http://localhost/Cafeteria-software/Public/Index.php?pg=login
echo.
echo   Usuario: admin
echo   PIN....: 1234
echo.
echo   *** CAMBIA EL PIN APENAS ENTRES (Menu - Usuarios) ***
echo.
pause
exit /b 0

:error
echo.
echo [ERROR] Fallo la instalacion. Revisa el mensaje de arriba.
echo         Si MySQL tiene contrasena, edita este archivo y agrega
echo         -p tu_contrasena a las lineas que llaman a mysql.exe
echo.
pause
exit /b 1
