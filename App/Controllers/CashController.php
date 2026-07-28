<?php
require_once __DIR__ . '/../Models/cashRegister.php';

class CashController {
    private $cashModel;

    public function __construct() {
        $this->cashModel = new CashRegister();
    }

    /**
     * GET caja activa
     * Ruta: ?pg=cash&action=active
     */
    public function active() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $caja = $this->cashModel->getCajaActiva();
            echo json_encode([
                'success' => true,
                'data' => $caja
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST abrir caja
     * Body JSON: { saldoInicial, notas?, idUsuario? }
     * Ruta: ?pg=cash&action=open
     */
    public function open() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            // Validar que esté autenticado
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception('Sesión no válida. Por favor, inicia sesión nuevamente');
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            
            // Obtener parámetros
            $saldoInicial = isset($data['saldoInicial']) ? floatval($data['saldoInicial']) : 0.0;
            $notas = $data['notas'] ?? null;
            $idUsuario = (int)$_SESSION['usuario_id']; // Usar siempre del usuario logueado

            // Validar monto
            if ($saldoInicial < 0) {
                throw new Exception('El saldo inicial no puede ser negativo');
            }

            // Verificar si ya hay caja abierta
            if ($this->cashModel->hasCajaAbierta()) {
                throw new Exception('Ya existe una caja abierta. Debe cerrarse antes de abrir una nueva');
            }

            // Abrir caja
            $idCaja = $this->cashModel->openCashRegister($saldoInicial, $idUsuario, $notas);
            $caja = $this->cashModel->getCajaResumen($idCaja);

            echo json_encode([
                'success' => true,
                'message' => 'Caja abierta correctamente',
                'idCaja' => $idCaja,
                'saldoInicial' => $saldoInicial,
                'data' => $caja
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * POST cerrar caja
     * Body JSON: { idCaja, saldoReal, notas? }
     * Ruta: ?pg=cash&action=close
     */
    public function close() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            // Validar que esté autenticado
            if (!isset($_SESSION['usuario_id'])) {
                throw new Exception('Sesión no válida');
            }

            $data = json_decode(file_get_contents('php://input'), true) ?: [];
            $idCaja = isset($data['idCaja']) ? intval($data['idCaja']) : null;
            $saldoReal = isset($data['saldoReal']) ? floatval($data['saldoReal']) : null;
            $notas = $data['notas'] ?? null;

            if ($idCaja === null || $saldoReal === null) {
                throw new Exception('idCaja y saldoReal son requeridos');
            }

            if ($saldoReal < 0) {
                throw new Exception('El saldo real no puede ser negativo');
            }

            $activa = $this->cashModel->getCajaActiva();
            if (!$activa || (int)$activa['idCaja'] !== $idCaja) {
                throw new Exception('La caja indicada no está activa');
            }

            $this->cashModel->closeCashRegister($idCaja, $saldoReal, $notas);
            $resumen = $this->cashModel->getCajaResumen($idCaja);
            echo json_encode([
                'success' => true,
                'message' => 'Caja cerrada correctamente',
                'data' => $resumen
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * GET resumen de caja
     * Params: idCaja (opcional, usa activa si no se pasa)
     * Ruta: ?pg=cash&action=summary&idCaja=123
     */
    public function summary() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $idCaja = isset($_GET['idCaja']) ? intval($_GET['idCaja']) : null;
            if ($idCaja === null) {
                $activa = $this->cashModel->getCajaActiva();
                if (!$activa) {
                    echo json_encode(['success' => true, 'data' => null]);
                    return;
                }
                $idCaja = (int)$activa['idCaja'];
            }
            $resumen = $this->cashModel->getCajaResumen($idCaja);
            echo json_encode(['success' => true, 'data' => $resumen]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
