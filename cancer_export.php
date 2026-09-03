<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Exportar Reporte Operacional CANCER a Excel (.xlsx) - PLANTILLA OFICIAL
 *
 * Genera el archivo Excel usando como base la plantilla oficial
 * "Reporte_Actividades_Cancer.xlsx" (copiar a uploads/Reporte_Actividades_Cancer.xlsx)
 * y llenando las celdas con los datos calculados por includes/cancer_data.php
 * (adaptacion web de los procedimientos del archivo "03 Creacion de Procedimientos").
 *
 * MAPA DE CELDAS (hoja "Plantilla", igual que el flujo ODBC original):
 *   Encabezado:
 *     B4 = PERIODO (texto), B6 = Codigo RENAES,
 *     D5 = Establecimiento / IPRESS seleccionado (celda combinada D5:N6:
 *     el valor debe escribirse en D5, que es el ancla de la combinacion;
 *     escribir en D6 no se visualiza porque no es la celda ancla)
 *   Seccion 1  MUJERES TAMIZADAS CUELLO UTERINO: filas 11-24, cols D..O
 *   Seccion 2  MUJERES TAMIZADAS CANCER MAMA:    filas 29-72, cols D..K
 *   Seccion 4  OTROS CANCERES:                   filas 96-123, cols D..AC
 *   Seccion 5  PROCEDIMIENTOS DIAGNOSTICO:       filas 129-191, cols D..AI
 *   Seccion 6  LESIONES PRE MALIGNAS:            filas 197-206, cols D..O
 *   Seccion 10 CONSEJERIAS:                      filas 313-320, cols D..AG
 *   Seccion 15 ATENDIDOS SEGUN TIPO DE CANCER:   filas 374-383, cols B..AI
 *   Seccion 17 ATENCIONES CANCER ADULTO:         fila 403, cols B..AI
 *   Seccion 19 DETECCION TEMPRANA INFANTIL:      filas 427-431, cols C..H
 *   (Las secciones restantes de la plantilla quedan en 0, igual que en el
 *    flujo original: no tienen procedimiento asociado.)
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/cancer_data.php';
require_once 'includes/ExcelTemplateFiller.php';

// ==================== FILTROS (mismos parametros que reporte_cancer.php) ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');

if ($fAnio === '') {
    $pdo = getDBConnection();
    $anios = cancerGetAniosDisponibles($pdo);
    $fAnio = $anios[0] ?? date('Y');
}

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
];

// ==================== EJECUTAR REPORTE ====================
$reporte = cancerEjecutarReporte(getDBConnection(), $filtros);
if (!empty($reporte['error'])) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#c0392b">Error al generar el reporte</h3>';
    echo '<p>' . htmlspecialchars($reporte['error']) . '</p>';
    echo '<p><a href="reporte_cancer.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== PLANTILLA ====================
$templatePath = __DIR__ . '/uploads/Reporte_Actividades_Cancer.xlsx';
if (!is_readable($templatePath)) {
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Plantilla no encontrada</title></head><body style="font-family:sans-serif">';
    echo '<h3 style="color:#e67e22">Plantilla oficial no encontrada</h3>';
    echo '<p>Para exportar el reporte con el diseno oficial, copie el archivo';
    echo ' <code>Reporte_Actividades_Cancer.xlsx</code> a la carpeta <code>uploads/</code> del sistema:</p>';
    echo '<pre style="background:#f4f6f7;padding:1rem;border-radius:6px;">uploads/Reporte_Actividades_Cancer.xlsx</pre>';
    echo '<p>Luego vuelva a pulsar <em>Exportar Excel</em>.</p>';
    echo '<p><a href="reporte_cancer.php?' . http_build_query($filtros) . '">&laquo; Volver al reporte</a></p>';
    echo '</body></html>';
    exit;
}

// ==================== AYUDANTES DE COLUMNAS ====================
/** Convierte indice de columna (1=A) a letra Excel. */
function cnrCol(int $n): string {
    $s = '';
    while ($n > 0) {
        $m = ($n - 1) % 26;
        $s = chr(65 + $m) . $s;
        $n = intdiv($n - $m - 1, 26);
    }
    return $s;
}

/** Valor de celda (m1/m2) de una fila del reporte para (sexo, gedad). */
function cnrVal(array $fila, ?string $sexo, $gedad, bool $esAte): array {
    if (!empty($fila['cero'])) return [0, 0];
    $v = $fila['valores'];
    $cell = null;
    if ($sexo !== null) {
        $cell = $v[$sexo][$gedad] ?? null;
    } else {
        foreach ($v as $vx) { if (isset($vx[$gedad])) { $cell = $vx[$gedad]; break; } }
    }
    if ($cell === null) return [0, 0];
    return $esAte
        ? [(int)$cell['atenciones'], (int)$cell['atendidos']]
        : [(int)$cell['casos'], (int)$cell['personas']];
}

