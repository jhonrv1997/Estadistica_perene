<?php
/**
 * Sistema de Gestion de Datos HIS
 * Archivo de Configuracion
 */

// Configuracion de Base de Datos
define('DB_HOST', 'sql102.infinityfree.com');
define('DB_NAME', 'if0_42181393_his');
define('DB_USER', 'if0_42181393');
define('DB_PASS', 'MYexuBRT6kg');
define('DB_CHARSET', 'utf8mb4');

// Configuracion de la Aplicacion
define('APP_NAME', 'Sistema HIS - Gestion de Datos');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'hisperene.gt.tc'); // URL base del sistema (ej: https://tudominio.com/his)

// Configuracion de Sesiones
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos

// Configuracion de Upload
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB

// Conexion a Base de Datos
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci",
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Error de conexion BD: " . $e->getMessage());
            die("Error de conexion a la base de datos. Verifique la configuracion.");
        }
    }
    return $pdo;
}

// Zona horaria
date_default_timezone_set('America/Lima');

// Iniciar sesion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
