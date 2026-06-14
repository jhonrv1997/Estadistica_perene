<?php
/**
 * Sistema de Gestion de Datos HIS
 * Ejecutar procesamiento (consolidacion)
 * Solo se permite si los 4 archivos han sido importados
 */

// Aumentar tiempo limite de ejecucion para procesamiento masivo
@set_time_limit(0);
@ignore_user_abort(true);

require_once 'includes/auth.php';
verificarAutenticacion();

require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: import.php');
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    $_SESSION['mensaje'] = 'Token de seguridad invalido.';
    $_SESSION['tipo_mensaje'] = 'danger';
    header('Location: import.php');
    exit;
}

$accion = $_POST['accion'] ?? '';

// Accion: Resetear estado de importacion
if ($accion === 'resetear') {
    reiniciarEstadoImportacion();
    $_SESSION['mensaje'] = 'Estado de importacion reiniciado. Debe importar los 4 archivos nuevamente antes de procesar.';
    $_SESSION['tipo_mensaje'] = 'info';
    header('Location: import.php');
    exit;
}

// Accion: Procesar por periodo o completo
if ($accion === 'procesar' || $accion === 'procesar_completo') {
    
    // VALIDACION CRITICA: Los 4 archivos deben estar importados
    if (!verificarTodosImportados()) {
        $faltantes = obtenerArchivosFaltantes();
        $_SESSION['mensaje'] = 'No se puede ejecutar el procesamiento. Falta importar: ' . implode(', ', $faltantes);
        $_SESSION['tipo_mensaje'] = 'danger';
        header('Location: import.php');
        exit;
    }
    
    if ($accion === 'procesar_completo') {
        // Reconstruccion completa (sin periodo) - procesa todos los periodos en batch
        $resultado = ejecutarProcesamiento(null, null);
    } else {
        // Procesamiento por periodo
        $anio = $_POST['proc_anio'] ?? null;
        $mes = $_POST['proc_mes'] ?? null;
        
        // Si no se especifica periodo, procesar todo
        if (empty($anio)) $anio = null;
        if (empty($mes)) $mes = null;
        
        $resultado = ejecutarProcesamiento($anio, $mes);
    }
    
    if ($resultado['success']) {
        // Reiniciar estado de importacion despues de procesar exitosamente
        reiniciarEstadoImportacion();
        
        if ($resultado['registros'] > 0) {
            $_SESSION['mensaje'] = $resultado['mensaje'] . '. Estado de importacion reiniciado.';
            $_SESSION['tipo_mensaje'] = 'success';
        } else {
            $_SESSION['mensaje'] = $resultado['mensaje'] . '. Estado de importacion reiniciado.';
            $_SESSION['tipo_mensaje'] = 'warning';
        }
    } else {
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo_mensaje'] = 'warning';
    }
}

header('Location: import.php');
exit;
