<?php
require_once __DIR__ . '/../Models/products.php';
require_once __DIR__ . '/../Models/Categories.php';
require_once __DIR__ . '/../Models/Tables.php';
require_once __DIR__ . '/../Models/sales.php';
require_once __DIR__ . '/../Models/inventory.php';

class SalesController {
    private $productModel;
    private $categoriesModel;
    private $tablesModel;
    private $salesModel;
    private $inventoryModel;

    /** Métodos de pago válidos (incluye 'transferencia' por datos históricos). */
    private static $metodosPago = ['efectivo', 'bancolombia', 'nequi', 'transferencia'];

    public function __construct() {
        $this->productModel = new Products();
        $this->categoriesModel = new Categories();
        $this->tablesModel = new Tables();
        $this->salesModel = new Sales();
        $this->inventoryModel = new Inventory();
    }

    /** Normaliza el método de pago recibido; por defecto 'efectivo'. */
    private function metodoValido($m) {
        return in_array($m, self::$metodosPago, true) ? $m : 'efectivo';
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

    /**
     * Buscar un producto por código de barras (para el escáner en ventas).
     * Devuelve el producto solo si existe, está activo y es de tipo venta.
     * Si no existe: success=false con encontrado=false (para mostrar
     * "producto no encontrado" sin romper nada).
     */
    public function findByBarcode() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
            if ($codigo === '') {
                echo json_encode(['success' => false, 'encontrado' => false, 'error' => 'Código vacío']);
                return;
            }

            $producto = $this->productModel->getByBarcode($codigo);
            if (!$producto) {
                echo json_encode(['success' => false, 'encontrado' => false, 'codigo' => $codigo]);
                return;
            }

            // Adjuntar stock si maneja inventario
            if (!empty($producto['manejaStock'])) {
                $producto['stockActual'] = $this->inventoryModel->obtenerStockActual($producto['idProducto']);
            } else {
                $producto['stockActual'] = null;
            }

            echo json_encode(['success' => true, 'encontrado' => true, 'data' => $producto]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'encontrado' => false, 'error' => $e->getMessage()]);
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
            // El usuario SIEMPRE sale de la sesión, nunca del cliente
            // (evita que se registren movimientos a nombre de otro).
            $idUsuario = $_SESSION['usuario_id'] ?? null;
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
            // El usuario SIEMPRE sale de la sesión, nunca del cliente
            // (evita que se registren movimientos a nombre de otro).
            $idUsuario = $_SESSION['usuario_id'] ?? null;

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
            $metodoPago = $this->metodoValido($data['metodoPago'] ?? 'efectivo');

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
            
            $metodoPago = $this->metodoValido($data['metodoPago'] ?? 'efectivo');
            // El usuario SIEMPRE sale de la sesión, nunca del cliente
            // (evita que se registren movimientos a nombre de otro).
            $idUsuario = $_SESSION['usuario_id'] ?? null;
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
     * Vista de facturas del día (HTML del modal "Ver facturas Hoy").
     * Vive bajo 'sales' para que los empleados puedan verla; el historial
     * completo de reportes sigue siendo solo de administradores.
     */
    public function dailyBills() {
        require_once __DIR__ . '/../Views/Reports/daily.report.php';
    }

    /**
     * Datos paginados de las facturas (JSON). Reemplaza el endpoint de reportes
     * para esta pantalla y usa parámetros seguros (sin interpolar entrada).
     */
    public function dailyBillsData() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $limit = 10;
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;

            // Filtros con placeholders (nada de concatenar entrada del usuario)
            $where = ["estado = 'completada'"];
            $params = [];

            if (!empty($_POST['idVenta'])) {
                $where[] = 'idVenta = ?';
                $params[] = intval($_POST['idVenta']);
            }
            if (!empty($_POST['precioDesde'])) {
                $where[] = 'total >= ?';
                $params[] = floatval($_POST['precioDesde']);
            }
            if (!empty($_POST['precioHasta'])) {
                $where[] = 'total <= ?';
                $params[] = floatval($_POST['precioHasta']);
            }
            if (!empty($_POST['metodoPago']) && in_array($_POST['metodoPago'], self::$metodosPago, true)) {
                // "Transferencia" agrupa TODO lo que no es efectivo (Bancolombia,
                // Nequi y el histórico 'transferencia'); si no, no salían las de
                // Bancolombia/Nequi al filtrar por transferencia.
                if ($_POST['metodoPago'] === 'transferencia') {
                    $where[] = "metodoPago IN ('transferencia','bancolombia','nequi')";
                } else {
                    $where[] = 'metodoPago = ?';
                    $params[] = $_POST['metodoPago'];
                }
            }
            if (!empty($_POST['fecha'])) {
                $partes = explode(' - ', $_POST['fecha']);
                $d1 = DateTime::createFromFormat('d/m/Y', trim($partes[0] ?? ''));
                $d2 = DateTime::createFromFormat('d/m/Y', trim($partes[1] ?? ($partes[0] ?? '')));
                if ($d1 && $d2) {
                    $where[] = 'fechaVenta BETWEEN ? AND ?';
                    $params[] = $d1->format('Y-m-d') . ' 00:00:00';
                    $params[] = $d2->format('Y-m-d') . ' 23:59:59';
                }
            }

            $whereSql = ' WHERE ' . implode(' AND ', $where);

            $resultados = $this->salesModel->getPaginatedFiltered($whereSql, $params, $offset, $limit);
            $total = $this->salesModel->countFiltered($whereSql, $params);
            $totalPaginas = (int) ceil($total / $limit);

            echo json_encode([
                'resultados'   => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
                'totales'      => $this->salesModel->getTotalsFiltered($whereSql, $params),
            ]);
        } catch (Exception $e) {
            echo json_encode(['resultados' => [], 'totalPaginas' => 0, 'paginaActual' => 1, 'error' => $e->getMessage()]);
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

            // Anular: marca como cancelada y devuelve el stock al inventario
            $this->salesModel->cancelInvoice($idVenta, $observacion, $_SESSION['usuario_id'] ?? null);

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
            $metodoPago = $this->metodoValido($data['metodoPago'] ?? 'efectivo');
            
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
