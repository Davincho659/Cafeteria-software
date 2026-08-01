-- ============================================================================
--  DATOS INICIALES — Sistema POS
-- ============================================================================
--  Deja el sistema LISTO PARA USAR pero VACÍO de datos del negocio:
--  sin productos, sin ventas, sin inventario, sin compras ni gastos.
--
--  Solo carga lo mínimo indispensable para que la aplicación arranque:
--    · Unidades de medida (el sistema las necesita para los productos)
--    · Un usuario administrador inicial
--    · La configuración del negocio con valores por defecto
--
--  Ejecutar DESPUÉS de schema.sql.
--  Ver install/INSTALACION.md para los pasos completos.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1) Unidades de medida
--    Definen cómo se cuenta cada producto:
--      · unidad → se venden enteros (gaseosas, empanadas)
--      · peso   → admite decimales (café, maíz, queso)
--      · volumen→ admite decimales (aceite, leche)
--    Se pueden agregar más desde la base de datos si el negocio las necesita.
-- ----------------------------------------------------------------------------
INSERT INTO unidades_medida (idUnidad, nombre, abreviatura, tipo, activo) VALUES
    (1, 'Unidad',    'u',  'unidad',  1),
    (2, 'Kilogramo', 'kg', 'peso',    1),
    (3, 'Litro',     'L',  'volumen', 1),
    (4, 'Gramo',     'g',  'peso',    1),
    (5, 'Libra',     'lb', 'peso',    1),
    (6, 'Bulto',     'blt','peso',    1),
    (7, 'Paquete',   'paq','unidad',  1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ----------------------------------------------------------------------------
-- 2) Usuario administrador inicial
--    Usuario: admin        PIN: 1234
--
--    *** CAMBIA ESTE PIN APENAS ENTRES ***  (Menú → Usuarios)
--    El PIN se guarda cifrado con bcrypt: el hash de abajo corresponde a 1234
--    y no se puede leer en texto plano.
-- ----------------------------------------------------------------------------
INSERT INTO usuarios (nombre, pin, rol) VALUES
    ('admin', '$2y$10$UC7LhQyMK/TFOu2MeCIUyOBPxtApro0VuVZ0ungdDwqCl5Im.i7H2', 'admin')
ON DUPLICATE KEY UPDATE rol = 'admin';

-- ----------------------------------------------------------------------------
-- 3) Configuración del negocio
--    El dueño la edita desde Menú → Configuración (nombre, logo, colores y los
--    datos que salen impresos en el tiquete).
-- ----------------------------------------------------------------------------
INSERT INTO configuracion (clave, valor) VALUES
    ('nombre_negocio',   'Mi Negocio'),
    ('logo',             'logo.jpg'),
    ('color_primario',   '#5B3411'),
    ('color_secundario', '#6B3E1A'),
    ('color_acento',     '#E07A2F'),
    ('moneda',           '$'),
    ('mensaje_factura',  '¡Gracias por su compra!'),
    ('nit',              ''),
    ('direccion',        ''),
    ('telefono',         ''),
    ('mensaje_pie',      'Vuelva pronto')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

-- ----------------------------------------------------------------------------
-- 4) Mesas / barras del salón  (OPCIONAL)
--    Descomenta y ajusta si el negocio atiende en mesas. También se pueden
--    crear después desde Menú → Mesas, que además permite ubicarlas
--    arrastrándolas en el plano del salón.
-- ----------------------------------------------------------------------------
-- INSERT INTO mesas (numero, nombre, estado, tipo) VALUES
--     (1, 'Mesa 1', 'libre', 'mesa'),
--     (2, 'Mesa 2', 'libre', 'mesa'),
--     (3, 'Mesa 3', 'libre', 'mesa'),
--     (4, 'Barra 1','libre', 'barra');

-- ----------------------------------------------------------------------------
-- 5) Categorías de productos  (OPCIONAL)
--    Se crean mejor desde Menú → Productos, donde se les puede poner imagen.
-- ----------------------------------------------------------------------------
-- INSERT INTO categorias (nombre, imagen) VALUES
--     ('Fritos',   'default.png'),
--     ('Bebidas',  'default.png');
