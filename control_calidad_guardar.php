<?php
/**
 * Sistema de Gestion de Datos HIS
 * Guardado manual de observaciones de Control de Calidad (solo admin)
 */
require_once 'includes/auth.php';
verificarAutenticacion();
verificarAdmin();
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: control_calidad.php');
    exit;
}

$pdo = getDBConnection();

// Validar campos requeridos
$anio = trim($_POST['anio'] ?? '');
$mes = trim($_POST['mes'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if ($anio === '' || $mes === '' || $descripcion === '') {
    header('Location: control_calidad.php?error=Faltan+campos+obligatorios');
    exit;
}

try {
    $sql = "INSERT INTO CONTROL_CALIDAD_OBSERVACIONES
        (anio, mes, fecha_observacion, nombre_establecimiento, tipo_observacion, severidad,
         campo_afectado, descripcion, id_cita, codigo_item, numero_documento_paciente,
         numero_documento_personal, estado, usuario_registro)
        VALUES
        (:anio, :mes, :fecha_obs, :estab, :tipo, :sev, :campo, :desc, :idcita, :citem,
         :dpac, :dper, 'PENDIENTE', :usuario)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':anio' => $anio,
        ':mes' => $mes,
        ':fecha_obs' => !empty($_POST['fecha_observacion']) ? $_POST['fecha_observacion'] : null,
        ':estab' => !empty($_POST['nombre_establecimiento']) ? $_POST['nombre_establecimiento'] : null,
        ':tipo' => !empty($_POST['tipo_observacion']) ? $_POST['tipo_observacion'] : 'Otro',
        ':sev' => !empty($_POST['severidad']) ? $_POST['severidad'] : 'MEDIA',
        ':campo' => !empty($_POST['campo_afectado']) ? $_POST['campo_afectado'] : null,
        ':desc' => $descripcion,
        ':idcita' => !empty($_POST['id_cita']) ? $_POST['id_cita'] : null,
        ':citem' => !empty($_POST['codigo_item']) ? $_POST['codigo_item'] : null,
        ':dpac' => !empty($_POST['numero_documento_paciente']) ? $_POST['numero_documento_paciente'] : null,
        ':dper' => !empty($_POST['numero_documento_personal']) ? $_POST['numero_documento_personal'] : null,
        ':usuario' => $_SESSION['usuario'] ?? 'admin',
    ]);
    header('Location: control_calidad.php?ok=1');
    exit;
} catch (Exception $e) {
    header('Location: control_calidad.php?error=' . urlencode('Error: ' . $e->getMessage()));
    exit;
}
