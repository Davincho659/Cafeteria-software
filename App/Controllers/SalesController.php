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
                $products = $this->productModel->getAll([
                    'estado' => 1,
                    'tipo' => 'venta'
                ]);
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
            $idVenta = $this->salesModel->createPendingSale($idMesa, $idUsuario);
            
            // Actualizar estado de la mesa a ocupada
            $this->tablesModel->updateState($idMesa, 'ocupada');
            
            $productosAgregados = [];
            
            // Si hay productos, agregarlos a la venta
            if ($tieneProductos) {
                foreach ($productos as $producto) {
                    $idProducto = isset($producto['idProducto']) ? intval($producto['idProducto']) : null;
                    $cantidad = intval($producto['cantidad']);
                    $precioUnitario = floatval($producto['precioUnitario']);

                    // Validar que el producto exista (si no es NULL)
                    $productoData = null;
                    if ($idProducto !== null) {
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
                        'nombre' => $productoData ? $productoData['nombre'] : null,
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


    public function addProductToSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idVenta'])) {
                throw new Exception('El ID de la venta es requerido');
            }
            
            // idProducto puede ser null para productos manuales (montos manuales)
            $idVenta = intval($data['idVenta']);
            $idProducto = isset($data['idProducto']) && $data['idProducto'] !== '' ? intval($data['idProducto']) : null;
            $cantidad = intval($data['cantidad'] ?? 1);
            $precioUnitario = floatval($data['precioUnitario']);
            $idUsuario = $data['idUsuario'] ?? $_SESSION['usuario_id'] ?? null;

            // Validar producto solo si no es NULL (monto manual)
            if ($idProducto !== null) {
                $producto = $this->productModel->getById($idProducto);
                if (!$producto) {
                    throw new Exception("Producto no encontrado: ID {$idProducto}");
                }
                if ($producto['manejaStock']) {
                    if (!$this->inventoryModel->verificarStock($idProducto, $cantidad)) {
                        throw new Exception("Stock insuficiente para: " . $producto['nombre']);
                    }
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
    /**
     * Completar venta de mesa (heredado, mantener para compatibilidad)
     * Busca la venta pendiente por mesa y la completa
     */
    public function completeTableSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idMesa'])) {
                throw new Exception('El ID de la mesa es requerido');
            }
            
            $idMesa = intval($data['idMesa']);
            $metodoPago = $data['metodoPago'] ?? 'efectivo';

            // Obtener el idVenta de la mesa que tenga venta pendiente
            $idVenta = $this->salesModel->getVentaByMesa($idMesa);
            
            if (!$idVenta) {
                throw new Exception('No hay venta pendiente para esta mesa');
            }

            // Completar la venta
            $idVentaCompletada = $this->salesModel->completeSale($idVenta, $metodoPago);
            
            // Actualizar estado de la mesa a libre
            $this->tablesModel->updateState($idMesa, 'libre');
            
            echo json_encode([
                'success' => true,
                'message' => 'Venta completada correctamente',
                'saleId' => $idVentaCompletada
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    /**
     * CANCELAR una venta PENDIENTE/TEMPORAL (ELIMINA completamente de BD)
     * 
     * Diferencia importante:
     * - CancelSale: Venta PENDIENTE → ELIMINA de BD (no deja rastro)
     * - cancelSaleByInvoice: Venta COMPLETADA → MARCA como cancelada (mantiene registro)
     * 
     * GENÉRICO para mesas y mostrador/carrito
     * Elimina la venta y todos sus detalles de la BD
     */
    public function CancelSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idVenta'])) {
                throw new Exception('El ID de la venta es requerido');
            }
            
            $idVenta = intval($data['idVenta']);

            // Obtener la venta para saber si es de mesa
            $venta = $this->salesModel->getSaleById($idVenta);
            if (!$venta) {
                throw new Exception('Venta no encontrada');
            }

            // Cancelar la venta
            $this->salesModel->cancelSale($idVenta);
            
            // Si es una mesa, marcar como libre
            if ($venta['idMesa'] !== null && $venta['idMesa'] !== 0) {
                $this->tablesModel->updateState($venta['idMesa'], 'libre');
            }

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
            $data = json_decode(file_get_contents('php://input'), true);
            
            $metodoPago = $data['metodoPago'] ?? 'efectivo';
            $idUsuario = $data['idUsuario'] ?? $_SESSION['usuario_id'] ?? null;
            $idMesa = $data['tableId'] ?? null;  // Opcional
            $productos = $data['productos'] ?? [];  // Puede estar vacío

            // 1. Crear venta pendiente (siempre)
            $idVenta = $this->salesModel->createPendingSale($idMesa, $idUsuario);
            
            // 2. Si hay productos, agregarlos todos
            if (!empty($productos) && is_array($productos)) {
                foreach ($productos as $producto) {
                    $idProducto = isset($producto['idProducto']) ? intval($producto['idProducto']) : null;
                    $cantidad = intval($producto['cantidad'] ?? 1);
                    $precioUnitario = floatval($producto['precioUnitario']);

                    // Validar producto solo si no es NULL (monto manual)
                    if ($idProducto !== null) {
                        $productoData = $this->productModel->getById($idProducto);
                        if (!$productoData) {
                            throw new Exception("Producto no encontrado: ID {$idProducto}");
                        }
                        if ($productoData['manejaStock']) {
                            if (!$this->inventoryModel->verificarStock($idProducto, $cantidad)) {
                                throw new Exception("Stock insuficiente para: " . $productoData['nombre']);
                            }
                        }
                    }

                    // Agregar a la venta
                    $this->salesModel->addOrUpdateProductToSale(
                        $idVenta,
                        $idProducto,
                        $cantidad,
                        $precioUnitario,
                        $idUsuario
                    );
                }
                
                // 3. Si hay productos, completar la venta
                $idVentaCompletada = $this->salesModel->completeSale($idVenta, $metodoPago);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Venta creada y completada exitosamente',
                    'idVenta' => $idVentaCompletada
                ]);
            } else {
                // Si NO hay productos, solo devolver la venta pendiente (carrito vacío)
                echo json_encode([
                    'success' => true,
                    'message' => 'Venta pendiente creada correctamente',
                    'data' => [
                        'idVenta' => $idVenta,
                        'estado' => 'pendiente',
                        'total' => 0,
                        'productos' => []
                    ]
                ]);
            }
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

    /**
     * WARNING: metodo todavia no usuado en el front-end
     */

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

    /**
     * ANULAR una venta COMPLETADA (MARCA como cancelada, mantiene registro)
     * 
     * Diferencia importante:
     * - CancelSale: Venta PENDIENTE → ELIMINA de BD (no deja rastro)
     * - cancelSaleByInvoice: Venta COMPLETADA → MARCA como cancelada (mantiene para auditoría)
     * 
     * Usado en reportes para anular facturas completadas
     * El estado cambia de 'completada' a 'cancelada'
     */
    public function cancelSaleByInvoice() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idVenta'])) {
                throw new Exception('El ID de la venta es requerido');
            }

            $idVenta = intval($data['idVenta']);
            $observacion = isset($data['observacion']) ? trim($data['observacion']) : null;

            // Obtener venta para verificar que exista
            $venta = $this->salesModel->getSaleById($idVenta);
            if (!$venta) {
                throw new Exception('Venta no encontrada');
            }

            // Solo se pueden cancelar ventas completadas
            if ($venta['estado'] !== 'completada') {
                throw new Exception('Solo se pueden cancelar ventas completadas');
            }

            // Marcar como cancelada usando el método existente
            $this->salesModel->cancelInvoice($idVenta, $observacion);

            echo json_encode([
                'success' => true,
                'message' => 'Venta cancelada correctamente',
                'saleId' => $idVenta
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function LoadActiveSales() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $activeSales = $this->salesModel->getActiveSales();
            echo json_encode([
                'success' => true,
                'data' => $activeSales
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Completar una venta (genérico para mesas y mostrador)
     */
    public function CompleteSale() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['idVenta'])) {
                throw new Exception('El ID de la venta es requerido');
            }
            
            $idVenta = intval($data['idVenta']);
            $metodoPago = $data['metodoPago'] ?? 'efectivo';
            
            $idVentaCompletada = $this->salesModel->completeSale($idVenta, $metodoPago);
            
            echo json_encode([
                'success' => true,
                'message' => 'Venta completada exitosamente',
                'saleId' => $idVentaCompletada
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
