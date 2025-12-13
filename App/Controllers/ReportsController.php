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
        if (empty($_POST['idVenta']) AND empty($_POST['precioDesde']) AND empty($_POST['precioHasta']) AND empty($_POST['fechaDesde']) AND empty($_POST['fechaHasta']) AND empty($_POST['metodoPago'])) {
            $resultados = $this->salesModel->getAll();
        } else {
            $query = "";
            if (!empty($_POST['idVenta'])) {
                $query .= " AND idVenta = " . intval($_POST['idVenta']);
            }
            if (!empty($_POST['precioDesde'])) {
                $query .= " AND total >= " . floatval($_POST['precioDesde']);
            }
            if (!empty($_POST['precioHasta'])) {
                $query .= " AND total <= " . floatval($_POST['precioHasta']);
            }
            if (!empty($_POST['fechaDesde']) AND !empty($_POST['fechaHasta'])) {
                $query .= " AND fechaVenta BETWEEN '" . $_POST['fechaDesde'] . " 00:00:00' AND '" . $_POST['fechaHasta'] . " 23:59:59'";
            }
            if (!empty($_POST['fechaHasta']) AND empty($_POST['fechaDesde'])) {
                $query .= " AND fechaVenta <= '" . $_POST['fechaHasta'] . " 23:59:59'";
            }
            if (!empty($_POST['metodoPago'])) {
                $query .= " AND metodoPago = '" . $_POST['metodoPago'] . "'";
            }
            $resultados = $this->salesModel->filter($query);
        }
        
        // Renderizar la vista (dayBills.view.php se encarga de no cargar header/footer si es AJAX)
        require_once __DIR__ . '/../Views/dayBills.view.php';
    }

}