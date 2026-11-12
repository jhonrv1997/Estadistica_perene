<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional ADULTO a Excel (.xlsx) -
 *         PLANTILLA OFICIAL
 *
 * Genera el archivo Excel usando como base la plantilla oficial
 * "Reporte_Actividades_Adulto.xlsx" (copiar a
 * uploads/Reporte_Actividades_Adulto.xlsx) y llenando las celdas con
 * los datos calculados por includes/adulto_data.php (adaptacion web de
 * los 21 procedimientos usp_TRAMA_BASE_ADULTO_RPT_* del archivo
 * "03 Creacion de Procedimientos").
 *
 * MAPA DE CELDAS (hoja "Plantilla", igual que el flujo ODBC original):
 *   Encabezado:
 *     A5 "PERIODO"            -> B5  = "MES AÑO" (ej. "JUNIO 2026"; solo el
 *                                 anio si no hay mes; "TODOS LOS PERIODOS"
 *                                 si no hay ninguno)
 *     C5 "RED DE SALUD"       -> D5  = "CHANCHAMAYO" (ya viene fija en la
 *                                 plantilla, no se toca)
 *     A7 "CODIGO RENAES"      -> B7  = codigo del establecimiento
 *                                 (Codigo_Unico RENAES; vacio si el filtro
 *                                 es "-- Todos --")
 *     C7 "ESTABLECIMIENTO"    -> D7 (combinada D7:G7) = nombre del EE.SS o
 *                                 "TODOS"
 *   1.-  MORBILIDAD (filas 14-23): D=F, E=M, F=TOTAL
 *   2.-  PLAN DE ATENCION INTEGRAL (29-30): D/E/F
 *   3.-  EVALUACION ANTROPOMETRICA (36-43): D/E/F
 *   4.-  RIESGO NUTRICIONAL (49-50): D/E/F
 *   5.-  DNT: VALORACION (56-58) + HTA (62-64) + DM (68-73): D/E/F
 *   6.-  PLANIFICACION FAMILIAR:
 *          Clasificacion (79-84): D/E/F;
 *          Metodos (88-113): fila A/I con E=NUEVO, F=CONTI; y E115 = TOTAL
 *          de nuevas usuarias de los 13 metodos (suma calculada);
 *          AOE (116-119): fila A/I con E=cifra
 *   7.-  PREVENCION DE CANCER (124-125): D/E/F
 *   8.-  ATENCION EN SALUD BUCAL (130-157): D/E/F (7 prestaciones x
 *          NO GESTANTES/GESTANTES x INICIAN/TRATADO)
 *   9.-  SALUD MENTAL (162-172): D/E/F
 *   10.- TBC (177-178): D/E/F
 *   11.- SALUD SEXUAL Y REPRODUCTIVA (183-195): D/E/F
 *   12.- ITS-VIH (200-206): D/E/F
 *   13.- SALUD OCULAR (211-215): D/E/F
 *   14.- ACTIVIDADES PREVENTIVAS PROMOCIONALES (220-224): D/E/F
 *   15.- INMUNIZACIONES (229-238): D/E/F
 *   16.- SESIONES (243-246): D=Nº, E=Part/Benef
 *   17.- OTRAS CONSEJERIAS (251-256): D=TOTAL
 *
 * El mapa posicional vive en aduSecciones() (includes/adulto_data.php)
 * y la construccion de celdas en aduCeldasExport()
 * (includes/adulto_render.php).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/adulto_data.php';
require_once 'includes/adulto_render.php';
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
       . '<h3 style="color:#c0392b">No se pudo exportar el reporte ADULTO</h3>'
       . '<p>' . htmlspecialchars($causa) . '</p>'
       . '<ul><li>Genere primero el reporte en <a href="reporte_adulto.php">reporte_adulto.php</a> con los filtros deseados '
       . '(el asistente por partes acumula el resultado sin agotar los l&iacute;mites).</li>'
       . '<li>Luego pulse <em>Exportar Excel</em> desde esa misma p&aacute;gina: reutiliza el resultado ya calculado.</li>'
       . '<li>O bien acote los filtros (un mes y/o un establecimiento).</li></ul>'
       . '</body></html>';
});

