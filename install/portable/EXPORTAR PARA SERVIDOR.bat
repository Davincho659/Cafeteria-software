@echo off
chcp 65001 >nul
title Exportar datos para el servidor
color 0B

REM ===================================================================
REM  EXPORTAR PARA SERVIDOR
REM ===================================================================
REM  Genera UN archivo con todo lo que el dueno cargo (productos,
REM  categorias, proveedores, inventario, ventas, configuracion) listo
REM  para subirlo al servidor el dia que se monte en la nube.
REM
REM  Este es el archivo que garantiza que NO SE PIERDA NADA al migrar.
REM ===================================================================

set "RAIZ=%~dp0"
set "MYSQL=%RAIZ%xampp\mysql\bin"
set "DESTINO=%RAIZ%PARA-EL-SERVIDOR"

if not exist "%DESTINO%" mkdir "%DESTINO%"

for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd"') do set "HOY=%%i"
set "ARCHIVO=%DESTINO%\migracion_%HOY%.sql"

cls
echo.
echo   ============================================
echo      EXPORTAR PARA EL SERVIDOR
echo   ============================================
echo.
echo   Preparando el archivo de migracion...
echo.

REM Verificar que la base este encendida
"%MYSQL%\mysql.exe" -u root -e "SELECT 1" >nul 2>&1
if errorlevel 1 (
    color 0C
    echo   [ERROR] La base de datos no esta encendida.
    echo.
    echo   Abre primero "INICIAR POS.bat" y vuelve a intentarlo.
    echo.
    pause
    exit /b 1
)

REM --single-transaction  : no bloquea mientras exporta
REM --routines --events   : incluye la logica interna de la base
REM --add-drop-table      : permite reimportar sin errores
"%MYSQL%\mysqldump.exe" -u root cafeteria_software ^
    --routines --events --single-transaction --add-drop-table ^
    --default-character-set=utf8mb4 > "%ARCHIVO%" 2>nul

if not exist "%ARCHIVO%" goto FALLO
for %%A in ("%ARCHIVO%") do if %%~zA LSS 5000 goto FALLO

REM --- Contar lo que se exporto, para confirmar al usuario ---
REM  El conteo se hace con PowerShell: batch se enreda con las comillas del SQL.
set "NPROD=?"
set "NCAT=?"
set "NVEN=?"
set "CONTEO=%TEMP%\pos_conteo.txt"
powershell -NoProfile -Command "& '%MYSQL%\mysql.exe' -u root -N -e 'SELECT (SELECT COUNT(*) FROM cafeteria_software.productos), (SELECT COUNT(*) FROM cafeteria_software.categorias), (SELECT COUNT(*) FROM cafeteria_software.ventas)'" > "%CONTEO%" 2>nul
for /f "tokens=1,2,3" %%a in ('type "%CONTEO%" 2^>nul') do (
    set "NPROD=%%a"
    set "NCAT=%%b"
    set "NVEN=%%c"
)
del "%CONTEO%" >nul 2>&1

color 0A
echo   ============================================
echo    ARCHIVO LISTO
echo   ============================================
echo.
echo    Productos    : %NPROD%
echo    Categorias   : %NCAT%
echo    Ventas       : %NVEN%
echo.
echo    Guardado en:
echo    %ARCHIVO%
echo.
echo   ============================================
echo    QUE HACER CON ESTE ARCHIVO
echo   ============================================
echo.
echo    1. Copialo a una USB o mandalo por correo/Drive
echo       a quien va a montar el servidor.
echo.
echo    2. En el servidor se importa y TODO queda igual:
echo       los mismos productos, precios e inventario.
echo.
echo    3. No borres nada de este computador hasta
echo       confirmar que el servidor quedo funcionando.
echo.
echo   ============================================
echo.

REM Abrir la carpeta para que sea facil copiarlo
explorer "%DESTINO%"
pause
exit /b 0

:FALLO
color 0C
echo   [ERROR] No se pudo generar el archivo.
echo   Verifica que el sistema POS este abierto.
echo.
pause
exit /b 1
