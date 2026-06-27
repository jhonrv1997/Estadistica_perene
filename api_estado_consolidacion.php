<?php
/**
 * API: Estado de Consolidacion por Periodo
 * Retorna JSON con el estado actual de cada periodo.
 * Usado por import.php para actualizar el panel de botones rapidos
 * sin necesidad de recargar toda la pagina.
 */

require_once 'includes/auth.php';
verificarAutenticacion();

require_once 'includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    $pdo = getDBConnection();

    $todosImportados = verificarTodosImportados();

    // Consulta directa (no usar la funcion que silencia errores)
    $sql = "
        SELECT
            t.Anio,
            t.Mes,
            t.regs_trama,
            COALESCE(c.regs_consolidado, 0) as regs_consolidado,
            CASE WHEN c.regs_consolidado > 0 THEN 'consolidado' ELSE 'pendiente' END as estado
        FROM (
            SELECT TRIM(Anio) as Anio, TRIM(Mes) as Mes,
                   COUNT(*) as regs_trama
            FROM NOMINAL_TRAMA_NUEVO
            WHERE Anio IS NOT NULL AND Anio != '' AND Mes IS NOT NULL AND Mes != ''
            GROUP BY TRIM(Anio), TRIM(Mes)
        ) t
        LEFT JOIN (
            SELECT TRIM(Anio) as Anio, TRIM(Mes) as Mes,
                   COUNT(*) as regs_consolidado
            FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
            WHERE Anio IS NOT NULL AND Anio != '' AND Mes IS NOT NULL AND Mes != ''
            GROUP BY TRIM(Anio), TRIM(Mes)
        ) c ON TRIM(t.Anio) = TRIM(c.Anio) AND CAST(TRIM(t.Mes) AS UNSIGNED) = CAST(TRIM(c.Mes) AS UNSIGNED)
        ORDER BY t.Anio DESC, CAST(TRIM(t.Mes) AS UNSIGNED) DESC
    ";
    $stmt = $pdo->query($sql);
    $estadoConsolidacion = $stmt->fetchAll();

    $periodosPendientes = array_filter($estadoConsolidacion, function($p) {
        return $p['estado'] === 'pendiente';
    });
    $periodosConsolidados = array_filter($estadoConsolidacion, function($p) {
        return $p['estado'] === 'consolidado';
    });

    // Generar HTML para los botones rapidos
    $botonesHtml = '';
    foreach ($periodosPendientes as $pp) {
        $mes = trim($pp['Mes']);
        $anio = trim($pp['Anio']);
        $nombreMes = getNombreMes($mes);
        $label = $nombreMes . ' ' . $anio;
        $regs = number_format($pp['regs_trama']);

        $botonesHtml .= '<form method="POST" action="process.php" class="mb-1 process-form" data-periodo="' . htmlspecialchars($label) . '">'
            . '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">'
            . '<input type="hidden" name="accion" value="procesar">'
            . '<input type="hidden" name="proc_anio" value="' . htmlspecialchars($anio) . '">'
            . '<input type="hidden" name="proc_mes" value="' . htmlspecialchars($mes) . '">'
            . '<button type="submit" class="btn btn-warning btn-sm w-100 text-start process-btn">'
            . '<i class="fas fa-play me-1"></i> Procesar: ' . htmlspecialchars($label) . ' '
            . '<span class="badge bg-dark ms-1">' . $regs . ' regs.</span>'
            . '</button></form>';
    }

    // Generar HTML para periodos consolidados
    $consolidadosHtml = '';
    foreach ($periodosConsolidados as $pc) {
        $mes = trim($pc['Mes']);
        $anio = trim($pc['Anio']);
        $nombreMes = getNombreMes($mes);
        $regs = number_format($pc['regs_consolidado']);

        $consolidadosHtml .= '<div class="alert alert-success py-1 px-2 mb-1 small d-flex justify-content-between align-items-center">'
            . '<span><i class="fas fa-check-circle me-1"></i><strong>' . htmlspecialchars($nombreMes . ' ' . $anio) . '</strong></span>'
            . '<span class="badge bg-success">' . $regs . ' regs.</span></div>';
    }

    echo json_encode([
        'success' => true,
        'todosImportados' => $todosImportados,
        'hayPeriodosPendientes' => count($periodosPendientes) > 0,
        'totalPendientes' => count($periodosPendientes),
        'totalConsolidados' => count($periodosConsolidados),
        'botonesHtml' => $botonesHtml,
        'consolidadosHtml' => $consolidadosHtml,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'todosImportados' => false,
        'hayPeriodosPendientes' => false,
        'totalPendientes' => 0,
        'totalConsolidados' => 0,
        'botonesHtml' => '',
        'consolidadosHtml' => '',
    ], JSON_UNESCAPED_UNICODE);
}