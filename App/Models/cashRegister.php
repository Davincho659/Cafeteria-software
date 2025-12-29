<?php
require_once __DIR__ . '/../Core/Conexion.php';

class CashRegister {
	private $db;

	public function __construct() {
		$this->db = Database::getConnection();
	}

	/**
	 * Obtener caja activa (única caja abierta)
	 */
	public function getCajaActiva() {
		$sql = "SELECT * FROM cajas WHERE estado = 'abierta' LIMIT 1";
		$stmt = $this->db->query($sql);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		return $result ?: null;
	}

	/**
	 * Abrir caja (valida que no exista otra abierta)
	 */
	public function openCashRegister($saldoInicial, $idUsuario, $notas = null) {
		// Validación en BD via triggers, reforzamos en app
		if ($this->hasCajaAbierta()) {
			throw new Exception('Ya existe una caja abierta. Debe cerrarse antes de abrir una nueva.');
		}

		$sql = "INSERT INTO cajas (idUsuario, saldoInicial, estado, notas) VALUES (?, ?, 'abierta', ?)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([$idUsuario, $saldoInicial, $notas]);
		return (int)$this->db->lastInsertId();
	}

	/**
	 * Registrar movimiento en caja (ingreso/egreso/ajuste)
	 */
	public function addMovement($idCaja, $tipo_movimiento, $monto, $referencia = null, $tipo_referencia = null, $idUsuario = null, $descripcion = '') {
		if (!in_array($tipo_movimiento, ['VENTA','COMPRA','GASTO','AJUSTE'], true)) {
			throw new InvalidArgumentException('tipo_movimiento inválido');
		}

		// Si no hay caja o está cerrada, error
		$caja = $this->getCajaActiva();
		if (!$caja || (int)$caja['idCaja'] !== (int)$idCaja) {
			throw new Exception('No hay caja activa o ID de caja no coincide');
		}

		$sql = "INSERT INTO movimientos_caja (idCaja, tipo_movimiento, referencia, tipo_referencia, monto, descripcion, idUsuario) 
				VALUES (?, ?, ?, ?, ?, ?, ?)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([$idCaja, $tipo_movimiento, $referencia, $tipo_referencia, $monto, $descripcion, $idUsuario]);
		return (int)$this->db->lastInsertId();
	}

	/**
	 * Cerrar caja: guarda saldo real, calcula saldoCalculado y diferencia
	 */
	public function closeCashRegister($idCaja, $saldoReal, $notas = null) {
		// Obtener totales de movimientos
		$sqlTotales = "SELECT COALESCE(SUM(monto), 0) as totalMovimientos FROM movimientos_caja WHERE idCaja = ?";
		$stmt = $this->db->prepare($sqlTotales);
		$stmt->execute([$idCaja]);
		$totales = $stmt->fetch(PDO::FETCH_ASSOC);

		// Obtener saldo inicial
		$sqlCaja = "SELECT saldoInicial FROM cajas WHERE idCaja = ? LIMIT 1";
		$stmtCaja = $this->db->prepare($sqlCaja);
		$stmtCaja->execute([$idCaja]);
		$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
		if (!$caja) {
			throw new Exception('Caja no encontrada');
		}

		$saldoCalculado = (float)$caja['saldoInicial'] + (float)$totales['totalMovimientos'];

		// Actualizar cierre
		$sqlUpdate = "UPDATE cajas SET estado = 'cerrada', fechaCierre = NOW(), saldoReal = ?, saldoCalculado = ?, notas = ? WHERE idCaja = ?";
		$stmtUpdate = $this->db->prepare($sqlUpdate);
		$stmtUpdate->execute([$saldoReal, $saldoCalculado, $notas, $idCaja]);
		return true;
	}

