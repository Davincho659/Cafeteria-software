<?php
require_once __DIR__ . '/../Models/Products.php';
require_once __DIR__ . '/../Models/Categories.php';
require_once __DIR__ . '/../Models/Tables.php';
require_once __DIR__ . '/../Models/Sales.php';

class SalesController {
    private $productModel;
    private $categoriesModel;
    private $tablesModel;
    private $salesModel;

    public function __construct() {
        $this->productModel = new Products();
        $this->categoriesModel = new Categories();
        $this->tablesModel = new Tables();
        $this->salesModel = new Sales();
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

    /**
     * Obtener productos (todos o por categoría)
     * @param GET idCategory (opcional)
     * @return JSON { success: bool, data: [...productos...] }
     */
    public function getProducts() {
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

    public function CreateSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $salesData = json_decode(file_get_contents('php://input'), true);
            
            $idMesa = $salesData['tableId'] ?? null;
            $metodoPago = $salesData['metodoPago'];
            $total = $salesData['total'];
            $idUsuario = $salesData['idUsuario'];
            $productos = $salesData['productos'];

            $sale = $this->salesModel->createSale(null,$idMesa,'completada',$metodoPago,$total,$idUsuario);

            foreach ($productos as $item) {
                $this->salesModel->createSalesDetail(intval($sale),null,intval($item['idProducto']),$item['cantidad'],$item['precioUnitario']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Venta creada exitosamente',
                'saleId' => $sale
            ]);
        } catch (Exeption $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } 
    }

    public function GetSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'id inválido']);
                return;
            }

            $venta = $this->salesModel->getSaleById($id);
            if (!$venta) {
                echo json_encode(['success' => false, 'error' => 'Venta no encontrada']);
                return;
            }

            $detalles = $this->salesModel->getSaleDetails($id);

            echo json_encode([
                'success' => true,
                'data' => [
                    'venta' => $venta,
                    'detalles' => $detalles
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    function seeTodayBills() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $sales = $this->salesModel->getSalesByDate(date('Y-m-d'));
            echo json_encode([
                'success' => true,
                'data' => $sales
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /* public function dayBills() {
        // Recolectar filtros desde GET/POST (preferir GET para AJAX)
        $filters = [];
        $filters['idventa'] = $_REQUEST['idventa'] ?? null;
        $filters['preciodesde'] = $_REQUEST['preciodesde'] ?? null;
        $filters['preciohasta'] = $_REQUEST['preciohasta'] ?? null;
        $filters['fechadesde'] = $_REQUEST['fechadesde'] ?? null;
        $filters['fechahasta'] = $_REQUEST['fechahasta'] ?? null;
        $filters['metodopago'] = $_REQUEST['metodopago'] ?? null;

        // Obtener resultados filtrados
        try {
            $resultados = $this->salesModel->getFilteredSales($filters);
        } catch (Exception $e) {
            $resultados = [];
        }

        // Hacer disponible la variable a la vista
        // La vista dayBills.view.php ya respeta ?ajax=1 para no incluir header
        require_once __DIR__ . '/../Views/dayBills.view.php';
    } */
}