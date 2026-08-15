<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Exportar Reporte Operacional ESNI a Excel (.xlsx) - PLANTILLA OFICIAL
 *
 * Genera un archivo Excel usando como base la plantilla oficial "Plantilla.xlsx"
 * (ubicada en uploads/Plantilla.xlsx) y llenando las celdas con los datos del
 * reporte ESNI generado por el motor data-driven.
 *
 * Mapeo de celdas (Seccion A - Menores de 01 anio):
 *
 *   ENCABEZADO (filtros seleccionados por el usuario):
 *     C2 = Establecimiento (nombre)
 *     H2 = Mes (nombre, ej: "Enero")
 *     L2 = Anio
 *
 *   BCG (dosis unica -> G=Casos, J=Total=Casos):
 *     G7  = BCG - 24 HORAS (Casos)
 *     J7  = BCG - 24 HORAS (Total = G7)
 *     G8  = BCG - 28 DIAS (Casos)
 *     J8  = BCG - 28 DIAS (Total = G8)
 *     G9  = BCG - DE 01M A 11M 29D (Casos)
 *     J9  = BCG - DE 01M A 11M 29D (Total = G9)
 *
 *   HEPATITIS VIRAL B (dosis unica -> G=Casos, J=Total=Casos):
 *     G10 = HEPATITIS VIRAL B - 12 HORAS (Casos)
 *     J10 = HEPATITIS VIRAL B - 12 HORAS (Total = G10)
 *     G11 = HEPATITIS VIRAL B - 24 HORAS (Casos)
 *     J11 = HEPATITIS VIRAL B - 24 HORAS (Total = G11)
 *
 *   ANTIPOLIO IPV (3 dosis -> G=1ra, H=2da, I=3ra, J=Total=G+H+I):
 *     G13 = ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS
 *     H13 = ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS
 *     I13 = ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS
 *     J13 = G13 + H13 + I13
 *
 *   PENTAVALENTE (3 dosis -> G=1ra, H=2da, I=3ra, J=Total=G+H+I):
 *     G15 = PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS
 *     H15 = PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS
 *     I15 = PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS
 *     J15 = G15 + H15 + I15
 *
 *   ROTAVIRUS (2 dosis -> G=1ra, H=2da, J=Total=G+H):
 *     G20 = ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS
 *     H20 = ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS
 *     J20 = G20 + H20
 *
 *   NEUMOCOCO (2 dosis -> G=1ra, H=2da, J=Total=G+H):
 *     G21 = NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS
 *     H21 = NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS
 *     J21 = G21 + H21
 *
 *   INFLUENZA (2 dosis -> G=1ra, H=2da, J=Total=G+H):
 *     G22 = INFLUENZA - 06 Y 07 MESES - 1RA DOSIS
 *     H22 = INFLUENZA - 06 Y 07 MESES - 2DA DOSIS
 *     J22 = G22 + H22
 *
 * Si en el futuro se requiere llenar celdas de otras secciones (B, C, ...),
 * basta con extender el array $cellMap mas abajo, indicando la etiqueta
 * exacta de la linea (campo ESNI_LINEA_REPORTE.etiqueta) y la celda destino.
 */

require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/esni_data.php';
require_once 'includes/ExcelTemplateFiller.php';

// ============================================================================
// 0. Validar que exista la plantilla en uploads/Plantilla.xlsx
// ============================================================================
$templatePath = __DIR__ . '/uploads/Plantilla.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    htmlError(
        'Falta la plantilla Excel',
        'No se encontro <code>uploads/Plantilla.xlsx</code> en el servidor.<br><br>' .
        '<b>Soluci&oacute;n:</b> Suba el archivo <code>Plantilla.xlsx</code> a la carpeta <code>uploads/</code> del proyecto mediante FTP o el administrador de archivos del hosting.<br><br>' .
        'Ruta esperada: <code>' . htmlspecialchars($templatePath) . '</code>'
    );
}

// Verificar que la extension ZipArchive este disponible
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    htmlError(
        'Extension ZIP no disponible',
        'El servidor PHP no tiene cargada la extension <code>zip</code> (clase <code>ZipArchive</code>).<br><br>' .
        '<b>Soluci&oacute;n:</b> En InfinityFree esto se activa desde el panel de control &rarr; PHP Configuration &rarr; marcar "zip". En otros hostings, editar <code>php.ini</code> y agregar <code>extension=zip</code>.'
    );
}

