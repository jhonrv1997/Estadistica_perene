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
 *     F5 "CODIGO RENIPRESS:" -> H5 = codigo del establecimiento
 *            (Codigo_Unico RENAES; vacio si el filtro es "-- Todos --")
 *     A6:B6 "PERIODO:"    -> C6 = solo el MES ("JUNIO"), D6 = el ANIO ("2026")
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

// ====== PROTECCION ANTI HTTP 500 (errores fatales de PHP) ======
ob_start();
register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    while (ob_get_level() > 0) { @ob_end_clean(); }
    if (!headers_sent()) http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    $msg   = $e['message'];
    $esMem = (stripos($msg, 'memory') !== false);
    $esTmp = (stripos($msg, 'execution time') !== false || stripos($msg, 'time limit') !== false);
    $causa = $esMem ? 'Se agotó la memoria (memory_limit) al calcular el reporte para el Excel.'
            : ($esTmp ? 'Se agotó el tiempo de ejecución (max_execution_time) al calcular el reporte.'
                      : 'Ocurrió un error fatal de PHP durante el export.');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:system-ui,sans-serif">'
       . '<h3 style="color:#c0392b">No se pudo exportar el reporte ADOLESCENTE</h3>'
       . '<p>' . htmlspecialchars($causa) . '</p>'
       . '<p class="small">Sugerencias: genere primero el reporte en <a href="reporte_adolescente.php">reporte_adolescente.php</a> '
       . '(el asistente lo construye por partes) y luego pulse <em>Exportar Excel</em> desde ahí, o acote los filtros '
       . '(un mes y/o un establecimiento).</p>'
       . '</body></html>';
});

// ==================== FILTROS (mismos parametros que reporte_adolescente.php) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fToken           = trim($_GET['token'] ?? '');   // token del asistente por trozos
$reporte          = null;

// ---- Camino 1: reporte YA generado por el asistente (buffer por token) ----
// Evita recalcular rangos amplios (que agotarian memoria/tiempo del hosting).
if ($fToken !== '') {
    $pdo  = getDBConnection();
    $rowT = adoCacheLeer($pdo, $fToken);
    if ($rowT
        && (int)$rowT['id_usuario'] === (int)($_SESSION['usuario_id'] ?? 0)
        && $rowT['estado'] === 'listo'
        && $rowT['payload'] !== null && $rowT['payload'] !== '') {
        $reporte = json_decode((string)$rowT['payload'], true) ?: null;
        if (!is_array($reporte) || !empty($reporte['error'])) {
            $reporte = null;
        } else {
            // los filtros reales del reporte cacheado mandan sobre la URL
            $fRow = json_decode((string)$rowT['filtros'], true);
            if (is_array($fRow)) {
                $fAnio            = (string)($fRow['anio'] ?? $fAnio);
                $fMes             = (string)($fRow['mes'] ?? $fMes);
                $fEstablecimiento = (string)($fRow['establecimiento'] ?? $fEstablecimiento);
            }
        }
    }
}

if ($fAnio === '' && $reporte === null) {
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
if ($reporte === null) {
    if (!adoAmbitoDirecto($filtros)) {
        // Rango amplio SIN token: la consulta unica agotaria los limites del
        // hosting. Generar antes el reporte en la pagina (asistente por
        // trozos) y exportar desde ahi, o acotar los filtros.
        http_response_code(200);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Rango demasiado amplio</title></head><body style="font-family:system-ui,sans-serif">';
        echo '<h3 style="color:#e67e22">Rango demasiado amplio para exportar de una sola vez</h3>';
        echo '<p>El periodo seleccionado (' . htmlspecialchars($fAnio !== '' ? $fAnio : 'todos los años')
           . ($fMes !== '' ? ' / mes ' . htmlspecialchars($fMes) : '')
           . ($fEstablecimiento !== '' ? ' / EE.SS ' . htmlspecialchars($fEstablecimiento) : ' / todos los EE.SS')
           . ') requiere el asistente por partes.</p>';
        echo '<ol><li>Abra <a href="reporte_adolescente.php?' . http_build_query($filtros) . '">reporte_adolescente.php</a> y pulse <em>Generar Reporte</em>: el sistema lo construye por pasos.</li>';
        echo '<li>Al terminar, pulse <em>Exportar Excel</em> desde esa misma p&aacute;gina (usar&aacute; el resultado ya calculado).</li></ol>';
        echo '<p>O bien acote los filtros: seleccione un <strong>mes</strong> y/o un <strong>establecimiento</strong>.</p>';
        echo '</body></html>';
        exit;
    }
    $reporte = adoEjecutarReporte(getDBConnection(), $filtros);
}
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
// Cabecera: C6 = solo el MES, D6 = el ANIO, H5 = CODIGO del establecimiento
// (Codigo_Unico RENAES), H6 = MICRO RED, H7 = nombre del EE.SS (o "TODOS").
// La RED (C5:D5) ya viene fija en la plantilla.
//
// El <select> de reporte_adolescente.php envia el Codigo_Unico (RENAES);
// por COMPATIBILIDAD, si llegara el NOMBRE del EE.SS se recupera desde
// ZSPERENE tanto el nombre (H7) como su codigo (H5).
$nombreEst = 'TODOS';
$codigoEst = '';
if ($fEstablecimiento !== '') {
    $est = adoGetEstablecimientosZS(getDBConnection()); // [codigo => nombre]
    if (isset($est[$fEstablecimiento])) {
        $codigoEst = $fEstablecimiento;                  // llego el codigo
        $nombreEst = $est[$fEstablecimiento];
    } else {
        $nombreEst = $fEstablecimiento;                  // llego el nombre
        $codByName = array_search($fEstablecimiento, $est, true);
        if ($codByName !== false) {
            $codigoEst = (string)$codByName;             // su codigo RENAES
        }
    }
}
$cells = adoCeldasExport($reporte, $fAnio, $fMes, $fEstablecimiento !== '' ? $nombreEst : '', $codigoEst);

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cells);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Reporte_Actividades_Adolescentes_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
