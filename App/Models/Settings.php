<?php
require_once __DIR__ . '/../Core/Conexion.php';

/**
 * ============================================================================
 * SETTINGS - Configuración del negocio (clave/valor)
 * ============================================================================
 * Guarda el nombre del sitio, el logo y los colores del tema.
 *
 * La tabla se crea sola la primera vez (CREATE TABLE IF NOT EXISTS) para que
 * el módulo funcione al subirlo a cualquier hosting sin correr migraciones a
 * mano. Los valores por defecto se siembran una sola vez.
 * ============================================================================
 */
class Settings {
    private $db;
    private static $cache = null;

    /** Valores por defecto (también definen qué claves son válidas) */
    private static $defaults = [
        'nombre_negocio'   => 'La casa del pastel',
        'logo'             => 'logo.jpg',
        'color_primario'   => '#5B3411',
        'color_secundario' => '#6B3E1A',
        'color_acento'     => '#E07A2F',
        'moneda'           => '$',
        'mensaje_factura'  => '¡Gracias por su compra!',
        // Datos que se imprimen en el encabezado/pie del tiquete térmico
        'nit'              => '',
        'direccion'        => '',
        'telefono'         => '',
        'mensaje_pie'      => 'Vuelva pronto',
    ];

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ensureTable();
    }

    /** Crea la tabla y siembra los valores por defecto si hace falta. */
    private function ensureTable() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS configuracion (
            clave VARCHAR(50) NOT NULL PRIMARY KEY,
            valor TEXT DEFAULT NULL,
            fechaActualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        // Sembrar solo las claves que aún no existan
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO configuracion (clave, valor) VALUES (?, ?)"
        );
        foreach (self::$defaults as $clave => $valor) {
            $stmt->execute([$clave, $valor]);
        }
    }

    /** Devuelve todos los valores (mezclados con los por defecto). */
    public function getAll() {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rows = $this->db->query("SELECT clave, valor FROM configuracion")
                         ->fetchAll(PDO::FETCH_KEY_PAIR);

        // Los defaults rellenan cualquier clave faltante
        self::$cache = array_merge(self::$defaults, $rows ?: []);
        return self::$cache;
    }

    /** Obtiene un valor por clave. */
    public function get($clave, $porDefecto = null) {
        $all = $this->getAll();
        if (array_key_exists($clave, $all) && $all[$clave] !== null && $all[$clave] !== '') {
            return $all[$clave];
        }
        return $porDefecto ?? (self::$defaults[$clave] ?? null);
    }

    /**
     * Guarda un lote de valores. Solo se aceptan claves conocidas para no
     * permitir que se inyecten claves arbitrarias desde el formulario.
     */
    public function saveMany(array $valores) {
        $stmt = $this->db->prepare(
            "INSERT INTO configuracion (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );

        foreach ($valores as $clave => $valor) {
            if (!array_key_exists($clave, self::$defaults)) {
                continue; // clave desconocida: se ignora
            }
            $stmt->execute([$clave, $valor]);
        }

        self::$cache = null; // invalidar cache
        return true;
    }

    /** Claves de color, para validar/normalizar en el controlador. */
    public static function colorKeys() {
        return ['color_primario', 'color_secundario', 'color_acento'];
    }
}
