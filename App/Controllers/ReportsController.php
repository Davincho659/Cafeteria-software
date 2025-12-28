<?php
require_once __DIR__ . '/../Models/Sales.php';
require_once __DIR__ . '/../Models/Purchases.php';
require_once __DIR__ . '/../Models/Inventory.php';

class ReportsController {
    private $salesModel;
    private $purchasesModel;
    private $inventoryModel;

    public function __construct() {
        $this->salesModel = new Sales();
        $this->purchasesModel = new Purchases();
        $this->inventoryModel = new Inventory();
    }

    
    public function sales() {
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            $limit = 10; 
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;
            $query = "";

            if (!empty($_POST['idVenta'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " idVenta = " . intval($_POST['idVenta']);
            }

            if (!empty($_POST['precioDesde'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " total >= " . floatval($_POST['precioDesde']);
            }

            if (!empty($_POST['precioHasta'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " total <= " . floatval($_POST['precioHasta']);
            }
            
            if (!empty($_POST['fecha'])) {
                $partes_fecha = explode(" - ", $_POST['fecha']);

                $fecha_inicio = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[0]))->format('Y-m-d') . ' 00:00:00';
                $fecha_fin = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[1]))->format('Y-m-d') . ' 23:59:59';

                $query .= (!empty($query) ? " AND" : " WHERE") . 
                        " fechaVenta BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            }

            if (!empty($_POST['metodoPago'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . 
                        " metodoPago = '" . $_POST['metodoPago'] . "'";
            }

            $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
            $total = $this->salesModel->countWithFilters($query);
            $totalPaginas = ceil($total / $limit);

        // 👉 RESPUESTA JSON
        
            echo json_encode([
                'resultados' => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
            ]);
            exit;
        }
        require_once __DIR__ . '/../Views/Reports/sales.report.php';
    }

    public function Daily() {
        // Si es petición AJAX con POST (paginación/filtros) → JSON
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $limit = 10; 
            $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
            $offset = ($page - 1) * $limit;
            $query = "";

            if (!empty($_POST['idVenta'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " idVenta = " . intval($_POST['idVenta']);
            }

            if (!empty($_POST['precioDesde'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " total >= " . floatval($_POST['precioDesde']);
            }

            if (!empty($_POST['precioHasta'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " total <= " . floatval($_POST['precioHasta']);
            }
            
            if (!empty($_POST['fecha'])) {
                $partes_fecha = explode(" - ", $_POST['fecha']);

                $fecha_inicio = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[0]))->format('Y-m-d') . ' 00:00:00';
                $fecha_fin = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[1]))->format('Y-m-d') . ' 23:59:59';

                $query .= (!empty($query) ? " AND" : " WHERE") . 
                        " fechaVenta BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            }

            if (!empty($_POST['metodoPago'])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . 
                        " metodoPago = '" . $_POST['metodoPago'] . "'";
            }

            $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
            $total = $this->salesModel->countWithFilters($query);
            $totalPaginas = ceil($total / $limit);

            echo json_encode([
                'resultados' => $resultados,
                'totalPaginas' => $totalPaginas,
                'paginaActual' => $page,
            ]);
            exit;
        }
        
        // Si es petición AJAX GET (carga inicial del modal) → HTML sin layout
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            require_once __DIR__ . '/../Views/Reports/daily.report.php';
            exit;
        }
        
        // Si es petición normal → cargar con layout (manejado por Index.php)
        require_once __DIR__ . '/../Views/Reports/daily.report.php';
    }

    
}