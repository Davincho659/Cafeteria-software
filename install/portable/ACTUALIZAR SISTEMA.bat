@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title Actualizar el sistema POS
color 0B

REM ===================================================================
REM  ACTUALIZAR SISTEMA
REM ===================================================================
REM  Reemplaza el CODIGO del sistema por una version mas nueva, dejando
REM  intacto todo lo que cargo el negocio:
REM
REM     - La base de datos (productos, ventas, inventario)
REM     - Las fotos de productos y categorias
REM     - Los respaldos
REM
REM  Como usarlo:
REM     1. Trae la version nueva en una carpeta llamada ACTUALIZACION,
REM        al lado de este archivo, con el proyecto dentro:
REM
REM           POS-LaCasaDelPastel\
REM              xampp\
REM              ACTUALIZACION\
REM                 Cafeteria-software\   <- la version nueva
REM              ACTUALIZAR SISTEMA.bat
REM
REM     2. Cierra el sistema con APAGAR POS.bat
REM     3. Doble click aqui.
REM ===================================================================

set "RAIZ=%~dp0"
set "XAMPP=%RAIZ%xampp"
set "ACTUAL=%XAMPP%\htdocs\Cafeteria-software"
set "NUEVA=%RAIZ%ACTUALIZACION\Cafeteria-software"

cls
echo.
echo   ============================================
echo      ACTUALIZAR EL SISTEMA
echo   ============================================
echo.

if not exist "%ACTUAL%\Public\Index.php" goto FALTA_ACTUAL
if not exist "%NUEVA%\Public\Index.php" goto FALTA_NUEVA

echo   Se va a reemplazar el codigo del sistema.
echo.
echo   SE CONSERVA todo lo del negocio:
echo      - Base de datos ^(productos, ventas, inventario^)
echo      - Fotos de productos y categorias
echo      - Respaldos
echo.
echo   ============================================
echo.
set "CONFIRMA="
set /p "CONFIRMA=  Escribe ACTUALIZAR para continuar: "
if /I not "!CONFIRMA!"=="ACTUALIZAR" goto CANCELADO

REM ---------- 1. Respaldo previo ----------
echo.
echo   [1/4] Guardando copia de seguridad...
if exist "%RAIZ%RESPALDAR AHORA.bat" (
    call "%RAIZ%RESPALDAR AHORA.bat" silencioso
    if not errorlevel 1 (
        echo         Copia guardada.
    ) else (
        echo         [AVISO] Sin copia: la base parece apagada.
        echo                 Se continua igual: el codigo no toca los datos.
    )
)

REM ---------- 2. Guardar copia del codigo actual ----------
echo   [2/4] Guardando la version anterior...
set "PREVIA=%RAIZ%VERSION-ANTERIOR"
if exist "%PREVIA%" rmdir /S /Q "%PREVIA%" >nul 2>&1
robocopy "%ACTUAL%" "%PREVIA%" /E /R:1 /W:1 /NFL /NDL /NJH /NJS /NP >nul 2>&1
echo         Guardada en VERSION-ANTERIOR ^(por si hay que volver atras^).

REM ---------- 3. Copiar el codigo nuevo ----------
REM  /XD excluye las carpetas con lo que cargo el negocio. Sin esto, las
REM  fotos de los productos se reemplazarian por las del equipo de
REM  desarrollo y el dueno perderia su trabajo.
echo   [3/4] Copiando la version nueva...
robocopy "%NUEVA%" "%ACTUAL%" /E /R:1 /W:1 /NFL /NDL /NJH /NJS /NP ^
    /XD "%NUEVA%\Public\Assets\img\products" "%NUEVA%\Public\Assets\img\categories" "%NUEVA%\storage" "%NUEVA%\.git" >nul 2>&1
echo         Codigo actualizado.

REM ---------- 4. Comprobar ----------
echo   [4/4] Comprobando...
if not exist "%ACTUAL%\Public\Index.php" goto ERROR_COPIA

set "NFOTOS=0"
if exist "%ACTUAL%\Public\Assets\img\products" (
    for /f %%i in ('dir /b "%ACTUAL%\Public\Assets\img\products\*.*" 2^>nul ^| find /c /v ""') do set "NFOTOS=%%i"
)

color 0A
echo         Todo correcto.
echo.
echo   ============================================
echo    SISTEMA ACTUALIZADO
echo   ============================================
echo.
echo    Fotos conservadas : !NFOTOS!
echo    Version anterior  : VERSION-ANTERIOR
echo.
echo   ============================================
echo    AHORA
echo   ============================================
echo.
echo    1. Abre el sistema con "INICIAR POS.bat"
echo    2. Entra como administrador
echo    3. Ve a Productos y pulsa "Revisar fotos"
echo       ^(deja las fotos listas para migrarlas^)
echo    4. Comprueba que esten todos los productos
echo       y que cada uno muestre su imagen
echo.
pause
exit /b 0

:CANCELADO
color 0E
echo.
echo   Cancelado. No se cambio nada.
echo.
pause
exit /b 0

:FALTA_ACTUAL
color 0C
echo   [ERROR] No se encuentra el sistema instalado en:
echo      %ACTUAL%
echo.
echo   Este archivo debe estar junto a la carpeta xampp.
echo.
pause
exit /b 1

:FALTA_NUEVA
color 0C
echo   [ERROR] No se encuentra la version nueva.
echo.
echo   Se busco en:
echo      %NUEVA%\Public\Index.php
echo.
echo   Trae la version nueva en una carpeta ACTUALIZACION
echo   al lado de este archivo:
echo.
echo      POS-LaCasaDelPastel\
echo         xampp\
echo         ACTUALIZACION\
echo            Cafeteria-software\    ^<-- la version nueva
echo         ACTUALIZAR SISTEMA.bat
echo.
pause
exit /b 1

:ERROR_COPIA
color 0C
echo.
echo   [ERROR] La copia no se completo.
echo.
echo   El sistema anterior esta guardado en:
echo      %PREVIA%
echo.
echo   Copia su contenido de vuelta a:
echo      %ACTUAL%
echo.
pause
exit /b 1
