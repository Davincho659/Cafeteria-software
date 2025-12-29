<?php

require_once __DIR__ . '/../Models/products.php';
require_once __DIR__ . '/../Models/Categories.php';
require_once __DIR__ . '/../Core/Functions.php';

class ProductController {

    private $productsModel;
    private $categoriesModel;

    public function __construct() {
        $this->productsModel = new Products();
        $this->categoriesModel = new Categories();
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
            $precioCompra = !empty($_POST['precioCompra']) ? floatval($_POST['precioCompra']) : null;
            $precioVenta = !empty($_POST['precioVenta']) ? floatval($_POST['precioVenta']) : null;

            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $idProducto = $this->productsModel->create($idCategoria, $nombre, $tipo, $precioVenta, $precioCompra, null);
                $imageName = $_FILES['imagen']['name'] ?? null;
                $file = $_FILES['imagen']['tmp_name'] ?? null;
                $imageTipe = $_FILES['imagen']['type'] ? explode('/', $_FILES['imagen']['type'])[1] : null;
                $image = $idProducto.".".$imageTipe;
                $path = __DIR__ . '/../../Public/Assets/img/products/' . $image;
                
                move_uploaded_file($file, $path);

                $this->productsModel->update($idProducto, [
                    'idCategoria' => $idCategoria,
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'precioCompra' => $precioCompra,
                    'precioVenta' => $precioVenta,
                    'imagen' => $image,
                    'idUnidadBase' => 1,
                    'manejaStock' => 0
                ]);
            } else {
                $image = 'default.png';
                $idProducto = $this->productsModel->create($idCategoria, $nombre, $tipo, $precioVenta, $precioCompra, $image);
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

            $path = __DIR__ . '/../../Public/Assets/img/products';

            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                
                $currentimage = $currentProduct['imagen'] ?? null;
                
                $imageName = $_FILES['imagen']['name'] ?? null;
                $file = $_FILES['imagen']['tmp_name'] ?? null;
                $imageTipe = $_FILES['imagen']['type'] ? explode('/', $_FILES['imagen']['type'])[1] : null;
                $image = $idProducto.".".$imageTipe;

                if (file_exists($path . $currentimage)) {
                    @unlink($path . $currentimage);
                }
                
                move_uploaded_file($file, $path. $image);
                $success = $this->productsModel->update($idProducto, [
                    'idCategoria' => $_POST['categoria'],
                    'nombre' => trim($_POST['nombre']),
                    'tipo' => $_POST['tipo'],
                    'precioCompra' => !empty($_POST['precioCompra']) ? floatval($_POST['precioCompra']) : null,
                    'precioVenta' => !empty($_POST['precioVenta']) ? floatval($_POST['precioVenta']) : null,
                    'imagen' => $image,
                    'idUnidadBase' => $currentProduct['idUnidadBase'] ?? 1,
                    'manejaStock' => $currentProduct['manejaStock'] ?? 0
                ]);
            } else {
                $success = $this->productsModel->update($idProducto, [
                    'idCategoria' => $_POST['categoria'],
                    'nombre' => trim($_POST['nombre']),
                    'tipo' => $_POST['tipo'],
                    'precioCompra' => !empty($_POST['precioCompra']) ? floatval($_POST['precioCompra']) : null,
                    'precioVenta' => !empty($_POST['precioVenta']) ? floatval($_POST['precioVenta']) : null,
                    'imagen' => $currentProduct['imagen'],
                    'idUnidadBase' => $currentProduct['idUnidadBase'] ?? 1,
                    'manejaStock' => $currentProduct['manejaStock'] ?? 0
                ]);
            }

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

    // Eliminar producto (y su imagen física si existe)
    public function deleteProduct() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
            return;
        }

        try {
            $currentProduct = $this->productsModel->getById($id);
            if ($currentProduct && !empty($currentProduct['imagen'])) {
                $dest = __DIR__ . '/../../Public/Assets/img/products';
                $imagePath = $dest . DIRECTORY_SEPARATOR . basename($currentProduct['imagen']);
                if (file_exists($imagePath)) @unlink($imagePath);
            }
            $success = $this->productsModel->delete($id);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => $success]);
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
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $idCategoria = $this->categoriesModel->create($nombre);
                $imageName = $_FILES['image']['name'] ?? null;
                $file = $_FILES['image']['tmp_name'] ?? null;
                $imageTipe = $_FILES['image']['type'] ? explode('/', $_FILES['image']['type'])[1] : null;
                $image = $idCategoria.".".$imageTipe;
                $path = __DIR__ . '/../../Public/Assets/img/categories/' . $image;
                
                move_uploaded_file($file, $path);

                $this->categoriesModel->insertImage($idCategoria, $image);
            } else {
                $image = 'default.png';
                $idCategoria = $this->categoriesModel->create($nombre);
                $this->categoriesModel->insertImage($idCategoria, $image);
            }

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

            if (isset($_FILES['image'] ) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                
                $currentCategory = $this->categoriesModel->getById($idCategoria);
                $currentimage = $currentCategory['imagen'] ?? null;
                
                $imageName = $_FILES['image']['name'] ?? null;
                $file = $_FILES['image']['tmp_name'] ?? null;
                $imageTipe = $_FILES['image']['type'] ? explode('/', $_FILES['image']['type'])[1] : null;
                $image = $idCategoria.".".$imageTipe;

                if (file_exists($path . $currentimage)) {
                    @unlink($path . $currentimage);
                }
                

                move_uploaded_file($file, $path. $image);
                $success = $this->categoriesModel->update($idCategoria, $nombre, $image);
            } else {
                $image = 'default.png';
                $success = $this->categoriesModel->update($idCategoria, $nombre, $image);
            }
            
            
            

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