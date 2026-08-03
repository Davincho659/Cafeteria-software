@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title Exportar para el servidor
color 0B

REM ===================================================================
REM  EXPORTAR PARA SERVIDOR
REM ===================================================================
REM  Prepara TODO lo que hay que llevarse el dia que el sistema pase a
REM  internet. Son dos cosas distintas, y ambas hacen falta:
REM
REM    1. Los DATOS  -> un archivo .sql con productos, inventario,
REM                     ventas, proveedores y configuracion.
REM    2. Las IMAGENES -> las fotos de productos y categorias, que NO
REM                     estan dentro de la base: en la base solo se
REM                     guarda el nombre del archivo ("1.jpeg") y la
REM                     foto vive en una carpeta. Si solo se lleva el
REM                     .sql, los productos aparecen sin foto.
REM ===================================================================

set "RAIZ=%~dp0"
set "XAMPP=%RAIZ%xampp"
set "MYSQL=%XAMPP%\mysql\bin"
set "PROYECTO=%XAMPP%\htdocs\Cafeteria-software"
set "IMAGENES=%PROYECTO%\Public\Assets\img"

for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd" 2^>nul`) do set "HOY=%%i"
if not defined HOY set "HOY=export"

set "DESTINO=%RAIZ%PARA-EL-SERVIDOR\%HOY%"
set "ARCHIVO=%DESTINO%\datos.sql"

cls
echo.
echo   ============================================
echo      EXPORTAR PARA EL SERVIDOR
echo   ============================================
echo.

REM --- Comprobaciones ---
if not exist "%MYSQL%\mysqldump.exe" goto ERROR_XAMPP
"%MYSQL%\mysql.exe" -u root -e "SELECT 1" >nul 2>&1
if errorlevel 1 goto ERROR_APAGADO

if not exist "%DESTINO%" mkdir "%DESTINO%" >nul 2>&1

REM ---------- 1. Los datos ----------
echo   [1/3] Guardando los datos...
"%MYSQL%\mysqldump.exe" -u root cafeteria_software ^
    --routines --events --single-transaction --add-drop-table ^
    --default-character-set=utf8mb4 > "%ARCHIVO%" 2>nul

if not exist "%ARCHIVO%" goto ERROR_VOLCADO
set "TAMANO=0"
for %%A in ("%ARCHIVO%") do set "TAMANO=%%~zA"
if not defined TAMANO set "TAMANO=0"
if "!TAMANO!"=="" set "TAMANO=0"
if !TAMANO! LSS 5000 goto ERROR_VOLCADO
echo         Datos guardados.

REM ---------- 2. Las imagenes ----------
REM  Se copian tal cual, conservando los nombres: la base los referencia.
echo   [2/3] Copiando las fotos de productos...
if exist "%IMAGENES%" (
    REM  /R:1 /W:1 son imprescindibles: por defecto robocopy reintenta un millon
    REM  de veces esperando 30 s cada una, y si una foto tiene los permisos de
    REM  Windows danados la copia se queda colgada para siempre.
    robocopy "%IMAGENES%" "%DESTINO%\img" /E /R:1 /W:1 /NFL /NDL /NJH /NJS /NP >nul 2>&1
    echo         Fotos copiadas.
) else (
    echo         [AVISO] No se encontro la carpeta de imagenes:
    echo                 %IMAGENES%
)

