<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Exportar Reporte Operacional ESNI a Excel (.xlsx)
 *
 * Genera un archivo Excel con el mismo layout que el archivo oficial MINSA
 * "ReporteActividadesEsni2019.xlsx", reemplazando el flujo:
 *   SQL Server -> 4 .txt -> Excel con conexion ODBC.
 *
 * El reporte se genera al momento desde el motor data-driven.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/esni_data.php';
require_once 'includes/ExcelWriter.php';

$pdo = getDBConnection();

if (!esniEsquemaInstalado($pdo)) {
    http_response_code(500);
    die('Esquema ESNI no instalado. Ejecute Database/install_esni.sql primero.');
}

// Estrategia fija del modulo ESNI: solo Id_Ups = 301204 (Inmunizaciones)
define('ESNI_ID_UPS', '301204');

// Filtros
$filtros = [
    'anio'            => trim($_GET['anio'] ?? ''),
    'mes'             => trim($_GET['mes'] ?? ''),
    'establecimiento' => trim($_GET['establecimiento'] ?? ''),
    'profesional'     => trim($_GET['profesional'] ?? ''),
    'id_ups'          => ESNI_ID_UPS,
];

// El valor de "establecimiento" llega como Codigo_Unico (cargado desde ZSPERENE).
// Se resuelve el nombre para mostrarlo en los metadatos del Excel.
$nombreEstablecimientoExport = '';
if ($filtros['establecimiento'] !== '') {
    $estExport = esniGetEstablecimientosZS($pdo);
    $nombreEstablecimientoExport = $estExport[$filtros['establecimiento']] ?? $filtros['establecimiento'];
}

$cols = esniResolverColumnas($pdo);
$reporte = esniEjecutarReporte($pdo, $filtros, $cols);

if (!empty($reporte['error'])) {
    http_response_code(500);
    die('Error al generar reporte: ' . $reporte['error']);
}

// === Generar archivo .xlsx con ExcelWriter (sin dependencias externas) ===
$ew = new ExcelWriter();

// Cabecera del reporte
$filtrosTxt = [];
if (!empty($filtros['anio']))            $filtrosTxt[] = 'Anio: ' . $filtros['anio'];
if (!empty($filtros['mes']))             $filtrosTxt[] = 'Mes: ' . getNombreMes((int)$filtros['mes']);
if (!empty($filtros['establecimiento'])) $filtrosTxt[] = 'EE.SS.: ' . $nombreEstablecimientoExport;
if (!empty($filtros['profesional']))     $filtrosTxt[] = 'Profesional: ' . $filtros['profesional'];
$filtrosStr = empty($filtrosTxt) ? 'TODOS' : implode(' | ', $filtrosTxt);

// --- Hoja 1: Reporte Operacional (mismo layout que el Excel oficial) ---
$headers = ['N°', 'Sección', 'Tipo de Vacuna / Dosis', 'Vacuna', 'Dosis', 'Grupo Edad', 'Sexo', 'Cantidad'];
$rows = [];
$n = 0;
foreach ($reporte['secciones'] as $sec) {
    foreach ($sec['lineas'] as $lin) {
        $n++;
        $rows[] = [
            $n,
            $sec['codigo'],
            $lin['etiqueta'],
            $lin['vacuna_codigo'] ?: '-',
            $lin['dosis_codigo'] ?: '-',
            $lin['grupo_edad_codigo'] ?: '-',
            $lin['sexo'] === 'F' ? 'Mujer' : ($lin['sexo'] === 'M' ? 'Varon' : 'Ambos'),
            (int)$lin['cantidad'],
        ];
    }
    // Subtotal por seccion
    $rows[] = ['', '', 'TOTAL SECCION ' . $sec['codigo'], '', '', '', '', (int)$sec['total']];
}
// Total general
$rows[] = ['', '', 'TOTAL GENERAL', '', '', '', '', (int)$reporte['totales']['total_dosis']];

$ew->addSheet('Reporte ESNI', $headers, $rows, [6, 10, 50, 12, 10, 14, 8, 12]);

// --- Hoja 2: Resumen por Seccion ---
$headers2 = ['Sección', 'Título', 'Layout', 'N° Líneas', 'Total Casos', '% del Total'];
$rows2 = [];
$totalGral = max(1, $reporte['totales']['total_dosis']);
foreach ($reporte['secciones'] as $sec) {
    $rows2[] = [
        $sec['codigo'],
        $sec['titulo'],
        $sec['layout'],
        count($sec['lineas']),
        (int)$sec['total'],
        round(($sec['total'] / $totalGral) * 100, 2) . '%',
    ];
}
$rows2[] = ['', 'TOTAL GENERAL', '', '', (int)$reporte['totales']['total_dosis'], '100%'];
$ew->addSheet('Resumen', $headers2, $rows2, [10, 60, 16, 10, 14, 12]);

// --- Hoja 3: Filtros y metadatos ---
$headers3 = ['Campo', 'Valor'];
$rows3 = [
    ['Titulo del Reporte', 'INFORME ANALITICO DE INMUNIZACIONES'],
    ['Sistema', 'IntelHIS - Sistema de Gestion de Datos HIS-MINSA'],
    ['Fecha de generacion', date('d/m/Y H:i:s')],
    ['Usuario', $_SESSION['usuario'] ?? 'sistema'],
    ['Tabla origen', $cols['_tabla'] ?? '?'],
    ['Filtros aplicados', $filtrosStr],
    ['Total de dosis', (int)$reporte['totales']['total_dosis']],
    ['Total lineas con datos', (int)$reporte['totales']['total_lineas_con_datos']],
    ['Filas HIS leidas', (int)$reporte['filas_leidas']],
    ['Tiempo de ejecucion (s)', $reporte['tiempo_ejecucion'] ?? '?'],
    ['', ''],
    ['Modulo', 'ESNI v1.0 - Data-Driven Rule Engine'],
    ['Esquema', 'Includes/esni_data.php'],
];
$ew->addSheet('Metadata', $headers3, $rows3, [25, 80]);

// Generar y descargar
$nombreArchivo = 'Reporte_ESNI_' . date('Ymd_His') . '.xlsx';
$ew->download($nombreArchivo);
exit;
