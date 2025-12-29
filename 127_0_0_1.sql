-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-12-2025 a las 03:34:08
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cafeteria_software`
--
CREATE DATABASE IF NOT EXISTS `cafeteria_software` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cafeteria_software`;

DELIMITER $$
--
-- Procedimientos
--
DROP PROCEDURE IF EXISTS `liberar_mesa`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `liberar_mesa` (IN `p_idMesa` INT, IN `p_metodoPago` VARCHAR(50))   BEGIN
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
END$$

--
-- Funciones
--
DROP FUNCTION IF EXISTS `mesa_tiene_venta_activa`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `mesa_tiene_venta_activa` (`p_idMesa` INT) RETURNS TINYINT(1) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_count INT;
    
    SELECT COUNT(*) INTO v_count
    FROM ventas
    WHERE idMesa = p_idMesa 
      AND estado = 'pendiente';
    
    RETURN v_count > 0;
END$$

DROP FUNCTION IF EXISTS `obtener_venta_activa_mesa`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `obtener_venta_activa_mesa` (`p_idMesa` INT) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_idVenta INT;
    
    SELECT idVenta INTO v_idVenta
    FROM ventas
    WHERE idMesa = p_idMesa 
      AND estado = 'pendiente'
    ORDER BY fechaCreacion DESC
    LIMIT 1;
    
    RETURN v_idVenta;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `idCategoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras`
--