// ==================== FILTROS (GET) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fToken           = trim($_GET['token'] ?? '');   // token del asistente por trozos

$reporte = null;

// ==================== REPORTE YA GENERADO (token del asistente) ====================
if ($fToken !== '') {
    $pdoTmp = getDBConnection();
    $rowT = aduCacheLeer($pdoTmp, $fToken);
    if ($rowT
        && (int)$rowT['id_usuario'] === (int)($_SESSION['usuario_id'] ?? 0)
        && $rowT['estado'] === 'listo'
        && $rowT['payload'] !== null && $rowT['payload'] !== '') {
        $reporte = json_decode((string)$rowT['payload'], true) ?: null;
        if (is_array($reporte) && !empty($reporte['error'])) $reporte = null;
        // recuperar los filtros originales guardados en el buffer
        if ($reporte) {
            $fRow = json_decode((string)$rowT['filtros'], true);
            if (is_array($fRow)) {
                $fAnio            = (string)($fRow['anio'] ?? $fAnio);
                $fMes             = (string)($fRow['mes'] ?? $fMes);
                $fEstablecimiento = (string)($fRow['establecimiento'] ?? $fEstablecimiento);
            }
        }
    }
}

// ==================== FILTROS POR DEFECTO ====================
if ($fAnio === '') {
    $pdoTmp = getDBConnection();
    $anios = aduGetAniosDisponibles($pdoTmp);
    $fAnio = $anios[0] ?? date('Y');
}

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
];

// ==================== EJECUTAR REPORTE ====================
if ($reporte === null) {
    if (!aduAmbitoDirecto($filtros)) {
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
        echo '<ol><li>Abra <a href="reporte_adulto.php?' . http_build_query($filtros) . '">reporte_adulto.php</a> y pulse <em>Generar Reporte</em>: el sistema lo construye por pasos.</li>';
        echo '<li>Al terminar, pulse <em>Exportar Excel</em> desde esa misma p&aacute;gina (usar&aacute; el resultado ya calculado).</li></ol>';
        echo '<p>O bien acote los filtros: seleccione un <strong>mes</strong> y/o un <strong>establecimiento</strong>.</p>';
        echo '</body></html>';
        exit;
    }
    $reporte = aduEjecutarReporte(getDBConnection(), $filtros);
}
if (!empty($reporte['error'])) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">Error al generar el reporte</h3>';
    echo '<p>' . htmlspecialchars($reporte['error']) . '</p>';
    echo '<p><a href="reporte_adulto.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== PLANTILLA ====================
$templatePath = __DIR__ . '/uploads/Reporte_Actividades_Adulto.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Plantilla no encontrada</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#e67e22">Plantilla oficial no encontrada</h3>';
    echo '<p>Para exportar el reporte con el dise&ntilde;o oficial, copie el archivo';
    echo ' <code>Reporte_Actividades_Adulto.xlsx</code> a la carpeta <code>uploads/</code> del sistema:</p>';
    echo '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Reporte_Actividades_Adulto.xlsx</pre>';
    echo '<p>Luego vuelva a pulsar <em>Exportar Excel</em>.</p>';
    echo '<p><a href="reporte_adulto.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== MAPA DE CELDAS ====================
// Cabecera: B5 = PERIODO ("JUNIO 2026"), B7 = CODIGO RENAES
// (Codigo_Unico RENAES), D7 (combinada D7:G7) = EE.SS o "TODOS".
// La RED (D5) ya viene fija en la plantilla ("CHANCHAMAYO").
//
// El <select> de reporte_adulto.php envia el Codigo_Unico (RENAES);
// por COMPATIBILIDAD, si llegara el NOMBRE del EE.SS se recupera desde
// ZSPERENE tanto el nombre (D7) como su codigo (B7).
$nombreEst = 'TODOS';
$codigoEst = '';
if ($fEstablecimiento !== '') {
    $est = aduGetEstablecimientosZS(getDBConnection()); // [codigo => nombre]
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
$cells = aduCeldasExport($reporte, $fAnio, $fMes, $fEstablecimiento !== '' ? $nombreEst : '', $codigoEst);

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cells);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Reporte_Actividades_Adulto_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
