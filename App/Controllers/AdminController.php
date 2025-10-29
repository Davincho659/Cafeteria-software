<?php

require_once __DIR__ . '/../Models/products.php';
require_once __DIR__ . '/../Models/Categories.php';
require_once __DIR__ . '/../Core/Functions.php';

class AdminController {
	// Procesa la creación de un producto (formulario multipart/form-data)
	public function createProduct() {
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header('Location: ?pg=admin');
			exit;
		}

		$idCategoria = $_POST['categoria'] ?? null;
		$nombre = $_POST['nombre'] ?? '';
		$tipo = $_POST['tipo'] ?? '';
		$precioCompra = $_POST['precioCompra'] ?? null;
		$precioVenta = $_POST['precioVenta'] ?? null;

		// manejar imagen
		$imageDbPath = null;
		if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
			$dest = __DIR__ . '/../../Public/Assets/img/products';
			$res = saveUploadedImage($_FILES['imagen'], $dest);
			if (!empty($res) && isset($res['success']) && $res['success']) {
				$imageDbPath = 'products/' . $res['filename'];
			} else {
				// Aquí podrías manejar errores, p.ej. guardar en sesión un mensaje
			}
		}

		$products = new Products();
		$products->create($idCategoria, $nombre, $tipo, $precioVenta, $precioCompra, $imageDbPath);

		header('Location: ?pg=admin');
		exit;
	}

    public function getCategories() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $categorias= $this->categoriesModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $categorias
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}