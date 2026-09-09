<?php
/**
 * ============================================================================
 * MANIFEST DE LA APLICACIÓN (PWA)
 * ============================================================================
 * Es lo que hace que el sistema se pueda "instalar" en el celular: al agregarlo
 * a la pantalla de inicio queda con su propio ícono y se abre a pantalla
 * completa, sin barra de navegador, igual que una aplicación descargada.
 *
 * Se genera con PHP en vez de ser un archivo fijo para que tome el nombre y los
 * colores que el negocio haya puesto en Configuración: si mañana cambian el
 * nombre, el ícono de la pantalla de inicio cambia con él.
 * ============================================================================
 */

require_once __DIR__ . '/../App/Core/Init.php';
require_once __DIR__ . '/../App/Models/Settings.php';

// Si la base no responde, el manifest debe seguir sirviéndose: sin él, el
// celular deja de poder instalar la aplicación.
try {
    $cfg = (new Settings())->getAll();
} catch (Throwable $e) {
    $cfg = [
        'nombre_negocio' => 'POS',
        'color_primario' => '#5B3411',
    ];
}

$nombre = $cfg['nombre_negocio'] ?: 'POS';
$color  = $cfg['color_primario'] ?: '#5B3411';

// Nombre corto: el que va bajo el ícono en la pantalla de inicio. Se recorta
// por palabras, no por letras, para que no quede partido a la mitad.
$corto = $nombre;
if (mb_strlen($nombre) > 15) {
    $recorte = mb_substr($nombre, 0, 15);
    $ultimoEspacio = mb_strrpos($recorte, ' ');
    $corto = $ultimoEspacio > 0 ? mb_substr($recorte, 0, $ultimoEspacio) : $recorte;
}
$corto = trim($corto);

// La aplicación vive en la carpeta Public: el alcance se calcula solo para que
// funcione igual en localhost que en el dominio del servidor.
$base = rtrim(str_replace(chr(92), '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';

$icono = function ($archivo, $tam, $proposito = 'any') use ($base) {
    return [
        'src'     => $base . 'assets/img/pwa/' . $archivo,
        'sizes'   => $tam . 'x' . $tam,
        'type'    => 'image/png',
        'purpose' => $proposito,
    ];
};

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache');

echo json_encode([
    'name'             => $nombre,
    'short_name'       => $corto,
    'description'      => 'Sistema de ventas de ' . $nombre,
    'start_url'        => $base . 'Index.php?pg=login',
    'scope'            => $base,
    // standalone = se abre sin barra de direcciones, como una app
    'display'          => 'standalone',
    'orientation'      => 'any',
    'background_color' => '#ffffff',
    'theme_color'      => $color,
    'lang'             => 'es-CO',
    'dir'              => 'ltr',
    'categories'       => ['business', 'productivity'],
    'icons'            => [
        $icono('icon-96.png', 96),
        $icono('icon-144.png', 144),
        $icono('icon-180.png', 180),
        $icono('icon-192.png', 192),
        $icono('icon-384.png', 384),
        $icono('icon-512.png', 512),
        // "maskable": Android recorta el ícono en círculo o cuadrado redondeado
        // y estos llevan el logo centrado con margen para que no se corte.
        $icono('icon-maskable-192.png', 192, 'maskable'),
        $icono('icon-maskable-512.png', 512, 'maskable'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
