-- ============================================
-- SISTEMA DE CAJA: Tablas y Triggers
-- ============================================
-- Ejecutar si las tablas no existen
-- ============================================

-- Tabla CAJAS: Registro de aperturas y cierres
CREATE TABLE IF NOT EXISTS `cajas` (
  `idCaja` INT NOT NULL AUTO_INCREMENT,
  `idUsuario` INT NOT NULL COMMENT 'Usuario que abre la caja',
  `fechaApertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fechaCierre` DATETIME NULL,
  `saldoInicial` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Efectivo al abrir',
  `saldoReal` DECIMAL(10,2) NULL COMMENT 'Efectivo físico al cerrar',
  `saldoCalculado` DECIMAL(10,2) NULL COMMENT 'Saldo según movimientos',
  `estado` ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',
  `notas` TEXT NULL,
  PRIMARY KEY (`idCaja`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_usuario` (`idUsuario`),
  INDEX `idx_fecha_apertura` (`fechaApertura`),
  CONSTRAINT `fk_cajas_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla MOVIMIENTOS_CAJA: Todos los flujos de dinero
CREATE TABLE IF NOT EXISTS `movimientos_caja` (
  `idMovimiento` INT NOT NULL AUTO_INCREMENT,
  `idCaja` INT NOT NULL,
  `tipo_movimiento` ENUM('VENTA','COMPRA','GASTO','AJUSTE') NOT NULL,
  `referencia` VARCHAR(50) NULL COMMENT 'ID de venta/compra/gasto',
  `tipo_referencia` VARCHAR(20) NULL COMMENT 'venta, compra, gasto',
  `monto` DECIMAL(10,2) NOT NULL COMMENT 'Positivo=ingreso, Negativo=egreso',
  `descripcion` VARCHAR(255) NULL,
  `idUsuario` INT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idMovimiento`),
  INDEX `idx_caja` (`idCaja`),
  INDEX `idx_tipo` (`tipo_movimiento`),
  INDEX `idx_referencia` (`referencia`, `tipo_referencia`),
  INDEX `idx_fecha` (`fecha`),
  CONSTRAINT `fk_movimientos_caja` FOREIGN KEY (`idCaja`) REFERENCES `cajas` (`idCaja`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_movimientos_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VISTA: Resumen de caja con totales
-- ============================================
CREATE OR REPLACE VIEW vista_resumen_caja AS
SELECT 
    c.idCaja,
    c.idUsuario,
    c.fechaApertura,
    c.fechaCierre,
    c.saldoInicial AS montoApertura,
    c.saldoReal,
    c.saldoCalculado,
    c.estado,
    c.notas,
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'VENTA' THEN m.monto ELSE 0 END), 0) AS totalVentas,
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'COMPRA' THEN ABS(m.monto) ELSE 0 END), 0) AS totalCompras,
    COALESCE(SUM(CASE WHEN m.tipo_movimiento = 'GASTO' THEN ABS(m.monto) ELSE 0 END), 0) AS totalGastos,
    COALESCE(SUM(CASE WHEN m.monto > 0 THEN m.monto ELSE 0 END), 0) AS totalIngresos,
    COALESCE(SUM(CASE WHEN m.monto < 0 THEN ABS(m.monto) ELSE 0 END), 0) AS totalEgresos,
    COALESCE(SUM(m.monto), 0) AS totalNeto,
    c.saldoInicial + COALESCE(SUM(m.monto), 0) AS efectivoActual,
    CASE 
        WHEN c.saldoReal IS NOT NULL THEN c.saldoReal - (c.saldoInicial + COALESCE(SUM(m.monto), 0))
        ELSE NULL 
    END AS diferencia
FROM cajas c
LEFT JOIN movimientos_caja m ON c.idCaja = m.idCaja
GROUP BY c.idCaja;

-- ============================================
-- TRIGGER: Solo una caja abierta a la vez
-- ============================================
DELIMITER $$

DROP TRIGGER IF EXISTS `trg_solo_una_caja_abierta` $$
CREATE TRIGGER `trg_solo_una_caja_abierta`
BEFORE INSERT ON `cajas`
FOR EACH ROW
BEGIN
    DECLARE caja_abierta_count INT;
    
    -- Contar cajas abiertas
    SELECT COUNT(*) INTO caja_abierta_count 
    FROM cajas 
    WHERE estado = 'abierta';
    
    -- Si ya existe una abierta, error
    IF caja_abierta_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Ya existe una caja abierta. Debe cerrarse antes de abrir una nueva.';
    END IF;
    
    -- Forzar estado abierta
    SET NEW.estado = 'abierta';
    SET NEW.fechaApertura = NOW();
END$$

DELIMITER ;

-- ============================================
-- Añadir idCaja a tablas existentes (si no existe)
-- ============================================

-- Ventas
ALTER TABLE `ventas` 
ADD COLUMN IF NOT EXISTS `idCaja` INT NULL AFTER `idUsuario`,
ADD INDEX IF NOT EXISTS `idx_ventas_caja` (`idCaja`),
ADD CONSTRAINT `fk_ventas_caja` FOREIGN KEY IF NOT EXISTS (`idCaja`) REFERENCES `cajas` (`idCaja`) ON UPDATE CASCADE;

-- Compras
ALTER TABLE `compras` 
ADD COLUMN IF NOT EXISTS `idCaja` INT NULL AFTER `idUsuario`,
ADD INDEX IF NOT EXISTS `idx_compras_caja` (`idCaja`),
ADD CONSTRAINT `fk_compras_caja` FOREIGN KEY IF NOT EXISTS (`idCaja`) REFERENCES `cajas` (`idCaja`) ON UPDATE CASCADE;

-- Gastos
ALTER TABLE `gastos` 
ADD COLUMN IF NOT EXISTS `idCaja` INT NULL AFTER `idUsuario`,
ADD INDEX IF NOT EXISTS `idx_gastos_caja` (`idCaja`),
ADD CONSTRAINT `fk_gastos_caja` FOREIGN KEY IF NOT EXISTS (`idCaja`) REFERENCES `cajas` (`idCaja`) ON UPDATE CASCADE;

-- ============================================
-- VERIFICACIÓN
-- ============================================
SELECT 'Sistema de caja instalado correctamente ✓' AS Status;

-- Ver estructura
DESCRIBE cajas;
DESCRIBE movimientos_caja;

-- Ver si hay cajas abiertas
SELECT * FROM cajas WHERE estado = 'abierta';
