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
            $categories= $this->categoriesModel->getAll();
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

    public function GetProducts() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idCategory = $_GET['idCategory'] ?? null;
            if ($idCategory == null) {
                $products = $this->productModel->getAll();
                echo json_encode([
                    'success' => true,
                    'data' => $products
                ]);
            } else {
                $products = $this->productModel->getByCategory($idCategory);
                echo json_encode([
                    'success' => true,
                    'data' => $products
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