/** Escribe una fila del reporte en la fila Excel indicada. */
function cnrEscribirFila(array &$cells, array $fila, int $row, array $mapa): void {
    $esAte = $mapa['esAte'];
    // Totales
    $t1 = $esAte ? $fila['total']['atenciones'] : $fila['total']['casos'];
    $t2 = $esAte ? $fila['total']['atendidos'] : $fila['total']['personas'];
    $cells[cnrCol($mapa['colT1']) . $row] = (int)$t1;
    $cells[cnrCol($mapa['colT2']) . $row] = (int)$t2;
    // Grupos de edad (+ sexo si aplica)
    $col = $mapa['colIni'];
    foreach ($mapa['gedades'] as $g) {
        $sexos = $mapa['conSexo'] ? ['M', 'F'] : [null];
        foreach ($sexos as $sx) {
            [$m1, $m2] = cnrVal($fila, $sx, $g['key'], $esAte);
            $cells[cnrCol($col) . $row] = $m1;
            $cells[cnrCol($col + 1) . $row] = $m2;
            $col += 2;
        }
    }
}

// ==================== MAPEO SECCION -> FILAS/COLUMNAS DEL EXCEL ====================
// (posicional: la fila i-esima de cada seccion del motor coincide con el orden
//  de las filas de la plantilla oficial)
$mapas = [
    'RPT01_01' => ['filaIni' => 11,  'colT1' => 4,  'colT2' => 5,  'colIni' => 6,  'conSexo' => false],
    'RPT01_02' => ['filaIni' => 29,  'colT1' => 4,  'colT2' => 5,  'colIni' => 6,  'conSexo' => false],
    'RPT01_04' => ['filaIni' => 96,  'colT1' => 4,  'colT2' => 5,  'colIni' => 6,  'conSexo' => true],
    'RPT02_01' => ['filaIni' => 129, 'colT1' => 4,  'colT2' => 5,  'colIni' => 6,  'conSexo' => true],
    'RPT02_02' => ['filaIni' => 197, 'colT1' => 4,  'colT2' => 5,  'colIni' => 6,  'conSexo' => false],
    'RPT04_01' => ['filaIni' => 313, 'colT1' => 4,  'colT2' => 5,  'colIni' => 6,  'conSexo' => true],
    'RPT06_01' => ['filaIni' => 374, 'colT1' => 2,  'colT2' => 3,  'colIni' => 4,  'conSexo' => true],
    'RPT06_03' => ['filaIni' => 403, 'colT1' => 2,  'colT2' => 3,  'colIni' => 4,  'conSexo' => true],
    'RPT07_01' => ['filaIni' => 427, 'colT1' => 3,  'colT2' => 4,  'colIni' => 5,  'conSexo' => true],
];

$cells = [];

// ==================== ENCABEZADO DEL REPORTE ====================
// B4 = PERIODO (texto "AAAA - MES"; igual que el flujo ODBC original, pero
// en la celda B4 junto a la etiqueta PERIODO de A4 en lugar de la combinada C4:J4).
$periodoTxt = [];
if ($fAnio !== '') $periodoTxt[] = $fAnio;
if ($fMes !== '')  $periodoTxt[] = strtoupper(getNombreMes((int)$fMes));
$cells['B4'] = $periodoTxt ? implode(' - ', $periodoTxt) : 'TODOS LOS PERIODOS';

// D5 = dato del select "establecimiento" del filtro (nombre del IPRESS).
// D5 es el ANCLA de la celda combinada D5:N6 de la plantilla: escribiendo en
// D5 el nombre queda visible en el recuadro junto a la etiqueta IPRESS (C6).
// Si no se filtro por establecimiento se indica "TODOS LOS ESTABLECIMIENTOS"
// (mismo criterio que PERIODO con "TODOS LOS PERIODOS").
$cells['D5'] = 'TODOS LOS ESTABLECIMIENTOS';
if ($fEstablecimiento !== '') {
    $cells['B6'] = $fEstablecimiento; // CODIGO RENAES
    $pdo = getDBConnection();
    $est = cancerGetEstablecimientosZS($pdo);
    // Dato del select "establecimiento": el nombre mostrado en la opcion; si el
    // codigo ya no esta en el catalogo ZSPERENE se usa el propio codigo como respaldo.
    $cells['D5'] = $est[$fEstablecimiento] ?? $fEstablecimiento;
}

// Datos por seccion
foreach ($reporte['secciones'] as $sec) {
    if (!isset($mapas[$sec['codigo']])) continue;
    $mapa = $mapas[$sec['codigo']];
    $mapa['esAte']   = ($sec['medidas'][0] === 'atenciones');
    $mapa['gedades'] = $sec['gedades'];
    $row = $mapa['filaIni'];
    foreach ($sec['filas'] as $fila) {
        cnrEscribirFila($cells, $fila, $row, $mapa);
        $row++;
    }
}

// ==================== GENERAR DESCARGA ====================
$filler = new ExcelTemplateFiller($templatePath);
$filler->setCellValues($cells);

$mesTxt = $fMes !== '' ? '_' . str_pad($fMes, 2, '0', STR_PAD_LEFT) : '';
$eessTxt = $fEstablecimiento !== '' ? '_' . preg_replace('/[^A-Za-z0-9]/', '', $fEstablecimiento) : '';
$nombre = "Reporte_Actividades_Cancer_{$fAnio}{$mesTxt}{$eessTxt}.xlsx";
$filler->download($nombre);
exit;
