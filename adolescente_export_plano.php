<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional ADOLESCENTE a PLANO consolidado (.xlsx)
 *         - NUEVA FUNCIONALIDAD "Exportar Plano"
 *
 * Genera el archivo plano horizontal usando como base la plantilla
 * "Adolescentes_Plano.xlsx" (copiar a uploads/Adolescentes_Plano.xlsx) y el
 * archivo de referencia "Ubicacion_Filas_Adolescentes.xlsx" (copiar a
 * uploads/Ubicacion_Filas_Adolescentes.xlsx).
 *
 * ANTECEDENTE:
 *   adolescente_export.php llena la plantilla oficial
 *   "Reporte_Actividades_Adolescentes.xlsx" con los datos calculados por
 *   includes/adolescente_data.php (mapa de celdas de adoCeldasExport,
 *   includes/adolescente_render.php).
 *
 * NUEVA FUNCIONALIDAD (misma data, destino plano):
 *   Los datos del reporte son EXACTAMENTE los mismos que se escriben en la
 *   plantilla oficial (adoCeldasExport), pero en lugar de la plantilla oficial
 *   se depositan en el plano consolidado "Adolescentes_Plano.xlsx" siguiendo
 *   la referencia de ubicacion:
 *
 *     - "Ubicacion_Filas_Adolescentes.xlsx" (espejo compactado del reporte):
 *       cada celda de datos contiene el NUMERO DE DESTINO. Ej: C11 = 4.
 *     - "Reporte_Actividades_Adolescentes.xlsx" / adoCeldasExport: la celda
 *       equivalente (MISMA fila, el espejo calza fila por fila) contiene el
 *       DATO. Ej: C11 = 2.
 *     - "Adolescentes_Plano.xlsx": su fila 12 (A12:PK12) contiene la
 *       numeracion de columnas 1..427; el numero 4 corresponde a D12.
 *       => El dato 2 se rellena en D13. Para todas las demas celdas se sigue
 *          la misma logica (fila de datos = 13, C13:PK13).
 *
 * La logica de mapeo vive en includes/adolescente_plano.php (offset 0: el
 * espejo calza fila por fila con la plantilla oficial; 424 numeros 4..427
 * verificados automaticamente contra el motor) y la lectura de celdas
 * numericas en includes/ExcelTemplateReader.php.
 *
 * CACHE DEL MAPEO (mejora de rendimiento):
 *   El mapa de ubicaciones y el indice del plano solo dependen de 2 archivos
 *   estaticos, por lo que se cachean en JSON dentro de cache/plano/ (la
 *   carpeta se crea sola al primer uso). La cache se regenera automaticamente
 *   si cambia alguno de los xlsx (mtime o size distintos), asi que reemplazar
 *   una plantilla nunca produce datos viejos.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/adolescente_data.php';
require_once 'includes/adolescente_render.php';  // adoCeldasExport() (mismos datos que Exportar Excel)
require_once 'includes/ExcelTemplateFiller.php';
require_once 'includes/adolescente_plano.php';   // NUEVO: mapeo de las 3 plantillas

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
    $causa = $esMem ? 'Se agotó la memoria (memory_limit) al calcular el reporte para el plano.'
            : ($esTmp ? 'Se agotó el tiempo de ejecución (max_execution_time) al calcular el reporte.'
                      : 'Ocurrió un error fatal de PHP durante el export del plano.');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:system-ui,sans-serif">'
       . '<h3 style="color:#c0392b">No se pudo exportar el plano ADOLESCENTE</h3>'
       . '<p>' . htmlspecialchars($causa) . '</p>'
       . '<p class="small">Sugerencias: genere primero el reporte en <a href="reporte_adolescente.php">reporte_adolescente.php</a> '
       . '(el asistente lo construye por partes) y luego pulse <em>Exportar Plano</em> desde ahí, o acote los filtros '
       . '(un mes y/o un establecimiento).</p>'
       . '</body></html>';
});

// ==================== FILTROS (mismos parametros que reporte_adolescente.php) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fToken           = trim($_GET['token'] ?? '');   // token del asistente por trozos
$reporte          = null;

