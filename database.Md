CREATE DATABASE IF NOT EXISTS cafeteria_software;
USE cafeteria_software;

-- =====================================================
-- Tabla: categorias
-- =====================================================
CREATE TABLE categorias (
    idCategoria INT(11) PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL
);

-- =====================================================
-- Tabla: proveedores
-- =====================================================
CREATE TABLE proveedores (
    idProveedor INT(11) PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    telefono INT NULL
);

-- =====================================================
-- Tabla: productos
-- =====================================================
CREATE TABLE productos (
    idProducto INT(11) PRIMARY KEY AUTO_INCREMENT,
    idCategoria INT(11),
    nombre VARCHAR(100) NOT NULL,
    stock INT(11) not null default 0,
    precioCompra DECIMAL(10,2) DEFAULT 0.00,
    precioVenta DECIMAL(10,2) DEFAULT 0.00,
    tipo ENUM('venta', 'insumo') NOT NULL,
    CONSTRAINT fk01 FOREIGN KEY (idCategoria) REFERENCES categorias(idCategoria)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =====================================================
-- Tabla: compras
-- =====================================================
CREATE TABLE compras (
    idCompra INT(11) PRIMARY KEY AUTO_INCREMENT,
    idProveedor INT(11),
    fechaCompra DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) DEFAULT 0.00,
    CONSTRAINT fk02 FOREIGN KEY (idProveedor) REFERENCES proveedores(idProveedor)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =====================================================
-- Tabla: detalle_compra
-- =====================================================
CREATE TABLE detalle_compra (
    idDetalleCompra INT(11) PRIMARY KEY AUTO_INCREMENT,
    idCompra INT(11),
    idProducto INT(11),
    cantidad INT NOT NULL,
    precioUnitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    CONSTRAINT fk03 FOREIGN KEY (idCompra) REFERENCES compras(idCompra)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk04 FOREIGN KEY (idProducto) REFERENCES productos(idProducto)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =====================================================
-- Tabla: mesas
-- =====================================================
CREATE TABLE mesas (
    idMesa INT(11) PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100),
    numero INT,
    estado ENUM('libre', 'ocupada') DEFAULT 'libre'
);

-- =====================================================
-- Tabla: ventas
-- =====================================================
CREATE TABLE ventas (
    idVenta INT(11) PRIMARY KEY AUTO_INCREMENT,
    idMesa INT(11),
    fechaVenta DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('completada', 'pendiente') DEFAULT 'pendiente',
    metodoPago ENUM('efectivo', 'tarjeta', 'otro') DEFAULT 'efectivo',
    total DECIMAL(10,2) DEFAULT 0.00,
    CONSTRAINT fk05 FOREIGN KEY (idMesa) REFERENCES mesas(idMesa)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =====================================================
-- Tabla: detalleventa
-- =====================================================
CREATE TABLE detalleventa (
    idDetalleVenta INT(11) PRIMARY KEY AUTO_INCREMENT,
    idVenta INT(11),
    idProducto INT(11),
    cantidad INT NOT NULL DEFAULT 1,
    precioUnitario DECIMAL(10,2) NOT NULL,
    subTotal DECIMAL(10,2) DEFAULT 0.00,
    CONSTRAINT fk06 FOREIGN KEY (idVenta) REFERENCES ventas(idVenta)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk07 FOREIGN KEY (idProducto) REFERENCES productos(idProducto)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =====================================================
-- Tabla: gastos
-- =====================================================
CREATE TABLE gastos (
    idGasto INT(11) PRIMARY KEY AUTO_INCREMENT,
    idProducto INT(11),
    descripcion VARCHAR(255),
    fechaGasto DATETIME DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(10,2),
    tipo ENUM('otro', 'producto'),
    CONSTRAINT fk08 FOREIGN KEY (idProducto) REFERENCES productos(idProducto)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =====================================================
-- Tabla: usuarios
-- =====================================================
CREATE TABLE usuarios (
    idUsuario INT(11) PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    pin INT NOT NULL,
    rol ENUM('admin', 'empleado') DEFAULT 'empleado'
);