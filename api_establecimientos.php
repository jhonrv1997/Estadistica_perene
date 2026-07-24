<?php
/**
 * API: Obtener establecimientos por MicroRed (Zona Sanitaria)
 * Usado por consulta_atenciones.php para el select cascada
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();
header('Content-Type: application/json');

$microred = trim($_GET['microred'] ?? '');

if ($microred === '') {
    // Si no hay microred, devolver todos
    $stmt = $pdo->query("SELECT Codigo_Unico, Nombre_Establecimiento FROM ZSPERENE ORDER BY Nombre_Establecimiento");
} else {
    $stmt = $pdo->prepare("SELECT Codigo_Unico, Nombre_Establecimiento FROM ZSPERENE WHERE MicroRed = :microred ORDER BY Nombre_Establecimiento");
    $stmt->execute([':microred' => $microred]);
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
exit;