DROP TABLE IF EXISTS `compras`;
CREATE TABLE `compras` (
  `idCompra` int(11) NOT NULL,
  `idProveedor` int(11) DEFAULT NULL,
  `fechaCompra` datetime DEFAULT current_timestamp(),
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `idUsuario` int(11) DEFAULT NULL,
  `tipoCompra` enum('detallada','rapida') DEFAULT 'detallada' COMMENT 'Detallada: con productos, Rápida: solo total',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción para compras rápidas'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compra`
--

DROP TABLE IF EXISTS `detalle_compra`;
CREATE TABLE `detalle_compra` (
  `idDetalleCompra` int(11) NOT NULL,
  `idCompra` int(11) DEFAULT NULL,
  `idProducto` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL,
  `precioUnitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

DROP TABLE IF EXISTS `detalle_venta`;
CREATE TABLE `detalle_venta` (
  `idDetalleVenta` int(11) NOT NULL,
  `idVenta` int(11) DEFAULT NULL,
  `idProducto` int(11) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precioUnitario` decimal(10,2) NOT NULL,
  `subTotal` decimal(10,2) GENERATED ALWAYS AS (`precioUnitario` * `cantidad`) STORED,
  `fechaAgregado` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gastos`
--

DROP TABLE IF EXISTS `gastos`;
CREATE TABLE `gastos` (
  `idGasto` int(11) NOT NULL,
  `idProducto` int(11) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fechaGasto` datetime DEFAULT current_timestamp(),
  `monto` decimal(10,2) NOT NULL,
  `tipo` enum('otro','producto') DEFAULT 'producto',
  `idUsuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

DROP TABLE IF EXISTS `inventario`;
CREATE TABLE `inventario` (
  `idInventario` int(11) NOT NULL,
  `idProducto` int(11) NOT NULL,
  `tipoMovimiento` enum('entrada','salida','ajuste') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stockAnterior` int(11) NOT NULL DEFAULT 0,
  `stockActual` int(11) NOT NULL DEFAULT 0,
  `referencia` varchar(50) DEFAULT NULL COMMENT 'ID de compra o venta relacionada',
  `tipoReferencia` enum('compra','venta','ajuste_manual') DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fechaMovimiento` datetime DEFAULT current_timestamp(),
  `idUsuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Historial completo de movimientos de inventario';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesas`
--

DROP TABLE IF EXISTS `mesas`;
CREATE TABLE `mesas` (
  `idMesa` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `numero` int(11) DEFAULT NULL,
  `estado` enum('libre','ocupada') DEFAULT 'libre'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE `productos` (
  `idProducto` int(11) NOT NULL,
  `idCategoria` int(11) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `precioCompra` decimal(10,2) DEFAULT 0.00,
  `precioVenta` decimal(10,2) DEFAULT 0.00,
  `tipo` enum('venta','insumo') NOT NULL DEFAULT 'venta',
  `imagen` varchar(255) DEFAULT NULL,
  `manejaStock` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
CREATE TABLE `proveedores` (
  `idProveedor` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `idUsuario` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `pin` int(11) NOT NULL,
  `rol` enum('admin','empleado') DEFAULT 'empleado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ventas`
--

DROP TABLE IF EXISTS `ventas`;
CREATE TABLE `ventas` (
  `idVenta` int(11) NOT NULL,
  `idMesa` int(11) DEFAULT NULL,
  `fechaVenta` datetime DEFAULT current_timestamp(),
  `estado` enum('pendiente','completada','cancelada') DEFAULT 'pendiente',
  `metodoPago` enum('efectivo','transferencia') DEFAULT 'efectivo',
  `total` decimal(10,2) DEFAULT 0.00,
  `idUsuario` int(11) DEFAULT NULL,
  `tipoVenta` enum('detallada','rapida') DEFAULT 'detallada' COMMENT 'Detallada: con productos, Rápida: solo total',
  `descripcion` varchar(255) DEFAULT NULL COMMENT 'Descripción para ventas rápidas',
  `fechaCreacion` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación de la venta',
  `fechaActualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Última actualización'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_ventas_activas_mesas`
-- (Véase abajo para la vista actual)
--
DROP VIEW IF EXISTS `vista_ventas_activas_mesas`;
CREATE TABLE `vista_ventas_activas_mesas` (
`idMesa` int(11)
,`numeroMesa` int(11)
,`nombreMesa` varchar(100)
,`estadoMesa` enum('libre','ocupada')
,`idVenta` int(11)
,`total` decimal(10,2)
,`fechaCreacion` datetime
,`fechaActualizacion` datetime
,`cantidadProductos` bigint(21)
,`cantidadItems` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_ventas_activas_mesas`
--
DROP TABLE IF EXISTS `vista_ventas_activas_mesas`;

DROP VIEW IF EXISTS `vista_ventas_activas_mesas`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_ventas_activas_mesas`  AS SELECT `m`.`idMesa` AS `idMesa`, `m`.`numero` AS `numeroMesa`, `m`.`nombre` AS `nombreMesa`, `m`.`estado` AS `estadoMesa`, `v`.`idVenta` AS `idVenta`, `v`.`total` AS `total`, `v`.`fechaCreacion` AS `fechaCreacion`, `v`.`fechaActualizacion` AS `fechaActualizacion`, count(`dv`.`idDetalleVenta`) AS `cantidadProductos`, sum(`dv`.`cantidad`) AS `cantidadItems` FROM ((`mesas` `m` left join `ventas` `v` on(`m`.`idMesa` = `v`.`idMesa` and `v`.`estado` = 'pendiente')) left join `detalle_venta` `dv` on(`v`.`idVenta` = `dv`.`idVenta`)) GROUP BY `m`.`idMesa`, `m`.`numero`, `m`.`nombre`, `m`.`estado`, `v`.`idVenta`, `v`.`total`, `v`.`fechaCreacion`, `v`.`fechaActualizacion` ORDER BY `m`.`numero` ASC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`idCategoria`);

--
-- Indices de la tabla `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`idCompra`),
  ADD KEY `fk02` (`idProveedor`),
  ADD KEY `fk10` (`idUsuario`);

--
-- Indices de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD PRIMARY KEY (`idDetalleCompra`),
  ADD KEY `fk03` (`idCompra`),
  ADD KEY `fk04` (`idProducto`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`idDetalleVenta`),
  ADD KEY `fk06` (`idVenta`),
  ADD KEY `fk07` (`idProducto`);

--
-- Indices de la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD PRIMARY KEY (`idGasto`),
  ADD KEY `fk08` (`idProducto`),
  ADD KEY `fk11` (`idUsuario`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`idInventario`),
  ADD KEY `fk13` (`idUsuario`),
  ADD KEY `idx_producto` (`idProducto`),
  ADD KEY `idx_fecha` (`fechaMovimiento`),
  ADD KEY `idx_tipo` (`tipoMovimiento`);

--
-- Indices de la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`idMesa`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`idProducto`),
  ADD KEY `fk01` (`idCategoria`);

--
-- Indices de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  ADD PRIMARY KEY (`idProveedor`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`idUsuario`);

--
-- Indices de la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD PRIMARY KEY (`idVenta`),
  ADD KEY `fk09` (`idUsuario`),
  ADD KEY `idx_mesa_estado` (`idMesa`,`estado`),
  ADD KEY `idx_estado` (`estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `idCategoria` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `compras`
--
ALTER TABLE `compras`
  MODIFY `idCompra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  MODIFY `idDetalleCompra` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `idDetalleVenta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gastos`
--
ALTER TABLE `gastos`
  MODIFY `idGasto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario`
--
ALTER TABLE `inventario`
  MODIFY `idInventario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mesas`
--
ALTER TABLE `mesas`
  MODIFY `idMesa` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `idProducto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedores`
--
ALTER TABLE `proveedores`
  MODIFY `idProveedor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `idUsuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ventas`
--
ALTER TABLE `ventas`
  MODIFY `idVenta` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `fk02` FOREIGN KEY (`idProveedor`) REFERENCES `proveedores` (`idProveedor`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk10` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD CONSTRAINT `fk03` FOREIGN KEY (`idCompra`) REFERENCES `compras` (`idCompra`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk04` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk06` FOREIGN KEY (`idVenta`) REFERENCES `ventas` (`idVenta`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk07` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `gastos`
--
ALTER TABLE `gastos`
  ADD CONSTRAINT `fk08` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk11` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `fk12` FOREIGN KEY (`idProducto`) REFERENCES `productos` (`idProducto`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk13` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk01` FOREIGN KEY (`idCategoria`) REFERENCES `categorias` (`idCategoria`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `ventas`
--
ALTER TABLE `ventas`
  ADD CONSTRAINT `fk05` FOREIGN KEY (`idMesa`) REFERENCES `mesas` (`idMesa`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk09` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
