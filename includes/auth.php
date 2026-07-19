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
