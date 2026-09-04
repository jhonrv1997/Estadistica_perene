<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional MATERNO a Excel (.xlsx) - PLANTILLA OFICIAL
 *
 * Genera el archivo Excel usando como base la plantilla oficial
 * "Reporte_Actividades_Materno.xlsx" (copiar a uploads/Reporte_Actividades_Materno.xlsx)
 * y llenando las celdas con los datos calculados por includes/materno_data.php
 * (adaptacion web de los procedimientos RPT_01..RPT_10 del archivo
 * "03 Creacion de Procedimientos").
 *
 * MAPA DE CELDAS (hoja "Plantilla", igual que el flujo ODBC original):
 *   Encabezado:
 *     B6 = PERIODO (texto, celda combinada B6:D6 - escribir en el ancla B6)
 *     B8 = EE.SS (texto, celda combinada B8:E8 - escribir en el ancla B8)
 *   Seccion I   ATENCION PRENATAL REENFOCADA:    filas 16-20,  cols B..AA (26)
 *   Seccion II  BIENESTAR / PSICOPROFILAXIS:     filas 25-29,  cols B..G (6)
 *   Seccion III ANEMIA / PLAN DE PARTO:          filas 25-29,  cols J..T (11)
 *   Seccion IV  COMPLICACIONES:                  filas 33-47,  cols F..J (TOTAL + 4 grupos)
 *   Seccion V   MORBILIDAD RN:                   filas 33-39,  col O (N unica)
 *   Seccion VI  MICRONUTRIENTES:                 filas 53-57,  cols B..K (10)
 *   Seccion VII PUERPERIO:                       filas 53-57,  cols N..P (3)
 *   Seccion VIII VISITA DOMICILIARIA:            filas 50-51,  cols S..U (2 filas x 3 grupos)
 *   Seccion IX-1 TRANSMISION VERTICAL GESTANTES: filas 64-68,  cols B..V (21)
 *   Seccion IX-2 PUERPERAS INMEDIATAS:           filas 75-79,  cols B..G (6)
 *   Seccion IX-3 PRUEBA VIH T.P. / ABORTO:       SIN ZONA en la plantilla (no se exporta;
 *               en el flujo ODBC original esas categorias quedaban siempre en 0)
 *   Seccion X   CONSEJERIA LACTANCIA MATERNA:    filas 85-89,  cols B..D (3)
 *
 * Nota: la columna B de la seccion I ("Total" de Gestante Atendida) es en la
 * plantilla la formula =SUM(C:E); se escribe el valor ya calculado por el motor
 * para no depender del recalculo de formulas al abrir el archivo.
 * El mapa posicional (xmap) vive en maternoSecciones() (includes/materno_data.php)
 * y la construccion de celdas en maternoCeldasExport() (includes/materno_render.php).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/materno_data.php';
require_once 'includes/materno_render.php';
require_once 'includes/ExcelTemplateFiller.php';

// ==================== FILTROS (mismos parametros que reporte_materno.php) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');

if ($fAnio === '') {
    $pdo = getDBConnection();
    $anios = maternoGetAniosDisponibles($pdo);
    $fAnio = $anios[0] ?? date('Y');
}

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
];

// ==================== EJECUTAR REPORTE ====================
$reporte = maternoEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">Error al generar el reporte</h3>';
    echo '<p>' . htmlspecialchars($reporte['error']) . '</p>';
    echo '<p><a href="reporte_materno.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== PLANTILLA ====================
$templatePath = __DIR__ . '/uploads/Reporte_Actividades_Materno.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Plantilla no encontrada</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#e67e22">Plantilla oficial no encontrada</h3>';
    echo '<p>Para exportar el reporte con el dise&ntilde;o oficial, copie el archivo';
    echo ' <code>Reporte_Actividades_Materno.xlsx</code> a la carpeta <code>uploads/</code> del sistema:</p>';
    echo '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Reporte_Actividades_Materno.xlsx</pre>';
    echo '<p>Luego vuelva a pulsar <em>Exportar Excel</em>.</p>';
    echo '<p><a href="reporte_materno.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== MAPA DE CELDAS ====================
$periodoTxt = [];
if ($fAnio !== '') $periodoTxt[] = $fAnio;
if ($fMes !== '')  $periodoTxt[] = strtoupper(getNombreMes((int)$fMes));

$nombreEst = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $est = maternoGetEstablecimientosZS(getDBConnection());
    $nombreEst = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

$cells = maternoCeldasExport($reporte, implode(' - ', $periodoTxt), $nombreEst);

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cells);

$mesTxt = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Reporte_Actividades_Materno_{$fAnio}{$mesTxt}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
