<?php
require_once __DIR__ . '/../Models/Purchases.php';
require_once __DIR__ . '/../Models/Suppliers.php';
require_once __DIR__ . '/../Models/Products.php';

class PurchasesController {
    private $purchasesModel;
    private $suppliersModel;
    private $productsModel;

    public function __construct() {
        $this->purchasesModel = new Purchases();
        $this->suppliersModel = new Suppliers();
        $this->productsModel = new Products();
    }

    /**
     * Mostrar vista de compras
     */
    public function index() {
        require_once __DIR__ . '/../Views/purchases.view.php';
    }

    /**
     * Obtener proveedores (JSON)
     */
    public function getSuppliers() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $suppliers = $this->suppliersModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener productos (JSON)
     */
    public function getProducts() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $products = $this->productsModel->getAll();
            echo json_encode([
                'success' => true,
                'data' => $products
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crear compra detallada (con productos)
     */
    public function createDetailedPurchase() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos
            if (empty($data['idProveedor'])) {
                throw new Exception('El proveedor es requerido');
            }
            if (empty($data['productos']) || !is_array($data['productos'])) {
                throw new Exception('Debe agregar al menos un producto');
            }

            $idUsuario = $data['idUsuario'] ?? null;

            // Calcular total en servidor para evitar desajustes
            $total = 0;
            foreach ($data['productos'] as $item) {
                $cantidad = isset($item['cantidad']) ? (float)$item['cantidad'] : 0;
                $precio = isset($item['precioUnitario']) ? (float)$item['precioUnitario'] : 0;
                if ($cantidad <= 0) {
                    throw new Exception('Cantidad inválida para un producto');
                }
                if ($precio < 0) {
                    throw new Exception('Precio inválido para un producto');
                }
                $total += $cantidad * $precio;
            }

            if ($total <= 0) {
                throw new Exception('El total calculado es inválido');
            }

            // Crear compra + detalles en una sola transacción
            $idCompra = $this->purchasesModel->createPurchaseWithDetails(
                $data['idProveedor'],
                $data['productos'],
                $idUsuario
            );

            echo json_encode([
                'success' => true,
                'message' => 'Compra registrada exitosamente',
                'idCompra' => $idCompra
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crear compra rápida (solo total)
     */
    public function createQuickPurchase() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idProveedor'])) {
                throw new Exception('El proveedor es requerido');
            }
            if (!isset($data['total'])) {
                throw new Exception('El total es requerido');
            }
            if (empty($data['descripcion'])) {
                throw new Exception('La descripción es requerida para compras rápidas');
            }

            $total = (float)$data['total'];
            if ($total <= 0) {
                throw new Exception('El total debe ser mayor a cero');
            }

            $idUsuario = $data['idUsuario'] ?? null;

            // Crear compra rápida
            $idCompra = $this->purchasesModel->createPurchase(
                $data['idProveedor'],
                $total,
                'rapida',
                $data['descripcion'],
                $idUsuario
            );

            echo json_encode([
                'success' => true,
                'message' => 'Compra rápida registrada exitosamente',
                'idCompra' => $idCompra
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener compras (todas o filtradas)
     */
    public function getPurchases() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $filtros = [];
            
            if (isset($_GET['idProveedor'])) {
                $filtros['idProveedor'] = $_GET['idProveedor'];
            }
            if (isset($_GET['tipoCompra'])) {
                $filtros['tipoCompra'] = $_GET['tipoCompra'];
            }
            if (isset($_GET['fechaDesde'])) {
                $filtros['fechaDesde'] = $_GET['fechaDesde'];
            }
            if (isset($_GET['fechaHasta'])) {
                $filtros['fechaHasta'] = $_GET['fechaHasta'];
            }

            $purchases = empty($filtros) 
                ? $this->purchasesModel->getAll() 
                : $this->purchasesModel->getFiltered($filtros);

            echo json_encode([
                'success' => true,
                'data' => $purchases
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener compra por ID
     */
    public function getPurchase() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            $purchase = $this->purchasesModel->getById($id);
            if (!$purchase) {
                throw new Exception('Compra no encontrada');
            }

            $details = $this->purchasesModel->getDetails($id);

            echo json_encode([
                'success' => true,
                'data' => [
                    'compra' => $purchase,
                    'detalles' => $details
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener compras del día
     */
    public function getTodayPurchases() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $purchases = $this->purchasesModel->getTodayPurchases();
            echo json_encode([
                'success' => true,
                'data' => $purchases
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}