<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Exportar Reporte Operacional MATERNO a PLANO consolidado (.xlsx)
 *         - NUEVA FUNCIONALIDAD "Exportar Plano"
 *
 * Genera el archivo plano horizontal usando como base la plantilla
 * "Materno_Plano.xlsx" (copiar a uploads/Materno_Plano.xlsx) y el archivo de
 * referencia "Ubicacion_Filas_Materno.xlsx" (copiar a uploads/).
 *
 * ANTECEDENTE:
 *   materno_export.php llena la plantilla oficial "Reporte_Actividades_Materno.xlsx"
 *   con los datos calculados por includes/materno_data.php (adaptacion web de
 *   los procedimientos RPT_01..RPT_10 del archivo "03 Creacion de Procedimientos").
 *
 * NUEVA FUNCIONALIDAD (misma data, destino plano):
 *   Los datos del reporte son EXACTAMENTE los mismos que se escriben en la
 *   plantilla oficial (maternoCeldasExport), pero en lugar de la plantilla
 *   oficial se depositan en el plano consolidado "Materno_Plano.xlsx" siguiendo
 *   la referencia de ubicacion:
 *
 *     - "Ubicacion_Filas_Materno.xlsx" (espejo del reporte): cada celda de
 *       datos contiene el NUMERO DE DESTINO. Ej: C16 = 4.
 *     - "Reporte_Actividades_Materno.xlsx" / maternoCeldasExport: la celda
 *       equivalente (MISMA fila, el espejo calza fila por fila) contiene el
 *       DATO. Ej: C16 = 5.
 *     - "Materno_Plano.xlsx": su fila 12 (A12:SV12) contiene la numeracion de
 *       columnas 1..516; el numero 4 corresponde a D12.
 *       => El dato 5 se rellena en D13. Para todas las demas celdas se sigue
 *          la misma logica (fila de datos = 13).
 *
 *   Excepciones cubiertas por includes/materno_plano.php:
 *     - Ajuste de columna V. MORBILIDAD RN (espejo P -> oficial O) y
 *       VIII. VISITA DOMICILIARIA (espejo T -> oficial U).
 *     - Seccion IX-3 (TAMIZAJE VIH EN PARTO, numeros 498-501): sin zona en la
 *       plantilla oficial; se llena con los totales de RPT09_3.
 *     - La columna B de la seccion I (Total de Gestante Atendida, calculada)
 *       no es numerada por el espejo y el plano no la recibe.
 *
 * La logica de mapeo vive en includes/materno_plano.php y la lectura de celdas
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
require_once 'includes/materno_data.php';
require_once 'includes/materno_render.php';      // maternoCeldasExport() (mismos datos que Exportar Excel)
require_once 'includes/ExcelTemplateFiller.php';
require_once 'includes/materno_plano.php';       // NUEVO: mapeo de las 3 plantillas

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

/** Pagina de error amigable (mismo estilo que materno_export.php). */
function maternoPlanoError(string $titulo, string $detalleHtml, array $filtros): void {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($titulo) . '</title></head>'
       . '<body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">' . htmlspecialchars($titulo) . '</h3>';
    echo '<p>' . $detalleHtml . '</p>';
    echo '<p><a href="reporte_materno.php?' . htmlspecialchars(http_build_query($filtros)) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== EJECUTAR REPORTE (mismos datos que "Exportar Excel") ====================
$reporte = maternoEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    maternoPlanoError('Error al generar el reporte', htmlspecialchars($reporte['error']), $filtros);
}

// ==================== PLANTILLAS ====================
$planoPath     = __DIR__ . '/uploads/Materno_Plano.xlsx';
$ubicacionPath = __DIR__ . '/uploads/Ubicacion_Filas_Materno.xlsx';

