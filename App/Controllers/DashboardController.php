<?php
require_once __DIR__ . '/../Models/Closings.php';
require_once __DIR__ . '/../Models/cashRegister.php';

/**
 * Dashboard administrativo: foto del negocio en tiempo real.
 * Reutiliza el modelo Closings para no duplicar las consultas.
 */
class DashboardController {
    private $closings;
    private $cashRegister;

    public function __construct() {
        $this->closings = new Closings();
        $this->cashRegister = new CashRegister();
    }

    public function index() {
        require_once __DIR__ . '/../Views/dashboard.view.php';
    }

    /** Datos del dashboard en JSON. */
    public function data() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $hoy = date('Y-m-d');
            $ayer = date('Y-m-d', strtotime('-1 day'));
            $hace6 = date('Y-m-d', strtotime('-6 days'));
            $inicioMes = date('Y-m-01');

            $resumenHoy = $this->closings->getResumen($hoy, $hoy);
            $resumenAyer = $this->closings->getResumen($ayer, $ayer);
            $resumenMes = $this->closings->getResumen($inicioMes, $hoy);

            // Caja activa
            $caja = $this->cashRegister->getCajaActiva();
            $cajaResumen = $caja ? $this->cashRegister->getCajaResumen($caja['idCaja']) : null;

            echo json_encode([
                'success'      => true,
                'fecha'        => $hoy,
                'hoy'          => $resumenHoy,
                'ayer'         => $resumenAyer,
                'mes'          => $resumenMes,
                'serie7'       => $this->closings->getSerie($hace6, $hoy, 'dia'),
                'horasHoy'     => $this->closings->getVentasPorHora($hoy, $hoy),
                'topHoy'       => $this->closings->getTopProductos($hoy, $hoy, 5),
                'stock'        => $this->closings->getStockResumen(),
                'ventasRecientes' => $this->closings->getVentasRecientes(8),
                'caja'         => $caja ? [
                    'abierta'         => true,
                    'efectivoActual'  => $cajaResumen['efectivoActual'] ?? 0,
                    'totalIngresos'   => $cajaResumen['totalIngresos'] ?? 0,
                    'totalEgresos'    => $cajaResumen['totalEgresos'] ?? 0,
                    'fechaApertura'   => $caja['fechaApertura'] ?? null,
                ] : ['abierta' => false],
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