REM ---------- 3. Instrucciones ----------
echo   [3/3] Escribiendo las instrucciones...
> "%DESTINO%\LEER ANTES DE SUBIR.txt" (
    echo ===========================================================
    echo   PAQUETE DE MIGRACION AL SERVIDOR
    echo   Generado el %HOY%
    echo ===========================================================
    echo.
    echo QUE HAY AQUI
    echo -----------------------------------------------------------
    echo   datos.sql   Todo lo que hay en el sistema: productos,
    echo               inventario, ventas, compras, proveedores,
    echo               usuarios y configuracion.
    echo.
    echo   img\        Las fotos de los productos y las categorias.
    echo               La base de datos NO guarda las fotos, solo el
    echo               nombre del archivo. Sin esta carpeta los
    echo               productos apareceran sin imagen.
    echo.
    echo COMO SE SUBE
    echo -----------------------------------------------------------
    echo   1. Subir el codigo del sistema al servidor.
    echo.
    echo   2. Importar datos.sql en la base del servidor.
    echo      Desde phpMyAdmin: pestana Importar, elegir el archivo.
    echo.
    echo   3. Copiar TODO el contenido de la carpeta img\ dentro de
    echo      Public/Assets/img/ del servidor, reemplazando lo que
    echo      haya. Deben quedar las subcarpetas products y
    echo      categories con sus archivos.
    echo.
    echo   4. Entrar al sistema y comprobar:
    echo      - que esten todos los productos
    echo      - que cada producto muestre su foto
    echo      - que el inventario cuadre
    echo.
    echo   5. NO borrar nada del computador del negocio hasta
    echo      confirmar que el servidor funciona bien.
    echo.
    echo ===========================================================
)

REM --- Conteo, para confirmar que se llevo todo ---
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

set "NIMG=0"
if exist "%DESTINO%\img" (
    for /f %%i in ('dir /b /s "%DESTINO%\img\*.*" 2^>nul ^| find /c /v ""') do set "NIMG=%%i"
)

REM  Comprobar que cada foto que la base referencia llego al paquete. Si falta
REM  alguna, el producto apareceria sin imagen en el servidor.
set "FALTANTES="
for /f "usebackq delims=" %%F in (`powershell -NoProfile -Command "$faltan=@(); $r=& '%MYSQL%\mysql.exe' -u root -N -e \"SELECT imagen FROM cafeteria_software.productos WHERE imagen IS NOT NULL AND imagen<>''\" 2>$null; foreach($i in $r){ if($i -and -not (Test-Path (Join-Path '%DESTINO%\img\products' $i.Trim()))){ $faltan += $i.Trim() } }; if($faltan.Count){ $faltan -join ', ' } else { '' }" 2^>nul`) do set "FALTANTES=%%F"

color 0A
echo.
echo   ============================================
echo    PAQUETE LISTO
echo   ============================================
echo.
echo    Productos    : !NPROD!
echo    Categorias   : !NCAT!
echo    Ventas       : !NVEN!
echo    Fotos        : !NIMG!
echo.
if defined FALTANTES (
    color 0E
    echo   --------------------------------------------
    echo    ATENCION: estas fotos no se pudieron copiar
    echo   --------------------------------------------
    echo    !FALTANTES!
    echo.
    echo    Esos productos apareceran sin imagen. Vuelve a
    echo    subirles la foto desde el sistema ^(Productos -^>
    echo    editar^) y exporta de nuevo.
    echo.
)
echo    Guardado en:
echo    %DESTINO%
echo.
echo   ============================================
echo    QUE HACER CON ESTA CARPETA
echo   ============================================
echo.
echo    1. Copiala COMPLETA a una USB o subela a Drive.
echo       Van juntos los datos y las fotos.
echo.
echo    2. Dentro hay un archivo con las instrucciones
echo       de como subirlo al servidor.
echo.
echo    3. No borres nada de este computador hasta
echo       confirmar que el servidor quedo funcionando.
echo.
echo   ============================================
echo.

explorer "%DESTINO%"
pause
exit /b 0

:ERROR_XAMPP
color 0C
echo   [ERROR] No se encuentra XAMPP en:
echo      %XAMPP%
echo.
pause
exit /b 1

:ERROR_APAGADO
color 0C
echo   [ERROR] La base de datos no esta encendida.
echo.
echo   Abre primero "INICIAR POS.bat" y vuelve a intentarlo.
echo.
pause
exit /b 1

:ERROR_VOLCADO
color 0C
echo   [ERROR] No se pudo guardar los datos.
echo.
echo   Comprueba que el sistema este funcionando y que exista
echo   la base cafeteria_software.
echo.
pause
exit /b 1
