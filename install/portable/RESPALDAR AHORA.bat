@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title Respaldo del Sistema POS

REM ===================================================================
REM  RESPALDAR AHORA
REM ===================================================================
REM  Guarda una copia de TODOS los datos del negocio: productos,
REM  inventario, ventas, compras, gastos y configuracion.
REM
REM  Se ejecuta solo al abrir y al cerrar el sistema, y tambien se puede
REM  correr a mano en cualquier momento.
REM
REM  Uso silencioso (sin ventana):  RESPALDAR AHORA.bat silencioso
REM ===================================================================

set "RAIZ=%~dp0"
set "XAMPP=%RAIZ%xampp"
set "MYSQL=%XAMPP%\mysql\bin"
set "DESTINO=%RAIZ%RESPALDOS"
set "SILENCIO=%~1"
set "CALLADO=no"
if /I "%SILENCIO%"=="silencioso" set "CALLADO=si"

if not exist "%DESTINO%" mkdir "%DESTINO%" >nul 2>&1

REM --- Fecha y hora para el nombre del archivo ---
REM  Se usa PowerShell porque el formato de %date% cambia segun el idioma
REM  de Windows. Si fallara, se recurre a un nombre con la hora del sistema.
set "SELLO="
for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss" 2^>nul`) do set "SELLO=%%i"
if not defined SELLO set "SELLO=%RANDOM%"

set "ARCHIVO=%DESTINO%\datos_!SELLO!.sql"

if "!CALLADO!"=="no" (
    cls
    echo.
    echo   ============================================
    echo      RESPALDO DE SEGURIDAD
    echo   ============================================
    echo.
    echo   Guardando una copia de todos los datos...
    echo.
)

if not exist "%MYSQL%\mysqldump.exe" goto FALLO

"%MYSQL%\mysqldump.exe" -u root --databases cafeteria_software --routines --events --single-transaction > "!ARCHIVO!" 2>nul

if errorlevel 1 goto FALLO
if not exist "!ARCHIVO!" goto FALLO

REM --- Comprobar que el respaldo no salio vacio ---
REM  El tamano se guarda en una variable antes de compararlo: usarlo
REM  directamente en el IF hace que, si viene vacio, la linea quede como
REM  "if  LSS 5000" y Batch aborte con "no se esperaba ... en este momento".
set "TAMANO=0"
for %%A in ("!ARCHIVO!") do set "TAMANO=%%~zA"
if not defined TAMANO set "TAMANO=0"
if "!TAMANO!"=="" set "TAMANO=0"

REM  Un respaldo real pesa decenas de KB; menos de 5 KB significa que
REM  MySQL no estaba encendido o que la base no existe todavia.
if !TAMANO! LSS 5000 (
    del "!ARCHIVO!" >nul 2>&1
    goto FALLO
)

REM --- Conservar solo los ultimos 30 respaldos ---
for /f "skip=30 delims=" %%F in ('dir /b /o-d "%DESTINO%\datos_*.sql" 2^>nul') do del "%DESTINO%\%%F" >nul 2>&1

if "!CALLADO!"=="no" (
    color 0A
    echo   [OK] Respaldo guardado correctamente:
    echo.
    echo        !ARCHIVO!
    echo.
    echo   ============================================
    echo    IMPORTANTE
    echo   ============================================
    echo   Copia de vez en cuando la carpeta RESPALDOS
    echo   a una memoria USB o a Google Drive.
    echo.
    echo   Si este computador se dana, esa copia es lo
    echo   unico que salva el trabajo.
    echo   ============================================
    echo.
    pause
)
exit /b 0

:FALLO
if "!CALLADO!"=="no" (
    color 0C
    echo   [ERROR] No se pudo hacer el respaldo.
    echo.
    echo   Suele ser porque la base de datos no esta encendida.
    echo   Abre primero "INICIAR POS.bat" e intentalo de nuevo.
    echo.
    pause
)
exit /b 1
