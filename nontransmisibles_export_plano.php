<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional NO TRANSMISIBLES a PLANO consolidado (.xlsx)
 *         - NUEVA FUNCIONALIDAD "Exportar Plano"
 *
 * Genera el archivo plano horizontal usando como base la plantilla
 * "Notransmisibles_Plano.xlsx" (copiar a uploads/Notransmisibles_Plano.xlsx) y
 * el archivo de referencia "Ubicacion_Filas_NoTransmisibles.xlsx" (copiar a
 * uploads/).
 *
 * ANTECEDENTE:
 *   nontransmisibles_export.php llena la plantilla oficial 2026
 *   "Reporte_Actividades_NoTransmisiblesN.xlsx" con los datos calculados por
 *   includes/nontransmisibles_data.php (adaptacion web de los 24 procedimientos
 *   usp_TRAMA_BASE_NT_2025_* del archivo "03 Creacion de Procedimientos").
 *
 * NUEVA FUNCIONALIDAD (misma data, destino plano):
 *   Los datos del reporte son EXACTAMENTE los mismos que se escriben en la
 *   plantilla oficial (ntCeldasExport), pero en lugar de la plantilla oficial
 *   se depositan en el plano consolidado "Notransmisibles_Plano.xlsx" siguiendo
 *   la referencia de ubicacion:
 *
 *     - "Ubicacion_Filas_NoTransmisibles.xlsx" (espejo del reporte): cada celda
 *       de datos contiene el NUMERO DE DESTINO. Ej: C12 = 4.
 *     - "Reporte_Actividades_NoTransmisiblesN.xlsx" / ntCeldasExport: la celda
 *       equivalente (MISMA fila, el espejo calza fila por fila) contiene el
 *       DATO. Ej: C12 = 5.
 *     - "Notransmisibles_Plano.xlsx": su fila 7 (A7:GHG7) contiene la
 *       numeracion de columnas 1..4947; el numero 4 corresponde a D7.
 *       => El dato 5 se rellena en D8. Para todas las demas celdas se sigue
 *          la misma logica (fila de datos = 8). Las columnas 1-3 del plano son
 *          la identificacion del registro (A=Ubigeo, B=Codigo RENAES,
 *          C=Dpto/Red/Provincia/Distrito/EE.SS).
 *
 *   Excepciones cubiertas por includes/nontransmisibles_plano.php:
 *     - Las filas 99 ("PACIENTE CONTROLADO", numeros 641-653) y 107 ("ATENCION
 *       INTEGRAL ESPECIALIZADO", numeros 745-757) del espejo no se producen en
 *       el motor 2026: esas 26 columnas del plano quedan vacias.
 *
 * La logica de mapeo vive en includes/nontransmisibles_plano.php y la lectura
 * de celdas numericas en includes/ExcelTemplateReader.php.
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
require_once 'includes/nontransmisibles_data.php';
require_once 'includes/nontransmisibles_render.php';   // ntCeldasExport() (mismos datos que Exportar Excel)
require_once 'includes/ExcelTemplateFiller.php';
require_once 'includes/nontransmisibles_plano.php';    // NUEVO: mapeo de las 3 plantillas

// El plano NT tiene 4947 columnas (sheet XML de ~1.2 MB): pedir memoria extra
// de forma tolerante (en hostings que la limiten, sigue funcionando con el
// memory_limit por defecto gracias al cache del mapeo en cache/plano/).
if (function_exists('ini_set')) {
    @ini_set('memory_limit', '512M');
}

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

/** Pagina de error amigable (mismo estilo que nontransmisibles_export.php). */
function ntPlanoError(string $titulo, string $detalleHtml, array $filtros): void {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($titulo) . '</title></head>'
       . '<body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">' . htmlspecialchars($titulo) . '</h3>';
    echo '<p>' . $detalleHtml . '</p>';
    echo '<p><a href="reporte_no_transmisibles.php?' . htmlspecialchars(http_build_query($filtros)) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== EJECUTAR REPORTE (mismos datos que "Exportar Excel") ====================
$reporte = ntEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    ntPlanoError('Error al generar el reporte', htmlspecialchars($reporte['error']), $filtros);
}

// ==================== PLANTILLAS ====================
$planoPath     = __DIR__ . '/uploads/Notransmisibles_Plano.xlsx';
$ubicacionPath = __DIR__ . '/uploads/Ubicacion_Filas_NoTransmisibles.xlsx';

if (!is_readable($planoPath)) {
    ntPlanoError('Plantilla del plano no encontrada',
        'Para exportar el plano consolidado, copie el archivo <code>Notransmisibles_Plano.xlsx</code> '
        . 'a la carpeta <code>uploads/</code> del sistema:<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Notransmisibles_Plano.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}
if (!is_readable($ubicacionPath)) {
    ntPlanoError('Archivo de ubicacion no encontrado',
        'Para exportar el plano consolidado, copie el archivo <code>Ubicacion_Filas_NoTransmisibles.xlsx</code> '
        . '(numeros de destino de cada celda) a la carpeta <code>uploads/</code> del sistema:'
        . '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Ubicacion_Filas_NoTransmisibles.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}

// ==================== DATOS DEL REPORTE (identicos a nontransmisibles_export.php) ====================
$nombreEst = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $est = ntGetEstablecimientosZS(getDBConnection());
    $nombreEst = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

$periodoTxt = [];
if ($fAnio !== '') $periodoTxt[] = $fAnio;
if ($fMes !== '')  $periodoTxt[] = strtoupper(getNombreMes((int)$fMes));
$periodoTxt = $periodoTxt ? implode(' - ', $periodoTxt) : '';

$celdasReporte = ntCeldasExport($reporte, $periodoTxt, $fEstablecimiento, $fEstablecimiento !== '' ? $nombreEst : '');

// ==================== MAPEO DE LAS 3 PLANTILLAS ====================
// El mapeo usa cache en disco (cache/plano/): la primera exportacion parsea
// los xlsx y guarda el resultado en JSON; las siguientes reutilizan la cache
// y solo la regeneran si cambia un archivo fuente.
try {
    // 1) Referencia de ubicacion: [celdaOrigen => numeroDestino], ej: C12 => 4
    $mapaUbicacion = ntPlanoMapaUbicacion($ubicacionPath);

    // 2) Referencia del destino: [numeroDestino => columna], ej: 4 => D (fila 7 del plano)
    $indicePlano = ntPlanoIndiceDestino($planoPath);

    // 3) Cruce: dato de C12 (celda equivalente del espejo) -> columna D -> celda D8
    $celdasPlano = ntPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano, NT_PLANO_FILA_DATOS, NT_PLANO_OFFSET_FILA);
} catch (Throwable $e) {
    ntPlanoError('Error al mapear las plantillas del plano', htmlspecialchars($e->getMessage()), $filtros);
}

if (empty($celdasPlano)) {
    ntPlanoError('El plano quedaria vacio',
        'No se encontro correspondencia entre <code>Ubicacion_Filas_NoTransmisibles.xlsx</code> y los datos del reporte. '
        . 'Verifique que el archivo de ubicacion corresponde a la plantilla actual.', $filtros);
}

// ==================== IDENTIFICACION DEL REGISTRO (fila de datos) ====================
// Columnas de cabecera del plano (fila 4): A=Ubigeo, B=Codigo RENAES y
// C=Dpto/Red/Provincia/Distrito/EE.SS (numeros 1, 2 y 3 de la fila 7).
// El catalogo ZSPERENE no trae ubigeo, por lo que la columna A se deja vacia
// (mismo criterio que el plano de Zoonosis).
if ($fEstablecimiento !== '') {
    $celdasPlano['B' . NT_PLANO_FILA_DATOS] = $fEstablecimiento; // Codigo RENAES
    $celdasPlano['C' . NT_PLANO_FILA_DATOS] = $nombreEst;        // Nombre del EE.SS
} else {
    $celdasPlano['C' . NT_PLANO_FILA_DATOS] = 'TODOS LOS ESTABLECIMIENTOS DE LA LISTA';
}

// ==================== PERIODO (celda B3 del plano: "MES: ...") ====================
// La cabecera del plano trae "MES: OCTUBRE". Se actualiza con el periodo
// exportado (mismo criterio que la cabecera del reporte web y del plano Materno).
if ($fAnio !== '' && $fMes !== '') {
    $periodoPlano = $fAnio . ' - ' . strtoupper(getNombreMes((int)$fMes));
} elseif ($fAnio !== '') {
    $periodoPlano = $fAnio . ' - ACUMULADO';
} elseif ($fMes !== '') {
    $periodoPlano = strtoupper(getNombreMes((int)$fMes));
} else {
    $periodoPlano = 'TODOS LOS PERIODOS';
}
$celdasPlano['B3'] = $periodoPlano;

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($planoPath);
$filler->setCellValues($celdasPlano);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Notransmisibles_Plano_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
