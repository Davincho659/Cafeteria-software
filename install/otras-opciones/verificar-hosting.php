<?php
/**
 * ============================================================================
 * VERIFICADOR DE HOSTING — POS Cafetería
 * ============================================================================
 * Sube SOLO este archivo al hosting (a la carpeta public_html) y ábrelo en el
 * navegador: https://tudominio.com/verificar-hosting.php
 *
 * Te dice, en cristiano, si ese hosting sirve para el sistema — ANTES de pagar
 * un año o de subir nada.
 *
 * ⚠️ BÓRRALO del servidor cuando termines de revisar.
 * ============================================================================
 */

$pruebas = [];

// --- PHP 8 o superior ---
$phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
$pruebas[] = [
    'nombre'   => 'Versión de PHP',
    'ok'       => $phpOk,
    'critico'  => true,
    'detalle'  => 'Tienes PHP ' . PHP_VERSION,
    'ayuda'    => 'Busca en el panel (cPanel) la opción "Select PHP Version" y cámbiala a 8.0 o superior.',
];

// --- Extensiones necesarias ---
$extensiones = [
    'pdo_mysql' => 'Conectarse a la base de datos',
    'mysqli'    => 'Compatibilidad con MySQL',
    'fileinfo'  => 'Validar las imágenes que se suben',
    'mbstring'  => 'Manejar tildes y ñ correctamente',
    'json'      => 'Comunicación entre pantalla y servidor',
    'session'   => 'Mantener la sesión del cajero',
];
foreach ($extensiones as $ext => $paraQue) {
    $pruebas[] = [
        'nombre'  => 'Extensión ' . $ext,
        'ok'      => extension_loaded($ext),
        'critico' => in_array($ext, ['pdo_mysql', 'json', 'session'], true),
        'detalle' => $paraQue,
        'ayuda'   => 'Actívala en el panel del hosting (PHP Extensions) o pídesela al soporte.',
    ];
}

// --- Permisos de escritura ---
$carpeta = __DIR__ . '/prueba_escritura_pos';
$puedeEscribir = @mkdir($carpeta) && @file_put_contents($carpeta . '/x.txt', 'ok') !== false;
if (is_file($carpeta . '/x.txt')) { @unlink($carpeta . '/x.txt'); }
if (is_dir($carpeta)) { @rmdir($carpeta); }
$pruebas[] = [
    'nombre'  => 'Permiso para crear carpetas y archivos',
    'ok'      => $puedeEscribir,
    'critico' => true,
    'detalle' => 'El sistema necesita guardar sesiones, imágenes de productos y registros',
    'ayuda'   => 'Pide al soporte permisos de escritura (755) en la carpeta del sitio.',
];

// --- Zona horaria ---
$tz = date_default_timezone_get();
$pruebas[] = [
    'nombre'  => 'Zona horaria del servidor',
    'ok'      => true, // el sistema la fuerza a Colombia por su cuenta
    'critico' => false,
    'detalle' => 'El servidor usa ' . $tz . '. El sistema la corrige a Colombia automáticamente.',
    'ayuda'   => '',
];

// --- HTTPS ---
$https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$pruebas[] = [
    'nombre'  => 'Certificado de seguridad (HTTPS)',
    'ok'      => $https,
    'critico' => false,
    'detalle' => $https ? 'La conexión es segura' : 'Estás entrando por http, sin cifrar',
    'ayuda'   => 'Activa el SSL gratuito (Let\'s Encrypt) desde el panel del hosting. Es indispensable antes de usarlo con dinero real.',
];

// --- Memoria y subida de archivos ---
$memoria = ini_get('memory_limit');
$pruebas[] = [
    'nombre'  => 'Memoria disponible',
    'ok'      => true,
    'critico' => false,
    'detalle' => 'Límite: ' . $memoria . ' (con 128M sobra)',
    'ayuda'   => '',
];
$subida = ini_get('upload_max_filesize');
$pruebas[] = [
    'nombre'  => 'Tamaño máximo de imagen',
    'ok'      => true,
    'critico' => false,
    'detalle' => 'Hasta ' . $subida . ' por foto de producto',
    'ayuda'   => '',
];

// --- Resumen ---
$fallosCriticos = 0;
$avisos = 0;
foreach ($pruebas as $p) {
    if (!$p['ok']) {
        if ($p['critico']) { $fallosCriticos++; } else { $avisos++; }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>¿Sirve este hosting para el POS?</title>
<style>
  body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#f4f6f8;
         margin:0; padding:24px; color:#222; }
  .caja { max-width:760px; margin:0 auto; background:#fff; border-radius:12px;
          box-shadow:0 2px 12px rgba(0,0,0,.08); overflow:hidden; }
  header { padding:22px 26px; background:#5B3411; color:#fff; }
  header h1 { margin:0; font-size:20px; }
  header p { margin:4px 0 0; opacity:.85; font-size:14px; }
  .veredicto { padding:20px 26px; font-size:16px; font-weight:600; }
  .ok   { background:#e7f6ec; color:#14663a; border-bottom:1px solid #cfe9d9; }
  .malo { background:#fdecea; color:#8a1c13; border-bottom:1px solid #f5c6c2; }
  .medio{ background:#fff6e5; color:#8a5a00; border-bottom:1px solid #f2e0b8; }
  table { width:100%; border-collapse:collapse; }
  td { padding:12px 26px; border-bottom:1px solid #eee; vertical-align:top; font-size:14px; }
  td.est { width:42px; font-size:18px; text-align:center; }
  .nom { font-weight:600; }
  .det { color:#666; font-size:13px; margin-top:2px; }
  .ayuda { color:#8a5a00; font-size:13px; margin-top:6px; background:#fff6e5;
           padding:8px 10px; border-radius:6px; }
  footer { padding:18px 26px; background:#fafafa; font-size:13px; color:#666; }
</style>
</head>
<body>
<div class="caja">
  <header>
    <h1>¿Este hosting sirve para el POS?</h1>
    <p>Revisión automática — <?= date('d/m/Y H:i') ?></p>
  </header>

  <?php if ($fallosCriticos > 0): ?>
    <div class="veredicto malo">
      ❌ NO sirve así como está — hay <?= $fallosCriticos ?> problema(s) que impiden funcionar.<br>
      <span style="font-weight:400">Mira abajo qué pedirle al soporte. Si no lo pueden arreglar, busca otro hosting.</span>
    </div>
  <?php elseif ($avisos > 0): ?>
    <div class="veredicto medio">
      ⚠️ Sirve, pero faltan <?= $avisos ?> cosa(s) por ajustar antes de usarlo con dinero real.
    </div>
  <?php else: ?>
    <div class="veredicto ok">
      ✅ Todo correcto. Este hosting sirve para el sistema.
    </div>
  <?php endif; ?>

  <table>
    <?php foreach ($pruebas as $p): ?>
    <tr>
      <td class="est"><?= $p['ok'] ? '✅' : ($p['critico'] ? '❌' : '⚠️') ?></td>
      <td>
        <div class="nom"><?= htmlspecialchars($p['nombre']) ?></div>
        <div class="det"><?= htmlspecialchars($p['detalle']) ?></div>
        <?php if (!$p['ok'] && $p['ayuda']): ?>
          <div class="ayuda"><strong>Qué hacer:</strong> <?= htmlspecialchars($p['ayuda']) ?></div>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <footer>
    🔒 <strong>Borra este archivo del servidor</strong> cuando termines de revisarlo.
  </footer>
</div>
</body>
</html>
