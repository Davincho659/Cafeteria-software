<?php
require_once __DIR__ . '/../Models/Products.php';
require_once __DIR__ . '/../Models/Categories.php';
require_once __DIR__ . '/../Models/Tables.php';

class SalesController {
    private $productModel;
    private $categoriesModel;
    private $tablesModel;

    public function __construct() {
        $this->productModel = new Products();
        $this->categoriesModel = new Categories();
        $this->tablesModel = new Tables();
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

    public function GetTables() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $tables = $this->tablesModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $tables
            ]);
        } catch (Exeption $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } 
    }
}