if (!is_readable($planoPath)) {
    maternoPlanoError('Plantilla del plano no encontrada',
        'Para exportar el plano consolidado, copie el archivo <code>Materno_Plano.xlsx</code> '
        . 'a la carpeta <code>uploads/</code> del sistema:<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Materno_Plano.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}
if (!is_readable($ubicacionPath)) {
    maternoPlanoError('Archivo de ubicacion no encontrado',
        'Para exportar el plano consolidado, copie el archivo <code>Ubicacion_Filas_Materno.xlsx</code> '
        . '(numeros de destino de cada celda) a la carpeta <code>uploads/</code> del sistema:'
        . '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Ubicacion_Filas_Materno.xlsx</pre>'
        . '<p>Luego vuelva a pulsar <em>Exportar Plano</em>.</p>', $filtros);
}

// ==================== DATOS DEL REPORTE (identicos a materno_export.php) ====================
$nombreEst = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $est = maternoGetEstablecimientosZS(getDBConnection());
    $nombreEst = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

$periodoTxt = [];
if ($fAnio !== '') $periodoTxt[] = $fAnio;
if ($fMes !== '')  $periodoTxt[] = strtoupper(getNombreMes((int)$fMes));

$celdasReporte = maternoCeldasExport($reporte, implode(' - ', $periodoTxt), $nombreEst);

// ==================== MAPEO DE LAS 3 PLANTILLAS ====================
// El mapeo usa cache en disco (cache/plano/): la primera exportacion parsea
// los xlsx y guarda el resultado en JSON; las siguientes reutilizan la cache
// y solo la regeneran si cambia un archivo fuente.
try {
    // 1) Referencia de ubicacion: [celdaOrigen => numeroDestino], ej: C16 => 4
    $mapaUbicacion = maternoPlanoMapaUbicacion($ubicacionPath);

    // 2) Referencia del destino: [numeroDestino => columna], ej: 4 => D (fila 12 del plano)
    $indicePlano = maternoPlanoIndiceDestino($planoPath);

    // 3) Cruce: dato de C16 (celda equivalente del espejo) -> columna D -> celda D13
    $celdasPlano = maternoPlanoConstruirCeldas($celdasReporte, $mapaUbicacion, $indicePlano, MATERNO_PLANO_FILA_DATOS, MATERNO_PLANO_OFFSET_FILA);

    // 4) Seccion IX-3 (TAMIZAJE VIH EN PARTO, numeros 498-501): sin zona en la
    //    plantilla oficial, se llena con los totales de RPT09_3.
    $celdasPlano += maternoPlanoCeldasIX3($reporte, $indicePlano, MATERNO_PLANO_FILA_DATOS);
} catch (Throwable $e) {
    maternoPlanoError('Error al mapear las plantillas del plano', htmlspecialchars($e->getMessage()), $filtros);
}

if (empty($celdasPlano)) {
    maternoPlanoError('El plano quedaria vacio',
        'No se encontro correspondencia entre <code>Ubicacion_Filas_Materno.xlsx</code> y los datos del reporte. '
        . 'Verifique que el archivo de ubicacion corresponde a la plantilla actual.', $filtros);
}

// ==================== IDENTIFICACION DEL REGISTRO (fila de datos) ====================
// Columnas de cabecera del plano (fila 7): A=Codigo_Unico, B=Codigo RENAES y
// C=Establecimiento (numeros 1, 2 y 3 de la fila 12). Se identifican con los
// mismos textos que el flujo del reporte oficial (B6/B8 de materno_export.php).
if ($fEstablecimiento !== '') {
    $celdasPlano['A' . MATERNO_PLANO_FILA_DATOS] = $fEstablecimiento; // Codigo_Unico
    $celdasPlano['B' . MATERNO_PLANO_FILA_DATOS] = $fEstablecimiento; // Codigo RENAES
    $celdasPlano['C' . MATERNO_PLANO_FILA_DATOS] = $nombreEst;        // Nombre del EE.SS
} else {
    $celdasPlano['C' . MATERNO_PLANO_FILA_DATOS] = 'TODOS LOS ESTABLECIMIENTOS DE LA LISTA';
}

// ==================== PERIODO (celda B6 del plano: "MES: ...") ====================
// La cabecera del plano trae "MES: ACUMULADO". Se actualiza con el periodo
// exportado (mismo criterio que la cabecera del reporte web).
$periodoPlano = $periodoTxt ? implode(' - ', $periodoTxt) : '';
if ($fAnio !== '' && $fMes === '') {
    $periodoPlano .= ' - ACUMULADO';
}
$celdasPlano['B6'] = $periodoPlano !== '' ? $periodoPlano : 'ACUMULADO';

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($planoPath);
$filler->setCellValues($celdasPlano);

$mesTxt2 = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Materno_Plano_{$fAnio}{$mesTxt2}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
