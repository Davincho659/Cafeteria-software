<?php

require_once __DIR__ . '/../Models/products.php';
require_once __DIR__ . '/../Models/Categories.php';
require_once __DIR__ . '/../Core/Functions.php';
require_once __DIR__ . '/../Models/UnitsOfMeasure.php';

class ProductController {

    private $productsModel;
    private $categoriesModel;
    private $unitsModel;

    public function __construct() {
        $this->productsModel = new Products();
        $this->categoriesModel = new Categories();
        $this->unitsModel = new UnitsOfMeasure();
    }
	// Procesa la creación de un producto (formulario multipart/form-data)
	public function createProduct() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Validar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            // Validar datos requeridos
            if (empty($_POST['categoria'])) throw new Exception('La categoría es requerida');
            if (empty($_POST['nombre'])) throw new Exception('El nombre es requerido');
            if (empty($_POST['tipo'])) throw new Exception('El tipo es requerido');
            

            // Preparar datos
            $idCategoria = $_POST['categoria'];
            $nombre = trim($_POST['nombre']);
            $tipo = $_POST['tipo'];
            $manejaStock = isset($_POST['manejaStock']) ? (int)$_POST['manejaStock'] : 0;
            $idUnidadBase = !empty($_POST['idUnidadBase']) ? (int)$_POST['idUnidadBase'] : null;
            $precioCompra = !empty($_POST['precioCompra']) ? floatval($_POST['precioCompra']) : null;
            $precioVenta = !empty($_POST['precioVenta']) ? floatval($_POST['precioVenta']) : null;

            // Código de barras: OPCIONAL. Vacío => null. Si viene, debe ser único.
            $codigoBarras = !empty($_POST['codigoBarras']) ? trim($_POST['codigoBarras']) : null;
            if ($codigoBarras !== null && $this->productsModel->barcodeInUse($codigoBarras)) {
                throw new Exception('Ese código de barras ya está asignado a otro producto');
            }

            $path = __DIR__ . '/../../Public/Assets/img/products';
            $tieneArchivo = isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK;
            $imagenUrl = trim($_POST['imagenUrl'] ?? '');

            // Si viene por URL, se descarga y VALIDA antes de crear el producto
            // (así un enlace malo no deja registros a medias).
            $urlTemp = null;
            if (!$tieneArchivo && $imagenUrl !== '') {
                $urlTemp = downloadImageToTemp($imagenUrl);
                if (!$urlTemp['success']) {
                    throw new Exception('No se pudo usar el enlace de imagen: ' . $urlTemp['error']);
                }
            }

            // Crear producto (por defecto sin imagen)
            $idProducto = $this->productsModel->create($idCategoria, $nombre, $tipo, $precioVenta, $precioCompra, 'default.png', $idUnidadBase, $manejaStock, 1, $codigoBarras);

