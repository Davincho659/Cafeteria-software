<?php

require_once __DIR__ . '/../Models/Products.php';
require_once __DIR__ . '/../Models/Categories.php';

require "../App/Views/sales.view.php";
class SalesController {
    private $productModel;
    private $categoriesModel;

    public function __construct() {
        $this->productModel = new Products();
        $this->categoriesModel = new Categories();
    }

    public function index() {
        require "../App/Views/sales.view.php";
    }

    public function getCategories() {
        try {
            $categorias= $this->categoriesModel->getAllCategories();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $categorias
            ]);
            exit;
        } catch (Exception $e) {
            // Manejo de errores
            http_response_code(500);
            echo "Error al obtener categorías: " . $e->getMessage();
        }
    }
}