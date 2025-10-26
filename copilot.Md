<?php
<?php
// ruta absoluta para guardar
$imgFolder = __DIR__ . '/../../Public/Assets/img/products';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // otros campos...
    $categoryId = $_POST['category_id'] ?? null;

    // manejar imagen subida (usar saveUploadedImage helper)
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $res = saveUploadedImage($_FILES['image'], $imgFolder);
        if ($res['success']) {
            $productImageForDb = 'products/' . $res['filename'];
        } else {
            // manejar error (opcional)
            $productImageForDb = null; // o conservar old si editando
        }
    } else {
        // No subió imagen -> mantener NULL para heredar de categoría
        $productImageForDb = null;
    }

    // Guardar en BD: image = $productImageForDb
}




/**
 * Devuelve la ruta pública (relativa a Public/) de la imagen para un producto.
 * $product: array o objeto con ['image', 'category_id']
 * $category: array o objeto con ['image']
 */
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
    return 'assets/img/default-product.png';
}