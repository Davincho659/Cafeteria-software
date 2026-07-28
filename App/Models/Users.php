<?php

class Users {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Verificar credenciales de login.
     *
     * El PIN se guarda hasheado (password_hash), NUNCA en texto plano. Aquí se
     * busca por nombre y se compara con password_verify, que es resistente a
     * ataques de tiempo.
     *
     * Compatibilidad: si un usuario todavía tuviera el PIN en texto plano
     * (instalación antigua sin migrar), se valida y se re-hashea al vuelo.
     */
    public function getByNameAndPin($nombre, $pin) {
        $sql = "SELECT * FROM usuarios WHERE nombre = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            return false;
        }

        $hash = (string) $usuario['pin'];
        $info = password_get_info($hash);

        if ($info['algo']) {
            // PIN ya hasheado: verificación normal
            if (password_verify((string) $pin, $hash)) {
                return $usuario;
            }
            return false;
        }

        // PIN en texto plano (aún sin migrar): comparar y actualizar a hash
        if (hash_equals($hash, (string) $pin)) {
            $this->changePin((int) $usuario['idUsuario'], (string) $pin);
            return $usuario;
        }

        return false;
    }

    /**
     * Genera el hash de un PIN. Único punto donde se define el algoritmo.
     */
    public static function hashPin($pin) {
        return password_hash((string) $pin, PASSWORD_DEFAULT);
    }

    /**
     * Obtener usuario por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM usuarios WHERE idUsuario = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener todos los usuarios. Nunca se selecciona la columna 'pin'.
     */
    public function getAll() {
        $sql = "SELECT idUsuario, nombre, rol FROM usuarios ORDER BY nombre ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuántos administradores hay. Se usa para impedir que el negocio quede sin
     * ningún admin (nadie podría volver a entrar a configuración/reportes).
     */
    public function countAdmins(): int {
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin'";
        $stmt = $this->db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['total'] ?? 0);
    }

    /**
     * ¿Este usuario tiene ventas registradas? Si las tiene NO se debe borrar:
     * se perdería la trazabilidad de quién hizo cada venta.
     */
    public function hasSales($id): bool {
        $sql = "SELECT 1 FROM ventas WHERE idUsuario = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return (bool) $stmt->fetch();
    }

    /**
     * ¿Existe otro usuario (distinto de $exceptoId) con ese nombre?
     * El nombre es la credencial de acceso, así que debe ser único.
     */
    public function nameInUse($nombre, $exceptoId = null): bool {
        $sql = "SELECT idUsuario FROM usuarios WHERE nombre = ?";
        $params = [$nombre];
        if ($exceptoId !== null) {
            $sql .= " AND idUsuario <> ?";
            $params[] = $exceptoId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }

    /**
     * Crear usuario
     */
    public function create($nombre, $pin, $rol = 'empleado') {
        $sql = "INSERT INTO usuarios (nombre, pin, rol) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre, self::hashPin($pin), $rol]);
        return $this->db->lastInsertId();
    }

    /**
     * Actualizar usuario
     */
    public function update($id, $nombre, $pin = null, $rol = null) {
        if ($pin !== null && $rol !== null) {
            $sql = "UPDATE usuarios SET nombre = ?, pin = ?, rol = ? WHERE idUsuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, self::hashPin($pin), $rol, $id]);
        } elseif ($pin !== null) {
            $sql = "UPDATE usuarios SET nombre = ?, pin = ? WHERE idUsuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, self::hashPin($pin), $id]);
        } elseif ($rol !== null) {
            $sql = "UPDATE usuarios SET nombre = ?, rol = ? WHERE idUsuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $rol, $id]);
        } else {
            $sql = "UPDATE usuarios SET nombre = ? WHERE idUsuario = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre, $id]);
        }
    }

    /**
     * Eliminar usuario
     */
    public function delete($id) {
        $sql = "DELETE FROM usuarios WHERE idUsuario = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Verificar si existe un usuario con ese nombre
     */
    public function existsByName($nombre) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE nombre = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nombre]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Cambiar PIN de usuario
     */
    public function changePin($id, $newPin) {
        $sql = "UPDATE usuarios SET pin = ? WHERE idUsuario = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([self::hashPin($newPin), $id]);
    }
}