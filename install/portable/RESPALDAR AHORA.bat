@echo off
chcp 65001 >nul
title Respaldo del Sistema POS

REM ===================================================================
REM  RESPALDAR AHORA
REM ===================================================================
REM  Guarda una copia de TODOS los datos (productos, inventario, ventas).
REM  Se ejecuta solo cada vez que se inicia el POS, y tambien se puede
REM  correr a mano en cualquier momento.
REM
REM  Uso silencioso (sin ventana):  RESPALDAR AHORA.bat silencioso
REM ===================================================================

set "RAIZ=%~dp0"
set "MYSQL=%RAIZ%xampp\mysql\bin"
set "DESTINO=%RAIZ%RESPALDOS"
set "SILENCIO=%~1"

if not exist "%DESTINO%" mkdir "%DESTINO%"

REM --- Fecha y hora en formato ordenable (independiente del idioma) ---
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm"') do set "SELLO=%%i"
set "ARCHIVO=%DESTINO%\datos_%SELLO%.sql"

if /I not "%SILENCIO%"=="silencioso" (
    cls
    echo.
    echo   ============================================
    echo      RESPALDO DE SEGURIDAD
    echo   ============================================
    echo.
    echo   Guardando una copia de todos los datos...
    echo.
)

"%MYSQL%\mysqldump.exe" -u root --databases cafeteria_software --routines --events --single-transaction > "%ARCHIVO%" 2>nul

if errorlevel 1 goto FALLO
if not exist "%ARCHIVO%" goto FALLO

REM Descartar respaldos vacios (si MySQL no estaba encendido)
for %%A in ("%ARCHIVO%") do if %%~zA LSS 5000 (
    del "%ARCHIVO%" >nul 2>&1
    goto FALLO
)

REM --- Conservar solo los ultimos 30 respaldos ---
set /a CUENTA=0
for /f "skip=30 delims=" %%F in ('dir /b /o-d "%DESTINO%\datos_*.sql" 2^>nul') do (
    del "%DESTINO%\%%F" >nul 2>&1
)

if /I not "%SILENCIO%"=="silencioso" (
    color 0A
    echo   [OK] Respaldo guardado correctamente:
    echo.
    echo        %ARCHIVO%
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
if /I not "%SILENCIO%"=="silencioso" (
    color 0C
    echo   [ERROR] No se pudo hacer el respaldo.
    echo.
    echo   Asegurate de que el sistema POS este abierto
    echo   (usa "INICIAR POS.bat") e intenta de nuevo.
    echo.
    pause
)
exit /b 1
