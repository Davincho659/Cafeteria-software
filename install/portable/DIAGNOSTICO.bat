@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title Diagnostico del paquete POS
color 0B

REM ===================================================================
REM  DIAGNOSTICO
REM ===================================================================
REM  Revisa que el paquete este bien armado y dice exactamente que
REM  falta. Usalo cuando "INICIAR POS" no funcione.
REM ===================================================================

set "RAIZ=%~dp0"
set "XAMPP=%RAIZ%xampp"
set "PROYECTO=%XAMPP%\htdocs\Cafeteria-software"
set /a FALLOS=0

cls
echo.
echo   ============================================
echo      DIAGNOSTICO DEL PAQUETE POS
echo   ============================================
echo.
echo   Carpeta revisada:
echo   %RAIZ%
echo.
echo   --------------------------------------------
echo    ESTRUCTURA
echo   --------------------------------------------

if exist "%XAMPP%\" (echo   [OK]    Carpeta xampp) else (echo   [FALTA] Carpeta xampp & set /a FALLOS+=1)
if exist "%XAMPP%\apache\bin\httpd.exe" (echo   [OK]    Servidor web ^(apache^)) else (echo   [FALTA] xampp\apache\bin\httpd.exe & set /a FALLOS+=1)
if exist "%XAMPP%\mysql\bin\mysqld.exe" (echo   [OK]    Base de datos ^(mysql^)) else (echo   [FALTA] xampp\mysql\bin\mysqld.exe & set /a FALLOS+=1)
if exist "%XAMPP%\mysql\bin\mysqldump.exe" (echo   [OK]    Herramienta de respaldos) else (echo   [FALTA] xampp\mysql\bin\mysqldump.exe & set /a FALLOS+=1)
if exist "%XAMPP%\php\php.exe" (echo   [OK]    PHP) else (echo   [FALTA] xampp\php\php.exe & set /a FALLOS+=1)
if exist "%XAMPP%\htdocs\" (echo   [OK]    Carpeta htdocs) else (echo   [FALTA] xampp\htdocs & set /a FALLOS+=1)
if exist "%XAMPP%\mysql\data\" (echo   [OK]    Datos de MySQL) else (echo   [FALTA] xampp\mysql\data & set /a FALLOS+=1)
REM Sin la carpeta temporal, InnoDB aborta y MySQL no arranca.
if exist "%XAMPP%\tmp\" (echo   [OK]    Carpeta temporal) else (echo   [AVISO] falta xampp\tmp ^(se crea sola al iniciar^))

REM Sin my.ini, mysqld aborta con "Fatal error in defaults handling".
set "MYINI="
if exist "%XAMPP%\mysql\bin\my.ini" set "MYINI=%XAMPP%\mysql\bin\my.ini"
if not defined MYINI if exist "%XAMPP%\mysql\my.ini" set "MYINI=%XAMPP%\mysql\my.ini"
if not defined MYINI if exist "%XAMPP%\mysql\my.cnf" set "MYINI=%XAMPP%\mysql\my.cnf"
if defined MYINI (echo   [OK]    Configuracion MySQL: !MYINI!) else (echo   [FALTA] my.ini de MySQL & set /a FALLOS+=1)

echo.
echo   --------------------------------------------
echo    SISTEMA POS
echo   --------------------------------------------

if exist "%PROYECTO%\" (echo   [OK]    Carpeta Cafeteria-software) else (echo   [FALTA] xampp\htdocs\Cafeteria-software & set /a FALLOS+=1)
if exist "%PROYECTO%\Public\Index.php" (echo   [OK]    Public\Index.php) else (echo   [FALTA] Cafeteria-software\Public\Index.php & set /a FALLOS+=1)
if exist "%PROYECTO%\App\Core\Init.php" (echo   [OK]    App\Core) else (echo   [FALTA] Cafeteria-software\App\Core & set /a FALLOS+=1)
REM Los archivos que crean la base van en el paquete, no dentro del proyecto.
if exist "%RAIZ%base-inicial\schema.sql" (
    echo   [OK]    base-inicial\schema.sql
) else (
    if exist "%PROYECTO%\install\schema.sql" (
        echo   [OK]    schema.sql ^(en la ubicacion antigua^)
    ) else (
        echo   [FALTA] base-inicial\schema.sql
        set /a FALLOS+=1
    )
)

echo.
echo   --------------------------------------------
echo    BASE DE DATOS
echo   --------------------------------------------
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 (
    echo      ^(la base esta apagada: abre INICIAR POS para revisarla^)
) else (
    "%XAMPP%\mysql\bin\mysql.exe" -u root -e "USE cafeteria_software;" >nul 2>&1
    if errorlevel 1 (
        echo      [FALTA] La base cafeteria_software no existe
        echo              ^(se crea sola al abrir INICIAR POS^)
    ) else (
        "%XAMPP%\mysql\bin\mysql.exe" -u root -e "SELECT 1 FROM cafeteria_software.usuarios LIMIT 1;" >nul 2>&1
        if errorlevel 1 (
            echo      [PROBLEMA] La base existe pero esta VACIA, sin tablas.
            echo                 Por eso no deja iniciar sesion.
            echo                 Al abrir INICIAR POS se completara sola.
            set /a FALLOS+=1
        ) else (
            echo      [OK]    Base de datos con sus tablas
        )
    )
)

echo.
echo   --------------------------------------------
echo    QUE HAY REALMENTE EN htdocs
echo   --------------------------------------------
if exist "%XAMPP%\htdocs\" (
    for /d %%D in ("%XAMPP%\htdocs\*") do echo      [carpeta]  %%~nxD
) else (
    echo      ^(no se puede leer htdocs^)
)

echo.
echo   --------------------------------------------
echo    PROGRAMAS EN EJECUCION
echo   --------------------------------------------
tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if errorlevel 1 (echo      Base de datos: apagada) else (echo      Base de datos: ENCENDIDA)
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if errorlevel 1 (echo      Servidor web:  apagado) else (echo      Servidor web:  ENCENDIDO)

echo.
echo   --------------------------------------------
echo    PUERTOS
echo   --------------------------------------------
netstat -an | find ":80 " | find "LISTENING" >nul
if errorlevel 1 (echo      Puerto 80:   libre) else (echo      Puerto 80:   OCUPADO)
netstat -an | find ":3306" | find "LISTENING" >nul
if errorlevel 1 (echo      Puerto 3306: libre) else (echo      Puerto 3306: OCUPADO)

echo.
echo   ============================================
if !FALLOS! EQU 0 (
    color 0A
    echo    TODO CORRECTO - el paquete esta bien armado
    echo   ============================================
    echo.
    echo    Ya puedes usar "INICIAR POS.bat"
) else (
    color 0C
    echo    HAY !FALLOS! PROBLEMA^(S^)
    echo   ============================================
    echo.
    echo    Revisa arriba las lineas que dicen [FALTA].
    echo.
    echo    Recuerda que la estructura debe ser:
    echo.
    echo       POS-LaCasaDelPastel\
    echo          xampp\
    echo             htdocs\
    echo                Cafeteria-software\
    echo          INICIAR POS.bat
    echo          DIAGNOSTICO.bat
    echo.
    echo    Los .bat van AFUERA de xampp, no adentro.
)
echo.
pause
