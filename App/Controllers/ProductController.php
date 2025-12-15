<?php

require_once __DIR__ . '/../Models/products.php';
require_once __DIR__ . '/../Models/Categories.php';
require_once __DIR__ . '/../Core/Functions.php';

class ProductController {
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

            // Procesar imagen si existe
            $imageDbPath = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $dest = __DIR__ . '/../../Public/Assets/img/products';
                
                // Asegurar que el directorio existe
                if (!is_dir($dest)) {
                    mkdir($dest, 0755, true);
                }
                
                $res = saveUploadedImage($_FILES['imagen'], $dest);
                if (!$res['success']) {
                    throw new Exception($res['error'] ?? 'Error al subir la imagen');
                }
                $imageDbPath = 'products/' . $res['filename'];
            }

            // Crear producto
            $products = new Products();
            $result = $products->create($idCategoria, $nombre, $tipo, $precioVenta, $precioCompra, $imageDbPath);

            if (!$result) {
                throw new Exception('Error al crear el producto en la base de datos');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Producto creado exitosamente'
            ]);

        } catch (Exception $e) {
            // Si hubo error y se subió una imagen, intentar eliminarla
            if (isset($imageDbPath)) {
                $imagePath = __DIR__ . '/../../Public/Assets/img/' . $imageDbPath;
                if (file_exists($imagePath)) @unlink($imagePath);
            }

            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
	}

    
    /* public function uploadImage() {
            header('Content-Type: application/json');
            
            if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'error' => 'No se recibió la imagen']);
                return;
            }

            $dest = __DIR__ . '/../../Public/Assets/img/products';
            $res = saveUploadedImage($_FILES['imagen'], $dest);
            
            if ($res['success']) {
                echo json_encode([
                    'success' => true,
                    'path' => 'products/' . $res['filename']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al guardar la imagen']);
            }
        }
     */
	

    // Obtener un producto por ID (JSON)
    public function getProduct() {
        header('Content-Type: application/json; charset=utf-8');
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
            return;
        }

        try {
            $products = new Products();
            $product = $products->getById($id);
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
            $products = new Products();
            $list = $products->getAll();
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
            $products = new Products();
            $currentProduct = $products->getById($idProducto);
            if (!$currentProduct) {
                throw new Exception('Producto no encontrado');
            }

            // Validar datos requeridos
            if (empty($_POST['categoria'])) throw new Exception('La categoría es requerida');
            if (empty($_POST['nombre'])) throw new Exception('El nombre es requerido');
            if (empty($_POST['tipo'])) throw new Exception('El tipo es requerido');

            // Preparar datos de actualización
            $updateData = [
                'idCategoria' => $_POST['categoria'],
                'nombre' => trim($_POST['nombre']),
                'tipo' => $_POST['tipo'],
                'precioCompra' => !empty($_POST['precioCompra']) ? floatval($_POST['precioCompra']) : null,
                'precioVenta' => !empty($_POST['precioVenta']) ? floatval($_POST['precioVenta']) : null,
                'imagen' => $currentProduct['imagen'] // Mantener imagen actual por defecto
            ];

            // Procesar nueva imagen si existe
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $dest = __DIR__ . '/../../Public/Assets/img/products';
                
                // Asegurar que el directorio existe
                if (!is_dir($dest)) {
                    mkdir($dest, 0755, true);
                }

                $res = saveUploadedImage($_FILES['imagen'], $dest, $currentProduct['imagen'] ?? null);
                if (!$res['success']) {
                    throw new Exception($res['error'] ?? 'Error al subir la imagen');
                }
                $updateData['imagen'] = 'products/' . $res['filename'];
            }

            // Actualizar producto
            $success = $products->update($idProducto, $updateData);
            if (!$success) {
                throw new Exception('Error al actualizar el producto en la base de datos');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Producto actualizado exitosamente'
            ]);

        } catch (Exception $e) {
            // Si hubo error al actualizar y se subió una imagen nueva, intentar eliminarla
            if (isset($res) && isset($res['filename'])) {
                $newImagePath = $dest . '/' . $res['filename'];
                if (file_exists($newImagePath)) @unlink($newImagePath);
            }

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
            $products = new Products();
            $currentProduct = $products->getById($id);
            if ($currentProduct && !empty($currentProduct['imagen'])) {
                $imagePath = __DIR__ . '/../../Public/Assets/img/' . $currentProduct['imagen'];
                if (file_exists($imagePath)) @unlink($imagePath);
            }
            $success = $products->delete($id);
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
            $categoriesModel = new Categories();
            $categories = $categoriesModel->getAll();
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

    public function createCategorie() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $nombre = $_POST['nombre'] ?? null;
            if (!$nombre) {
                throw new Exception('El nombre de la categoría es requerido');
            }

            $categoriesModel = new Categories();
            $idCategoria = $categoriesModel->create($nombre);

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

    public function updateCategories() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $idCategoria = $_POST['idCategoria'] ?? null;
            $nombre = $_POST['nombre'] ?? null;
            if (!$idCategoria || !$nombre) {
                throw new Exception('ID y nombre de la categoría son requeridos');
            }

            $categoriesModel = new Categories();
            // Aquí deberías implementar el método update en el modelo Categories
            $success = $categoriesModel->update($idCategoria, $nombre);

            if (!$success) {
                throw new Exception('Error al actualizar la categoría');
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
}