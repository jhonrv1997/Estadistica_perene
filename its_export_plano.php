<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional ITS/VIH a PLANO consolidado (.xlsx)
 *         - NUEVA FUNCIONALIDAD "Exportar Plano"
 *
 * Genera el archivo plano horizontal usando como base la plantilla
 * "ITS_Plano.xlsx" (copiar a uploads/ITS_Plano.xlsx) y el archivo de
 * referencia "Ubicacion_Filas_ITS.xlsx" (copiar a uploads/).
 *
 * ANTECEDENTE:
 *   its_export.php llena la plantilla oficial "Reporte_Actividades_ITS.xlsx"
 *   con los datos calculados por includes/its_data.php (mapa de celdas de
 *   itsCeldasExport, includes/its_render.php).
 *
 * NUEVA FUNCIONALIDAD (misma data, destino plano):
 *   Los datos del reporte son EXACTAMENTE los mismos que se escriben en la
 *   plantilla oficial (itsCeldasExport), pero en lugar de la plantilla oficial
 *   se depositan en el plano consolidado "ITS_Plano.xlsx" siguiendo la
 *   referencia de ubicacion:
 *
 *     - "Ubicacion_Filas_ITS.xlsx" (espejo compactado del reporte): cada celda
 *       de datos contiene el NUMERO DE DESTINO. Ej: B5 = 4.
 *     - "Reporte_Actividades_ITS.xlsx" / itsCeldasExport: la celda equivalente
 *       (B13, el espejo esta 8 filas arriba) contiene el DATO. Ej: B13 = 2.
 *     - "ITS_Plano.xlsx": su fila 12 (A12:CBM12) contiene la numeracion de
 *       columnas 1..2093; el numero 4 corresponde a D12.
 *       => El dato 2 se rellena en D13. Para todas las demas celdas se sigue
 *          la misma logica (fila de datos = 13).
 *
 * La logica de mapeo vive en includes/its_plano.php (incluye la correccion del
 * typo H9=1241 -> 124 de la plantilla de ubicacion) y la lectura de celdas
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
require_once 'includes/its_data.php';
require_once 'includes/its_render.php';         // itsCeldasExport() (mismos datos que Exportar Excel)
require_once 'includes/ExcelTemplateFiller.php';
require_once 'includes/its_plano.php';          // NUEVO: mapeo de las 3 plantillas

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

/** Pagina de error amigable (mismo estilo que its_export.php). */
function itsPlanoError(string $titulo, string $detalleHtml, array $filtros): void {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($titulo) . '</title></head>'
       . '<body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">' . htmlspecialchars($titulo) . '</h3>';
    echo '<p>' . $detalleHtml . '</p>';
    echo '<p><a href="reporte_its.php?' . htmlspecialchars(http_build_query($filtros)) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== EJECUTAR REPORTE (mismos datos que "Exportar Excel") ====================
$reporte = itsEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    itsPlanoError('Error al generar el reporte', htmlspecialchars($reporte['error']), $filtros);
}

// ==================== PLANTILLAS ====================
$planoPath     = __DIR__ . '/uploads/ITS_Plano.xlsx';
$ubicacionPath = __DIR__ . '/uploads/Ubicacion_Filas_ITS.xlsx';

if (!is_readable($planoPath)) {
    itsPlanoError('Plantilla del plano no encontrada',
        'Para exportar el plano consolidado, copie el archivo <code>ITS_Plano.xlsx</code> '
        . 'a la carpeta <code>uploads/</code> del sistema:<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/ITS_Plano.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}
if (!is_readable($ubicacionPath)) {
    itsPlanoError('Archivo de ubicacion no encontrado',
        'Para exportar el plano consolidado, copie el archivo <code>Ubicacion_Filas_ITS.xlsx</code> '
        . '(numeros de destino de cada celda) a la carpeta <code>uploads/</code> del sistema:'
        . '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Ubicacion_Filas_ITS.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}

// ==================== DATOS DEL REPORTE (identicos a its_export.php) ====================
$nombreEst = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $est = itsGetEstablecimientosZS(getDBConnection());
    $nombreEst = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

$celdasReporte = itsCeldasExport($reporte, $fAnio, $fMes, $fEstablecimiento !== '' ? $nombreEst : '', $fEstablecimiento !== '' ? $nombreEst : '');

// ==================== MAPEO DE LAS 3 PLANTILLAS ====================
// El mapeo usa cache en disco (cache/plano/): la primera exportacion parsea
// los xlsx y guarda el resultado en JSON; las siguientes reutilizan la cache
// y solo la regeneran si cambia un archivo fuente.
try {
    // 1) Referencia de ubicacion: [celdaOrigen => numeroDestino], ej: B5 => 4
    //    (con la correccion del typo H9=1241 -> 124 ya aplicada).
    $mapaUbicacion = itsPlanoMapaUbicacion($ubicacionPath);

    // 2) Referencia del destino: [numeroDestino => columna], ej: 4 => D (fila 12 del plano)
    $indicePlano = itsPlanoIndiceDestino($planoPath);

    // 3) Cruce: dato de B13 (equivalente oficial de B5, espejo -8 filas) -> columna D -> celda D13
    $celdasPlano = itsPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano, ITS_PLANO_FILA_DATOS, ITS_PLANO_OFFSET_FILA);
} catch (Throwable $e) {
    itsPlanoError('Error al mapear las plantillas del plano', htmlspecialchars($e->getMessage()), $filtros);
}

if (empty($celdasPlano)) {
    itsPlanoError('El plano quedaria vacio',
        'No se encontro correspondencia entre <code>Ubicacion_Filas_ITS.xlsx</code> y los datos del reporte. '
        . 'Verifique que el archivo de ubicacion corresponde a la plantilla actual.', $filtros);
}

// ==================== IDENTIFICACION DEL REGISTRO (fila de datos) ====================
// Columnas de cabecera del plano (fila 7): A=RENAES, B=RENAES y
// C=Establecimiento (numeros 1, 2 y 3 de la fila 12). Se identifican con los
// mismos textos que el flujo del reporte oficial (C8/I6).
if ($fEstablecimiento !== '') {
    $celdasPlano['B' . ITS_PLANO_FILA_DATOS] = $fEstablecimiento;                 // Codigo RENAES
    $celdasPlano['C' . ITS_PLANO_FILA_DATOS] = $nombreEst;                        // Nombre del EE.SS
} else {
    $celdasPlano['C' . ITS_PLANO_FILA_DATOS] = 'TODOS LOS ESTABLECIMIENTOS DE LA LISTA';
}

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($planoPath);
$filler->setCellValues($celdasPlano);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "ITS_Plano_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
