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
