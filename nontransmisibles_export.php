<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional NO TRANSMISIBLES a Excel (.xlsx) - PLANTILLA OFICIAL
 *
 * Genera el archivo Excel usando como base la plantilla oficial
 * "Reporte_Actividades_NoTransmisibles.xlsx" (copiar a
 * uploads/Reporte_Actividades_NoTransmisibles.xlsx) y llenando las celdas con
 * los datos calculados por includes/nontransmisibles_data.php (adaptacion web
 * de los 24 procedimientos usp_TRAMA_BASE_NT_2025_* del archivo
 * "03 Creacion de Procedimientos").
 *
 * MAPA DE CELDAS (hoja "Plantilla", igual que el flujo ODBC original):
 *   Encabezado:
 *     B5 = PERIODO (celda libre junto a la etiqueta A5)
 *     B7 = CODIGO RENAES (B8 contiene la formula =+B7*-1 de la plantilla)
 *     H7 = IPRESS (area combinada H7:L7; H5 trae fija la RED "CHANCHAMAYO")
 *   Grupo 01 VALORACION (gedad 05-11..60+, cols C=TOTAL, D-O):
 *     VR01 FACTORES DE RIESGO:      filas 12-31
 *     VR02 EVALUACION PAB:          filas 36-37
 *     VR03 VALORACION CLINICA:      filas 42-43
 *     VR04 VALORACION CLINICA:      filas 47-50
 *     VR05 INTERVENCION:            fila  55
 *   Grupo 02 HTA (gedad 05-11..60+, cols C=TOTAL, D-O):
 *     HTA1 CASOS:                   filas 62-69
 *     HTA2 EMERGENCIA:              filas 75-77
 *     HTA3 DISLIPIDEMIAS:           fila  82
 *     HTA4 SIN DANO ORGANO:         filas 87-92
 *     HTA5 TRATAMIENTO ESPECIAL.:   filas 98-103 + 105-108
 *     HTA6 RIESGO CV:               filas 113-117
 *     HTA7 SESIONES:                filas 122-127 (C=No, D=PARTICIPANTES)
 *   Grupo 03 DM (gedad MENORES 1a..60+, 8 grupos):
 *     DM01 CASOS:                   filas 134-146 (C=TOTAL, D-S)
 *     DM02 GLUCEMIA:                filas 152-158 (C=TOTAL, D-S)
 *     DM03 CONTROL (I-2/I-3/I-4):   filas 163-214 (E=TOTAL, F-U)
 *     DM04 TRATAMIENTO (II/III):    filas 219-270 (E=TOTAL, F-U)
 *     DM05 ATENCION:                filas 275-282 (C=TOTAL, D-S)
 *     DM06 VALORACION:              filas 287-374 (E=TOTAL, F-U)
 *     DM07 NEFROPATIA:              filas 379-389 (C=TOTAL, D-S)
 *   Grupo 04 TM TELESALUD (gedad 05-11..60+, cols C=TOTAL, D-O):
 *     TM01 TELEORIENTACION:         filas 396-398
 *     TM02 TELEMONITOREO:           fila  403
 *     TM03 TELEMONITOREO HTA:       filas 408-409
 *     TM04 TELEMONITOREO DM:        filas 414-415
 *     TM05 TELECONSULTAS:           fila  419
 *
 * El mapa posicional (colsX + campo 'fila' de cada fila) vive en ntSecciones()
 * (includes/nontransmisibles_data.php) y la construccion de celdas en
 * ntCeldasExport() (includes/nontransmisibles_render.php).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/nontransmisibles_data.php';
require_once 'includes/nontransmisibles_render.php';
require_once 'includes/ExcelTemplateFiller.php';

// ==================== FILTROS (mismos parametros que reporte_no_transmisibles.php) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');

if ($fAnio === '') {
    $pdo = getDBConnection();
    $anios = ntGetAniosDisponibles($pdo);
    $fAnio = $anios[0] ?? date('Y');
}

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
];

// ==================== EJECUTAR REPORTE ====================
$reporte = ntEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">Error al generar el reporte</h3>';
    echo '<p>' . htmlspecialchars($reporte['error']) . '</p>';
    echo '<p><a href="reporte_no_transmisibles.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== PLANTILLA ====================
$templatePath = __DIR__ . '/uploads/Reporte_Actividades_NoTransmisibles.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Plantilla no encontrada</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#e67e22">Plantilla oficial no encontrada</h3>';
    echo '<p>Para exportar el reporte con el dise&ntilde;o oficial, copie el archivo';
    echo ' <code>Reporte_Actividades_NoTransmisibles.xlsx</code> a la carpeta <code>uploads/</code> del sistema:</p>';
    echo '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Reporte_Actividades_NoTransmisibles.xlsx</pre>';
    echo '<p>Luego vuelva a pulsar <em>Exportar Excel</em>.</p>';
    echo '<p><a href="reporte_no_transmisibles.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== MAPA DE CELDAS ====================
$periodoTxt = [];
if ($fAnio !== '') $periodoTxt[] = $fAnio;
if ($fMes !== '')  $periodoTxt[] = strtoupper(getNombreMes((int)$fMes));
$periodoTxt = $periodoTxt ? implode(' - ', $periodoTxt) : '';

$nombreEst = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $est = ntGetEstablecimientosZS(getDBConnection());
    $nombreEst = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

$cells = ntCeldasExport($reporte, $periodoTxt, $fEstablecimiento, $fEstablecimiento !== '' ? $nombreEst : '');

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cells);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Reporte_Actividades_NoTransmisibles_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
