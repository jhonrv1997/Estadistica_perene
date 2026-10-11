<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional ITS/VIH a Excel (.xlsx) - PLANTILLA OFICIAL
 *
 * Genera el archivo Excel usando como base la plantilla oficial
 * "Reporte_Actividades_ITS.xlsx" (copiar a uploads/Reporte_Actividades_ITS.xlsx)
 * y llenando las celdas con los datos calculados por includes/its_data.php
 * (adaptacion web de los 15 procedimientos usp_TRAMA_BASE_ITS_VIH_2022_*
 * del archivo "03 Creacion de Procedimientos").
 *
 * MAPA DE CELDAS (hoja "Plantilla", igual que el flujo ODBC original):
 *   Encabezado:
 *     K6 = AÑO (etiqueta K5)          K8 = MES (K8:L8 combinada, etiqueta K7)
 *     C8 = ESTABLECIMIENTO (C8:G8)    I6 = IPRESS (etiqueta I5)
 *     A6 = JUNIN, C6 = PERENE, A8 = CHANCHAMAYO (ya vienen en la plantilla)
 *   1.- POBLACION GENERAL: ITS (filas 13-25):
 *     F: B/C/D + E/F/G + H/I/J + K/L/M + N/O/P (etapa x DIAG/TRAT/CONT)
 *     M: Q/R/S + T/U/V + W/X/Y + Z/AA/AB + AC/AD/AE
 *     Totales: AF=F diag, AG=M diag, AH=F trat, AI=M trat, AJ=F cont, AK=M cont
 *   2.- HEPATITIS B (29-36) / 2A. HEPATITIS C (40-45) / 3.- TAMIZAJE VIH
 *   (49-57) / 4.- EXPOSICION (61-69) / 5.- VIOLENCIA (73-77) / 6.- PVV
 *   (81-92): F B-F, M G-K, L=F, M=M, N=Total
 *   7.- GESTANTES por grupo etareo (95-105): B-E etapas 12-17/18-29/30-59/60+,
 *     F=Total
 *   7.- GESTANTES por trimestre (108-118): B=1ER, C=2DO, D=3ER, E=Total
 *   7A.- RECIEN NACIDOS (121-130): B=Total
 *   8.- POBLACION CLAVE (134-145): HSH B-D, TRANS E-G, TS-FEM H-J, TS-TRANS
 *     K-M, TS-H N-P (etapas 18-29/30-59/60+); totales Q-U
 *   9.- PC ITS (149-159): DIAGNOSTICOS grupos B-P; TRATADOS Q-U
 *   9.- PC CONTACTOS (162-172): B=CONTACTOS, C=CONTACTOS TRATADOS
 *   10.- BRIGADAS MCC (177-184): OFERTA COMUNITARIA B-U (HSH B-E, TRANS F-I,
 *     TS-FEM J-M, TS-H N-Q, TS-TRANS R-U; etapas 12-17..60+); OFERTA MOVIL V-AO
 *   11.- BMA (188-195) / 12.- TELEMEDICINA (199-203): F B-F, M G-K, L=F, M=M
 *
 * El mapa posicional vive en itsSecciones() (includes/its_data.php) y la
 * construccion de celdas en itsCeldasExport() (includes/its_render.php).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/its_data.php';
require_once 'includes/its_render.php';
require_once 'includes/ExcelTemplateFiller.php';

// ==================== FILTROS (mismos parametros que reporte_its.php) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');

if ($fAnio === '') {
    $pdo = getDBConnection();
    $anios = itsGetAniosDisponibles($pdo);
    $fAnio = $anios[0] ?? date('Y');
}

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
];

// ==================== EJECUTAR REPORTE ====================
$reporte = itsEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">Error al generar el reporte</h3>';
    echo '<p>' . htmlspecialchars($reporte['error']) . '</p>';
    echo '<p><a href="reporte_its.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== PLANTILLA ====================
$templatePath = __DIR__ . '/uploads/Reporte_Actividades_ITS.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Plantilla no encontrada</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#e67e22">Plantilla oficial no encontrada</h3>';
    echo '<p>Para exportar el reporte con el dise&ntilde;o oficial, copie el archivo';
    echo ' <code>Reporte_Actividades_ITS.xlsx</code> a la carpeta <code>uploads/</code> del sistema:</p>';
    echo '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Reporte_Actividades_ITS.xlsx</pre>';
    echo '<p>Luego vuelva a pulsar <em>Exportar Excel</em>.</p>';
    echo '<p><a href="reporte_its.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== MAPA DE CELDAS ====================
$nombreEst = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $est = itsGetEstablecimientosZS(getDBConnection());
    $nombreEst = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

// Cabecera: K6 = AÑO, K8 = MES, C8 = ESTABLECIMIENTO, I6 = IPRESS
$cells = itsCeldasExport($reporte, $fAnio, $fMes, $fEstablecimiento !== '' ? $nombreEst : '', $fEstablecimiento !== '' ? $nombreEst : '');

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cells);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Reporte_Actividades_ITS_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
