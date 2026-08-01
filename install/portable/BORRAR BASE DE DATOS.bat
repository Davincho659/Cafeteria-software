@echo off
setlocal enabledelayedexpansion
chcp 65001 >nul
title Borrar base de datos - Sistema POS
color 0C

REM ===================================================================
REM  BORRAR BASE DE DATOS
REM ===================================================================
REM  Deja el sistema como recien instalado: sin productos, sin ventas,
REM  solo con el usuario admin para poder entrar.
REM
REM  Sirve para dos cosas:
REM    - Empezar de cero en el computador del negocio.
REM    - Reparar una base que quedo a medias y no deja iniciar sesion.
REM
REM  Antes de borrar hace una copia de seguridad, por si acaso.
REM ===================================================================

set "RAIZ=%~dp0"
set "XAMPP=%RAIZ%xampp"
set "MYSQL=%XAMPP%\mysql\bin"
set "PROYECTO=%XAMPP%\htdocs\Cafeteria-software"

cls
echo.
echo   ============================================
echo      BORRAR LA BASE DE DATOS
echo   ============================================
echo.
echo   Esto ELIMINA todo lo que haya en el sistema:
echo.
echo      - Productos y categorias
echo      - Inventario y compras
echo      - Ventas y reportes
echo      - Usuarios creados
echo.
echo   Queda como recien instalado, con:
echo.
echo      Usuario:  admin
echo      PIN:      1234
echo.
echo   ============================================
echo.
echo   Se guardara una copia antes de borrar.
echo.
set "CONFIRMA="
set /p "CONFIRMA=  Escribe BORRAR para continuar (o cierra esta ventana): "
if /I not "!CONFIRMA!"=="BORRAR" goto CANCELADO

echo.
echo   [1/3] Guardando copia de seguridad...
if exist "%RAIZ%RESPALDAR AHORA.bat" (
    call "%RAIZ%RESPALDAR AHORA.bat" silencioso
    if not errorlevel 1 (
        echo         Copia guardada en la carpeta RESPALDOS.
    ) else (
        echo         ^(no habia datos que copiar^)
    )
)

echo   [2/3] Borrando la base de datos...
"%MYSQL%\mysql.exe" -u root -e "DROP DATABASE IF EXISTS cafeteria_software;" 2>nul
if errorlevel 1 goto ERROR_MYSQL
echo         Base borrada.

echo   [3/3] Creando la base nueva, vacia...
if not exist "%PROYECTO%\install\schema.sql" goto FALTA_SCHEMA

"%MYSQL%\mysql.exe" -u root -e "CREATE DATABASE cafeteria_software CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" 2>nul
"%MYSQL%\mysql.exe" -u root cafeteria_software < "%PROYECTO%\install\schema.sql" 2> "%TEMP%\pos_sql_error.txt"
if exist "%PROYECTO%\install\seed.sql" "%MYSQL%\mysql.exe" -u root cafeteria_software < "%PROYECTO%\install\seed.sql" 2>> "%TEMP%\pos_sql_error.txt"

REM Comprobar que quedo bien
"%MYSQL%\mysql.exe" -u root -e "SELECT 1 FROM cafeteria_software.usuarios LIMIT 1;" >nul 2>&1
if errorlevel 1 goto ERROR_CREAR

del "%TEMP%\pos_sql_error.txt" >nul 2>&1
color 0A
echo         Base creada correctamente.
echo.
echo   ============================================
echo    LISTO - SISTEMA COMO RECIEN INSTALADO
echo   ============================================
echo.
echo    Entra con:   admin  /  1234
echo.
echo    Cambia ese PIN el primer dia.
echo.
pause
exit /b 0

:CANCELADO
color 0E
echo.
echo   Cancelado. No se borro nada.
echo.
pause
exit /b 0

:ERROR_MYSQL
echo.
echo   [ERROR] No se pudo conectar con la base de datos.
echo   Abre primero "INICIAR POS.bat" y vuelve a intentarlo.
echo.
pause
exit /b 1

:FALTA_SCHEMA
echo.
echo   [ERROR] Falta el archivo:
echo      %PROYECTO%\install\schema.sql
echo.
echo   Copia la carpeta "install" completa dentro del proyecto.
echo.
pause
exit /b 1

:ERROR_CREAR
echo.
echo   [ERROR] La base no se creo correctamente.
echo.
if exist "%TEMP%\pos_sql_error.txt" (
    echo   MySQL informo:
    echo   --------------------------------------------
    type "%TEMP%\pos_sql_error.txt"
    echo   --------------------------------------------
)
echo.
pause
exit /b 1
