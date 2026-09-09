<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional ZOONOSIS a Excel (.xlsx) - PLANTILLA OFICIAL
 *
 * Genera el archivo Excel usando como base la plantilla oficial
 * "Reporte_Actividades_Zoonosis.xlsx" (copiar a uploads/Reporte_Actividades_Zoonosis.xlsx)
 * y llenando las celdas con los datos calculados por includes/zoonosis_data.php
 * (adaptacion web de los 13 procedimientos usp_TRAMA_BASE_ZOONOSIS_2022_* del
 * archivo "03 Creacion de Procedimientos").
 *
 * MAPA DE CELDAS (hoja "Plantilla", igual que el flujo ODBC original):
 *   Encabezado:
 *     D5 = IPRESS  (celda combinada D5:M5 - escribir en el ancla D5)
 *     R4 = MES     (celda combinada R4:U4 - escribir en el ancla R4)
 *     R5 = AÑO     (celda combinada R5:U5 - escribir en el ancla R5)
 *   PONZ1  MORBILIDAD PONZOÑOSOS:   filas 12-56,  cols D,E,G,J,L,N,P (Total+5 etapas+GESTANTES)
 *   PONZ2  CON TRATAMIENTO (U310):  filas 59-62,  cols B,D,E,G,J,L,N
 *   RU1    PRE-EXPOSICION:          filas 67-69,  cols E,G,J,L,N,P
 *   RU2    ADMIN PRE-EXPOSICION:    filas 72-80,  cols E,G,J,L,N,P
 *   RU3    POST-EXPOSICION:         filas 84-140, cols G,J,L,N,P,R (19 bloques x T/F/M)
 *   RU4    VACUNACION POST:         filas 143-172, cols D,E,G,J,L,N (6 bloques x 5 situaciones)
 *   RU5    SUSPENSION:              filas 176-177, cols D,E,G,J,L,N
 *   RU6    REFERENCIAS:             filas 180-194, cols D,E,G,J,L,N (3 bloques x 5 situaciones)
 *   FRVH   FRASCOS VARH:            filas 197-199, cols B,D,E,G,J,L
 *   FRRIG  FRASCOS RIG:             filas 202-207, cols B,D,E,G,J,L
 *   RU7    DX RABIA HUMANA:         fila  211,    cols B,D,E,G,J,L
 *   RU8    OBSERVACION ANIMAL:      filas 214-234, col D (7 bloques x 3 visitas)
 *   RU9    VIGILANCIA RESERVORIO:   filas 237-256, col E (5 bloques x 4 muestras)
 *   RU10   CONTROL DE FOCO:         filas 259-267, col E (3 bloques x 3 situaciones)
 *   RU11   VACUNACION CANINA:       filas 270-272, col F
 *
 * El mapa posicional (colsX + campo 'fila' de cada fila) vive en zooSecciones()
 * (includes/zoonosis_data.php) y la construccion de celdas en zooCeldasExport()
 * (includes/zoonosis_render.php).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/zoonosis_data.php';
require_once 'includes/zoonosis_render.php';
require_once 'includes/ExcelTemplateFiller.php';

// ==================== FILTROS (mismos parametros que reporte_zoonosis.php) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');

if ($fAnio === '') {
    $pdo = getDBConnection();
    $anios = zooGetAniosDisponibles($pdo);
    $fAnio = $anios[0] ?? date('Y');
}

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
];

// ==================== EJECUTAR REPORTE ====================
$reporte = zooEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">Error al generar el reporte</h3>';
    echo '<p>' . htmlspecialchars($reporte['error']) . '</p>';
    echo '<p><a href="reporte_zoonosis.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== PLANTILLA ====================
$templatePath = __DIR__ . '/uploads/Reporte_Actividades_Zoonosis.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Plantilla no encontrada</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#e67e22">Plantilla oficial no encontrada</h3>';
    echo '<p>Para exportar el reporte con el dise&ntilde;o oficial, copie el archivo';
    echo ' <code>Reporte_Actividades_Zoonosis.xlsx</code> a la carpeta <code>uploads/</code> del sistema:</p>';
    echo '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Reporte_Actividades_Zoonosis.xlsx</pre>';
    echo '<p>Luego vuelva a pulsar <em>Exportar Excel</em>.</p>';
    echo '<p><a href="reporte_zoonosis.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== MAPA DE CELDAS ====================
$mesTxt  = $fMes !== '' ? strtoupper(getNombreMes((int)$fMes)) : '';
$nombreEst = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $est = zooGetEstablecimientosZS(getDBConnection());
    $nombreEst = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

$cells = zooCeldasExport($reporte, $mesTxt, $fAnio, $fEstablecimiento !== '' ? $nombreEst : '');

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cells);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Reporte_Actividades_Zoonosis_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
