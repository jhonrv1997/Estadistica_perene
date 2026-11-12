<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional ADOLESCENTE a Excel (.xlsx) -
 *         PLANTILLA OFICIAL
 *
 * Genera el archivo Excel usando como base la plantilla oficial
 * "Reporte_Actividades_Adolescentes.xlsx" (copiar a
 * uploads/Reporte_Actividades_Adolescentes.xlsx) y llenando las celdas con
 * los datos calculados por includes/adolescente_data.php (adaptacion web de
 * los 25 procedimientos usp_TRAMA_BASE_ADOLESCENTE_2020_RPT_* del archivo
 * "03 Creacion de Procedimientos").
 *
 * MAPA DE CELDAS (hoja "Plantilla", igual que el flujo ODBC original):
 *   Encabezado:
 *     A5:B5 "DIRESA/RED/M.Red/EE.SS :" y C5:D5 "RED DE SALUD CHANCHAMAYO"
 *            ya vienen fijos en la plantilla (no se tocan)
 *     F5 "CODIGO RENIPRESS:" H5 "120308ZS" (fijo de la plantilla)
 *     A6:B6 "PERIODO:"    -> C6 = periodo en texto ("JUNIO 2026" / "2026")
 *     F6 "MICRO RED:"     -> H6:I6 = "PERENE"
 *     F7 "EE.SS:"         -> H7:I7 = nombre del establecimiento o "TODOS"
 *   1.-  MORBILIDAD DEL ADOLESCENTE (filas 11-14): B=Total, C=12a-14a,
 *        D=15a-17a
 *   2.-  CONTROL Y SEGUIMIENTO (21-26): C=Total; EE.SS: D/E (P. basico),
 *        F/G (P. completo), H/I (P. especializado); II.EE: J/K (P. basico),
 *        L/M (P. completo). El combo P3I2 no existe (delete del SP 02).
 *   3.-  VISITA DOMICILIARIA (31-36): C=Total (la plantilla tiene =D+E, se
 *        sobrescribe con el valor), D/E
 *   4.-  EVALUACION FISICA NUTRICIONAL (41-58): E=Total, F/G (B:D combinado
 *        para la etiqueta)
 *   5.-  AGUDEZA VISUAL (63-66): E=Total, F/G
 *   6.-  AGUDEZA AUDITIVA (71-73): B/C/D
 *   7.-  ATENCION ODONTOLOGICA (78-82): B/C/D
 *   8.-  FISICO POSTURAL (87-92): B/C/D
 *   9.-  INMUNIZACIONES (97-106): B/C/D
 *   10.- DESARROLLO SEXUAL / PLANIFICACION FAMILIAR (111-124): C/D/E
 *   11.- EVALUACION CLINICA (128-144): B/C/D
 *   12.- CONSEJERIAS (149-161): B/C/D
 *   13.- TOMA DE PRUEBA RAPIDA (166-170): B/C/D
 *   14.- SALUD MENTAL (175-183): C/D/E
 *   15.- TAMIZAJES POSITIVOS (188-195): B/C/D
 *   16.- ACTIVIDADES PREVENTIVAS PROMOCIONALES:
 *          Sesiones (200-201): B/C/D; Actividades APP (204-212): B=Casos;
 *          Actividades comunitarias (215-226): B=Casos
 *   17.- SESIONES EDUCATIVAS 12-14 (231-239): B
 *   18.- SALUD FISICA NUTRICIONAL 15-17 (244-255): B
 *   19.- SSR 12-14 (260-264): B      20.- SSR 15-17 (269-275): B
 *   21.- PSICOSOCIAL 12-14 (280-289): B
 *   22.- PSICOSOCIAL 15-17 (294-305): B
 *   23.- COVID-19 (310-319): B/C/D
 *   24.- SEGUIMIENTO COVID (324-329): B/C/D
 *   25.- MEDICINA ALTERNATIVA (334-337): B/C/D
 *
 * El mapa posicional vive en adoSecciones() (includes/adolescente_data.php)
 * y la construccion de celdas en adoCeldasExport()
 * (includes/adolescente_render.php).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/adolescente_data.php';
require_once 'includes/adolescente_render.php';
require_once 'includes/ExcelTemplateFiller.php';

// ==================== FILTROS (mismos parametros que reporte_adolescente.php) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');

if ($fAnio === '') {
    $pdo = getDBConnection();
    $anios = adoGetAniosDisponibles($pdo);
    $fAnio = $anios[0] ?? date('Y');
}

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
];

// ==================== EJECUTAR REPORTE ====================
$reporte = adoEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">Error al generar el reporte</h3>';
    echo '<p>' . htmlspecialchars($reporte['error']) . '</p>';
    echo '<p><a href="reporte_adolescente.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== PLANTILLA ====================
$templatePath = __DIR__ . '/uploads/Reporte_Actividades_Adolescentes.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Plantilla no encontrada</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#e67e22">Plantilla oficial no encontrada</h3>';
    echo '<p>Para exportar el reporte con el dise&ntilde;o oficial, copie el archivo';
    echo ' <code>Reporte_Actividades_Adolescentes.xlsx</code> a la carpeta <code>uploads/</code> del sistema:</p>';
    echo '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Reporte_Actividades_Adolescentes.xlsx</pre>';
    echo '<p>Luego vuelva a pulsar <em>Exportar Excel</em>.</p>';
    echo '<p><a href="reporte_adolescente.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== MAPA DE CELDAS ====================
// Cabecera: C6 = PERIODO (texto), H6 = MICRO RED, H7 = nombre del EE.SS
// (o "TODOS"). La RED (C5) y el RENIPRESS (H5) ya vienen fijos en la
// plantilla.
//
// El <select> de reporte_adolescente.php envia el Codigo_Unico (RENAES);
// por COMPATIBILIDAD, si llegara el NOMBRE del EE.SS se recupera el nombre
// desde ZSPERENE para escribirlo en H7.
$nombreEst = 'TODOS';
if ($fEstablecimiento !== '') {
    $est = adoGetEstablecimientosZS(getDBConnection()); // [codigo => nombre]
    $nombreEst = isset($est[$fEstablecimiento])
        ? $est[$fEstablecimiento]                        // llego el codigo
        : $fEstablecimiento;                             // llego el nombre
}
$cells = adoCeldasExport($reporte, $fAnio, $fMes, $fEstablecimiento !== '' ? $nombreEst : '');

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cells);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Reporte_Actividades_Adolescentes_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