            $imagenFinal = null;
            if ($tieneArchivo) {
                $ext = $_FILES['imagen']['type'] ? explode('/', $_FILES['imagen']['type'])[1] : 'png';
                $image = $idProducto . '.' . $ext;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $path . DIRECTORY_SEPARATOR . $image)) {
                    $imagenFinal = $image;
                }
            } elseif ($urlTemp) {
                $imagenFinal = placeImageFromTemp($urlTemp['tmp'], $urlTemp['ext'], $path, (string) $idProducto) ?: null;
            }

            if ($imagenFinal) {
                $this->productsModel->update($idProducto, [
                    'idCategoria' => $idCategoria,
                    'nombre' => $nombre,
                    'codigoBarras' => $codigoBarras,
                    'tipo' => $tipo,
                    'precioCompra' => $precioCompra,
                    'precioVenta' => $precioVenta,
                    'imagen' => $imagenFinal,
                    'idUnidadBase' => $idUnidadBase,
                    'manejaStock' => $manejaStock,
                    'estado' => 1
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'idProducto' => $idProducto
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
	}

   
    // Obtener un producto por ID (JSON)
    public function getProduct() {
        header('Content-Type: application/json; charset=utf-8');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
            return;
        }

        try {
            $product = $this->productsModel->getById($id);
            if ($product) {
                echo json_encode(['success' => true, 'data' => $product]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Obtener lista de productos (JSON) para el admin
    public function getProducts() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            
            $list = $this->productsModel->getAll();
            echo json_encode(['success' => true, 'data' => $list]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Actualizar producto (acepta imagen mediante POST['imagen'] o $_FILES)
    public function updateProduct() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            // Validar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            // Validar ID del producto
            $idProducto = $_POST['idProducto'] ?? null;
            if (!$idProducto) {
                throw new Exception('ID de producto no proporcionado');
            }

            // Obtener producto actual
        
            $currentProduct = $this->productsModel->getById($idProducto);
            if (!$currentProduct) {
                throw new Exception('Producto no encontrado');
            }

            // Validar datos requeridos
            if (empty($_POST['categoria'])) throw new Exception('La categoría es requerida');
            if (empty($_POST['nombre'])) throw new Exception('El nombre es requerido');
            if (empty($_POST['tipo'])) throw new Exception('El tipo es requerido');

            // Preparar datos comunes
            $idCategoria = $_POST['categoria'];
            $nombre = trim($_POST['nombre']);
            $tipo = $_POST['tipo'];
            $manejaStock = isset($_POST['manejaStock']) ? (int)$_POST['manejaStock'] : 0;
            $idUnidadBase = !empty($_POST['idUnidadBase']) ? (int)$_POST['idUnidadBase'] : ($currentProduct['idUnidadBase'] ?? null);
            $precioCompra = !empty($_POST['precioCompra']) ? floatval($_POST['precioCompra']) : null;
            $precioVenta = !empty($_POST['precioVenta']) ? floatval($_POST['precioVenta']) : null;

            // Código de barras opcional; si viene, no puede estar en otro producto.
            $codigoBarras = !empty($_POST['codigoBarras']) ? trim($_POST['codigoBarras']) : null;
            if ($codigoBarras !== null && $this->productsModel->barcodeInUse($codigoBarras, $idProducto)) {
                throw new Exception('Ese código de barras ya está asignado a otro producto');
            }

            $path = __DIR__ . '/../../Public/Assets/img/products';
            $currentimage = $currentProduct['imagen'] ?? 'default.png';
            $tieneArchivo = isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK;
            $imagenUrl = trim($_POST['imagenUrl'] ?? '');
            $quitarImagen = !empty($_POST['quitarImagen']);

            // Por defecto se conserva la imagen actual
            $imagenFinal = $currentimage;

            if ($quitarImagen) {
                // Quitar imagen: borra el archivo y vuelve al default
                deleteImageFile($path, $currentimage);
                $imagenFinal = 'default.png';
            } elseif ($tieneArchivo) {
                $ext = $_FILES['imagen']['type'] ? explode('/', $_FILES['imagen']['type'])[1] : 'png';
                $image = $idProducto . '.' . $ext;
                deleteImageFile($path, $currentimage);
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $path . DIRECTORY_SEPARATOR . $image)) {
                    $imagenFinal = $image;
                } else {
                    throw new Exception('Error al subir la imagen. Verifica los permisos de la carpeta.');
                }
            } elseif ($imagenUrl !== '') {
                // Descargar y validar antes de reemplazar
                $urlTemp = downloadImageToTemp($imagenUrl);
                if (!$urlTemp['success']) {
                    throw new Exception('No se pudo usar el enlace de imagen: ' . $urlTemp['error']);
                }
                $nuevo = placeImageFromTemp($urlTemp['tmp'], $urlTemp['ext'], $path, (string) $idProducto, $currentimage);
                if ($nuevo === false) {
                    throw new Exception('No se pudo guardar la imagen descargada');
                }
                $imagenFinal = $nuevo;
            }

            $success = $this->productsModel->update($idProducto, [
                'idCategoria' => $idCategoria,
                'nombre' => $nombre,
                'codigoBarras' => $codigoBarras,
                'tipo' => $tipo,
                'precioCompra' => $precioCompra,
                'precioVenta' => $precioVenta,
                'imagen' => $imagenFinal,
                'idUnidadBase' => $idUnidadBase,
                'manejaStock' => $manejaStock,
                'estado' => $currentProduct['estado'] ?? 1
            ]);

            // Actualizar producto
            
            if (!$success) {
                throw new Exception('Error al actualizar el producto en la base de datos');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Producto actualizado exitosamente'
            ]);

        } catch (Exception $e) {

            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    // Desactivar o reactivar producto (soft delete)
    public function deleteProduct() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $id = $_GET['id'] ?? null;
        $status = isset($_GET['status']) ? (int)$_GET['status'] : 0; // 0 = inactivo, 1 = activo
        if (!$id) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
            return;
        }

        try {
            $currentProduct = $this->productsModel->getById($id);
            if (!$currentProduct) {
                throw new Exception('Producto no encontrado');
            }

            $success = $this->productsModel->setStatus($id, $status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $success,
                'estado' => $status,
                'message' => $status ? 'Producto activado' : 'Producto desactivado'
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getCategories() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            
            $categories = $this->categoriesModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $categories
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Repara los permisos de las imágenes de productos y categorías.
     *
     * Una imagen guardada por el servidor puede quedar accesible solo para su
     * propia cuenta: la aplicación la muestra bien, pero Windows no la deja
     * copiar, y al respaldar o migrar esos productos se quedan sin foto.
     *
     * Aquí se reescribe cada imagen desde el propio servidor —la única cuenta
     * que puede leerlas— para que queden con los permisos normales de su
     * carpeta. Conviene ejecutarlo antes de migrar al servidor.
     */
    public function repararImagenes() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $base = (defined('PUBLIC_PATH') ? PUBLIC_PATH : dirname(dirname(__DIR__)) . '/Public')
                  . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR . 'img';

            $carpetas = ['products', 'categories'];
            $reparadas = 0;
            $ilegibles = [];
            $revisadas = 0;

            foreach ($carpetas as $carpeta) {
                $ruta = $base . DIRECTORY_SEPARATOR . $carpeta;
                if (!is_dir($ruta)) {
                    continue;
                }

                foreach (scandir($ruta) ?: [] as $archivo) {
                    if ($archivo === '.' || $archivo === '..') {
                        continue;
                    }
                    $completa = $ruta . DIRECTORY_SEPARATOR . $archivo;
                    if (!is_file($completa)) {
                        continue;
                    }

                    $revisadas++;
                    if (normalizarPermisosImagen($completa)) {
                        $reparadas++;
                    } else {
                        $ilegibles[] = $carpeta . '/' . $archivo;
                    }
                }
            }

            echo json_encode([
                'success'   => true,
                'revisadas' => $revisadas,
                'reparadas' => $reparadas,
                'ilegibles' => $ilegibles,
                'message'   => "Se revisaron {$revisadas} imágenes y se normalizaron {$reparadas}.",
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getUnits() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $units = $this->unitsModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $units
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function createCategory() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $nombre = $_POST['nombre'] ?? null;
            if (!$nombre) {
                throw new Exception('El nombre de la categoría es requerido');
            }
            $catFolder = __DIR__ . '/../../Public/Assets/img/categories';
            $tieneArchivo = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
            $imagenUrl = trim($_POST['imageUrl'] ?? '');

            // Validar URL antes de crear (si aplica)
            $urlTemp = null;
            if (!$tieneArchivo && $imagenUrl !== '') {
                $urlTemp = downloadImageToTemp($imagenUrl);
                if (!$urlTemp['success']) {
                    throw new Exception('No se pudo usar el enlace de imagen: ' . $urlTemp['error']);
                }
            }

            $idCategoria = $this->categoriesModel->create($nombre);

            $image = 'default.png';
            if ($tieneArchivo) {
                $ext = $_FILES['image']['type'] ? explode('/', $_FILES['image']['type'])[1] : 'png';
                $image = $idCategoria . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $catFolder . DIRECTORY_SEPARATOR . $image);
            } elseif ($urlTemp) {
                $image = placeImageFromTemp($urlTemp['tmp'], $urlTemp['ext'], $catFolder, (string) $idCategoria) ?: 'default.png';
            }
            $this->categoriesModel->insertImage($idCategoria, $image);

            echo json_encode([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'idCategoria' => $idCategoria
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getCategory() {
        header('Content-Type: application/json; charset=utf-8');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
            return;
        }
        try {
            $category = $this->categoriesModel->getById($id);
            if ($category) {
                echo json_encode([
                    'success' => true,
                    'data' => $category
                ]);
            } else {
                throw new Exception('Categoría no encontrada');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateCategory() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            
            $idCategoria = $_POST['id'] ?? $_POST['idCategoria'] ?? null;
            $nombre = $_POST['nombre'] ?? null;
            $path = __DIR__ . '/../../Public/Assets/img/categories/';
            
            if (!$idCategoria || !$nombre) {
                throw new Exception('ID y nombre de la categoría son requeridos');
            }

            $catFolder = __DIR__ . '/../../Public/Assets/img/categories';
            $currentCategory = $this->categoriesModel->getById($idCategoria);
            $currentimage = $currentCategory['imagen'] ?? 'default.png';
            $tieneArchivo = isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK;
            $imagenUrl = trim($_POST['imageUrl'] ?? '');
            $quitarImagen = !empty($_POST['quitarImagen']);

            // Por defecto conserva la imagen actual (antes se perdía al editar)
            $image = $currentimage;

            if ($quitarImagen) {
                deleteImageFile($catFolder, $currentimage);
                $image = 'default.png';
            } elseif ($tieneArchivo) {
                $ext = $_FILES['image']['type'] ? explode('/', $_FILES['image']['type'])[1] : 'png';
                deleteImageFile($catFolder, $currentimage);
                $image = $idCategoria . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $catFolder . DIRECTORY_SEPARATOR . $image);
            } elseif ($imagenUrl !== '') {
                $urlTemp = downloadImageToTemp($imagenUrl);
                if (!$urlTemp['success']) {
                    throw new Exception('No se pudo usar el enlace de imagen: ' . $urlTemp['error']);
                }
                $nuevo = placeImageFromTemp($urlTemp['tmp'], $urlTemp['ext'], $catFolder, (string) $idCategoria, $currentimage);
                if ($nuevo === false) {
                    throw new Exception('No se pudo guardar la imagen descargada');
                }
                $image = $nuevo;
            }

            $success = $this->categoriesModel->update($idCategoria, $nombre, $image);
            
            
            

            if (!$success) {
                throw new Exception('Error al actualizar la categoría en la base de datos');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente'
            ]);
        } catch (Exception $e) {

            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteCategory() {
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $idCategoria = $_GET['id'] ?? null;

            if (!$idCategoria) {
                throw new Exception('ID de la categoría es requerido');
            }

            // Eliminar imagen física si existe
            $currentCategory = $this->categoriesModel->getById($idCategoria);
            if ($currentCategory && !empty($currentCategory['imagen'])) {
                $dest = __DIR__ . '/../../Public/Assets/img/categories/';
                $imagePath = $dest . DIRECTORY_SEPARATOR . basename($currentCategory['imagen']);
                if (is_file($imagePath)) {
                    @unlink($imagePath);
                }
            }

            $this->categoriesModel->delete($idCategoria);
            echo json_encode([
                'success' => true,
                'message' => 'Categoría eliminada exitosamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}