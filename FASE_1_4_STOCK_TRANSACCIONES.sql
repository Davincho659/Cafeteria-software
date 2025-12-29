-- ============================================================================
-- FASE 1.4: STOCK CON TRANSACCIONES - ALTERACIONES DE BD REQUERIDAS
-- ============================================================================

-- 1. AGREGAR COLUMNA tieneAlerta A TABLA inventario (si no existe)
-- Esta columna marca movimientos donde el stock quedó negativo

ALTER TABLE inventario ADD COLUMN tieneAlerta TINYINT(1) DEFAULT 0;

-- Index para búsqueda rápida de alertas
CREATE INDEX idx_inventario_tieneAlerta ON inventario(tieneAlerta, fechaMovimiento DESC);
CREATE INDEX idx_inventario_producto_alerta ON inventario(idProducto, tieneAlerta);

-- ============================================================================
-- 2. TABLA DE GASTOS (productos y externos)
-- ============================================================================

CREATE TABLE IF NOT EXISTS gastos (
    idGasto INT PRIMARY KEY AUTO_INCREMENT,
    idProducto INT NULL,
    tipo ENUM('producto', 'externo') NOT NULL DEFAULT 'producto',
    cantidad DECIMAL(10, 3) NULL, -- Para gastos de producto
    concepto VARCHAR(255) NULL, -- Para gastos externos (ej: "Limpieza", "Mantenimiento")
    motivo VARCHAR(255) NULL, -- Para gastos de producto (ej: "Merma", "Rotura", "Vencimiento")
    monto DECIMAL(15, 2) NOT NULL,
    descripcion TEXT NULL,
    idCaja INT NULL,
    idUsuario INT NULL,
    fechaRegistro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (idProducto) REFERENCES productos(idProducto) ON DELETE SET NULL,
    FOREIGN KEY (idCaja) REFERENCES cajas(idCaja) ON DELETE SET NULL,
    FOREIGN KEY (idUsuario) REFERENCES usuarios(idUsuario) ON DELETE SET NULL,
    
    INDEX idx_gastos_fecha (fechaRegistro DESC),
    INDEX idx_gastos_tipo (tipo),
    INDEX idx_gastos_producto (idProducto),
    INDEX idx_gastos_usuario (idUsuario),
    INDEX idx_gastos_caja (idCaja)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. VISTA: RESUMEN DIARIO DE GASTOS
-- ============================================================================

CREATE OR REPLACE VIEW vista_resumen_gastos_diarios AS
SELECT 
    DATE(g.fechaRegistro) AS fecha,
    g.tipo,
    COUNT(*) AS cantidad,
    SUM(g.monto) AS totalMonto
FROM gastos g
GROUP BY DATE(g.fechaRegistro), g.tipo
ORDER BY fecha DESC;

-- ============================================================================
-- 4. VISTA: ALERTAS DE STOCK NEGATIVO (RECIENTE)
-- ============================================================================

CREATE OR REPLACE VIEW vista_alertas_stock AS
SELECT 
    i.idInventario,
    i.idProducto,
    p.nombre AS producto,
    i.tipoMovimiento,
    i.cantidad,
    i.stockAnterior,
    i.stockActual,
    i.referencia,
    i.tipoReferencia,
    i.descripcion,
    i.fechaMovimiento,
    u.nombre AS usuario
FROM inventario i
INNER JOIN productos p ON i.idProducto = p.idProducto
LEFT JOIN usuarios u ON i.idUsuario = u.idUsuario
WHERE i.tieneAlerta = 1
ORDER BY i.fechaMovimiento DESC;

-- ============================================================================
-- 5. RESUMEN: CAMBIOS MENORES DE BD
-- ============================================================================
-- - Se agregó columna `tieneAlerta` a tabla `inventario`
-- - Se creó tabla `gastos` con soporte para gastos de producto y externos
-- - Se crearon vistas para reportes rápidos de alertas y resumen diario
-- - Las transacciones ahora permiten stock negativo pero lo registran con alerta
-- ============================================================================
