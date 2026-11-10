<?php
/**
 * Sistema de Gestion de Datos HIS
 * Verificacion de autenticacion
 */

require_once __DIR__ . '/../config.php';

function verificarAutenticacion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar si existe sesion activa
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario'])) {
        header('Location: ' . getAppUrl() . '/index.php');
        exit;
    }
    
    // Verificar timeout de sesion
    if (isset($_SESSION['ultimo_acceso'])) {
        $tiempoInactivo = time() - $_SESSION['ultimo_acceso'];
        if ($tiempoInactivo > SESSION_TIMEOUT) {
            session_destroy();
            header('Location: ' . getAppUrl() . '/index.php?timeout=1');
            exit;
        }
    }
    
    // Actualizar ultimo acceso
    $_SESSION['ultimo_acceso'] = time();
}

/**
 * Verificar si el usuario autenticado tiene rol de administrador
 * Llamar despues de verificarAutenticacion()
 * Si no es admin, redirige al dashboard con un mensaje de error
 */
function verificarAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Primero verificar autenticacion
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario'])) {
        header('Location: ' . getAppUrl() . '/index.php');
        exit;
    }
    // Verificar rol administrador
    $rol = strtolower(trim($_SESSION['rol'] ?? ''));
    if ($rol !== 'admin') {
        // Redirigir al dashboard con mensaje de acceso denegado
        header('Location: ' . getAppUrl() . '/dashboard.php?acceso_denegado=1');
        exit;
    }
}

/**
 * Retornar true si el usuario actual es administrador
 */
function esAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $rol = strtolower(trim($_SESSION['rol'] ?? ''));
    return $rol === 'admin';
}

/* =====================================================================
 * ACCESO POR MODULOS
 * ---------------------------------------------------------------------
 * Cada modulo protegido del sistema se define en el catalogo
 * MODULOS_SISTEMA. El rol 'admin' tiene acceso a todo por defecto.
 * Para el resto de usuarios, el administrador asigna modulo por
 * modulo desde usuarios.php; los permisos se guardan en la tabla
 * USUARIO_MODULOS (id_usuario, modulo).
 * ===================================================================== */

/**
 * Catalogo de modulos protegidos del sistema.
 * clave = identificador del modulo (tal como se guarda en USUARIO_MODULOS.modulo)
 */
define('MODULOS_SISTEMA', [
    'consulta_atenciones' => [
        'nombre'      => 'Consulta de Atenciones',
        'archivo'     => 'consulta_atenciones.php',
        'icono'       => 'fa-search',
        'descripcion' => 'Filtro General y Filtro Preventivas del consolidado HIS-MINSA',
    ],
    'reporte_operacionales' => [
        'nombre'      => 'Reportes Operacionales',
        'archivo'     => 'reporte_operacionales.php',
        'icono'       => 'fa-chart-line',
        'descripcion' => '17 estrategias: Adolescente, Adulto, Cancer, ITS, Materno, TBC, Zoonosis, etc.',
    ],
    'convenio_gestion' => [
        'nombre'      => 'Convenio de Gestion',
        'archivo'     => 'convenio_gestion.php',
        'icono'       => 'fa-file-contract',
        'descripcion' => 'Avance de los 34 indicadores del Convenio MINSA-GORE 2026',
    ],
    'convenio_fed' => [
        'nombre'      => 'Convenio FED',
        'archivo'     => 'convenio_fed.php',
        'icono'       => 'fa-medal',
        'descripcion' => 'Fondo de Estimulo al Desempeno: avance de 7 indicadores',
    ],
    'control_calidad' => [
        'nombre'      => 'Control de Calidad',
        'archivo'     => 'control_calidad.php',
        'icono'       => 'fa-clipboard-check',
        'descripcion' => 'Observaciones detectadas en los datos consolidados',
    ],
    'reporte_atenciones' => [
        'nombre'      => 'Reporte de Atenciones y Atendidos',
        'archivo'     => 'reporte_atenciones.php',
        'icono'       => 'fa-file-medical',
        'descripcion' => 'Atenciones y Atendidos, Produccion Diario/Mensual, Reporte 40A',
    ],
    'log' => [
        'nombre'      => 'Log de Auditoria',
        'archivo'     => 'log.php',
        'icono'       => 'fa-clipboard-list',
        'descripcion' => 'Historial de importaciones y procesamientos del sistema',
    ],
]);

/**
 * Modulos asignados a un usuario (según tabla USUARIO_MODULOS).
 * Usa cache estatica por peticion para no repetir la consulta.
 * Si la tabla aun no existe (instalacion pendiente), devuelve vacio.
 */
function getModulosUsuario($idUsuario) {
    static $cache = [];
    $idUsuario = (int)$idUsuario;
    if ($idUsuario <= 0) {
        return [];
    }
    if (isset($cache[$idUsuario])) {
        return $cache[$idUsuario];
    }
    $modulos = [];
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT modulo FROM USUARIO_MODULOS WHERE id_usuario = ?");
        $stmt->execute([$idUsuario]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $m) {
            if (array_key_exists($m, MODULOS_SISTEMA)) {
                $modulos[] = $m;
            }
        }
    } catch (Throwable $e) {
        error_log("getModulosUsuario: " . $e->getMessage());
    }
    $cache[$idUsuario] = $modulos;
    return $modulos;
}

/**
 * ¿Puede el usuario de la sesion actual acceder a un modulo?
 * - Admin: siempre true (acceso total por defecto).
 * - Otros roles: solo si el modulo fue asignado en usuarios.php.
 */
function puedeAccederModulo($modulo) {
    if (!array_key_exists($modulo, MODULOS_SISTEMA)) {
        return false;
    }
    if (esAdmin()) {
        return true;
    }
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    return in_array($modulo, getModulosUsuario($_SESSION['usuario_id']), true);
}

/**
 * Gate de acceso a una pagina de modulo.
 * Uso:  require_once 'includes/auth.php';  verificarAutenticacion();
 *       verificarModulo('control_calidad');
 * Si no tiene permiso redirige a home.php con acceso_denegado=1.
 */
function verificarModulo($modulo) {
    verificarAutenticacion();
    if (!puedeAccederModulo($modulo)) {
        header('Location: ' . getAppUrl() . '/home.php?acceso_denegado=1');
        exit;
    }
}

/**
 * Lista de claves de modulo permitidas para el usuario actual
 * (usada por el navbar y el home para filtrar items).
 */
function modulosDelUsuarioActual() {
    if (esAdmin()) {
        return array_keys(MODULOS_SISTEMA);
    }
    if (!isset($_SESSION['usuario_id'])) {
        return [];
    }
    return getModulosUsuario($_SESSION['usuario_id']);
}

function getAppUrl() {
    // Determinar URL base de la aplicacion
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }
    return $basePath;
}

function iniciarSesion($usuario, $password) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM USUARIOS WHERE usuario = ? AND estado = 1");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario_id'] = $user['id_usuario'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['rol'] = $user['rol'];
        $_SESSION['ultimo_acceso'] = time();
        
        // Actualizar ultimo acceso en BD
        $stmt = $pdo->prepare("UPDATE USUARIOS SET ultimo_acceso = NOW() WHERE id_usuario = ?");
        $stmt->execute([$user['id_usuario']]);
        
        return true;
    }
    return false;
}