/** Pagina de error amigable (mismo estilo que adolescente_export.php). */
function adoPlanoError(string $titulo, string $detalleHtml, array $filtros): void {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($titulo) . '</title></head>'
       . '<body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">' . htmlspecialchars($titulo) . '</h3>';
    echo '<p>' . $detalleHtml . '</p>';
    echo '<p><a href="reporte_adolescente.php?' . htmlspecialchars(http_build_query($filtros)) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

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

// ==================== EJECUTAR REPORTE (mismos datos que "Exportar Excel") ====================
if ($reporte === null) {
    if (!adoAmbitoDirecto($filtros)) {
        // Rango amplio SIN token: la consulta unica agotaria los limites del
        // hosting. Generar antes el reporte en la pagina (asistente por
        // trozos) y exportar desde ahi, o acotar los filtros.
        http_response_code(200);
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Rango demasiado amplio</title></head><body style="font-family:system-ui,sans-serif">';
        echo '<h3 style="color:#e67e22">Rango demasiado amplio para exportar el plano de una sola vez</h3>';
        echo '<p>El periodo seleccionado (' . htmlspecialchars($fAnio !== '' ? $fAnio : 'todos los años')
           . ($fMes !== '' ? ' / mes ' . htmlspecialchars($fMes) : '')
           . ($fEstablecimiento !== '' ? ' / EE.SS ' . htmlspecialchars($fEstablecimiento) : ' / todos los EE.SS')
           . ') requiere el asistente por partes.</p>';
        echo '<ol><li>Abra <a href="reporte_adolescente.php?' . http_build_query($filtros) . '">reporte_adolescente.php</a> y pulse <em>Generar Reporte</em>: el sistema lo construye por pasos.</li>';
        echo '<li>Al terminar, pulse <em>Exportar Plano</em> desde esa misma p&aacute;gina (usar&aacute; el resultado ya calculado).</li></ol>';
        echo '<p>O bien acote los filtros: seleccione un <strong>mes</strong> y/o un <strong>establecimiento</strong>.</p>';
        echo '</body></html>';
        exit;
    }
    $reporte = adoEjecutarReporte(getDBConnection(), $filtros);
}
if (!empty($reporte['error'])) {
    adoPlanoError('Error al generar el reporte', htmlspecialchars($reporte['error']), $filtros);
}

// ==================== PLANTILLAS ====================
$planoPath     = __DIR__ . '/uploads/Adolescentes_Plano.xlsx';
$ubicacionPath = __DIR__ . '/uploads/Ubicacion_Filas_Adolescentes.xlsx';

if (!is_readable($planoPath)) {
    adoPlanoError('Plantilla del plano no encontrada',
        'Para exportar el plano consolidado, copie el archivo <code>Adolescentes_Plano.xlsx</code> '
        . 'a la carpeta <code>uploads/</code> del sistema:<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Adolescentes_Plano.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}
if (!is_readable($ubicacionPath)) {
    adoPlanoError('Archivo de ubicacion no encontrado',
        'Para exportar el plano consolidado, copie el archivo <code>Ubicacion_Filas_Adolescentes.xlsx</code> '
        . '(numeros de destino de cada celda) a la carpeta <code>uploads/</code> del sistema:'
        . '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Ubicacion_Filas_Adolescentes.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}

// ==================== DATOS DEL REPORTE (identicos a adolescente_export.php) ====================
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

$celdasReporte = adoCeldasExport($reporte, $fAnio, $fMes, $fEstablecimiento !== '' ? $nombreEst : '', $codigoEst);

// ==================== MAPEO DE LAS 3 PLANTILLAS ====================
// El mapeo usa cache en disco (cache/plano/): la primera exportacion parsea
// los xlsx y guarda el resultado en JSON; las siguientes reutilizan la cache
// y solo la regeneran si cambia un archivo fuente.
try {
    // 1) Referencia de ubicacion: [celdaOrigen => numeroDestino], ej: C11 => 4
    $mapaUbicacion = adoPlanoMapaUbicacion($ubicacionPath);

    // 2) Referencia del destino: [numeroDestino => columna], ej: 4 => D (fila 12 del plano)
    $indicePlano = adoPlanoIndiceDestino($planoPath);

    // 3) Cruce: dato de C11 (equivalente oficial de la celda espejo C11,
    //    offset 0) -> columna D -> celda D13
    $celdasPlano = adoPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano, ADO_PLANO_FILA_DATOS, ADO_PLANO_OFFSET_FILA);
} catch (Throwable $e) {
    adoPlanoError('Error al mapear las plantillas del plano', htmlspecialchars($e->getMessage()), $filtros);
}

if (empty($celdasPlano)) {
    adoPlanoError('El plano quedaria vacio',
        'No se encontro correspondencia entre <code>Ubicacion_Filas_Adolescentes.xlsx</code> y los datos del reporte. '
        . 'Verifique que el archivo de ubicacion corresponde a la plantilla actual.', $filtros);
}

// ==================== IDENTIFICACION DEL REGISTRO (fila de datos) ====================
// Columnas de cabecera del plano (fila 6): A=Ubigeo, B=Codigo RENAES y
// C=Departamento/Red/Provincia/Distrito/EE.SS (numeros 1, 2 y 3 de la fila
// 12). El catalogo ZSPERENE no trae ubigeo, por lo que la columna A se deja
// vacia (mismo criterio que los planos de Zoonosis y NoTransmisibles).
if ($fEstablecimiento !== '') {
    if ($codigoEst !== '') {
        $celdasPlano['B' . ADO_PLANO_FILA_DATOS] = $codigoEst;  // Codigo RENAES
    }
    $celdasPlano['C' . ADO_PLANO_FILA_DATOS] = $nombreEst;      // Nombre del EE.SS
} else {
    $celdasPlano['C' . ADO_PLANO_FILA_DATOS] = 'TODOS LOS ESTABLECIMIENTOS DE LA LISTA';
}

// ==================== PERIODO (celda B5 del plano: "MES: ...") ====================
// La cabecera del plano trae "MES: OCTUBRE". Se actualiza con el periodo
// exportado (mismo criterio que la cabecera del reporte web y del plano
// Materno/NoTransmisibles).
if ($fAnio !== '' && $fMes !== '') {
    $periodoPlano = $fAnio . ' - ' . strtoupper(getNombreMes((int)$fMes));
} elseif ($fAnio !== '') {
    $periodoPlano = $fAnio . ' - ACUMULADO';
} elseif ($fMes !== '') {
    $periodoPlano = strtoupper(getNombreMes((int)$fMes));
} else {
    $periodoPlano = 'TODOS LOS PERIODOS';
}
$celdasPlano['B5'] = $periodoPlano;

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($planoPath);
$filler->setCellValues($celdasPlano);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $codigoEst !== '' ? $codigoEst : $fEstablecimiento) : '';
$nombre = "Adolescentes_Plano_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