// Crear el directorio uploads/tmp/ con permisos adecuados (si no existe)
$uploadsTmp = __DIR__ . '/uploads/tmp';
if (!is_dir($uploadsTmp)) {
    @mkdir($uploadsTmp, 0755, true);
}

// ============================================================================
// 1. Filtros (los mismos que reporte_esni.php)
// ============================================================================
$pdo = getDBConnection();

if (!esniEsquemaInstalado($pdo)) {
    http_response_code(500);
    die('Esquema ESNI no instalado. Ejecute Database/install_esni.sql primero.');
}

// Estrategia fija del modulo ESNI: solo Id_Ups = 301204 (Inmunizaciones)
define('ESNI_ID_UPS', '301204');

$filtros = [
    'anio'            => trim($_GET['anio'] ?? ''),
    'mes'             => trim($_GET['mes'] ?? ''),
    'establecimiento' => trim($_GET['establecimiento'] ?? ''),
    'profesional'     => trim($_GET['profesional'] ?? ''),
    'id_ups'          => ESNI_ID_UPS,
];

// El valor de "establecimiento" llega como Codigo_Unico (cargado desde ZSPERENE).
// Se resuelve el nombre para mostrarlo en C2.
$nombreEstablecimiento = '';
if ($filtros['establecimiento'] !== '') {
    $estExport = esniGetEstablecimientosZS($pdo);
    $nombreEstablecimiento = $estExport[$filtros['establecimiento']] ?? $filtros['establecimiento'];
}

// ============================================================================
// 2. Ejecutar reporte ESNI
// ============================================================================
$cols = esniResolverColumnas($pdo);
$reporte = esniEjecutarReporte($pdo, $filtros, $cols);

if (!empty($reporte['error'])) {
    http_response_code(500);
    die('Error al generar reporte: ' . $reporte['error']);
}

// ============================================================================
// 3. Indexar lineas de la SECCION A por etiqueta normalizada
// ----------------------------------------------------------------------------
// El motor de reglas devuelve $reporte['secciones'] con todas las secciones
// (A, B, C, H, ...). Nos interesa la seccion "A" (codigo = 'A').
//
// La etiqueta de la linea puede tener ligeras variaciones (espacios extra,
// Mayusculas) respecto a la nomenclatura de la plantilla. Por eso se
// normaliza con una funcion que:
//   - Pasa a MAYUSCULAS
//   - Colapsa espacios multiples
//   - Quita espacios al inicio/final
// ============================================================================

$seccionA = null;
foreach ($reporte['secciones'] as $sec) {
    if (strcasecmp($sec['codigo'], 'A') === 0) {
        $seccionA = $sec;
        break;
    }
}

// Construir mapa [etiqueta_normalizada => cantidad]
$casosPorEtiqueta = [];
if ($seccionA !== null) {
    foreach ($seccionA['lineas'] as $lin) {
        $etqNorm = esniNormalizarEtiqueta($lin['etiqueta']);
        // Si la etiqueta ya existe (no deberia), sumamos las cantidades
        // para ser tolerantes con configuraciones que registren la misma
        // vacuna en varias lineas con la misma etiqueta.
        if (isset($casosPorEtiqueta[$etqNorm])) {
            $casosPorEtiqueta[$etqNorm] += (int)$lin['cantidad'];
        } else {
            $casosPorEtiqueta[$etqNorm] = (int)$lin['cantidad'];
        }
    }
}

/**
 * Helper: obtiene la cantidad de casos para una etiqueta de linea.
 * Devuelve 0 si la etiqueta no existe en la seccion A (no se registraron
 * dosis para esa vacuna/dosis/grupo).
 *
 * @param array  $casosPorEtiqueta Mapa [etiqueta_normalizada => int].
 * @param string $etiqueta         Etiqueta tal como esta en ESNI_LINEA_REPORTE.
 * @return int
 */
function esniGetCasos(array $casosPorEtiqueta, string $etiqueta): int
{
    $etqNorm = esniNormalizarEtiqueta($etiqueta);
    return $casosPorEtiqueta[$etqNorm] ?? 0;
}

/**
 * Normaliza una etiqueta de linea para comparacion robusta.
 *   - MAYUSCULAS
 *   - Colapsa espacios multiples a uno solo
 *   - Quita espacios al inicio/final
 *   - Quita asterisco inicial "*" (marcador de "linea informativa")
 *
 * @param string $etiqueta
 * @return string
 */
