# ===================================================================
#  AJUSTAR RUTAS DE XAMPP
# ===================================================================
#  XAMPP guarda dentro de sus configuraciones la ruta donde vive. Puede
#  ser absoluta ("C:/xampp/mysql/data") o relativa ("/xampp/mysql/data",
#  que es lo habitual en la version portable). Al copiar el paquete a
#  otro computador o a otra carpeta, esas rutas dejan de existir:
#
#     Can't change dir to '...\mysql\data'
#
#  Este script detecta la ruta grabada, la compara con la real y
#  reescribe las configuraciones cuando no coinciden.
#
#  CUIDADO al reemplazar: "/xampp" aparece tanto al principio de una
#  ruta como DENTRO de otras ("/xampp/htdocs/xampp"). Sustituir a ciegas
#  produce rutas pegadas como ".../htdocsC:/.../xampp", que dejan a
#  Apache sin arrancar. Por eso solo se reemplaza cuando la ruta empieza
#  de verdad: precedida de comilla, espacio, igual o inicio de linea.
#
#  Uso:  powershell -ExecutionPolicy Bypass -File ajustar-rutas.ps1 -Xampp "C:\ruta\xampp"
# ===================================================================

param(
    [Parameter(Mandatory = $true)][string]$Xampp
)

$ErrorActionPreference = 'Stop'

function Salir($codigo, $mensaje) {
    Write-Output $mensaje
    exit $codigo
}

if (-not (Test-Path $Xampp)) { Salir 1 "ERROR: no existe la carpeta $Xampp" }

$rutaReal = (Resolve-Path $Xampp).Path.TrimEnd('\')
$realBarra = $rutaReal.Replace('\', '/')

# --- Carpetas de trabajo que XAMPP necesita ---
# El ZIP no siempre conserva las carpetas vacias. Sin "tmp", InnoDB aborta con
# "Unable to create temporary file"; sin "logs", Apache no levanta.
foreach ($carpeta in @('tmp', 'apache\logs', 'mysql\data')) {
    $ruta = Join-Path $rutaReal $carpeta
    if (-not (Test-Path $ruta)) {
        New-Item -ItemType Directory -Path $ruta -Force | Out-Null
        Write-Output "  [creada] $carpeta"
    }
}

# --- Archivos que contienen rutas ---
$objetivos = @(
    'mysql\bin\my.ini'
    'mysql\my.ini'
    'apache\conf\httpd.conf'
    'apache\conf\extra\httpd-xampp.conf'
    'apache\conf\extra\httpd-ssl.conf'
    'apache\conf\extra\httpd-vhosts.conf'
    'apache\conf\extra\httpd-multilang-errordoc.conf'
    'apache\conf\extra\httpd-autoindex.conf'
    'apache\conf\extra\httpd-dav.conf'
    'apache\conf\extra\httpd-manual.conf'
    'apache\conf\extra\httpd-userdir.conf'
    'apache\conf\extra\httpd-proxy.conf'
    'apache\conf\extra\httpd-info.conf'
    'php\php.ini'
    'phpMyAdmin\config.inc.php'
    'apache\bin\php.ini'
)

# -------------------------------------------------------------------
#  Restaurar los .original antes de nada
# -------------------------------------------------------------------
#  Se parte siempre del archivo tal como venia. Asi el ajuste es
#  repetible y, sobre todo, repara los archivos que hubieran quedado
#  con rutas pegadas por una version anterior de este script.
$restaurados = 0
foreach ($rel in $objetivos) {
    $ruta = Join-Path $rutaReal $rel
    $respaldo = "$ruta.original"
    if ((Test-Path $respaldo) -and (Test-Path $ruta)) {
        Copy-Item $respaldo $ruta -Force
        $restaurados++
    }
}
if ($restaurados -gt 0) { Write-Output "  [restaurados] $restaurados archivo(s) a su estado original" }

# --- Averiguar que ruta tienen grabada los archivos ---
$myIni = Join-Path $rutaReal 'mysql\bin\my.ini'
if (-not (Test-Path $myIni)) { $myIni = Join-Path $rutaReal 'mysql\my.ini' }
if (-not (Test-Path $myIni)) { Salir 2 "ERROR: no se encuentra my.ini dentro de $rutaReal" }

$lineaBasedir = Select-String -Path $myIni -Pattern '^\s*basedir\s*=\s*"?([^"\r\n]+)"?' | Select-Object -First 1
if (-not $lineaBasedir) { Salir 3 "ERROR: my.ini no declara basedir" }

# basedir apunta a <xampp>/mysql: se sube un nivel para obtener la raiz grabada
$basedirGrabado = $lineaBasedir.Matches[0].Groups[1].Value.Trim().TrimEnd('/', '\')
$grabadaBarra = ($basedirGrabado -replace '[/\\]mysql$', '').Replace('\', '/')

if ($grabadaBarra -ieq $realBarra) {
    Write-Output "RUTAS-OK"
    exit 0
}

Write-Output "AJUSTANDO"
Write-Output "  Grabada : $grabadaBarra"
Write-Output "  Real    : $realBarra"

# -------------------------------------------------------------------
#  Construir el patron de busqueda
# -------------------------------------------------------------------
#  Solo se considera inicio de ruta cuando delante hay una comilla, un
#  espacio, un igual, dos puntos (para "C:/xampp") o el principio de la
#  linea. Con eso, el "/xampp" de "htdocs/xampp" queda intacto.
$viejaBarra  = $grabadaBarra                    # p.ej. /xampp  o  C:/xampp
$viejaContra = $grabadaBarra.Replace('/', '\')  # p.ej. \xampp  o  C:\xampp
$nuevaBarra  = $realBarra
$nuevaContra = $realBarra.Replace('/', '\')

$delimitador = '(?<=^|["''\s=,;:(])'

$cambiados = 0
foreach ($rel in $objetivos) {
    $ruta = Join-Path $rutaReal $rel
    if (-not (Test-Path $ruta)) { continue }

    try {
        $contenido = [System.IO.File]::ReadAllText($ruta)
        $original = $contenido

        # El reemplazo se hace con un evaluador para que los caracteres
        # especiales del destino ($ y \) no se interpreten.
        $contenido = [regex]::Replace(
            $contenido,
            $delimitador + [regex]::Escape($viejaBarra),
            { param($m) $nuevaBarra },
            'IgnoreCase'
        )
        $contenido = [regex]::Replace(
            $contenido,
            $delimitador + [regex]::Escape($viejaContra),
            { param($m) $nuevaContra },
            'IgnoreCase'
        )

        if ($contenido -ne $original) {
            $respaldo = "$ruta.original"
            if (-not (Test-Path $respaldo)) { Copy-Item $ruta $respaldo -Force }

            [System.IO.File]::WriteAllText($ruta, $contenido)
            $cambiados++
            Write-Output "  [OK] $rel"
        }
    } catch {
        Write-Output "  [AVISO] no se pudo ajustar $rel : $($_.Exception.Message)"
    }
}

Write-Output "LISTO:$cambiados"
exit 0
