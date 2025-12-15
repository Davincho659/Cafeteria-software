<?php

require_once __DIR__ . '/../Models/sales.php';

class ReportsController {
    private $salesModel;

    public function __construct() {
        $this->salesModel = new Sales();
    }

    /**
     * Renderizar página de facturas con filtros
     * GET params: idventa, preciodesde, preciohasta, fechadesde, fechahasta, metodopago, ajax
     */
    public function dayBills() {

        $isAjaxModal = isset($_GET['ajax']) && $_GET['ajax'] == '1';
        $isAjaxData = isset($_GET['ajaxData']) && $_GET['ajaxData'] == '1';

        $limit = 10; 
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $offset = ($page - 1) * $limit;
        
        $query = " WHERE DATE(fechaVenta) = CURDATE()";
        
        if (!empty($_POST['idVenta'])) {
            $query .= (!empty($query) ? " AND" : " WHERE") . " idVenta = " . intval($_POST['idVenta']);
            
        }
        if (!empty($_POST['precioDesde'])) {
            $query .= " AND total >= " . floatval($_POST['precioDesde']);
        }
        if (!empty($_POST['precioHasta'])) {
            $query .= " AND total <= " . floatval($_POST['precioHasta']);
        }
        if (!empty($_POST['horaDesde'])) {
            $query .= " AND TIME(fechaVenta) >= '" . $_POST['horaDesde'] . "'";
        }
        if (!empty($_POST['horaHasta'])) {
            $query .= " AND TIME(fechaVenta) <= '" . $_POST['horaHasta'] . "'";
        }
        if (!empty($_POST['metodoPago'])) {
            $query .= " AND metodoPago = '" . $_POST['metodoPago'] . "'";
        }
        
        $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
        
        $total = $this->salesModel->countWithFilters($query);
        $totalPaginas = ceil($total / $limit);
        
        $paginacion = [
            'paginaActual' => $page,
            'totalPaginas' => $totalPaginas,
            'totalRegistros' => $total,
            'registrosPorPagina' => $limit
        ];
        
        if ($isAjaxData) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'resultados' => $resultados,
                'paginacion' => $paginacion
            ]);
            exit;
        }
        
        
        require_once __DIR__ . '/../Views/dayBills.view.php';
    }

    public function sales() {
        $limit = 10; 
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
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
            
            // Convertir formato de d/m/Y a Y-m-d para MySQL
            $fecha_inicio_obj = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[0]));
            $fecha_fin_obj = DateTime::createFromFormat('d/m/Y', trim($partes_fecha[1]));
            
            // Agregar hora completa para incluir TODO el día
            $fecha_inicio = $fecha_inicio_obj->format('Y-m-d') . ' 00:00:00';
            $fecha_fin = $fecha_fin_obj->format('Y-m-d') . ' 23:59:59';
            
            $query .= (!empty($query) ? " AND" : " WHERE") . " fechaVenta BETWEEN '" . $fecha_inicio . "' AND '" . $fecha_fin . "'";
        }
        
        if (!empty($_POST['metodoPago'])) {
            $metodoPago = $_POST['metodoPago'];
            if (in_array($metodoPago, ['efectivo', 'transferencia', ''])) {
                $query .= (!empty($query) ? " AND" : " WHERE") . " metodoPago = '" . $metodoPago . "'";
            }
        }
        
        
        $resultados = $this->salesModel->getWithPagination($query, $offset, $limit);
        
        $total = $this->salesModel->countWithFilters($query);
        $totalPaginas = ceil($total / $limit);
        require_once __DIR__ . '/../Views/Reports.view.php';
    }

}