function esniNormalizarEtiqueta(string $etiqueta): string
{
    $s = trim($etiqueta);
    // Quitar asterisco inicial si lo hay (lineas informativas como "* Personal de Salud")
    $s = preg_replace('/^\*\s*/', '', $s);
    // PASAR A MAYUSCULAS (preserva acentos)
    $s = mb_strtoupper($s, 'UTF-8');
    // Colapsar espacios multiples
    $s = preg_replace('/\s+/', ' ', $s);
    return $s;
}

// ============================================================================
// 4. Mapear lineas de seccion A a celdas de la plantilla
// ----------------------------------------------------------------------------
// El array $cellMap define, para cada celda destino de la plantilla, la
// etiqueta exacta de la linea (en ESNI_LINEA_REPORTE.etiqueta) de donde se
// toma el valor "Casos".
//
// Las celdas J (totales) se calculan despues como suma de las G/H/I
// correspondientes, salvo en los casos de dosis unica (BCG, Hepatitis B)
// donde J = G (la unica dosis aplicada).
// ============================================================================

// 4.1 Encabezado (filtros seleccionados por el usuario)
$nombreMes = $filtros['mes'] !== ''
    ? getNombreMes((int)$filtros['mes'])
    : 'TODOS';
$nombreAnio = $filtros['anio'] !== '' ? $filtros['anio'] : 'TODOS';
$nombreEst = $nombreEstablecimiento !== '' ? $nombreEstablecimiento : 'TODOS';

// 4.2 Casos por linea (Seccion A)
$bcg_24h    = esniGetCasos($casosPorEtiqueta, 'BCG - 24 HORAS');
$bcg_28d    = esniGetCasos($casosPorEtiqueta, 'BCG - 28 DIAS');
$bcg_01_11m = esniGetCasos($casosPorEtiqueta, 'BCG - DE 01M A 11M 29D');

$vhb_12h    = esniGetCasos($casosPorEtiqueta, 'HEPATITIS VIRAL B - 12 HORAS');
$vhb_24h    = esniGetCasos($casosPorEtiqueta, 'HEPATITIS VIRAL B - 24 HORAS');

$ipv_d1     = esniGetCasos($casosPorEtiqueta, 'ANTIPOLIO - IPV - 02 Y 04 MESES - 1RA DOSIS');
$ipv_d2     = esniGetCasos($casosPorEtiqueta, 'ANTIPOLIO - IPV - 02 Y 04 MESES - 2DA DOSIS');
$ipv_d3     = esniGetCasos($casosPorEtiqueta, 'ANTIPOLIO - IPV - 06 MESES - 3RA DOSIS');

$penta_d1   = esniGetCasos($casosPorEtiqueta, 'PENTAVALENTE - 02, 04 Y 06 MESES - 1RA DOSIS');
$penta_d2   = esniGetCasos($casosPorEtiqueta, 'PENTAVALENTE - 02, 04 Y 06 MESES - 2DA DOSIS');
$penta_d3   = esniGetCasos($casosPorEtiqueta, 'PENTAVALENTE - 02, 04 Y 06 MESES - 3RA DOSIS');

$rota_d1    = esniGetCasos($casosPorEtiqueta, 'ROTAVIRUS - 02 Y 04 MESES - 1RA DOSIS');
$rota_d2    = esniGetCasos($casosPorEtiqueta, 'ROTAVIRUS - 02 Y 04 MESES - 2DA DOSIS');

$neumo_d1   = esniGetCasos($casosPorEtiqueta, 'NEUMOCOCO - 02 Y 04 MESES - 1RA DOSIS');
$neumo_d2   = esniGetCasos($casosPorEtiqueta, 'NEUMOCOCO - 02 Y 04 MESES - 2DA DOSIS');

$inf_d1     = esniGetCasos($casosPorEtiqueta, 'INFLUENZA - 06 Y 07 MESES - 1RA DOSIS');
$inf_d2     = esniGetCasos($casosPorEtiqueta, 'INFLUENZA - 06 Y 07 MESES - 2DA DOSIS');

// 4.3 Calcular totales por vacuna (celdas J)
$ipv_total     = $ipv_d1   + $ipv_d2   + $ipv_d3;
$penta_total   = $penta_d1 + $penta_d2 + $penta_d3;
$rota_total    = $rota_d1  + $rota_d2;
$neumo_total   = $neumo_d1 + $neumo_d2;
$inf_total     = $inf_d1   + $inf_d2;

