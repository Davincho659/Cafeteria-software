<?php
require_once __DIR__ . '/../Models/Products.php';
require_once __DIR__ . '/../Models/Categories.php';





class SalesController {
    private $productModel;
    private $categoriesModel;

    public function __construct() {
        $this->productModel = new Products();
        $this->categoriesModel = new Categories();
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

    public function GetProducts() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idCategory = $_GET['idCategory'] ?? null;
            if ($idCategory == null) {
                $productos = $this->productModel->getAll();
                echo json_encode([
                    'success' => true,
                    'data' => $productos
                ]);
            } else {
                $productos = $this->productModel->getByCategory($idCategory);
                echo json_encode([
                    'success' => true,
                    'data' => $productos
                ]);
            }
            
        } catch (Exeption $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        
    }
}