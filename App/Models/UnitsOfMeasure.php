<?php
require_once __DIR__ . '/../Core/Conexion.php';

class UnitsOfMeasure {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener todas las unidades activas
     */
    public function getAll() {
        $sql = "SELECT * FROM unidades_medida WHERE activo = 1 ORDER BY tipo, nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener unidad por ID
     */
    public function getById($idUnidad) {
        $sql = "SELECT * FROM unidades_medida WHERE idUnidad = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idUnidad]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener unidad por abreviatura
     */
    public function getByAbreviatura($abreviatura) {
        $sql = "SELECT * FROM unidades_medida WHERE abreviatura = ? AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$abreviatura]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener unidades por tipo
     */
    public function getByTipo($tipo) {
        $sql = "SELECT * FROM unidades_medida WHERE tipo = ? AND activo = 1 ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tipo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nueva unidad
     */
    public function create($nombre, $abreviatura, $tipo) {
        $sql = "INSERT INTO unidades_medida (nombre, abreviatura, tipo) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre, $abreviatura, $tipo]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualizar unidad
     */
    public function update($idUnidad, $nombre, $abreviatura, $tipo, $activo = 1) {
        $sql = "UPDATE unidades_medida SET nombre = ?, abreviatura = ?, tipo = ?, activo = ? WHERE idUnidad = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$nombre, $abreviatura, $tipo, $activo, $idUnidad]);
    }

    /**
     * Desactivar unidad (no eliminar por integridad)
     */
    public function deactivate($idUnidad) {
        $sql = "UPDATE unidades_medida SET activo = 0 WHERE idUnidad = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$idUnidad]);
    }

    /**
     * Verificar si una unidad está en uso por productos
     */
    public function isInUse($idUnidad) {
        $sql = "SELECT COUNT(*) as total FROM productos WHERE idUnidadBase = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idUnidad]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'] > 0;
    }

    /**
     * Obtener unidades básicas (sistema por defecto)
     */
    public function getBasicUnits() {
        $sql = "SELECT * FROM unidades_medida WHERE idUnidad IN (1,2,3) ORDER BY idUnidad";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
