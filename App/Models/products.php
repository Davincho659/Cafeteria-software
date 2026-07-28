<?php

require_once __DIR__ . '/../Core/Conexion.php';

class Products {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    public function getAll(array $filters = []) {
        $query="SELECT p.idCategoria, p.idProducto, p.nombre, p.codigoBarras, p.precioCompra, p.precioVenta, p.tipo, p.idUnidadBase, p.imagen, p.manejaStock, COALESCE(p.estado,1) AS estado,
            c.nombre AS categoria, c.imagen AS categoria_imagen,
            u.nombre AS unidadNombre, u.abreviatura AS unidadAbreviatura
            FROM productos p 
            INNER JOIN categorias c ON p.idCategoria = c.idCategoria
            LEFT JOIN unidades_medida u ON p.idUnidadBase = u.idUnidad
            Where 1=1";

        $params = [];

        if (isset($filters['estado'])) {
            $query .= " AND p.estado = " . (int)$filters['estado'];
        }
        if (isset($filters['manejaStock'])) {
            $query .= " AND p.manejaStock = " . (int)$filters['manejaStock'];
        }
        if (isset($filters['tipo'])) {
            // Parametrizado: nunca concatenar entrada del usuario en el SQL.
            $query .= " AND p.tipo = ?";
            $params[] = (string)$filters['tipo'];
        }

        $strm = $this->db->prepare($query);
        $strm->execute($params);
        return $strm->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByCategory($id, $soloActivos = false) {
        $query = 'SELECT p.idCategoria, p.idProducto, p.nombre, p.precioCompra, p.precioVenta, p.tipo, p.idUnidadBase, p.manejaStock, p.imagen, COALESCE(p.estado,1) AS estado,
                c.nombre AS categoria, c.imagen AS categoria_imagen,
                u.nombre AS unidadNombre, u.abreviatura AS unidadAbreviatura
                FROM productos AS p 
                INNER JOIN categorias AS c ON p.idCategoria = c.idCategoria
                LEFT JOIN unidades_medida u ON p.idUnidadBase = u.idUnidad
                WHERE p.idCategoria = ?';

        if ($soloActivos) {
            $query .= ' AND p.estado = 1';
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($idCategoria, $nombre, $tipo, $precioVenta = null, $precioCompra = null, $imagen = 'default.png', $idUnidadBase = null, $manejaStock = 0, $estado = 1, $codigoBarras = null) {
        $query = "INSERT INTO productos (idCategoria, nombre, codigoBarras, tipo, precioVenta, precioCompra, imagen, idUnidadBase, manejaStock, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$idCategoria, $nombre, $codigoBarras, $tipo, $precioVenta, $precioCompra, $imagen, $idUnidadBase, $manejaStock, $estado]);
        return $this->db->lastInsertId();
    }

    /**
     * Buscar un producto por su código de barras.
     * Solo devuelve productos activos y de tipo 'venta' (los que se cobran).
     * El código de barras es OPCIONAL: si es vacío/nulo no busca nada.
     */
    public function getByBarcode($codigo) {
        $codigo = trim((string) $codigo);
        if ($codigo === '') {
            return null;
        }
        $query = 'SELECT p.idProducto, p.idCategoria, p.nombre, p.codigoBarras, p.precioVenta, p.precioCompra,
                         p.tipo, p.idUnidadBase, p.imagen, p.manejaStock, COALESCE(p.estado,1) AS estado,
                         c.nombre AS categoria
                  FROM productos p
                  INNER JOIN categorias c ON p.idCategoria = c.idCategoria
                  WHERE p.codigoBarras = ? AND p.estado = 1 AND p.tipo = "venta"
                  LIMIT 1';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * ¿Existe ese código de barras en otro producto? (para validar unicidad).
     */
    public function barcodeInUse($codigo, $exceptoId = null) {
        $codigo = trim((string) $codigo);
        if ($codigo === '') {
            return false;
        }
        $query = "SELECT idProducto FROM productos WHERE codigoBarras = ?";
        $params = [$codigo];
        if ($exceptoId !== null) {
            $query .= " AND idProducto <> ?";
            $params[] = $exceptoId;
        }
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    public function getById($id) {
        $query = 'SELECT p.*, c.nombre AS categoria, c.imagen AS categoria_imagen,
                  u.nombre AS unidadNombre, u.abreviatura AS unidadAbreviatura, u.tipo AS unidadTipo
                  FROM productos p
                  INNER JOIN categorias c ON p.idCategoria = c.idCategoria
                  LEFT JOIN unidades_medida u ON p.idUnidadBase = u.idUnidad
                  WHERE p.idProducto = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE productos SET idCategoria = ?, nombre = ?, codigoBarras = ?, tipo = ?, precioVenta = ?, precioCompra = ?, imagen = ?, idUnidadBase = ?, manejaStock = ?, estado = ? WHERE idProducto = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            $data['idCategoria'],
            $data['nombre'],
            $data['codigoBarras'] ?? null,
            $data['tipo'],
            $data['precioVenta'],
            $data['precioCompra'],
            $data['imagen'],
            $data['idUnidadBase'] ?? 1,
            $data['manejaStock'] ?? 0,
            $data['estado'] ?? 1,
            $id
        ]);
    }

    public function delete($id) {
        return $this->setStatus($id, 0);
    }

    public function setStatus($id, $estado) {
        $query = "UPDATE productos SET estado = ? WHERE idProducto = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([(int)$estado, $id]);
    }

    /**
     * Obtener costo promedio ponderado del producto desde historial de compras
     */
    public function getCostoPromedioProducto($idProducto) {
        $sql = "SELECT 
                    COALESCE(SUM(h.cantidad * h.precioUnitario) / NULLIF(SUM(h.cantidad),0), 0) AS costoPromedio
                FROM historial_precio_compra h
                WHERE h.idProducto = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['costoPromedio'] : 0.0;
    }

    /**
     * Obtener último precio de compra
     */
    public function getUltimoPrecioCompra($idProducto) {
        $sql = "SELECT precioUnitario 
                FROM historial_precio_compra 
                WHERE idProducto = ? 
                ORDER BY fechaRegistro DESC, idHistorial DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idProducto]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['precioUnitario'] : 0.0;
    }

    /**
     * Obtener resumen de costos (promedio y último)
     */
    public function getResumenCosto($idProducto) {
        return [
            'costoPromedio' => $this->getCostoPromedioProducto($idProducto),
            'ultimoPrecio' => $this->getUltimoPrecioCompra($idProducto),
        ];
    }

    
}