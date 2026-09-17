<?php
/**
 * Sistema de Gestion de Datos HIS
 * Archivo de Configuracion (CORREGIDO)
 *
 * CAMBIO CLAVE:
 * El certificado comodin del hosting (*.gt.tc) solo cubre UN nivel de
 * subdominio:  hisperene.gt.tc  ->  SI esta cubierto.
 * www.hisperene.gt.tc es un sub-subdominio (dos niveles) y NO esta
 * cubierto -> el navegador muestra error SSL (ERR_CERT_COMMON_NAME_INVALID).
 *
 * Por eso este archivo hace dos cosas:
 *   1. Redirige automaticamente cualquier entrada por "www." hacia la
 *      version canonica SIN www (301).
 *   2. Define APP_URL siempre con https:// y SIN www, para que ninguna
 *      redireccion o enlace generado por el sistema use "www.".
 */

/* =====================================================================
 * 1. PROTECCION ANTI-WWW (debe ejecutarse ANTES de todo lo demas)
 * ---------------------------------------------------------------------
 * Si alguien llega por www.hisperene.gt.tc (via http, donde el servidor
 * todavia responde), se le manda a la version sin www con un 301.
 * Nota: en https://www... el error de certificado aparece ANTES de que
 * PHP pueda actuar; esta guardia evita que el codigo genere enlaces o
 * sesiones sobre el host incorrecto.
 * ===================================================================== */
if (isset($_SERVER['HTTP_HOST']) && stripos($_SERVER['HTTP_HOST'], 'www.') === 0) {
    $host_sin_www = preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST']);
    $uri          = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $host_sin_www . $uri, true, 301);
    exit;
}

// Configuracion de Base de Datos
define('DB_HOST', 'sql102.infinityfree.com');
define('DB_NAME', 'if0_42181393_his');
define('DB_USER', 'if0_42181393');
define('DB_PASS', 'MYexuBRT6kg');
define('DB_CHARSET', 'utf8mb4');

// Configuracion de la Aplicacion
define('APP_NAME', 'Sistema HIS - Gestion de Datos');
define('APP_VERSION', '1.0.0');
// ANTES:  define('APP_URL', 'hisperene.gt.tc');  <- sin esquema, ambiguo
// AHORA:  URL canonica absoluta, SIEMPRE con https:// y SIN www
define('APP_URL', 'https://hisperene.gt.tc');

// Configuracion de Sesiones
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos

// Configuracion de Upload
// NOTA: InfinityFree (plan gratuito) limita la subida a ~10 MB por archivo.
// MAX_FILE_SIZE de 100MB sera rechazado por el servidor aunque PHP lo permita.
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB (limite real del hosting)

/* =====================================================================
 * 2. HELPERS DE URL / REDIRECCION
 * ---------------------------------------------------------------------
 * Usar SIEMPRE estas funciones para enlaces y redirecciones internas
 * en lugar de escribir URLs a mano. Garantizan https + sin www.
 * Ejemplos:
 *   echo app_url('login.php');
 *   app_redirect('index.php?ok=1');
 * ===================================================================== */
function app_url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function app_redirect($path = '') {
    header('Location: ' . app_url($path), true, 302);
    exit;
}

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
