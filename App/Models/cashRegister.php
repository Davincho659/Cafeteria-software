<?php
require_once __DIR__ . '/../Core/Conexion.php';

class CashRegister {
	private $db;

	public function __construct() {
		$this->db = Database::getConnection();
		$this->ensureSchema();
	}

	/**
	 * Migración automática (mismo patrón que Settings/Tables).
	 *
	 * Se agrega 'metodoPago' a movimientos_caja porque el ARQUEO DE CAJA debe
	 * comparar solo el EFECTIVO físico. Antes, una venta por Nequi/Bancolombia
	 * entraba a la caja igual que el efectivo, así que al contar el dinero
	 * siempre aparecía un faltante enorme (el monto de las transferencias) y el
	 * arqueo no servía para detectar descuadres reales.
	 */
	private function ensureSchema() {
		try {
			$col = $this->db->query("SHOW COLUMNS FROM movimientos_caja LIKE 'metodoPago'")->fetch();
			if (!$col) {
				$this->db->exec("ALTER TABLE movimientos_caja
					ADD COLUMN metodoPago VARCHAR(20) NULL DEFAULT NULL AFTER monto");

				// Backfill histórico: las ventas toman su método real; los
				// movimientos que no son venta (compras, gastos, ajustes) se
				// asumen en efectivo, que es como se manejan en el mostrador.
				$this->db->exec("UPDATE movimientos_caja mc
					JOIN ventas v ON v.idVenta = mc.referencia
					SET mc.metodoPago = v.metodoPago
					WHERE mc.tipo_movimiento = 'VENTA' AND mc.metodoPago IS NULL");

				$this->db->exec("UPDATE movimientos_caja
					SET metodoPago = 'efectivo'
					WHERE metodoPago IS NULL");
			}
		} catch (Exception $e) {
			// Si la migración falla (permisos, etc.) el sistema sigue
			// funcionando: los cálculos usan COALESCE sobre la columna.
		}
	}

	/**
	 * Condición SQL que identifica el dinero que SÍ está físicamente en la caja.
	 * Un movimiento cuenta como efectivo si su metodoPago es 'efectivo' o si es
	 * nulo (datos anteriores a la migración / movimientos que no son venta).
	 */
	private const SQL_ES_EFECTIVO = "(mc.metodoPago IS NULL OR mc.metodoPago = 'efectivo')";

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
	public function addMovement($idCaja, $tipo_movimiento, $monto, $referencia = null, $tipo_referencia = null, $idUsuario = null, $descripcion = '', $metodoPago = 'efectivo') {
		if (!in_array($tipo_movimiento, ['VENTA','COMPRA','GASTO','AJUSTE'], true)) {
			throw new InvalidArgumentException('tipo_movimiento inválido');
		}

		// Si no hay caja o está cerrada, error
		$caja = $this->getCajaActiva();
		if (!$caja || (int)$caja['idCaja'] !== (int)$idCaja) {
			throw new Exception('No hay caja activa o ID de caja no coincide');
		}

		$sql = "INSERT INTO movimientos_caja (idCaja, tipo_movimiento, referencia, tipo_referencia, monto, descripcion, idUsuario, metodoPago)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([$idCaja, $tipo_movimiento, $referencia, $tipo_referencia, $monto, $descripcion, $idUsuario, $metodoPago]);
		return (int)$this->db->lastInsertId();
	}

	/**
	 * Cerrar caja: guarda saldo real, calcula saldoCalculado y diferencia
	 */
	/**
	 * Cerrar caja con ARQUEO: se guarda el efectivo contado por el cajero
	 * ($saldoReal) y el efectivo que el sistema esperaba, para dejar registrada
	 * la diferencia (sobrante/faltante). Se guarda el EFECTIVO esperado, no el
	 * total del día, porque el cajero cuenta billetes, no transferencias.
	 *
	 * @return array datos del arqueo (esperado, contado, diferencia)
	 */
	public function closeCashRegister($idCaja, $saldoReal, $notas = null) {
		$resumen = $this->getCajaResumen($idCaja);
		if (!$resumen || empty($resumen['idCaja'])) {
			throw new Exception('Caja no encontrada');
		}

		$efectivoEsperado = (float)$resumen['efectivoEsperado'];
		$diferencia = (float)$saldoReal - $efectivoEsperado;

		$sqlUpdate = "UPDATE cajas SET estado = 'cerrada', fechaCierre = NOW(), saldoReal = ?, saldoCalculado = ?, notas = ? WHERE idCaja = ?";
		$stmtUpdate = $this->db->prepare($sqlUpdate);
		$stmtUpdate->execute([$saldoReal, $efectivoEsperado, $notas, $idCaja]);

		return [
			'efectivoEsperado' => $efectivoEsperado,
			'efectivoContado'  => (float)$saldoReal,
			'diferencia'       => $diferencia,
		];
	}

	/**
	 * Resumen de caja (si existe la vista, úsala; si no, calcula rápido)
	 */
	public function getCajaResumen($idCaja) {
		// NOTA: antes se usaba la vista SQL 'vista_resumen_caja', pero esa vista
		// mezcla las ventas por transferencia con el efectivo, así que el arqueo
		// nunca cuadraba. El cálculo se hace siempre aquí, que sí separa el
		// efectivo físico de los pagos electrónicos.
		$sqlCaja = "SELECT idCaja, idUsuario, fechaApertura, saldoInicial as montoApertura, saldoInicial, saldoReal, estado, fechaCierre, notas FROM cajas WHERE idCaja = ?";
		$stmtCaja = $this->db->prepare($sqlCaja);
		$stmtCaja->execute([$idCaja]);
		$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);

		// Una venta solo cuenta si está completada (las anuladas no suman).
		$ventaValida = "EXISTS (SELECT 1 FROM ventas v WHERE v.idVenta = mc.referencia AND v.estado = 'completada')";
		$esEfectivo = self::SQL_ES_EFECTIVO;

		$sqlMov = "SELECT
			COALESCE(SUM(CASE
				WHEN mc.tipo_movimiento='VENTA' AND {$ventaValida}
				THEN mc.monto ELSE 0 END),0) as totalVentas,

			-- Ventas cobradas EN EFECTIVO: es el único dinero que está en el cajón
			COALESCE(SUM(CASE
				WHEN mc.tipo_movimiento='VENTA' AND {$ventaValida} AND {$esEfectivo}
				THEN mc.monto ELSE 0 END),0) as totalVentasEfectivo,

			-- Ventas cobradas por transferencia (Nequi/Bancolombia): NO están en el cajón
			COALESCE(SUM(CASE
				WHEN mc.tipo_movimiento='VENTA' AND {$ventaValida} AND NOT {$esEfectivo}
				THEN mc.monto ELSE 0 END),0) as totalVentasTransferencia,

			COALESCE(SUM(CASE WHEN mc.tipo_movimiento='COMPRA' THEN ABS(mc.monto) ELSE 0 END),0) as totalCompras,
			COALESCE(SUM(CASE WHEN mc.tipo_movimiento='GASTO' THEN ABS(mc.monto) ELSE 0 END),0) as totalGastos,
			COALESCE(SUM(CASE
				WHEN mc.monto > 0 AND mc.tipo_movimiento = 'VENTA' AND {$ventaValida}
				THEN mc.monto
				WHEN mc.monto > 0 AND mc.tipo_movimiento != 'VENTA'
				THEN mc.monto ELSE 0 END),0) as totalIngresos,
			COALESCE(SUM(CASE WHEN mc.monto < 0 THEN ABS(mc.monto) ELSE 0 END),0) as totalEgresos,

			-- Egresos pagados en efectivo (salen del cajón)
			COALESCE(SUM(CASE
				WHEN mc.monto < 0 AND {$esEfectivo} THEN ABS(mc.monto) ELSE 0 END),0) as totalEgresosEfectivo,

			COALESCE(SUM(CASE
				WHEN mc.tipo_movimiento = 'VENTA' AND {$ventaValida}
				THEN mc.monto
				WHEN mc.tipo_movimiento != 'VENTA'
				THEN mc.monto ELSE 0 END),0) as totalNeto
			FROM movimientos_caja mc WHERE mc.idCaja = ?";
		$stmtMov = $this->db->prepare($sqlMov);
		$stmtMov->execute([$idCaja]);
		$mov = $stmtMov->fetch(PDO::FETCH_ASSOC);

		$montoApertura = (float)$caja['montoApertura'];

		// Total del día (incluye transferencias): sirve para saber cuánto se vendió.
		$saldoCalculado = $montoApertura + (float)$mov['totalNeto'];

		// EFECTIVO ESPERADO EN EL CAJÓN = base + ventas en efectivo − egresos en efectivo.
		// Este es el número contra el que se cuenta el dinero al cerrar.
		$efectivoEsperado = $montoApertura
			+ (float)$mov['totalVentasEfectivo']
			- (float)$mov['totalEgresosEfectivo'];

		// La diferencia del arqueo se mide contra el EFECTIVO esperado.
		$diferencia = isset($caja['saldoReal']) && $caja['saldoReal'] !== null
			? ((float)$caja['saldoReal'] - $efectivoEsperado)
			: null;

		$resumen = array_merge($caja ?: [], $mov ?: [], [
			'efectivoActual' => $efectivoEsperado,   // lo que debe haber físicamente
			'efectivoEsperado' => $efectivoEsperado,
			'totalConTransferencias' => $saldoCalculado,
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
		// Las ventas anuladas se excluyen igual que en getCajaResumen(),
		// si no el desglose no cuadra con el total.
		$sql = "SELECT tipo_movimiento, SUM(monto) as total
				FROM movimientos_caja
				WHERE idCaja = ? AND monto > 0
				  AND (
					tipo_movimiento != 'VENTA'
					OR EXISTS (SELECT 1 FROM ventas v WHERE v.idVenta = referencia AND v.estado = 'completada')
				  )
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
				  AND (
					tipo_movimiento != 'VENTA'
					OR EXISTS (SELECT 1 FROM ventas v WHERE v.idVenta = referencia AND v.estado = 'completada')
				  )
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

	/**
	 * Helper: registrar ingreso por venta.
	 * El método de pago es clave: solo el efectivo entra al arqueo físico.
	 */
	public function registrarIngresoVenta($idVenta, $monto, $idUsuario = null, $metodoPago = 'efectivo') {
		$caja = $this->getCajaActiva();
		if (!$caja) {
			throw new Exception('No hay caja abierta');
		}
		return $this->addMovement((int)$caja['idCaja'], 'VENTA', (float)$monto, (string)$idVenta, 'venta', $idUsuario, 'Venta #' . $idVenta, $metodoPago);
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

