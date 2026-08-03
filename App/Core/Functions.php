<?php

    function resolvePathCaseInsensitive(string $baseDir, string $relativePath): ?string {
        $baseDir = rtrim($baseDir, '/\\');
        if ($baseDir === '' || !is_dir($baseDir)) {
            return null;
        }

        // Normaliza separadores sin regex para evitar errores de patrón en hosting.
        $normalized = str_replace('\\', '/', trim($relativePath, '/\\'));
        if ($normalized === '') {
            return null;
        }

        $parts = array_values(array_filter(explode('/', $normalized), static function ($part) {
            return $part !== '';
        }));

        if (empty($parts)) {
            return null;
        }

        $current = $baseDir;
        $lastIndex = count($parts) - 1;

        foreach ($parts as $index => $part) {
            $entries = @scandir($current);
            if ($entries === false) {
                return null;
            }

            $matched = null;
            foreach ($entries as $entry) {
                if (strcasecmp($entry, $part) === 0) {
                    $matched = $entry;
                    break;
                }
            }

            if ($matched === null) {
                return null;
            }

            $current .= DIRECTORY_SEPARATOR . $matched;

            if ($index < $lastIndex && !is_dir($current)) {
                return null;
            }
        }

        return is_file($current) ? $current : null;
    }

function show($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

function loadView($view) {
    $viewsBase = defined('VIEWS_PATH') ? VIEWS_PATH : dirname(__DIR__) . '/Views';
    $relative = "{$view}.view.php";
    $viewPath = $viewsBase . "/{$relative}";

    if (is_file($viewPath)) {
        return $viewPath;
    }

    $resolvedPath = resolvePathCaseInsensitive($viewsBase, $relative);
    if ($resolvedPath !== null) {
        return $resolvedPath;
    }

    throw new RuntimeException("Vista no encontrada: {$relative}");
}

function esc($str) {
    return htmlspecialchars($str);
}

/**
 * Devuelve la ruta de un asset con un parámetro de versión basado en la fecha
 * de modificación del archivo (cache busting).
 *
 * Sin esto, al actualizar el sistema los navegadores siguen usando el CSS/JS
 * viejo hasta que el usuario limpia la caché a mano.
 *
 * Uso: <link rel="stylesheet" href="<?= asset('assets/css/pos-theme.css') ?>">
 */
function asset(string $relative): string {
    $relative = ltrim($relative, '/');
    $publicBase = defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(dirname(__DIR__)) . '/Public';

    $path = $publicBase . '/' . $relative;
    if (!is_file($path)) {
        // En Linux la carpeta real es "Assets" y la URL "assets": resolver sin
        // depender de mayúsculas/minúsculas.
        $path = resolvePathCaseInsensitive($publicBase, $relative);
    }

    $version = ($path !== null && is_file($path)) ? filemtime($path) : null;

    return $version === null ? $relative : $relative . '?v=' . $version;
}

function loadJs($script) {
    $publicBase = defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(dirname(__DIR__)) . '/Public';
    $jsFile = $publicBase . "/Assets/js/{$script}.js";

    if (file_exists($jsFile)) {
        return "<script src='assets/js/{$script}.js'></script>";
    }

    throw new RuntimeException("Script no encontrado: assets/js/{$script}.js");
}

    /**
     * Deja la imagen guardada con los permisos normales de su carpeta.
     *
     * Los archivos que llegan por subida o descarga conservan los permisos del
     * archivo temporal del que provienen. En Windows eso puede dejarlos
     * accesibles solo para la cuenta del servidor web: la aplicación los sigue
     * mostrando sin problema, pero al copiarlos para un respaldo o para migrar
     * al servidor, Windows los rechaza y esos productos terminan sin foto.
     *
     * Al reescribir el contenido en un archivo creado desde cero, el nuevo
     * hereda los permisos de la carpeta y deja de ser un caso especial.
     */
    function normalizarPermisosImagen(string $ruta): bool {
        if (!is_file($ruta)) {
            return false;
        }

        $datos = @file_get_contents($ruta);
        if ($datos === false || $datos === '') {
            return false;
        }

        $temporal = $ruta . '.tmp' . bin2hex(random_bytes(4));
        if (@file_put_contents($temporal, $datos) === false) {
            return false;
        }

        if (!@unlink($ruta) || !@rename($temporal, $ruta)) {
            @unlink($temporal);
            return false;
        }

        @chmod($ruta, 0664);
        return true;
    }

    function saveUploadedImage(array $file, string $destFolder, ?string $oldFilename = null): array {
        // $file => $_FILES['image']
        // $destFolder => absolute or relative to project webroot "Public/Assets/img/products"
        // Returns ['success' => true, 'filename' => 'products/xxx.jpg'] or ['success'=>false, 'error'=>'...']

        $allowedExt = ['jpg','jpeg','png','webp'];
        $maxSize = 2 * 1024 * 1024; // 2 MB

        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'error' => 'no_file'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'upload_error'];
        }


        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'size_exceeded'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $mapMimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($mapMimeToExt[$mime])) {
            return ['success' => false, 'error' => 'invalid_mime'];
        }

        $ext = $mapMimeToExt[$mime];

        // generar nombre único
        $filename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;

        // asegurar carpeta existe
        if (!is_dir($destFolder)) {
            if (!mkdir($destFolder, 0755, true)) {
                return ['success'=>false, 'error'=>'mkdir_failed'];
            }
        }

        $destination = rtrim($destFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'move_failed'];
        }

        normalizarPermisosImagen($destination);

        // borrar archivo antiguo si se pasó
        if ($oldFilename) {
            $oldPath = $destFolder . DIRECTORY_SEPARATOR . basename($oldFilename);
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return ['success' => true, 'filename' => $filename];
    }

    /**
     * Descarga una imagen desde una URL a un archivo temporal y la VALIDA.
     * Así, si el link es malo, se falla ANTES de tocar la base de datos, y la
     * imagen queda guardada localmente (ya no depende de internet ni de que el
     * link siga vivo).
     *
     * @return array ['success'=>true,'tmp'=>ruta_temporal,'ext'=>'png'] | ['success'=>false,'error'=>'...']
     */
    function downloadImageToTemp(string $url, int $maxSize = 3145728): array {
        $url = trim($url);
        if ($url === '') {
            return ['success' => false, 'error' => 'URL vacía'];
        }
        if (!preg_match('#^https?://#i', $url)) {
            return ['success' => false, 'error' => 'El enlace debe empezar por http:// o https://'];
        }

        // --- Descargar con límites (timeout + tamaño) ---
        $data = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_MAXREDIRS       => 3,
                CURLOPT_CONNECTTIMEOUT  => 8,
                CURLOPT_TIMEOUT         => 12,
                CURLOPT_SSL_VERIFYPEER  => true,
                CURLOPT_USERAGENT       => 'POS-Cafeteria/1.0',
                CURLOPT_NOPROGRESS      => false,
                // Aborta si supera el tamaño máximo mientras descarga
                CURLOPT_PROGRESSFUNCTION => function ($ch, $dltotal, $dlnow) use ($maxSize) {
                    return ($dlnow > $maxSize) ? 1 : 0;
                },
            ]);
            $data = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($data === false || $httpCode >= 400) {
                return ['success' => false, 'error' => 'No se pudo descargar la imagen del enlace'];
            }
        } elseif (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => [
                'timeout' => 12, 'follow_location' => 1, 'max_redirects' => 3,
                'user_agent' => 'POS-Cafeteria/1.0',
            ]]);
            $data = @file_get_contents($url, false, $ctx, 0, $maxSize + 1);
            if ($data === false) {
                return ['success' => false, 'error' => 'No se pudo descargar la imagen del enlace'];
            }
        } else {
            return ['success' => false, 'error' => 'El servidor no permite descargar imágenes por URL'];
        }

        if (strlen($data) === 0) {
            return ['success' => false, 'error' => 'El enlace no devolvió ninguna imagen'];
        }
        if (strlen($data) > $maxSize) {
            return ['success' => false, 'error' => 'La imagen supera el tamaño máximo permitido (3 MB)'];
        }

        // --- Validar que el contenido sea realmente una imagen ---
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $data);
        finfo_close($finfo);
        $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!isset($map[$mime])) {
            return ['success' => false, 'error' => 'El enlace no apunta a una imagen válida'];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'posimg');
        if ($tmp === false || file_put_contents($tmp, $data) === false) {
            return ['success' => false, 'error' => 'No se pudo guardar la imagen temporal'];
        }

        return ['success' => true, 'tmp' => $tmp, 'ext' => $map[$mime]];
    }

    /**
     * Mueve una imagen temporal (de downloadImageToTemp) a su carpeta final con
     * el nombre base indicado, y borra la imagen anterior para no acumular
     * archivos sin usar.
     *
     * @return string|false nombre de archivo guardado (ej. "24.png") o false
     */
    function placeImageFromTemp(string $tmp, string $ext, string $destFolder, string $baseName, ?string $oldFilename = null) {
        if (!is_dir($destFolder) && !mkdir($destFolder, 0755, true)) {
            return false;
        }
        $filename = $baseName . '.' . $ext;
        $dest = rtrim($destFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        if (!@rename($tmp, $dest)) {
            // rename puede fallar entre discos distintos; intentar copiar
            if (!@copy($tmp, $dest)) {
                @unlink($tmp);
                return false;
            }
            @unlink($tmp);
        }

        // El archivo viene del temporal del sistema y arrastra sus permisos.
        normalizarPermisosImagen($dest);

        // Borrar imagen anterior si era distinta (evita acumular archivos)
        if ($oldFilename && basename($oldFilename) !== $filename && $oldFilename !== 'default.png') {
            $old = rtrim($destFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($oldFilename);
            if (is_file($old)) {
                @unlink($old);
            }
        }
        return $filename;
    }

    /** Borra el archivo de imagen de una carpeta si no es el default. */
    function deleteImageFile(string $destFolder, ?string $filename): void {
        if (!$filename || $filename === 'default.png') {
            return;
        }
        $path = rtrim($destFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    function getProductImagePath($product, $category = null) {
        // Si producto tiene imagen propia
        if (!empty($product['imagen'])) {
            return 'assets/img/' . $product['imagen']; // si guardas 'products/xxx.jpg' en BD
        }
        if (!empty($product['image'])) {
            return 'assets/img/' . $product['image'];
        }

        // Si recibieron la categoría o podemos obtenerla por $product['category_id']
        if ($category && !empty($category['imagen'])) {
            return 'assets/img/' . $category['imagen']; // ej. 'categories/yyy.jpg'
        }
        if ($category && !empty($category['image'])) {
            return 'assets/img/' . $category['image'];
        }

        // fallback default
        return 'assets/img/products/default.jpg';
    }