	/**
	 * Resumen de caja (si existe la vista, úsala; si no, calcula rápido)
	 */
	public function getCajaResumen($idCaja) {
		try {
			$sql = "SELECT * FROM vista_resumen_caja WHERE idCaja = ?";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([$idCaja]);
			$vista = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($vista) {
				// Agregar detalles de ingresos y egresos
				$vista['detalleIngresos'] = $this->getDetalleIngresos($idCaja);
				$vista['detalleEgresos'] = $this->getDetalleEgresos($idCaja);
				return $vista;
			}
		} catch (Exception $e) {
			// Si la vista no existe, fallback a cálculo directo
		}

		$sqlCaja = "SELECT idCaja, idUsuario, fechaApertura, saldoInicial as montoApertura, saldoReal, estado, fechaCierre, notas FROM cajas WHERE idCaja = ?";
		$stmtCaja = $this->db->prepare($sqlCaja);
		$stmtCaja->execute([$idCaja]);
		$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

		$sqlMov = "SELECT 
			COALESCE(SUM(CASE WHEN tipo_movimiento='VENTA' THEN monto ELSE 0 END),0) as totalVentas,
			COALESCE(SUM(CASE WHEN tipo_movimiento='COMPRA' THEN ABS(monto) ELSE 0 END),0) as totalCompras,
			COALESCE(SUM(CASE WHEN tipo_movimiento='GASTO' THEN ABS(monto) ELSE 0 END),0) as totalGastos,
			COALESCE(SUM(CASE WHEN monto > 0 THEN monto ELSE 0 END),0) as totalIngresos,
			COALESCE(SUM(CASE WHEN monto < 0 THEN ABS(monto) ELSE 0 END),0) as totalEgresos,
			COALESCE(SUM(monto),0) as totalNeto
			FROM movimientos_caja WHERE idCaja = ?";
		$stmtMov = $this->db->prepare($sqlMov);
		$stmtMov->execute([$idCaja]);
		$mov = $stmtMov->fetch(PDO::FETCH_ASSOC);

		$montoApertura = (float)$caja['montoApertura'];
		$saldoCalculado = $montoApertura + (float)$mov['totalNeto'];
		$diferencia = isset($caja['saldoReal']) ? ((float)$caja['saldoReal'] - $saldoCalculado) : null;

		$resumen = array_merge($caja ?: [], $mov ?: [], [
			'efectivoActual' => $saldoCalculado,
			'diferencia' => $diferencia,
			'detalleIngresos' => $this->getDetalleIngresos($idCaja),
			'detalleEgresos' => $this->getDetalleEgresos($idCaja)
		]);

		return $resumen;
	}

	/**
	 * Obtener desglose de ingresos por tipo
	 */
	private function getDetalleIngresos($idCaja) {
		$sql = "SELECT tipo_movimiento, SUM(monto) as total
				FROM movimientos_caja
				WHERE idCaja = ? AND monto > 0
				GROUP BY tipo_movimiento";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([$idCaja]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$detalle = [];
		foreach ($rows as $row) {
			$detalle[$row['tipo_movimiento']] = $row['total'];
		}
		return $detalle;
	}

	/**
	 * Obtener desglose de egresos por tipo
	 */
	private function getDetalleEgresos($idCaja) {
		$sql = "SELECT tipo_movimiento, SUM(ABS(monto)) as total
				FROM movimientos_caja
				WHERE idCaja = ? AND monto < 0
				GROUP BY tipo_movimiento";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([$idCaja]);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$detalle = [];
		foreach ($rows as $row) {
			$detalle[$row['tipo_movimiento']] = $row['total'];
		}
		return $detalle;
	}

	/** Verifica si existe alguna caja abierta */
	public function hasCajaAbierta() {
		return (bool)$this->getCajaActiva();
	}

	/** Helper: registrar ingreso por venta */
	public function registrarIngresoVenta($idVenta, $monto, $idUsuario = null) {
		$caja = $this->getCajaActiva();
		if (!$caja) {
			throw new Exception('No hay caja abierta');
		}
		return $this->addMovement((int)$caja['idCaja'], 'VENTA', (float)$monto, (string)$idVenta, 'venta', $idUsuario, 'Venta #' . $idVenta);
	}

	/** Helper: registrar egreso por compra */
	public function registrarEgresoCompra($idCompra, $monto, $idUsuario = null) {
		$caja = $this->getCajaActiva();
		if (!$caja) {
			throw new Exception('No hay caja abierta');
		}
		// Egresos van como monto negativo
		$monto = -abs((float)$monto);
		return $this->addMovement((int)$caja['idCaja'], 'COMPRA', $monto, (string)$idCompra, 'compra', $idUsuario, 'Compra #' . $idCompra);
	}

	/** Helper: registrar egreso por gasto */
	public function registrarEgresoGasto($idGasto, $monto, $idUsuario = null) {
		$caja = $this->getCajaActiva();
		if (!$caja) {
			throw new Exception('No hay caja abierta');
		}
		$monto = -abs((float)$monto);
		return $this->addMovement((int)$caja['idCaja'], 'GASTO', $monto, (string)$idGasto, 'gasto', $idUsuario, 'Gasto #' . $idGasto);
	}
}

