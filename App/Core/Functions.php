<?php

function show($data) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

function loadView($view) {
    if(file_exists("../App/Views/{$view}.view.php")) {
        return "../App/Views/{$view}.view.php";
    } else {
        echo "Vista no encontrada";
    }
    
}

function esc($str) {
    return htmlspecialchars($str);
}

function loadJs($script) {
    if(file_exists("../Public/Assets/js/{$script}.js")) {
        return "<script src='/../Public/Assets/js/{$script}.js'></script>";
    } else {
        echo "Script no encontrado";
    }
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

    // borrar archivo antiguo si se pasó
    if ($oldFilename) {
        $oldPath = $destFolder . DIRECTORY_SEPARATOR . basename($oldFilename);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return ['success' => true, 'filename' => $filename];
}

function getProductImagePath($product, $category = null) {
    // Si producto tiene imagen propia
    if (!empty($product['image'])) {
        return 'assets/img/' . $product['image']; // si guardas 'products/xxx.jpg' en BD
    }

    // Si recibieron la categoría o podemos obtenerla por $product['category_id']
    if ($category && !empty($category['image'])) {
        return 'assets/img/' . $category['image']; // ej. 'categories/yyy.jpg'
    }

    // fallback default
    return 'assets/img/products/default.jpg';
}