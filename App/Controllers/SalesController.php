<?php
require_once __DIR__ . '/../Models/Products.php';
require_once __DIR__ . '/../Models/Categories.php';
require_once __DIR__ . '/../Models/Tables.php';
require_once __DIR__ . '/../Models/Sales.php';
require_once __DIR__ . '/../Models/Inventory.php';

class SalesController {
    private $productModel;
    private $categoriesModel;
    private $tablesModel;
    private $salesModel;
    private $inventoryModel;

    public function __construct() {
        $this->productModel = new Products();
        $this->categoriesModel = new Categories();
        $this->tablesModel = new Tables();
        $this->salesModel = new Sales();
        $this->inventoryModel = new Inventory();
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

    public function getProducts() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idCategory = $_GET['idCategory'] ?? null;
            
            if ($idCategory == null) {
                $products = $this->productModel->getAll();
            } else {
                $products = $this->productModel->getByCategory($idCategory);
            }
            
            foreach ($products as &$product) {
                if ($product['manejaStock']) {
                    $product['stockActual'] = $this->inventoryModel->obtenerStockActual($product['idProducto']);
                } else {
                    $product['stockActual'] = null;
                }
            }
            
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

    public function GetTables() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $tables = $this->tablesModel->getAllWithActiveSales();
            echo json_encode([
                'success' => true,
                'data' => $tables
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } 
    }

    /**
     * Transferir productos del carrito a una mesa o abrir una mesa vacía
     * 
     * Este método maneja dos casos:
     * 1. Si hay productos: los transfiere del carrito a la mesa
     * 2. Si no hay productos: crea una nueva venta vacía en la mesa
     * 
     * En ambos casos, la mesa queda marcada como ocupada
     */
    public function transferProductsToTable() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validaciones de entrada
            if (empty($data['idMesa'])) {
                throw new Exception('El ID de la mesa es requerido');
            }
            if (!isset($data['productos']) || !is_array($data['productos'])) {
                throw new Exception('El array de productos es requerido');
            }
            
            $idMesa = intval($data['idMesa']);
            $productos = $data['productos'];
            $idUsuario = $data['idUsuario'] ?? $_SESSION['usuario_id'] ?? null;
            $tieneProductos = count($productos) > 0;

            // Verificar que la mesa exista
            $mesa = $this->tablesModel->getById($idMesa);
            if (!$mesa) {
                throw new Exception('Mesa no encontrada');
            }

            // Verificar que la mesa esté disponible
            if (!empty($mesa['idVenta'])) {
                throw new Exception('La mesa ya está ocupada');
            }

            // Crear o obtener venta para la mesa
            $idVenta = $this->salesModel->getOrCreateTableSale($idMesa, $idUsuario);
            
            $productosAgregados = [];
            
            // Si hay productos, agregarlos a la venta
            if ($tieneProductos) {
                foreach ($productos as $producto) {
                    $idProducto = intval($producto['idProducto']);
                    $cantidad = intval($producto['cantidad']);
                    $precioUnitario = floatval($producto['precioUnitario']);

                    // Validar que el producto exista
                    $productoData = $this->productModel->getById($idProducto);
                    if (!$productoData) {
                        throw new Exception("Producto no encontrado: ID {$idProducto}");
                    }

                    // Validar stock si el producto lo maneja
                    if ($productoData['manejaStock']) {
                        if (!$this->inventoryModel->verificarStock($idProducto, $cantidad)) {
                            throw new Exception("Stock insuficiente para: " . $productoData['nombre']);
                        }
                    }

                    // Agregar producto a la venta
                    $idDetalle = $this->salesModel->addOrUpdateProductToSale(
                        $idVenta,
                        $idProducto,
                        $cantidad,
                        $precioUnitario,
                        $idUsuario
                    );

                    $productosAgregados[] = [
                        'idDetalle' => $idDetalle,
                        'idProducto' => $idProducto,
                        'nombre' => $productoData['nombre'],
                        'cantidad' => $cantidad,
                        'precioUnitario' => $precioUnitario
                    ];
                }
            }

            // Obtener venta actualizada con todos los detalles
            $venta = $this->salesModel->getSaleById($idVenta);
            $detalles = $this->salesModel->getSaleDetails($idVenta);

            // Mensaje descriptivo según el caso
            $mensaje = $tieneProductos 
                ? count($productosAgregados) . ' producto(s) transferido(s) a la mesa ' . $mesa['numero']
                : 'Mesa ' . $mesa['numero'] . ' abierta correctamente';

            echo json_encode([
                'success' => true,
                'message' => $mensaje,
                'data' => [
                    'idVenta' => $idVenta,
                    'idMesa' => $idMesa,
                    'numeroMesa' => $mesa['numero'],
                    'venta' => $venta,
                    'productos' => $detalles,
                    'productosAgregados' => $productosAgregados,
                    'tieneProductos' => $tieneProductos
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function openTableSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idMesa'])) {
                throw new Exception('El ID de la mesa es requerido');
            }
            
            $idMesa = intval($data['idMesa']);
            $idUsuario = $data['idUsuario'] ?? $_SESSION['usuario_id'] ?? null;

            $idVenta = $this->salesModel->getOrCreateTableSale($idMesa, $idUsuario);
            
            $venta = $this->salesModel->getSaleById($idVenta);
            $detalles = $this->salesModel->getSaleDetails($idVenta);

            echo json_encode([
                'success' => true,
                'data' => [
                    'idVenta' => $idVenta,
                    'venta' => $venta,
                    'productos' => $detalles
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function addProductToTableSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idVenta'])) {
                throw new Exception('El ID de la venta es requerido');
            }
            if (empty($data['idProducto'])) {
                throw new Exception('El ID del producto es requerido');
            }
            
            $idVenta = intval($data['idVenta']);
            $idProducto = intval($data['idProducto']);
            $cantidad = intval($data['cantidad'] ?? 1);
            $precioUnitario = floatval($data['precioUnitario']);
            $idUsuario = $data['idUsuario'] ?? $_SESSION['usuario_id'] ?? null;

            $producto = $this->productModel->getById($idProducto);
            if ($producto && $producto['manejaStock']) {
                if (!$this->inventoryModel->verificarStock($idProducto, $cantidad)) {
                    throw new Exception("Stock insuficiente para: " . $producto['nombre']);
                }
            }

            $idDetalle = $this->salesModel->addOrUpdateProductToSale(
                $idVenta,
                $idProducto,
                $cantidad,
                $precioUnitario,
                $idUsuario
            );

            $venta = $this->salesModel->getSaleById($idVenta);
            $detalles = $this->salesModel->getSaleDetails($idVenta);

            echo json_encode([
                'success' => true,
                'message' => 'Producto agregado correctamente',
                'data' => [
                    'idDetalle' => $idDetalle,
                    'venta' => $venta,
                    'productos' => $detalles
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateProductQuantity() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idDetalleVenta'])) {
                throw new Exception('El ID del detalle es requerido');
            }
            if (!isset($data['cantidad'])) {
                throw new Exception('La cantidad es requerida');
            }
            
            $idDetalleVenta = intval($data['idDetalleVenta']);
            $cantidad = intval($data['cantidad']);

            if ($cantidad <= 0) {
                throw new Exception('La cantidad debe ser mayor a 0');
            }

            $this->salesModel->updateProductQuantity($idDetalleVenta, $cantidad);

            echo json_encode([
                'success' => true,
                'message' => 'Cantidad actualizada correctamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function removeProductFromSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idDetalleVenta'])) {
                throw new Exception('El ID del detalle es requerido');
            }
            
            $idDetalleVenta = intval($data['idDetalleVenta']);

            $this->salesModel->removeProductFromSale($idDetalleVenta);

            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function completeTableSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idMesa'])) {
                throw new Exception('El ID de la mesa es requerido');
            }
            
            $idMesa = intval($data['idMesa']);
            $metodoPago = $data['metodoPago'] ?? 'efectivo';

            $idVenta = $this->salesModel->completeTableSale($idMesa, $metodoPago);

            echo json_encode([
                'success' => true,
                'message' => 'Venta completada exitosamente',
                'saleId' => $idVenta
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function cancelTableSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idMesa'])) {
                throw new Exception('El ID de la mesa es requerido');
            }
            
            $idMesa = intval($data['idMesa']);

            $this->salesModel->cancelTableSale($idMesa);

            echo json_encode([
                'success' => true,
                'message' => 'Venta cancelada correctamente'
            ]);
        } catch (Exception $e) {
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

            foreach ($productos as $item) {
                $producto = $this->productModel->getById($item['idProducto']);
                if ($producto && $producto['manejaStock']) {
                    if (!$this->inventoryModel->verificarStock($item['idProducto'], $item['cantidad'])) {
                        throw new Exception("Stock insuficiente para el producto: " . $producto['nombre']);
                    }
                }
            }

            $sale = $this->salesModel->createSale(
                null,
                $idMesa,
                'completada',
                $metodoPago,
                $total,
                $idUsuario,
                'detallada',
                null
            );

            foreach ($productos as $item) {
                $this->salesModel->createSalesDetail(
                    intval($sale),
                    null,
                    intval($item['idProducto']),
                    $item['cantidad'],
                    $item['precioUnitario'],
                    $idUsuario
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Venta creada exitosamente',
                'saleId' => $sale
            ]);
        } catch (Exception $e) {
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
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
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

    public function seeTodayBills() {
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

    public function checkStock() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idProducto = isset($_GET['idProducto']) ? intval($_GET['idProducto']) : 0;
            $cantidad = isset($_GET['cantidad']) ? intval($_GET['cantidad']) : 1;
            
            if ($idProducto <= 0) {
                throw new Exception('ID de producto inválido');
            }
            
            $producto = $this->productModel->getById($idProducto);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }
            
            if (!$producto['manejaStock']) {
                echo json_encode([
                    'success' => true,
                    'manejaStock' => false,
                    'disponible' => true
                ]);
                return;
            }
            
            $stockActual = $this->inventoryModel->obtenerStockActual($idProducto);
            $disponible = $stockActual >= $cantidad;
            
            echo json_encode([
                'success' => true,
                'manejaStock' => true,
                'stockActual' => $stockActual,
                'cantidadSolicitada' => $cantidad,
                'disponible' => $disponible
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
