
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cajas` (
  `idCaja` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` int(11) NOT NULL,
  `saldoInicial` decimal(10,2) NOT NULL DEFAULT 0.00,
  `saldoReal` decimal(10,2) DEFAULT NULL COMMENT 'Dinero físico contado al cierre',
  `saldoCalculado` decimal(10,2) DEFAULT NULL COMMENT 'Calculado: ingresos - egresos',
  `fechaApertura` datetime DEFAULT current_timestamp(),
  `fechaCierre` datetime DEFAULT NULL,
  `estado` enum('abierta','cerrada') DEFAULT 'abierta',
  `notas` text DEFAULT NULL COMMENT 'Observaciones al cerrar',
  PRIMARY KEY (`idCaja`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fechaApertura` (`fechaApertura`),
  KEY `idx_idUsuario` (`idUsuario`),
  CONSTRAINT `cajas_ibfk_1` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Registro de aperturas y cierres de caja';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `tr_validar_una_caja_abierta_insert`
BEFORE INSERT ON `cajas`
FOR EACH ROW
BEGIN
  IF NEW.`estado` = 'abierta' THEN
    IF (SELECT COUNT(*) FROM `cajas` WHERE `estado` = 'abierta') > 0 THEN
      SIGNAL SQLSTATE '45000' 
      SET MESSAGE_TEXT = 'ERROR: Ya existe una caja abierta. Ciérrala antes de abrir una nueva.';
    END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/  /*!50003 TRIGGER `tr_validar_una_caja_abierta_update`
BEFORE UPDATE ON `cajas`
FOR EACH ROW
BEGIN
  IF NEW.`estado` = 'abierta' AND OLD.`estado` = 'cerrada' THEN
    IF (SELECT COUNT(*) FROM `cajas` WHERE `estado` = 'abierta') > 0 THEN
      SIGNAL SQLSTATE '45000' 
      SET MESSAGE_TEXT = 'ERROR: Ya existe una caja abierta. Ciérrala antes de abrir una nueva.';
    END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categorias` (
  `idCategoria` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `imagen` varchar(255) NOT NULL DEFAULT 'default.png',
  PRIMARY KEY (`idCategoria`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compras` (
  `idCompra` int(11) NOT NULL AUTO_INCREMENT,
  `idProveedor` int(11) DEFAULT NULL,
  `fechaCompra` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `idUsuario` int(11) DEFAULT NULL,
  `idCaja` int(11) DEFAULT NULL,
  `tipoCompra` enum('detallada','rapida') DEFAULT 'detallada' COMMENT 'Detallada: con productos, Rápida: solo total',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción para compras rápidas',
  PRIMARY KEY (`idCompra`),
  KEY `fk02` (`idProveedor`),
  KEY `fk10` (`idUsuario`),
  KEY `idx_idCaja_compras` (`idCaja`),
  CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`idCaja`) REFERENCES `cajas` (`idCaja`) ON UPDATE CASCADE,
  CONSTRAINT `fk02` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`) ON UPDATE CASCADE,
  CONSTRAINT `fk10` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuracion` (
  `clave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL,
  `fechaActualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_compra` (
  `idDetalleCompra` int(11) NOT NULL AUTO_INCREMENT,
  `idCompra` int(11) DEFAULT NULL,
  `idProducto` int(11) DEFAULT NULL,
  `cantidad` decimal(10,3) NOT NULL COMMENT 'Cantidad comprada (soporta decimales)',
  `precioUnitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`idDetalleCompra`),
  KEY `fk03` (`idCompra`),
  KEY `fk04` (`idProducto`),
  CONSTRAINT `fk03` FOREIGN KEY (`idCompra`) REFERENCES `compras` (`idCompra`) ON UPDATE CASCADE,
  CONSTRAINT `fk04` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detalle_venta` (
  `idDetalleVenta` int(11) NOT NULL AUTO_INCREMENT,
  `idVenta` int(11) DEFAULT NULL,
  `idProducto` int(11) DEFAULT NULL,
  `cantidad` decimal(10,3) NOT NULL DEFAULT 1.000 COMMENT 'Cantidad vendida (soporta decimales para kg, L)',
  `precioUnitario` decimal(10,2) NOT NULL,
  `subTotal` decimal(10,2) GENERATED ALWAYS AS (`precioUnitario` * `cantidad`) STORED,
  `fechaAgregado` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`idDetalleVenta`),
  KEY `fk06` (`idVenta`),
  KEY `fk07` (`idProducto`),
  CONSTRAINT `fk06` FOREIGN KEY (`idVenta`) REFERENCES `ventas` (`idVenta`) ON UPDATE CASCADE,
  CONSTRAINT `fk07` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=510 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gastos` (
  `idGasto` int(11) NOT NULL AUTO_INCREMENT,
  `idProducto` int(11) DEFAULT NULL,
  `tipo` enum('producto','externo') NOT NULL DEFAULT 'producto',
  `cantidad` decimal(10,3) DEFAULT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `monto` decimal(15,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `idCaja` int(11) DEFAULT NULL,
  `idUsuario` int(11) DEFAULT NULL,
  `fechaRegistro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idGasto`),
  KEY `idx_gastos_fecha` (`fechaRegistro`),
  KEY `idx_gastos_tipo` (`tipo`),
  KEY `idx_gastos_producto` (`idProducto`),
  KEY `idx_gastos_usuario` (`idUsuario`),
  KEY `idx_gastos_caja` (`idCaja`),
  CONSTRAINT `gastos_ibfk_1` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON DELETE SET NULL,
  CONSTRAINT `gastos_ibfk_2` FOREIGN KEY (`idCaja`) REFERENCES `cajas` (`idCaja`) ON DELETE SET NULL,
  CONSTRAINT `gastos_ibfk_3` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_precio_compra` (
  `idHistorial` int(11) NOT NULL AUTO_INCREMENT,
  `idProducto` int(11) NOT NULL,
  `idProveedor` int(11) DEFAULT NULL,
  `idCompra` int(11) DEFAULT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `fechaRegistro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`idHistorial`),
  KEY `idx_producto` (`idProducto`),
  KEY `idx_proveedor` (`idProveedor`),
  KEY `idx_fecha` (`fechaRegistro`),
  KEY `fk_hpc_compra` (`idCompra`),
  CONSTRAINT `fk_hpc_compra` FOREIGN KEY (`idCompra`) REFERENCES `compras` (`idCompra`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hpc_producto` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE,
  CONSTRAINT `fk_hpc_proveedor` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventario` (
  `idInventario` int(11) NOT NULL AUTO_INCREMENT,
  `idProducto` int(11) NOT NULL,
  `tipoMovimiento` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL COMMENT 'Cantidad del movimiento (entrada/salida)',
  `stockAnterior` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Stock antes del movimiento',
  `stockActual` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Stock después del movimiento',
  `referencia` varchar(50) DEFAULT NULL COMMENT 'ID de compra o venta relacionada',
  `tipoReferencia` enum('compra','venta','ajuste_manual','consumo') DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fechaMovimiento` datetime DEFAULT current_timestamp(),
  `idUsuario` int(11) DEFAULT NULL,
  `tieneAlerta` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`idInventario`),
  KEY `fk13` (`idUsuario`),
  KEY `idx_producto` (`idProducto`),
  KEY `idx_fecha` (`fechaMovimiento`),
  KEY `idx_tipo` (`tipoMovimiento`),
  KEY `idx_inventario_tieneAlerta` (`tieneAlerta`,`fechaMovimiento`),
  KEY `idx_inventario_producto_alerta` (`idProducto`,`tieneAlerta`),
  CONSTRAINT `fk12` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE,
  CONSTRAINT `fk13` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historial completo de movimientos de inventario';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mesas` (
  `idMesa` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) DEFAULT NULL,
  `numero` int(11) DEFAULT NULL,
  `estado` enum('libre','ocupada') DEFAULT 'libre',
  `posX` decimal(6,2) NOT NULL DEFAULT 0.00,
  `posY` decimal(6,2) NOT NULL DEFAULT 0.00,
  `tipo` enum('mesa','barra') NOT NULL DEFAULT 'mesa',
  PRIMARY KEY (`idMesa`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `movimientos_caja` (
  `idMovimiento` int(11) NOT NULL AUTO_INCREMENT,
  `idCaja` int(11) NOT NULL,
  `tipo_movimiento` enum('VENTA','COMPRA','GASTO','AJUSTE') NOT NULL COMMENT 'Tipo de movimiento: +VENTA, -COMPRA, -GASTO, ±AJUSTE',
  `referencia` varchar(50) DEFAULT NULL COMMENT 'ID de venta, compra o gasto relacionado',
  `tipo_referencia` enum('venta','compra','gasto','ajuste_manual') DEFAULT NULL,
  `monto` decimal(10,2) NOT NULL COMMENT 'Monto: positivo=ingreso, negativo=egreso',
  `metodoPago` varchar(20) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `idUsuario` int(11) DEFAULT NULL COMMENT 'Quién registró el movimiento',
  `fechaMovimiento` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`idMovimiento`),
  KEY `idUsuario` (`idUsuario`),
  KEY `idx_idCaja` (`idCaja`),
  KEY `idx_tipo_movimiento` (`tipo_movimiento`),
  KEY `idx_fechaMovimiento` (`fechaMovimiento`),
  KEY `idx_referencia` (`referencia`),
  KEY `idx_tipo_referencia` (`tipo_referencia`),
  CONSTRAINT `movimientos_caja_ibfk_1` FOREIGN KEY (`idCaja`) REFERENCES `cajas` (`idCaja`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `movimientos_caja_ibfk_2` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historial de movimientos de dinero en caja (ingresos y egresos)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `productos` (
  `idProducto` int(11) NOT NULL AUTO_INCREMENT,
  `idCategoria` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `codigoBarras` varchar(50) DEFAULT NULL COMMENT 'Codigo de barras opcional',
  `precioCompra` decimal(10,2) DEFAULT 0.00,
  `precioVenta` decimal(10,2) DEFAULT 0.00,
  `tipo` enum('venta','insumo') NOT NULL DEFAULT 'venta',
  `idUnidadBase` int(11) DEFAULT NULL COMMENT 'Unidad base del producto (kg, L, unidad)',
  `imagen` varchar(255) NOT NULL DEFAULT 'default.png',
  `manejaStock` tinyint(1) DEFAULT 0,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`idProducto`),
  UNIQUE KEY `idx_codigoBarras` (`codigoBarras`),
  KEY `fk01` (`idCategoria`),
  KEY `idx_unidadBase` (`idUnidadBase`),
  KEY `idx_productos_estado` (`estado`),
  CONSTRAINT `fk01` FOREIGN KEY (`idCategoria`) REFERENCES `categorias` (`idCategoria`) ON UPDATE CASCADE,
  CONSTRAINT `fk_producto_unidad` FOREIGN KEY (`idUnidadBase`) REFERENCES `unidades_medida` (`idUnidad`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `idProveedor` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `telefono` int(11) DEFAULT NULL,
  PRIMARY KEY (`idProveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unidades_medida` (
  `idUnidad` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL COMMENT 'Nombre completo: Kilogramo, Litro, Unidad',
  `abreviatura` varchar(10) NOT NULL COMMENT 'kg, L, u',
  `tipo` enum('peso','volumen','unidad') NOT NULL COMMENT 'Clasificación de la unidad',
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`idUnidad`),
  UNIQUE KEY `idx_abreviatura` (`abreviatura`),
  KEY `idx_tipo` (`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Unidades de medida para productos (kg, L, unidad)';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `idUsuario` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `pin` varchar(255) NOT NULL,
  `rol` enum('admin','empleado') DEFAULT 'empleado',
  PRIMARY KEY (`idUsuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventas` (
  `idVenta` int(11) NOT NULL AUTO_INCREMENT,
  `idMesa` int(11) DEFAULT NULL,
  `fechaVenta` datetime DEFAULT current_timestamp(),
  `estado` enum('pendiente','completada','cancelada') DEFAULT 'pendiente',
  `metodoPago` enum('efectivo','transferencia','bancolombia','nequi') DEFAULT 'efectivo',
  `total` decimal(10,2) DEFAULT 0.00,
  `idUsuario` int(11) DEFAULT NULL,
  `idCaja` int(11) DEFAULT NULL,
  `tipoVenta` enum('mesa','venta') DEFAULT 'venta',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción para ventas rápidas',
  `fechaCreacion` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación de la venta',
  `fechaActualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Última actualización',
  PRIMARY KEY (`idVenta`),
  KEY `fk09` (`idUsuario`),
  KEY `idx_mesa_estado` (`idMesa`,`estado`),
  KEY `idx_estado` (`estado`),
  KEY `idx_idCaja` (`idCaja`),
  CONSTRAINT `fk05` FOREIGN KEY (`idMesa`) REFERENCES `mesas` (`idMesa`) ON UPDATE CASCADE,
  CONSTRAINT `fk09` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE,
  CONSTRAINT `ventas_ibfk_1` FOREIGN KEY (`idCaja`) REFERENCES `cajas` (`idCaja`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=492 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_alertas_stock` AS SELECT
 1 AS `idInventario`,
  1 AS `idProducto`,
  1 AS `producto`,
  1 AS `tipoMovimiento`,
  1 AS `cantidad`,
  1 AS `stockAnterior`,
  1 AS `stockActual`,
  1 AS `referencia`,
  1 AS `tipoReferencia`,
  1 AS `descripcion`,
  1 AS `fechaMovimiento`,
  1 AS `usuario` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_caja_activa` AS SELECT
 1 AS `idCaja`,
  1 AS `idUsuario`,
  1 AS `usuarioApertura`,
  1 AS `saldoInicial`,
  1 AS `fechaApertura`,
  1 AS `estado`,
  1 AS `tiempoAbierta` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_costo_promedio_producto` AS SELECT
 1 AS `idProducto`,
  1 AS `nombre`,
  1 AS `costoPromedio`,
  1 AS `ultimoPrecio`,
  1 AS `fechaUltimaCompra`,
  1 AS `totalComprado`,
  1 AS `comprasContadas` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_productos_con_unidad` AS SELECT
 1 AS `idProducto`,
  1 AS `nombre`,
  1 AS `precioCompra`,
  1 AS `precioVenta`,
  1 AS `tipo`,
  1 AS `idUnidadBase`,
  1 AS `unidadNombre`,
  1 AS `unidadAbreviatura`,
  1 AS `unidadTipo`,
  1 AS `idCategoria`,
  1 AS `categoriaNombre`,
  1 AS `manejaStock`,
  1 AS `imagen` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_resumen_caja` AS SELECT
 1 AS `idCaja`,
  1 AS `idUsuario`,
  1 AS `usuarioApertura`,
  1 AS `fechaApertura`,
  1 AS `saldoInicial`,
  1 AS `totalVentas`,
  1 AS `totalCompras`,
  1 AS `totalGastos`,
  1 AS `totalIngresos`,
  1 AS `totalEgresos`,
  1 AS `totalNeto`,
  1 AS `saldoCalculado`,
  1 AS `saldoReal`,
  1 AS `diferencia`,
  1 AS `estado`,
  1 AS `fechaCierre`,
  1 AS `notas` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_resumen_gastos_diarios` AS SELECT
 1 AS `fecha`,
  1 AS `tipo`,
  1 AS `cantidad`,
  1 AS `totalMonto` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_stock_actual` AS SELECT
 1 AS `idProducto`,
  1 AS `producto`,
  1 AS `idCategoria`,
  1 AS `categoria`,
  1 AS `idUnidadBase`,
  1 AS `unidadNombre`,
  1 AS `unidadAbreviatura`,
  1 AS `unidadTipo`,
  1 AS `manejaStock`,
  1 AS `precioCompra`,
  1 AS `precioVenta`,
  1 AS `imagen`,
  1 AS `stockActual`,
  1 AS `fechaUltimoMovimiento` */;
SET character_set_client = @saved_cs_client;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `vista_ventas_activas_mesas` AS SELECT
 1 AS `idMesa`,
  1 AS `numeroMesa`,
  1 AS `nombreMesa`,
  1 AS `estadoMesa`,
  1 AS `idVenta`,
  1 AS `total`,
  1 AS `fechaCreacion`,
  1 AS `fechaActualizacion`,
  1 AS `cantidadProductos`,
  1 AS `cantidadItems` */;
SET character_set_client = @saved_cs_client;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE FUNCTION `mesa_tiene_venta_activa`(p_idMesa INT) RETURNS tinyint(1)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE v_count INT;
    
    SELECT COUNT(*) INTO v_count
    FROM ventas
    WHERE idMesa = p_idMesa 
      AND estado = 'pendiente';
    
    RETURN v_count > 0;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE FUNCTION `obtener_caja_activa`() RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
BEGIN
  DECLARE v_idCaja INT(11);
  
  SELECT `idCaja` INTO v_idCaja
  FROM `cajas`
  WHERE `estado` = 'abierta'
  LIMIT 1;
  
  RETURN v_idCaja;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE FUNCTION `obtener_venta_activa_mesa`(p_idMesa INT) RETURNS int(11)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE v_idVenta INT;
    
    SELECT idVenta INTO v_idVenta
    FROM ventas
    WHERE idMesa = p_idMesa 
      AND estado = 'pendiente'
    ORDER BY fechaCreacion DESC
    LIMIT 1;
    
    RETURN v_idVenta;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE PROCEDURE `liberar_mesa`(
    IN p_idMesa INT,
    IN p_metodoPago VARCHAR(50)
)
BEGIN
    DECLARE v_idVenta INT;
    
    START TRANSACTION;
    
    -- Obtener venta activa
    SELECT idVenta INTO v_idVenta
    FROM ventas
    WHERE idMesa = p_idMesa 
      AND estado = 'pendiente'
    ORDER BY fechaCreacion DESC
    LIMIT 1;
    
    IF v_idVenta IS NOT NULL THEN
        -- Marcar venta como completada
        UPDATE ventas
        SET estado = 'completada',
            metodoPago = p_metodoPago,
            fechaVenta = NOW()
        WHERE idVenta = v_idVenta;
        
        -- Liberar mesa
        UPDATE mesas
        SET estado = 'libre'
        WHERE idMesa = p_idMesa;
        
        COMMIT;
        SELECT v_idVenta as idVenta, 'success' as status;
    ELSE
        ROLLBACK;
        SELECT NULL as idVenta, 'error' as status, 'No hay venta activa' as message;
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50001 DROP VIEW IF EXISTS `vista_alertas_stock`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_alertas_stock` AS select `i`.`idInventario` AS `idInventario`,`i`.`idProducto` AS `idProducto`,`p`.`nombre` AS `producto`,`i`.`tipoMovimiento` AS `tipoMovimiento`,`i`.`cantidad` AS `cantidad`,`i`.`stockAnterior` AS `stockAnterior`,`i`.`stockActual` AS `stockActual`,`i`.`referencia` AS `referencia`,`i`.`tipoReferencia` AS `tipoReferencia`,`i`.`descripcion` AS `descripcion`,`i`.`fechaMovimiento` AS `fechaMovimiento`,`u`.`nombre` AS `usuario` from ((`inventario` `i` join `productos` `p` on(`i`.`idProducto` = `p`.`idProducto`)) left join `usuarios` `u` on(`i`.`idUsuario` = `u`.`idUsuario`)) where `i`.`tieneAlerta` = 1 order by `i`.`fechaMovimiento` desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_caja_activa`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_caja_activa` AS select `c`.`idCaja` AS `idCaja`,`c`.`idUsuario` AS `idUsuario`,`u`.`nombre` AS `usuarioApertura`,`c`.`saldoInicial` AS `saldoInicial`,`c`.`fechaApertura` AS `fechaApertura`,`c`.`estado` AS `estado`,time_format(timediff(current_timestamp(),`c`.`fechaApertura`),'%H:%i:%s') AS `tiempoAbierta` from (`cajas` `c` join `usuarios` `u` on(`c`.`idUsuario` = `u`.`idUsuario`)) where `c`.`estado` = 'abierta' limit 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_costo_promedio_producto`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_costo_promedio_producto` AS select `p`.`idProducto` AS `idProducto`,`p`.`nombre` AS `nombre`,coalesce(sum(`h`.`cantidad` * `h`.`precioUnitario`) / nullif(sum(`h`.`cantidad`),0),0) AS `costoPromedio`,(select `h2`.`precioUnitario` from `historial_precio_compra` `h2` where `h2`.`idProducto` = `p`.`idProducto` order by `h2`.`fechaRegistro` desc,`h2`.`idHistorial` desc limit 1) AS `ultimoPrecio`,max(`h`.`fechaRegistro`) AS `fechaUltimaCompra`,sum(`h`.`cantidad`) AS `totalComprado`,count(`h`.`idHistorial`) AS `comprasContadas` from (`productos` `p` left join `historial_precio_compra` `h` on(`p`.`idProducto` = `h`.`idProducto`)) group by `p`.`idProducto`,`p`.`nombre` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_productos_con_unidad`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_productos_con_unidad` AS select `p`.`idProducto` AS `idProducto`,`p`.`nombre` AS `nombre`,`p`.`precioCompra` AS `precioCompra`,`p`.`precioVenta` AS `precioVenta`,`p`.`tipo` AS `tipo`,`p`.`idUnidadBase` AS `idUnidadBase`,`u`.`nombre` AS `unidadNombre`,`u`.`abreviatura` AS `unidadAbreviatura`,`u`.`tipo` AS `unidadTipo`,`c`.`idCategoria` AS `idCategoria`,`c`.`nombre` AS `categoriaNombre`,`p`.`manejaStock` AS `manejaStock`,`p`.`imagen` AS `imagen` from ((`productos` `p` left join `unidades_medida` `u` on(`p`.`idUnidadBase` = `u`.`idUnidad`)) left join `categorias` `c` on(`p`.`idCategoria` = `c`.`idCategoria`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_resumen_caja`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_resumen_caja` AS select `c`.`idCaja` AS `idCaja`,`c`.`idUsuario` AS `idUsuario`,`u`.`nombre` AS `usuarioApertura`,`c`.`fechaApertura` AS `fechaApertura`,`c`.`saldoInicial` AS `saldoInicial`,coalesce(sum(case when `mc`.`tipo_movimiento` = 'VENTA' and exists(select 1 from `ventas` `v` where `v`.`idVenta` = `mc`.`referencia` and `v`.`estado` = 'completada' limit 1) then `mc`.`monto` else 0 end),0) AS `totalVentas`,coalesce(sum(case when `mc`.`tipo_movimiento` = 'COMPRA' then abs(`mc`.`monto`) else 0 end),0) AS `totalCompras`,coalesce(sum(case when `mc`.`tipo_movimiento` = 'GASTO' then abs(`mc`.`monto`) else 0 end),0) AS `totalGastos`,coalesce(sum(case when `mc`.`monto` > 0 and `mc`.`tipo_movimiento` = 'VENTA' and exists(select 1 from `ventas` `v` where `v`.`idVenta` = `mc`.`referencia` and `v`.`estado` = 'completada' limit 1) then `mc`.`monto` when `mc`.`monto` > 0 and `mc`.`tipo_movimiento` <> 'VENTA' then `mc`.`monto` else 0 end),0) AS `totalIngresos`,coalesce(sum(case when `mc`.`monto` < 0 then abs(`mc`.`monto`) else 0 end),0) AS `totalEgresos`,coalesce(sum(case when `mc`.`tipo_movimiento` = 'VENTA' and exists(select 1 from `ventas` `v` where `v`.`idVenta` = `mc`.`referencia` and `v`.`estado` = 'completada' limit 1) then `mc`.`monto` when `mc`.`tipo_movimiento` <> 'VENTA' then `mc`.`monto` else 0 end),0) AS `totalNeto`,`c`.`saldoInicial` + coalesce(sum(case when `mc`.`tipo_movimiento` = 'VENTA' and exists(select 1 from `ventas` `v` where `v`.`idVenta` = `mc`.`referencia` and `v`.`estado` = 'completada' limit 1) then `mc`.`monto` when `mc`.`tipo_movimiento` <> 'VENTA' then `mc`.`monto` else 0 end),0) AS `saldoCalculado`,`c`.`saldoReal` AS `saldoReal`,case when `c`.`saldoReal` is null then NULL else `c`.`saldoReal` - (`c`.`saldoInicial` + coalesce(sum(case when `mc`.`tipo_movimiento` = 'VENTA' and exists(select 1 from `ventas` `v` where `v`.`idVenta` = `mc`.`referencia` and `v`.`estado` = 'completada' limit 1) then `mc`.`monto` when `mc`.`tipo_movimiento` <> 'VENTA' then `mc`.`monto` else 0 end),0)) end AS `diferencia`,`c`.`estado` AS `estado`,`c`.`fechaCierre` AS `fechaCierre`,`c`.`notas` AS `notas` from ((`cajas` `c` left join `usuarios` `u` on(`c`.`idUsuario` = `u`.`idUsuario`)) left join `movimientos_caja` `mc` on(`c`.`idCaja` = `mc`.`idCaja`)) group by `c`.`idCaja`,`c`.`idUsuario`,`c`.`fechaApertura`,`c`.`saldoInicial`,`c`.`saldoReal`,`c`.`estado`,`c`.`fechaCierre`,`c`.`notas`,`u`.`nombre` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_resumen_gastos_diarios`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_resumen_gastos_diarios` AS select cast(`g`.`fechaRegistro` as date) AS `fecha`,`g`.`tipo` AS `tipo`,count(0) AS `cantidad`,sum(`g`.`monto`) AS `totalMonto` from `gastos` `g` group by cast(`g`.`fechaRegistro` as date),`g`.`tipo` order by cast(`g`.`fechaRegistro` as date) desc */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_stock_actual`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_stock_actual` AS select `p`.`idProducto` AS `idProducto`,`p`.`nombre` AS `producto`,`p`.`idCategoria` AS `idCategoria`,`c`.`nombre` AS `categoria`,`p`.`idUnidadBase` AS `idUnidadBase`,`u`.`nombre` AS `unidadNombre`,`u`.`abreviatura` AS `unidadAbreviatura`,`u`.`tipo` AS `unidadTipo`,`p`.`manejaStock` AS `manejaStock`,`p`.`precioCompra` AS `precioCompra`,`p`.`precioVenta` AS `precioVenta`,`p`.`imagen` AS `imagen`,coalesce(`m`.`stockActual`,0) AS `stockActual`,`m`.`fechaMovimiento` AS `fechaUltimoMovimiento` from (((`productos` `p` left join (select `i1`.`idProducto` AS `idProducto`,`i1`.`stockActual` AS `stockActual`,`i1`.`fechaMovimiento` AS `fechaMovimiento` from (`inventario` `i1` join (select `inventario`.`idProducto` AS `idProducto`,max(concat(`inventario`.`fechaMovimiento`,lpad(`inventario`.`idInventario`,10,'0'))) AS `max_key` from `inventario` group by `inventario`.`idProducto`) `t` on(`i1`.`idProducto` = `t`.`idProducto` and concat(`i1`.`fechaMovimiento`,lpad(`i1`.`idInventario`,10,'0')) = `t`.`max_key`))) `m` on(`p`.`idProducto` = `m`.`idProducto`)) left join `categorias` `c` on(`p`.`idCategoria` = `c`.`idCategoria`)) left join `unidades_medida` `u` on(`p`.`idUnidadBase` = `u`.`idUnidad`)) where `p`.`manejaStock` = 1 and `p`.`estado` = 1 */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!50001 DROP VIEW IF EXISTS `vista_ventas_activas_mesas`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_ventas_activas_mesas` AS select `m`.`idMesa` AS `idMesa`,`m`.`numero` AS `numeroMesa`,`m`.`nombre` AS `nombreMesa`,`m`.`estado` AS `estadoMesa`,`v`.`idVenta` AS `idVenta`,`v`.`total` AS `total`,`v`.`fechaCreacion` AS `fechaCreacion`,`v`.`fechaActualizacion` AS `fechaActualizacion`,count(`dv`.`idDetalleVenta`) AS `cantidadProductos`,sum(`dv`.`cantidad`) AS `cantidadItems` from ((`mesas` `m` left join `ventas` `v` on(`m`.`idMesa` = `v`.`idMesa` and `v`.`estado` = 'pendiente')) left join `detalle_venta` `dv` on(`v`.`idVenta` = `dv`.`idVenta`)) group by `m`.`idMesa`,`m`.`numero`,`m`.`nombre`,`m`.`estado`,`v`.`idVenta`,`v`.`total`,`v`.`fechaCreacion`,`v`.`fechaActualizacion` order by `m`.`numero` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

