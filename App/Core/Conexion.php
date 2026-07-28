<?php

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $host = trim((string) (defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1')));
                $name = trim((string) (defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'cafeteria_software')));
                $user = trim((string) (defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root')));
                $pass = trim((string) (defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '')));
                $charset = trim((string) (defined('DB_CHARSET') ? DB_CHARSET : (getenv('DB_CHARSET') ?: 'utf8mb4')));
                $port = trim((string) (defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306')));
                $persistent = defined('DB_PERSISTENT')
                    ? DB_PERSISTENT
                    : filter_var(getenv('DB_PERSISTENT') ?: 'false', FILTER_VALIDATE_BOOLEAN);

                $isInfinityFree = isset($_SERVER['DOCUMENT_ROOT'])
                    && strpos((string) $_SERVER['DOCUMENT_ROOT'], 'infinityfree.com') !== false;

                if ($host === '' || $name === '' || $user === '') {
                    throw new RuntimeException('Configuración DB incompleta. Verifica DB_HOST, DB_NAME y DB_USER en App/Core/Config.php');
                }

                // Evita bloquear antes de tiempo: dejamos que PDO intente conectar
                // y devolvemos un mensaje accionable si falla.

                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => $persistent,
                ]);

                // ------------------------------------------------------------
                // ZONA HORARIA DE COLOMBIA (UTC-5) EN LA CONEXIÓN
                // ------------------------------------------------------------
                // Las fechas de ventas, cajas e inventario las escribe MySQL con
                // NOW() / CURRENT_TIMESTAMP, que usa la hora del SERVIDOR, no la
                // de PHP. Si el servidor está en otra zona (un VPS casi siempre
                // corre en UTC), las ventas quedarían con horas corridas y los
                // cierres del día no cuadrarían.
                //
                // Fijando el desfase por conexión, la hora guardada es siempre la
                // de Colombia sin importar dónde esté alojado el sistema.
                // Se usa el desfase fijo '-05:00' y no el nombre 'America/Bogota'
                // porque los nombres requieren cargar las tablas de zonas horarias
                // en MySQL, que muchos servidores no traen. Colombia no aplica
                // horario de verano, así que -05:00 es válido todo el año.
                self::$instance->exec("SET time_zone = '-05:00'");
            } catch (PDOException $e) {
                $extra = '';
                if (strpos($e->getMessage(), '[2002]') !== false) {
                    $extra = ' Verifica DB_HOST del panel, puerto 3306 y que DB_USER/DB_NAME no tengan espacios.';
                    if ($isInfinityFree) {
                        $extra .= ' En InfinityFree no uses 127.0.0.1 ni localhost; usa el host MySQL exacto del panel (ej: sqlXXX.epizy.com).';
                    }
                }
                throw new RuntimeException('Error de conexión a la base de datos: ' . $e->getMessage() . $extra, 0, $e);
            }
        }

        return self::$instance;
    }
}