// 4.4 Construir el mapa final celda => valor
$cellValues = [
    // Encabezado (texto)
    'C2' => $nombreEst,
    'H2' => $nombreMes,
    'L2' => $nombreAnio,

    // BCG (dosis unica -> G = J = Casos)
    'G7'  => $bcg_24h,    'J7'  => $bcg_24h,
    'G8'  => $bcg_28d,    'J8'  => $bcg_28d,
    'G9'  => $bcg_01_11m, 'J9'  => $bcg_01_11m,

    // Hepatitis Viral B (dosis unica -> G = J = Casos)
    'G10' => $vhb_12h,    'J10' => $vhb_12h,
    'G11' => $vhb_24h,    'J11' => $vhb_24h,

    // Antipolio IPV (3 dosis + total)
    'G13' => $ipv_d1, 'H13' => $ipv_d2, 'I13' => $ipv_d3, 'J13' => $ipv_total,

    // Pentavalente (3 dosis + total)
    'G15' => $penta_d1, 'H15' => $penta_d2, 'I15' => $penta_d3, 'J15' => $penta_total,

    // Rotavirus (2 dosis + total)
    'G20' => $rota_d1, 'H20' => $rota_d2, 'J20' => $rota_total,

    // Neumococo (2 dosis + total)
    'G21' => $neumo_d1, 'H21' => $neumo_d2, 'J21' => $neumo_total,

    // Influenza (2 dosis + total)
    'G22' => $inf_d1, 'H22' => $inf_d2, 'J22' => $inf_total,
];

// ============================================================================
// 5. Llenar la plantilla y descargar
// ============================================================================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cellValues);

$nombreArchivo = 'Reporte_ESNI_Plantilla_'
               . ($filtros['anio'] !== '' ? $filtros['anio'] : 'all')
               . ($filtros['mes'] !== '' ? '_' . str_pad($filtros['mes'], 2, '0', STR_PAD_LEFT) : '')
               . '_' . date('Ymd_His')
               . '.xlsx';

try {
    $filler->download($nombreArchivo);
} catch (Throwable $e) {
    http_response_code(500);
    htmlError(
        'Error al generar el Excel',
        'No se pudo generar el archivo Excel.<br><br>' .
        '<b>Error tecnico:</b><br>' .
        '<pre style="background:#f8f9fa;padding:.6rem;border-radius:.25rem;overflow:auto;">' .
        htmlspecialchars($e->getMessage()) . '</pre><br>' .
        '<b>Posibles causas:</b><br>' .
        '<ul>' .
        '<li>El directorio <code>uploads/tmp/</code> no existe o no es escribible. Cree la carpeta con permisos 0755 (o 0777).</li>' .
        '<li>La plantilla <code>uploads/Plantilla.xlsx</code> esta corrupta o no es un .xlsx valido.</li>' .
        '<li>El hosting tiene funciones restringidas (tempnam, copy, etc.). Ejecute <a href="esni_diagnostic.php">esni_diagnostic.php</a> para ver el detalle.</li>' .
        '</ul>'
    );
}
exit;

/**
 * Muestra un error HTML amigable y termina la ejecucion.
 *
 * @param string $titulo  Titulo corto del error.
 * @param string $mensaje Mensaje HTML con detalles y soluciones.
 */
function htmlError(string $titulo, string $mensaje): void
{
    // Asegurar que no haya salida previa que rompa el HTML
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
       . '<title>Error - ' . htmlspecialchars($titulo) . '</title>'
       . '<style>'
       . 'body{font-family:Arial,Helvetica,sans-serif;background:#f5f5f5;margin:0;padding:20px;color:#333;}'
       . '.container{max-width:720px;margin:40px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);}'
       . 'h1{color:#dc3545;margin:0 0 16px 0;font-size:1.5rem;}'
       . '.icon{font-size:48px;color:#dc3545;margin-bottom:16px;}'
       . 'pre{font-family:Consolas,monospace;font-size:.85rem;}'
       . 'a{color:#0d6efd;}'
       . '</style></head><body>'
       . '<div class="container">'
       . '<div class="icon">&#9888;</div>'
       . '<h1>' . htmlspecialchars($titulo) . '</h1>'
       . '<div style="line-height:1.6;">' . $mensaje . '</div>'
       . '<hr style="margin:24px 0;border:none;border-top:1px solid #eee;">'
       . '<p style="font-size:.85rem;color:#6c757d;margin:0;">'
       . 'Sistema de Gestion de Datos HIS - Modulo ESNI</p>'
       . '</div></body></html>';
    exit;
}
