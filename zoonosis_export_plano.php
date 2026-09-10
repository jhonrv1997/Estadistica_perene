<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional ZOONOSIS a PLANO consolidado (.xlsx)
 *         - NUEVA FUNCIONALIDAD "Exportar Plano"
 *
 * Genera el archivo plano horizontal usando como base la plantilla
 * "Zoonosis_Plano.xlsx" (copiar a uploads/Zoonosis_Plano.xlsx) y el archivo
 * de referencia "Ubicacion Filas.xlsx" (copiar a uploads/Ubicacion Filas.xlsx).
 *
 * ANTECEDENTE:
 *   zoonosis_export.php llena la plantilla oficial "Reporte_Actividades_Zoonosis.xlsx"
 *   con los datos calculados por includes/zoonosis_data.php (mapa de celdas de
 *   zooCeldasExport, includes/zoonosis_render.php).
 *
 * NUEVA FUNCIONALIDAD (misma data, destino plano):
 *   Los datos del reporte son EXACTAMENTE los mismos que se escriben en la
 *   plantilla oficial (zooCeldasExport), pero en lugar de la plantilla oficial
 *   se depositan en el plano consolidado "Zoonosis_Plano.xlsx" siguiendo la
 *   referencia de ubicacion:
 *
 *     - "Ubicacion Filas.xlsx" (espejo del reporte): cada celda de datos
 *       contiene el NUMERO DE DESTINO. Ej: D13 = 288.
 *     - "Reporte_Actividades_Zoonosis.xlsx" / zooCeldasExport: la misma celda
 *       contiene el DATO. Ej: D13 = 2.
 *     - "Zoonosis_Plano.xlsx": su fila 13 (A13:BPX13) contiene la numeracion
 *       de columnas 1..1792; el numero 288 corresponde a KB13.
 *       => El dato 2 se rellena en KB14. Para todas las demas celdas se sigue
 *          la misma logica (fila de datos = 14).
 *
 * La logica de mapeo vive en includes/zoonosis_plano.php y la lectura de
 * celdas numericas en includes/ExcelTemplateReader.php.
 *
 * CORRECCION DE DUPLICADOS (plantilla "Ubicacion Filas.xlsx"):
 *   La plantilla original traia 2 typos: 749 repetido en R130/P131 y 968
 *   repetido en D176/E176 (faltaban 750 y 969). Valores correctos segun el
 *   usuario: R130=750, P131=749, D176=968, E176=969. La plantilla entregada
 *   ya viene corregida y ademas includes/zoonosis_plano.php aplica la
 *   correccion en codigo como red de seguridad (zooPlanoCorrecciones()).
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
require_once 'includes/zoonosis_data.php';
require_once 'includes/zoonosis_render.php';    // zooCeldasExport() (mismos datos que Exportar Excel)
require_once 'includes/ExcelTemplateFiller.php';
require_once 'includes/zoonosis_plano.php';     // NUEVO: mapeo de las 3 plantillas

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

/** Pagina de error amigable (mismo estilo que zoonosis_export.php). */
function planoError(string $titulo, string $detalleHtml, array $filtros): void {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($titulo) . '</title></head>'
       . '<body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">' . htmlspecialchars($titulo) . '</h3>';
    echo '<p>' . $detalleHtml . '</p>';
    echo '<p><a href="reporte_zoonosis.php?' . htmlspecialchars(http_build_query($filtros)) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== EJECUTAR REPORTE (mismos datos que "Exportar Excel") ====================
$reporte = zooEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    planoError('Error al generar el reporte', htmlspecialchars($reporte['error']), $filtros);
}

// ==================== PLANTILLAS ====================
$planoPath     = __DIR__ . '/uploads/Zoonosis_Plano.xlsx';
$ubicacionPath = __DIR__ . '/uploads/Ubicacion Filas.xlsx';

if (!is_readable($planoPath)) {
    planoError('Plantilla del plano no encontrada',
        'Para exportar el plano consolidado, copie el archivo <code>Zoonosis_Plano.xlsx</code> '
        . 'a la carpeta <code>uploads/</code> del sistema:<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Zoonosis_Plano.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}
if (!is_readable($ubicacionPath)) {
    planoError('Archivo de ubicacion no encontrado',
        'Para exportar el plano consolidado, copie el archivo <code>Ubicacion Filas.xlsx</code> '
        . '(numeros de destino de cada celda) a la carpeta <code>uploads/</code> del sistema:'
        . '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Ubicacion Filas.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}

// ==================== DATOS DEL REPORTE (identicos a zoonosis_export.php) ====================
$mesTxt  = $fMes !== '' ? strtoupper(getNombreMes((int)$fMes)) : '';
$nombreEst = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $est = zooGetEstablecimientosZS(getDBConnection());
    $nombreEst = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

$celdasReporte = zooCeldasExport($reporte, $mesTxt, $fAnio, $fEstablecimiento !== '' ? $nombreEst : '');

// ==================== MAPEO DE LAS 3 PLANTILLAS ====================
// El mapeo usa cache en disco (cache/plano/): la primera exportacion parsea
// los xlsx y guarda el resultado en JSON; las siguientes reutilizan la cache
// y solo la regeneran si cambia un archivo fuente.
try {
    // 1) Referencia de ubicacion: [celdaOrigen => numeroDestino], ej: D13 => 288
    $mapaUbicacion = zooPlanoMapaUbicacion($ubicacionPath);

    // 2) Referencia del destino: [numeroDestino => columna], ej: 288 => KB (fila 13 del plano)
    $indicePlano = zooPlanoIndiceDestino($planoPath);

    // 3) Cruce: dato de D13 (2) -> columna KB -> celda KB14
    $celdasPlano = zooPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano, ZOO_PLANO_FILA_DATOS);
} catch (Throwable $e) {
    planoError('Error al mapear las plantillas del plano', htmlspecialchars($e->getMessage()), $filtros);
}

if (empty($celdasPlano)) {
    planoError('El plano quedaria vacio',
        'No se encontro correspondencia entre <code>Ubicacion Filas.xlsx</code> y los datos del reporte. '
        . 'Verifique que el archivo de ubicacion corresponde a la plantilla actual.', $filtros);
}

// ==================== IDENTIFICACION DEL REGISTRO (fila de datos) ====================
// Columnas de cabecera del plano: A=UBIGEO, B=UBIGEO y Codigo RENAES,
// C=DIRESA/RED/PROVINCIA/DISTRITO/ESTABLECIMIENTO. Se identifican con los
// mismos textos que el flujo del reporte oficial (D5/R4/O5).
if ($fEstablecimiento !== '') {
    $celdasPlano['B' . ZOO_PLANO_FILA_DATOS] = $fEstablecimiento;                 // Codigo RENAES
    $celdasPlano['C' . ZOO_PLANO_FILA_DATOS] = $nombreEst;                        // Nombre del EE.SS
} else {
    $celdasPlano['C' . ZOO_PLANO_FILA_DATOS] = 'TODOS LOS ESTABLECIMIENTOS DE LA LISTA';
}

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($planoPath);
$filler->setCellValues($celdasPlano);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Zoonosis_Plano_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
