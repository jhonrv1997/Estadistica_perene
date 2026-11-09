<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 01: Convenio de Gestion MINSA-GORE
 * Consulta y visualizacion de avances (todos los roles).
 *
 * ADAPTACION AL PATRON DEL CONVENIO FED (2026-11-09):
 *  Esta pagina replica la interfaz de dos paneles del modulo Convenio FED
 *  (convenio_fed.php), adaptada a las tablas del Convenio de Gestion:
 *
 *    PANEL 01 - Presentacion:
 *      Avance global del convenio (ponderado por peso_ponderado cuando los
 *      indicadores tengan peso asignado; promedio simple en caso contrario)
 *      y tarjetas de los indicadores activos con: codigo, nombre,
 *      descripcion, meta anual, resultado promedio (valor_resultado),
 *      promedio de porcentaje_avance (acumulado de todos los periodos),
 *      numero de registros y de establecimientos. Cada tarjeta incluye un
 *      boton "Ver detalle (Panel 02)".
 *
 *    PANEL 02 - Detalle del indicador:
 *      Cabecera con codigo, nombre, descripcion, responsable, unidad,
 *      peso y boton de ficha tecnica PDF (si esta adjunta), junto a los
 *      totales (meta anual, promedio de avance, suma de numerador y
 *      denominador). Incluye filtros de anio, mes ("Todos los meses",
 *      "Acumulado anual" o un mes especifico) y establecimiento, la tabla
 *      "Detalle de Avances por Establecimiento" (renipress, establecimiento,
 *      numerador, denominador, resultado promedio y porcentaje de avance,
 *      con busqueda, orden por columna y exportacion CSV), el grafico
 *      "Avance por Mes" (linea de tendencia con Chart.js y selector de
 *      establecimiento) y el Ranking de Establecimientos con barras.
 *
 *  Navegacion "un panel a la vez" (igual que FED v6): al cargar la pagina
 *  solo es visible el PANEL 01; el boton "Ver detalle" de cada tarjeta lleva
 *  a convenio_gestion.php?vista=detalle&ind=ID... y entonces solo es visible
 *  el PANEL 02, que incluye el boton "Regresar" para volver al PANEL 01
 *  conservando anio/mes/est.
 *
 *  NOTAS SOBRE LOS DATOS DEL CONVENIO DE GESTION:
 *   - La tabla CONVENIO_GESTION_AVANCE original no tenia renipress ni
 *     establecimiento: esas columnas se agregan automaticamente (ver
 *     includes/cg_common.php). Los registros ya existentes (consolidados por
 *     periodo, sin establecimiento) se muestran agrupados como
 *     "Consolidado Zona Sanitaria".
 *   - El campo mes permite NULL para el "acumulado anual": el filtro de mes
 *     ofrece por eso tres clases de valor: "Todos los meses" (suma todo el
 *     anio, incluida la fila anual si existe), "Acumulado anual" (solo la
 *     fila consolidada del anio) y cada mes en particular.
 *   - La columna valor_resultado (resultado calculado del indicador, propia
 *     de este convenio) se conserva: se muestra como "Resultado (prom.)" en
 *     las tarjetas y en la tabla de detalle.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/cg_common.php';

/* ============================================================================
 * 0. COMPATIBILIDAD CON URL ANTIGUAS (?tab=gestion | ?tab=importar)
 * ========================================================================== */
$tabLegacy = trim($_GET['tab'] ?? '');
if ($tabLegacy === 'gestion') {
    header('Location: cg_gestion_indicadores.php');
    exit;
}
if ($tabLegacy === 'importar') {
    header('Location: cg_importar_avances.php');
    exit;
}

/* ============================================================================
 * 1. PARAMETROS DE LA VISTA (GET)
 *    ind  (id_indicador del Panel 02; vacio = primer indicador activo)
 *    anio (obligatorio; por defecto el mas reciente con datos)
 *    mes  ('' = Todos los meses, 'anual' = fila consolidada del anio,
 *          '01'..'12' = mes especifico; se acepta tambien sin cero inicial,
 *          ej. mes=3, por compatibilidad con los enlaces de la pagina antigua)
 *    est  (renipress; '' = Todos, '__consolidado__' = filas sin establecimiento)
 * ========================================================================== */

$fAnio = trim($_GET['anio'] ?? '');
$fMes  = trim($_GET['mes'] ?? '');
$fEst  = trim($_GET['est'] ?? '');
$fInd  = trim($_GET['ind'] ?? '');

if ($fInd !== '' && !ctype_digit($fInd)) {
    $fInd = '';
}
// Normalizar el mes: '3' -> '03'; valores validos: '', 'anual', '01'..'12'
if ($fMes !== '' && $fMes !== 'anual') {
    if (ctype_digit($fMes) && (int)$fMes >= 1 && (int)$fMes <= 12) {
        $fMes = str_pad($fMes, 2, '0', STR_PAD_LEFT);
    } else {
        $fMes = '';
    }
}

$aniosDisp = [];
$establecimientosDisp = [];
$tieneConsolidado = false;   // hay registros sin establecimiento (formato antiguo)
$indicadoresFiltro = [];     // indicadores activos (selector del Panel 02)

if ($tablaAvancesCG !== null) {
    $aniosDisp = $pdo->query("SELECT DISTINCT anio FROM `{$tablaAvancesCG}` WHERE anio IS NOT NULL ORDER BY anio DESC")
                     ->fetchAll(PDO::FETCH_COLUMN);
}
if (empty($aniosDisp)) {
    $aniosDisp = [date('Y')];
}
if ($fAnio === '' || !in_array($fAnio, $aniosDisp, true)) {
    // Por defecto: el anio mas reciente con datos (o el anio actual)
    $fAnio = in_array((string)date('Y'), array_map('strval', $aniosDisp), true) ? (string)date('Y') : (string)$aniosDisp[0];
}

if ($tablaAvancesCG !== null && $tieneRenipressCG) {
    $rowsEst = $pdo->query("SELECT renipress, establecimiento FROM `{$tablaAvancesCG}`
                            GROUP BY renipress, establecimiento ORDER BY establecimiento ASC")->fetchAll();
    foreach ($rowsEst as $r) {
        if ($r['renipress'] === null || $r['renipress'] === '' || $r['establecimiento'] === null || $r['establecimiento'] === '') {
            $tieneConsolidado = true; // filas antiguas sin establecimiento
        } else {
            $establecimientosDisp[] = $r;
        }
    }
}
if ($tablaIndicadoresCG !== null) {
    $indicadoresFiltro = $pdo->query("SELECT id_indicador, codigo, nombre FROM `{$tablaIndicadoresCG}`
                                      WHERE estado = 1 ORDER BY orden ASC, codigo ASC")->fetchAll();
}

/* ============================================================================
 * 2. PANEL 01: AGREGADO GLOBAL POR INDICADOR (sin filtros; todos los periodos)
 *    Cada tarjeta muestra: codigo, nombre, descripcion, meta anual, resultado
 *    promedio y promedio total de porcentaje_avance.
 * ========================================================================== */

$panel01 = [];
$avanceGlobalPonderado = null;
$modoAvanceGlobal = '';   // 'ponderado' | 'promedio simple'
$pesoTotal = 0.0;

if ($sistemaCGok) {
    // Columnas opcionales: solo se consultan si existen (auto-reparacion de esquema)
    $colPeso   = $tienePesoCG     ? ', i.peso_ponderado'     : '';
    $colFicha  = $tieneFichaPdfCG ? ', i.ficha_tecnica_pdf'  : '';
    $exprNEst  = $tieneRenipressCG ? 'COUNT(DISTINCT a.renipress)' : '0';
    $sql01 = "SELECT i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                     i.meta_anual, i.responsable{$colPeso}{$colFicha},
                     COUNT(a.id_avance)          AS n_reg,
                     AVG(a.porcentaje_avance)    AS pct_prom,
                     AVG(a.valor_resultado)      AS res_prom,
                     SUM(a.valor_numerador)      AS sum_num,
                     SUM(a.valor_denominador)    AS sum_den,
                     {$exprNEst}                 AS n_est
              FROM `{$tablaIndicadoresCG}` i
              LEFT JOIN `{$tablaAvancesCG}` a ON a.id_indicador = i.id_indicador
              WHERE i.estado = 1
              GROUP BY i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                       i.meta_anual, i.responsable{$colPeso}{$colFicha}
              ORDER BY i.orden ASC, i.codigo ASC";
    $panel01 = $pdo->query($sql01)->fetchAll();

    /* Avance global del convenio: ponderado por el peso de cada indicador
     * (como en FED) cuando los indicadores tengan peso asignado; si ningun
     * indicador tiene peso, se usa el promedio simple de los indicadores con
     * datos. */
    $sumaPctSimple = 0.0;
    $nConDato = 0;
    $acumPonderado = null;
    foreach ($panel01 as $ri) {
        $sumNum = $ri['sum_num'] !== null ? (float)$ri['sum_num'] : null;
        $sumDen = $ri['sum_den'] !== null ? (float)$ri['sum_den'] : null;
        $pctCalc = ($sumDen !== null && $sumDen > 0 && $sumNum !== null) ? round(($sumNum / $sumDen) * 100, 2) : null;
        $pct = $pctCalc !== null ? $pctCalc : ($ri['pct_prom'] !== null ? (float)$ri['pct_prom'] : null);
        $peso = ($tienePesoCG && $ri['peso_ponderado'] !== null) ? (float)$ri['peso_ponderado'] : 0;
        if ($pct !== null && (int)$ri['n_reg'] > 0) {
            $sumaPctSimple += $pct;
            $nConDato++;
            if ($peso > 0) {
                $acumPonderado = ($acumPonderado ?? 0) + ($pct * $peso / 100);
                $pesoTotal += $peso;
            }
        }
    }
    if ($pesoTotal > 0 && $acumPonderado !== null) {
        $avanceGlobalPonderado = round(($acumPonderado / $pesoTotal) * 100, 1);
        $modoAvanceGlobal = 'ponderado';
    } elseif ($nConDato > 0) {
        $avanceGlobalPonderado = round($sumaPctSimple / $nConDato, 1);
        $modoAvanceGlobal = 'promedio simple';
    } else {
        $avanceGlobalPonderado = null;
    }
}

/* ============================================================================
 * 3. PANEL 02: INDICADOR SELECCIONADO
 *    Viene de ?ind=ID (enlace de cada tarjeta del Panel 01). Si no llega o es
 *    invalido, se toma el primer indicador activo para que el panel siempre
 *    muestre informacion.
 * ========================================================================== */

$idsActivos = array_map(function ($r) { return (int)$r['id_indicador']; }, $indicadoresFiltro);
$indId = 0;
if ($fInd !== '' && in_array((int)$fInd, $idsActivos, true)) {
    $indId = (int)$fInd;
} elseif (!empty($idsActivos)) {
    $indId = $idsActivos[0];
}

$indSel = null;
foreach ($panel01 as $p) {
    if ((int)$p['id_indicador'] === $indId) {
        $indSel = $p;
        break;
    }
}

/* ============================================================================
 * 4. PANEL 02: CABECERA + DETALLE AGRUPADO POR ESTABLECIMIENTO + RANKING
 *    Aplica los filtros anio (obligatorio), mes ('' = todos, 'anual' = fila
 *    consolidada, '01'..'12' = mes) y establecimiento. El detalle se agrupa
 *    SIEMPRE por establecimiento: numerador y denominador se suman y el
 *    porcentaje se recalcula sobre esos totales. Los registros sin
 *    establecimiento (formato antiguo del convenio) se agrupan como
 *    "Consolidado Zona Sanitaria".
 *    Si la columna renipress no pudo agregarse al esquema (hosting sin
 *    permisos ALTER y sin ejecutar update_gestion_v1.sql), el detalle, el
 *    grafico y el ranking se ocultan con un aviso explicativo.
 * ========================================================================== */

$statsInd = null;         // totales de la cabecera del Panel 02
$detalleEst = [];         // filas agrupadas de la tabla de detalle
$rankingEst = [];         // mismo detalle ordenado de mejor a peor avance
$pctDetalleGlobal = null;
$pctCabecera = null;
$cgTrendJson = [];        // datos del grafico "Avance por Mes" (linea de tendencia)

if ($sistemaCGok && $indSel !== null && $tieneRenipressCG) {
    $con = ['a.id_indicador = ?', 'a.anio = ?'];
    $params = [$indId, $fAnio];
    if ($fMes === 'anual')      { $con[] = "(a.mes IS NULL OR a.mes = '')"; }
    elseif ($fMes !== '')       { $con[] = 'a.mes = ?';       $params[] = $fMes; }
    if ($fEst === '__consolidado__') { $con[] = "(a.renipress IS NULL OR a.renipress = '')"; }
    elseif ($fEst !== '')            { $con[] = 'a.renipress = ?'; $params[] = $fEst; }
    $where = implode(' AND ', $con);

    // --- 4.1 Totales de la cabecera (suma numerador/denominador y promedio) ---
    $st = $pdo->prepare("SELECT COUNT(*) AS n_reg,
                                COUNT(DISTINCT a.renipress) AS n_est,
                                SUM(a.valor_numerador)   AS sum_num,
                                SUM(a.valor_denominador) AS sum_den,
                                AVG(a.porcentaje_avance) AS pct_prom,
                                AVG(a.valor_resultado)   AS res_prom
                         FROM `{$tablaAvancesCG}` a WHERE {$where}");
    $st->execute($params);
    $row = $st->fetch();
    if ($row && (int)$row['n_reg'] > 0) {
        $statsInd = $row;
        $pctCabecera = $row['pct_prom'] !== null ? round((float)$row['pct_prom'], 1) : null;
    }

    // --- 4.2 Detalle de Avances por Establecimiento (agrupado y sumado) ---
    $st = $pdo->prepare("SELECT a.renipress, a.establecimiento, COUNT(*) AS n_reg,
                                SUM(a.valor_numerador)   AS sum_num,
                                SUM(a.valor_denominador) AS sum_den,
                                AVG(a.porcentaje_avance) AS pct_prom,
                                AVG(a.valor_resultado)   AS res_prom
                         FROM `{$tablaAvancesCG}` a WHERE {$where}
                         GROUP BY a.renipress, a.establecimiento
                         ORDER BY a.establecimiento ASC");
    $st->execute($params);
    $detalleEst = $st->fetchAll();
    foreach ($detalleEst as $k => $de) {
        $num = $de['sum_num'] !== null ? (float)$de['sum_num'] : null;
        $den = $de['sum_den'] !== null ? (float)$de['sum_den'] : null;
        if ($den !== null && $den > 0 && $num !== null) {
            $detalleEst[$k]['pct'] = round(($num / $den) * 100, 2);
        } elseif ($de['pct_prom'] !== null) {
            $detalleEst[$k]['pct'] = round((float)$de['pct_prom'], 2);
        } else {
            $detalleEst[$k]['pct'] = null;
        }
        // Nombre de muestra para registros sin establecimiento (formato antiguo)
        $detalleEst[$k]['nombre_mostrar'] = ($de['establecimiento'] !== null && $de['establecimiento'] !== '')
            ? (string)$de['establecimiento'] : 'Consolidado Zona Sanitaria';
        $detalleEst[$k]['renipress_mostrar'] = ($de['renipress'] !== null && $de['renipress'] !== '')
            ? (string)$de['renipress'] : '-';
    }

    // --- 4.3 Ranking de establecimientos: mejor avance primero ---
    $rankingEst = $detalleEst;
    usort($rankingEst, function ($x, $y) {
        $px = $x['pct'];
        $py = $y['pct'];
        if ($px === null && $py === null) { return strcasecmp((string)$x['nombre_mostrar'], (string)$y['nombre_mostrar']); }
        if ($px === null) { return 1; }
        if ($py === null) { return -1; }
        return $py <=> $px;
    });

    // --- 4.4 Porcentaje global del detalle (fila TOTAL de la tabla) ---
    if ($statsInd !== null) {
        $gNum = $statsInd['sum_num'] !== null ? (float)$statsInd['sum_num'] : null;
        $gDen = $statsInd['sum_den'] !== null ? (float)$statsInd['sum_den'] : null;
        if ($gDen !== null && $gDen > 0 && $gNum !== null) {
            $pctDetalleGlobal = round(($gNum / $gDen) * 100, 2);
        } elseif ($statsInd['pct_prom'] !== null) {
            $pctDetalleGlobal = round((float)$statsInd['pct_prom'], 1);
        }
    }

    /* --- 4.5 Serie mensual del grafico "Avance por Mes" (linea de tendencia) ---
     * Datos de establecimiento, mes y porcentaje_avance del indicador y anio
     * del panel (siempre muestra los 12 meses; no depende de los filtros
     * mes/establecimiento del Panel 02). Se generan:
     *   - Serie "__todos__": agregado de TODOS los establecimientos (numerador
     *     y denominador sumados por mes, porcentaje recalculado).
     *   - Una serie por establecimiento con su porcentaje mensual (los
     *     registros sin establecimiento forman la serie "Consolidado Zona
     *     Sanitaria").
     * Todo se envia al navegador como JSON y el cambio de serie se hace con
     * radio-botones en JavaScript (sin recargar la pagina). */
    $filasTrend = [];
    try {
        $st = $pdo->prepare("SELECT a.renipress, a.establecimiento, a.mes,
                                    SUM(a.valor_numerador)   AS num,
                                    SUM(a.valor_denominador) AS den,
                                    AVG(a.porcentaje_avance) AS pct_prom
                             FROM `{$tablaAvancesCG}` a
                             WHERE a.id_indicador = ? AND a.anio = ?
                               AND a.mes IS NOT NULL AND a.mes <> ''
                             GROUP BY a.renipress, a.establecimiento, a.mes
                             ORDER BY a.mes ASC, a.establecimiento ASC");
        $st->execute([$indId, $fAnio]);
        $filasTrend = $st->fetchAll();
    } catch (Throwable $e) {
        $filasTrend = []; // si la consulta falla el grafico simplemente no se muestra
    }

    if (!empty($filasTrend)) {
        $pctTrendMes = function ($num, $den, $pctProm) {
            if ($den !== null && (float)$den > 0 && $num !== null) { return round(((float)$num / (float)$den) * 100, 2); }
            return $pctProm !== null ? round((float)$pctProm, 2) : null;
        };

        $porEst = [];                                   // serie por establecimiento
        $agg     = array_fill(1, 12, ['num' => 0.0, 'den' => 0.0]); // agregado por mes
        foreach ($filasTrend as $ft) {
            $rp = (string)($ft['renipress'] ?? '');
            $m  = (int)$ft['mes'];
            if ($m < 1 || $m > 12) { continue; }
            $esConsolidado = ($rp === '' || $ft['establecimiento'] === null || $ft['establecimiento'] === '');
            $clave = $esConsolidado ? '__consolidado__' : $rp;
            if (!isset($porEst[$clave])) {
                $porEst[$clave] = ['nombre' => $esConsolidado ? 'Consolidado Zona Sanitaria'
                                            : (string)$ft['establecimiento'],
                                   'pct' => array_fill(0, 12, null),
                                   'num' => array_fill(0, 12, null),
                                   'den' => array_fill(0, 12, null)];
            }
            $num = $ft['num'] !== null ? (float)$ft['num'] : 0.0;
            $den = $ft['den'] !== null ? (float)$ft['den'] : 0.0;
            $porEst[$clave]['pct'][$m - 1] = $pctTrendMes($ft['num'], $ft['den'], $ft['pct_prom']);
            $porEst[$clave]['num'][$m - 1] = round($num, 2);
            $porEst[$clave]['den'][$m - 1] = round($den, 2);
            $agg[$m]['num'] += $num;
            $agg[$m]['den'] += $den;
        }

        // Serie agregada de todos los establecimientos (primera opcion)
        $pctTodos = array_fill(0, 12, null);
        $numTodos = array_fill(0, 12, null);
        $denTodos = array_fill(0, 12, null);
        foreach ($agg as $m => $v) {
            if ($v['den'] > 0 || $v['num'] > 0) {
                $pctTodos[$m - 1] = $pctTrendMes($v['num'], $v['den'], null);
                $numTodos[$m - 1] = round($v['num'], 2);
                $denTodos[$m - 1] = round($v['den'], 2);
            }
        }

        $seriesTrend = [];
        $seriesTrend[] = ['key' => '__todos__', 'nombre' => 'Todos los establecimientos',
                          'pct' => $pctTodos, 'num' => $numTodos, 'den' => $denTodos];
        uasort($porEst, function ($a, $b) { return strcasecmp($a['nombre'], $b['nombre']); }); // alfabético
        foreach ($porEst as $clave => $e) {
            $seriesTrend[] = ['key' => $clave, 'nombre' => $e['nombre'],
                              'pct' => $e['pct'], 'num' => $e['num'], 'den' => $e['den']];
        }

        $cgTrendJson = ['anio' => (string)$fAnio, 'series' => $seriesTrend];
    }
}

/* ============================================================================
 * 5. HELPERS DE LA VISTA Y ENLACES ENTRE PANELES
 * ========================================================================== */

/** Formatea numeros (numerador/denominador) tolerando nulos */
function cgFmtNumero($v, $dec = 2) {
    return $v === null ? '-' : number_format((float)$v, $dec);
}

/** Formatea la meta anual quitando ceros sobrantes (95.00 -> 95; 87.50 -> 87.5) */
function cgFmtPctMeta($v) {
    if ($v === null) { return '-'; }
    $f = rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
    return $f === '' ? '0' : $f;
}

/** Etiqueta del filtro de mes para la linea de contexto del Panel 02 */
function cgEtiquetaMes($fMes) {
    if ($fMes === 'anual') { return 'Acumulado anual'; }
    if ($fMes === '')      { return 'Todos los meses'; }
    return getNombreMes((int)$fMes);
}

/** Nombre del establecimiento filtrado (para la linea de contexto del Panel 02) */
$nombreEstFiltro = '';
if ($fEst === '__consolidado__') {
    $nombreEstFiltro = 'Consolidado Zona Sanitaria';
} elseif ($fEst !== '') {
    foreach ($establecimientosDisp as $e) {
        if ((string)$e['renipress'] === (string)$fEst) { $nombreEstFiltro = (string)$e['establecimiento']; break; }
    }
}

/** Query string base para los enlaces Panel 01 -> Panel 02 (conserva filtros) */
$qsPanel2 = [];
if ($fAnio !== '') { $qsPanel2['anio'] = $fAnio; }
if ($fMes  !== '') { $qsPanel2['mes']  = $fMes; }
if ($fEst  !== '') { $qsPanel2['est']  = $fEst; }

/* ----------------------------------------------------------------------------
 * 5.1 VISTA ACTIVA: un solo panel visible a la vez
 *  - Carga por defecto (sin vista y sin ind) -> PANEL 01 visible, PANEL 02 oculto.
 *  - vista=detalle (o enlaces antiguos con ?ind=ID) -> PANEL 02 visible,
 *    PANEL 01 oculto. El boton "Regresar" del Panel 02 vuelve al Panel 01.
 * --------------------------------------------------------------------------- */
$verDetalle = (($_GET['vista'] ?? '') === 'detalle')
           || (($_GET['vista'] ?? '') === '' && $fInd !== '');
if (empty($indicadoresFiltro)) {
    $verDetalle = false;
}

/* URL del boton Regresar del Panel 02 -> Panel 01 (conserva anio/mes/est) */
$urlVolverPanel1 = 'convenio_gestion.php' . (empty($qsPanel2) ? '' : '?' . http_build_query($qsPanel2));

$pageTitle = 'Convenio de Gestion MINSA-GORE - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* ---- Estilos especificos de la vista de consulta del modulo Convenio de Gestion ---- */
html { scroll-behavior: smooth; }
.cg-anchor { scroll-margin-top: 14px; }
.cg-table-detalle th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
.cg-table-detalle th.sortable:hover { background: #e9ecef; }
.cg-table-detalle th.sortable::after { content: "\f0dc"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: .68rem; margin-left: 5px; color: #adb5bd; }
.cg-table-detalle th.sortable.sort-asc::after { content: "\f0de"; color: #0d6efd; }
.cg-table-detalle th.sortable.sort-desc::after { content: "\f0dd"; color: #0d6efd; }
.cg-num { font-variant-numeric: tabular-nums; }
.cg-barra { height: 7px; border-radius: 4px; }
.cg-est-name { font-weight: 600; }
.cg-info-import { background: #eef6ff; border: 1px solid #cfe5ff; border-radius: 8px; }

/* Barra para el rango Bajo (40-59%): complementa colorBarraCG() */
.bg-orange { background-color: #e67e22 !important; }

/* Insignia de numero de panel */
.cg-panel-badge {
    font-size: .72rem; letter-spacing: .5px; text-transform: uppercase; font-weight: 700;
    color: var(--his-primary); background: rgba(26, 82, 118, .08);
    border: 1px solid rgba(26, 82, 118, .18); padding: 3px 12px; border-radius: 20px; white-space: nowrap;
}

/* Panel 01: tarjetas de indicadores */
.cg-desc {
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
    overflow: hidden; font-size: .78rem; line-height: 1.35; margin-bottom: .5rem;
}

/* Boton/icono de la ficha tecnica PDF del indicador (Panel 02) */
.cg-ficha-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 12px; border-radius: 20px; font-size: .74rem; font-weight: 600;
    color: #b02a37; background: #fdecec; border: 1px solid #f1b8bd;
    text-decoration: none; white-space: nowrap; transition: all .15s ease;
}
.cg-ficha-btn:hover, .cg-ficha-btn:focus { background: #b02a37; color: #fff; border-color: #b02a37; }
.cg-ficha-btn .fa-file-pdf { font-size: .95rem; }

/* Panel 02: cabecera con datos del indicador */
.cg-stat-tile {
    background: #f6f9fc; border: 1px solid #e3ebf3; border-radius: 10px;
    padding: .6rem .5rem; height: 100%; text-align: center;
}
.cg-tile-label {
    font-size: .66rem; text-transform: uppercase; letter-spacing: .4px;
    color: #6c757d; font-weight: 600; margin-bottom: .15rem; white-space: nowrap;
}
.cg-tile-value { font-size: 1.28rem; font-weight: 700; color: #2c3e50; line-height: 1.15; font-variant-numeric: tabular-nums; }
.cg-tile-sub { font-size: .66rem; color: #98a6b3; margin-top: .1rem; }
.cg-tile-meta .cg-tile-value   { color: var(--his-primary); }
.cg-tile-avance .cg-tile-value { color: #1e8449; }
.cg-tile-num .cg-tile-value    { color: var(--his-info); }
.cg-tile-den .cg-tile-value    { color: #27ae60; }

/* Panel 02: ranking de establecimientos (grafico de barras con CSS) */
.cg-rank-row { display: flex; align-items: center; gap: .65rem; padding: .4rem 0; }
.cg-rank-row + .cg-rank-row { border-top: 1px dashed #eef1f5; }
.cg-rank-pos {
    width: 30px; height: 30px; border-radius: 50%; background: #eef2f7; color: #495057;
    font-weight: 700; font-size: .78rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.cg-pos-1 { background: #ffd43b; color: #7a5c00; }
.cg-pos-2 { background: #ced4da; color: #343a40; }
.cg-pos-3 { background: #f0c29b; color: #7a4300; }
.cg-rank-body { flex: 1 1 auto; min-width: 0; }
.cg-rank-head { display: flex; justify-content: space-between; align-items: baseline; gap: .75rem; margin-bottom: 3px; }
.cg-rank-name { font-weight: 600; font-size: .82rem; color: #2c3e50; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cg-rank-val { font-weight: 700; font-size: .82rem; color: #2c3e50; font-variant-numeric: tabular-nums; white-space: nowrap; }
.cg-rank-val.cg-sin-dato { color: #98a6b3; font-weight: 500; }
.cg-rank-track { position: relative; height: 16px; background: #eef2f7; border-radius: 8px; }
.cg-rank-bar { height: 100%; border-radius: 8px; transition: width .8s ease; }
.cg-rank-goal { position: absolute; top: -3px; bottom: -3px; width: 0; border-left: 2px dashed #8395a7; }
.cg-legend-dot { width: 12px; height: 12px; border-radius: 3px; display: inline-block; flex-shrink: 0; }
.cg-legend-goal { width: 0; height: 12px; border-left: 2px dashed #8395a7; display: inline-block; }

/* Fila a 2 columnas - grafico "Avance por Mes" (izquierda) y ranking (derecha) */
.cg-chart-box { background: #fff; border: 1px solid #e3ebf3; border-radius: 10px; padding: .9rem 1rem 1rem; box-shadow: 0 1px 2px rgba(16, 42, 67, .04); }
.cg-trend-radios { max-height: 118px; overflow-y: auto; padding: 2px 4px 2px 2px; }
.cg-trend-radios .cg-trend-pill { border-radius: 20px; padding: .18rem .65rem; font-size: .72rem; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cg-trend-radios .btn-check:checked + .cg-trend-pill { color: #fff; background: var(--his-primary); border-color: var(--his-primary); }
.cg-trend-wrap { position: relative; height: 330px; }
</style>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-file-contract me-2"></i>Convenio de Gestion MINSA-GORE</h4>
            <p class="subtitle">Zona Sanitaria Perene - Seguimiento del avance de los indicadores del Convenio de Gestion</p>
        </div>
        <?php if ($esAdmin && $sistemaCGok): ?>
        <div class="d-flex gap-2">
            <a href="cg_gestion_indicadores.php" class="btn btn-his btn-sm">
                <i class="fas fa-tasks me-1"></i> Gestion de Indicadores
            </a>
            <a href="cg_importar_avances.php" class="btn btn-warning btn-sm text-dark fw-semibold">
                <i class="fas fa-file-import me-1"></i> Importar Avances
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$sistemaCGok): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php if ($tablaIndicadoresCG === null): ?>
            La tabla <code>CONVENIO_GESTION_INDICADORES</code> no existe en la base de datos.
        <?php else: ?>
            La tabla <code>CONVENIO_GESTION_AVANCE</code> no existe en la base de datos.
        <?php endif; ?>
        Ejecute el script <code>Database/if0_42181393_his.sql</code> (o <code>Database/update_gestion_v1.sql</code>)
        desde phpMyAdmin para crear las tablas del Convenio de Gestion.
    </div>
<?php endif; ?>

<?php if ($sistemaCGok): ?>

<!-- ===================== AVISO DE ACTUALIZACION DE DATOS ===================== -->
<div class="cg-info-import d-flex align-items-center gap-2 px-3 py-2 mb-4">
    <i class="fas fa-clock text-primary fs-5"></i>
    <div class="small">
        <strong>Datos actualizados al:</strong>
        <?php
        $ultimaActualizacion = null;
        try {
            $colUltima = $tieneFechaImportacionCG
                ? 'MAX(COALESCE(fecha_importacion, fecha_registro))'
                : 'MAX(fecha_registro)';
            $ultimaActualizacion = $pdo->query("SELECT {$colUltima} FROM `{$tablaAvancesCG}`")->fetchColumn();
        } catch (Throwable $e) {
            $ultimaActualizacion = null;
        }
        ?>
        <?php if ($ultimaActualizacion): ?>
            <strong class="text-primary"><?= formatDateTime($ultimaActualizacion) ?></strong>
        <?php else: ?>
            <span class="text-muted">Aun no se ha importado informacion de avances.</span>
        <?php endif; ?>
        <span class="text-muted d-block d-md-inline">Fuente: BD Local</span>
    </div>
</div>

<!-- ============================================================= -->
<!-- PANEL 01 - PRESENTACION: tarjetas de indicadores              -->
<!-- Visible solo en la carga por defecto; el Panel 02 queda       -->
<!-- oculto hasta pulsar "Ver detalle" en una tarjeta.             -->
<!-- ============================================================= -->
<?php if (!$verDetalle): ?>
<div class="cg-anchor" id="panel01"></div>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="cg-panel-badge">Panel 01</span>
        <h5 class="mb-0 fw-bold text-dark">Presentacion de Indicadores</h5>
    </div>
    <span class="stat-mini"><i class="fas fa-layer-group me-1"></i><?= count($panel01) ?> indicadores activos &middot; Acumulado de todos los periodos</span>
</div>

<?php if (empty($panel01)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No hay indicadores activos registrados en el Convenio de Gestion.
        <?php if ($esAdmin): ?>Agreguelos en <a href="cg_gestion_indicadores.php"><strong>Gestion de Indicadores</strong></a>.<?php endif; ?>
    </div>
<?php else: ?>

<?php if ($avanceGlobalPonderado !== null): ?>
<!-- Avance global del convenio -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Avance Global del Convenio de Gestion (<?= clean($modoAvanceGlobal) ?>)</h6>
            <span class="avance-badge <?= clasificarAvanceCG($avanceGlobalPonderado)['clase'] ?>">
                <?= number_format($avanceGlobalPonderado, 1) ?>% &middot; <?= clasificarAvanceCG($avanceGlobalPonderado)['texto'] ?>
            </span>
        </div>
        <div class="progress" style="height: 18px; border-radius: 9px;">
            <div class="progress-bar <?= colorBarraCG($avanceGlobalPonderado) ?>"
                 role="progressbar" style="width: <?= min(100, $avanceGlobalPonderado) ?>%;"
                 aria-valuenow="<?= $avanceGlobalPonderado ?>" aria-valuemin="0" aria-valuemax="100">
                <?= number_format($avanceGlobalPonderado, 1) ?>%
            </div>
        </div>
        <small class="text-muted d-block mt-1">
            <i class="fas fa-info-circle me-1"></i>
            <?= $modoAvanceGlobal === 'ponderado'
                ? 'Promedio de los indicadores ponderado por su peso en el convenio (columna peso_ponderado).'
                : 'Promedio simple de los indicadores con datos: asigne pesos en la Gestion de Indicadores para el calculo ponderado.' ?>
        </small>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($panel01 as $p):
        $pctCard = $p['pct_prom'] !== null ? round((float)$p['pct_prom'], 1) : null;
        $avCard  = clasificarAvanceCG($pctCard);
        $urlPanel2 = 'convenio_gestion.php?' . http_build_query(array_merge($qsPanel2, ['vista' => 'detalle', 'ind' => (int)$p['id_indicador']])) . '#panel02';
        $nombreCorto = mb_strimwidth((string)$p['nombre'], 0, 90, '...');
        $pesoCard = ($tienePesoCG && $p['peso_ponderado'] !== null) ? cgFmtPctMeta($p['peso_ponderado']) : null;
    ?>
    <div class="col-xl-3 col-lg-4 col-md-6 col-12 d-flex">
        <div class="card indicador-card <?= $avCard['estado'] ?> w-100">
            <div class="d-flex flex-column h-100 w-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="indicador-codigo"><?= clean($p['codigo']) ?></span>
                    <span class="avance-badge <?= $avCard['clase'] ?>"><?= $avCard['texto'] ?></span>
                </div>
                <h6 class="indicador-nombre" title="<?= clean($p['nombre']) ?>"><?= clean($nombreCorto) ?></h6>
                <?php if (!empty($p['descripcion'])): ?>
                    <p class="cg-desc text-muted" title="<?= clean($p['descripcion']) ?>"><?= clean($p['descripcion']) ?></p>
                <?php endif; ?>
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted"><i class="fas fa-bullseye me-1"></i>Meta anual</span>
                        <strong><?= $p['meta_anual'] !== null ? cgFmtPctMeta($p['meta_anual']) . '%' : '-' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted"><i class="fas fa-percent me-1"></i>Promedio de avance</span>
                        <?php if ($pctCard !== null): ?>
                            <strong class="text-primary"><?= number_format($pctCard, 1) ?>%</strong>
                        <?php else: ?>
                            <span class="text-muted">Sin dato</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($p['res_prom'] !== null): ?>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted"><i class="fas fa-calculator me-1"></i>Resultado (prom.)</span>
                        <strong><?= number_format((float)$p['res_prom'], 2) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="progress progress-his cg-barra mb-2">
                        <?php if ($pctCard !== null): ?>
                            <div class="progress-bar <?= colorBarraCG($pctCard) ?>" role="progressbar" style="width: <?= min(100, $pctCard) ?>%;"></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span><i class="fas fa-database me-1"></i><?= number_format((int)$p['n_reg']) ?> registros</span>
                        <?php if ($tieneRenipressCG): ?>
                            <span><i class="fas fa-hospital me-1"></i><?= (int)$p['n_est'] ?> establec.</span>
                        <?php elseif ($pesoCard !== null): ?>
                            <span><i class="fas fa-balance-scale me-1"></i>Peso: <?= $pesoCard ?>%</span>
                        <?php endif; ?>
                    </div>
                    <a href="<?= clean($urlPanel2) ?>" class="btn btn-his btn-sm w-100" title="Muestra el Panel 02 con la informacion detallada de este indicador">
                        <i class="fas fa-arrow-circle-down me-1"></i> Ver detalle (Panel 02)
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; /* fin Panel 01 con indicadores */ ?>
<?php endif; /* fin vista Panel 01: el Panel 01 se oculta al consultar el detalle */ ?>

<!-- ============================================================= -->
<!-- PANEL 02 - DETALLE DEL INDICADOR SELECCIONADO                 -->
<!-- Visible solo al pulsar "Ver detalle" en el Panel 01;          -->
<!-- incluye el boton "Regresar" para volver al Panel 01.          -->
<!-- ============================================================= -->
<?php if ($verDetalle): ?>
<div class="cg-anchor" id="panel02"></div>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="cg-panel-badge">Panel 02</span>
        <h5 class="mb-0 fw-bold text-dark">Informacion Detallada del Indicador</h5>
    </div>
    <!-- Boton Regresar: vuelve a mostrar el Panel 01 y oculta el Panel 02 -->
    <a href="<?= clean($urlVolverPanel1) ?>" class="btn btn-outline-secondary btn-sm" title="Vuelve a la Presentacion de Indicadores (Panel 01)">
        <i class="fas fa-arrow-circle-left me-1"></i>Regresar
    </a>
</div>

<?php if ($indSel === null): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        No hay indicadores activos para mostrar. Los datos del Panel 02 apareceran cuando exista al menos un indicador activo
        <?php if ($esAdmin): ?>creado desde <a href="cg_gestion_indicadores.php"><strong>Gestion de Indicadores</strong></a><?php endif; ?>.
    </div>
<?php else: ?>

<div class="card shadow-sm mb-4">
    <!-- ---------- Cabecera del indicador ---------- -->
    <div class="card-header bg-white py-3">
        <div class="row g-3 align-items-center">
            <div class="col-xl-5">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="indicador-codigo"><?= clean($indSel['codigo']) ?></span>
                    <span class="avance-badge <?= clasificarAvanceCG($pctCabecera)['clase'] ?>"><?= clasificarAvanceCG($pctCabecera)['texto'] ?></span>
                    <?php /* Boton de la ficha tecnica del indicador (abre el PDF en otra ventana) */ ?>
                    <?php if ($tieneFichaPdfCG && !empty($indSel['ficha_tecnica_pdf'])): ?>
                        <a href="<?= clean(cgFichaUrl($indSel['ficha_tecnica_pdf'])) ?>" target="_blank" rel="noopener"
                           class="cg-ficha-btn" title="Ficha tecnica del indicador: abre el PDF en otra ventana">
                            <i class="fas fa-file-pdf"></i>
                            <span>Ficha tecnica (PDF)</span>
                        </a>
                    <?php endif; ?>
                </div>
                <h5 class="fw-bold mb-1" style="color: var(--his-primary);"><?= clean($indSel['nombre']) ?></h5>
                <p class="small text-muted mb-2"><?= !empty($indSel['descripcion']) ? clean($indSel['descripcion']) : 'Sin descripcion registrada.' ?></p>
                <div class="small text-muted d-flex flex-wrap gap-3">
                    <?php if (!empty($indSel['responsable'])): ?>
                        <span><i class="fas fa-user-tie me-1"></i><?= clean($indSel['responsable']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($indSel['unidad_medida'])): ?>
                        <span><i class="fas fa-ruler me-1"></i>Unidad: <?= clean($indSel['unidad_medida']) ?></span>
                    <?php endif; ?>
                    <?php if ($tienePesoCG && $indSel['peso_ponderado'] !== null): ?>
                        <span><i class="fas fa-balance-scale me-1"></i>Peso: <?= cgFmtPctMeta($indSel['peso_ponderado']) ?>%</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="cg-stat-tile cg-tile-meta">
                            <div class="cg-tile-label"><i class="fas fa-bullseye me-1"></i>Meta Anual</div>
                            <div class="cg-tile-value"><?= $indSel['meta_anual'] !== null ? cgFmtPctMeta($indSel['meta_anual']) . '%' : '-' ?></div>
                            <div class="cg-tile-sub">meta_anual</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="cg-stat-tile cg-tile-avance">
                            <div class="cg-tile-label"><i class="fas fa-percent me-1"></i>Promedio Total</div>
                            <div class="cg-tile-value"><?= $pctCabecera !== null ? number_format($pctCabecera, 1) . '%' : 'Sin dato' ?></div>
                            <div class="cg-tile-sub">promedio porcentaje_avance</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="cg-stat-tile cg-tile-num">
                            <div class="cg-tile-label"><i class="fas fa-arrow-up me-1"></i>Suma Numerador</div>
                            <div class="cg-tile-value"><?= cgFmtNumero($statsInd['sum_num'] ?? null) ?></div>
                            <div class="cg-tile-sub">suma valor_numerador</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="cg-stat-tile cg-tile-den">
                            <div class="cg-tile-label"><i class="fas fa-arrow-down me-1"></i>Suma Denominador</div>
                            <div class="cg-tile-value"><?= cgFmtNumero($statsInd['sum_den'] ?? null) ?></div>
                            <div class="cg-tile-sub">suma valor_denominador</div>
                        </div>
                    </div>
                </div>
                <div class="small text-muted mt-2 text-center text-xl-end">
                    <i class="fas fa-filter me-1"></i>
                    Periodo consultado: <strong><?= clean($fAnio) ?></strong> &middot;
                    <?= clean(cgEtiquetaMes($fMes)) ?> &middot;
                    <?= $fEst !== '' ? clean($nombreEstFiltro) : 'Todos los establecimientos (' . count($establecimientosDisp) . ')' ?>
                    <?php if ($statsInd !== null): ?>
                        &middot; <?= number_format((int)$statsInd['n_reg']) ?> registros
                        <?php if ($tieneRenipressCG): ?> &middot; <?= (int)$statsInd['n_est'] ?> establecimientos<?php endif; ?>
                        <?php if ($statsInd['res_prom'] !== null): ?>
                            &middot; Resultado prom.: <strong><?= number_format((float)$statsInd['res_prom'], 2) ?></strong>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ---------- Filtros del Panel 02 ---------- -->
    <div class="card-body border-top py-3">
        <form method="GET" action="convenio_gestion.php#panel02" class="row g-2 align-items-end" id="formPanel02">
            <input type="hidden" name="vista" value="detalle">
            <div class="col-lg-3 col-md-6 col-12">
                <label class="form-label small fw-semibold mb-0">Indicador</label>
                <select name="ind" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($indicadoresFiltro as $if): ?>
                        <option value="<?= (int)$if['id_indicador'] ?>" <?= $indId === (int)$if['id_indicador'] ? 'selected' : '' ?>>
                            <?= clean($if['codigo']) ?> - <?= clean(mb_strimwidth($if['nombre'], 0, 55, '...')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-0">Anio</label>
                <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($aniosDisp as $a): ?>
                        <option value="<?= clean($a) ?>" <?= (string)$fAnio === (string)$a ? 'selected' : '' ?>><?= clean($a) ?></option>
                    <?php endforeach; ?>
                    <?php if (!in_array((string)date('Y'), array_map('strval', $aniosDisp), true)): ?>
                        <option value="<?= date('Y') ?>" <?= (string)$fAnio === date('Y') ? 'selected' : '' ?>><?= date('Y') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-0">Mes</label>
                <select name="mes" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los meses --</option>
                    <option value="anual" <?= $fMes === 'anual' ? 'selected' : '' ?>>-- Acumulado anual --</option>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $mPad = str_pad((string)$m, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?= $mPad ?>" <?= $fMes === $mPad ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if ($tieneRenipressCG): ?>
            <div class="col-lg-4 col-md-6 col-12">
                <label class="form-label small fw-semibold mb-0">Establecimiento</label>
                <select name="est" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los establecimientos (<?= count($establecimientosDisp) ?>) --</option>
                    <?php if ($tieneConsolidado): ?>
                        <option value="__consolidado__" <?= $fEst === '__consolidado__' ? 'selected' : '' ?>>-- Consolidado Zona Sanitaria --</option>
                    <?php endif; ?>
                    <?php foreach ($establecimientosDisp as $e): ?>
                        <option value="<?= clean($e['renipress']) ?>" <?= $fEst === (string)$e['renipress'] ? 'selected' : '' ?>>
                            <?= clean($e['establecimiento']) ?> (<?= clean($e['renipress']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-lg-1 col-12">
                <button type="submit" class="btn btn-his btn-sm w-100" title="Consultar"><i class="fas fa-search"></i></button>
            </div>
            <div class="col-12">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    "Todos los meses" suma todos los registros del anio (incluida la fila de acumulado anual si existe);
                    "Acumulado anual" muestra solo la fila consolidada del anio (mes sin valor).
                </small>
            </div>
        </form>
    </div>

    <?php if (!$tieneRenipressCG): ?>
    <!-- Esquema sin las columnas renipress/establecimiento: detalle no disponible -->
    <div class="card-body border-top py-3">
        <div class="alert alert-warning mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            El detalle por establecimiento, el grafico mensual y el ranking requieren las columnas
            <code>renipress</code> y <code>establecimiento</code> en la tabla <code>CONVENIO_GESTION_AVANCE</code>,
            que no pudieron crearse automaticamente en este servidor.
            Ejecute el script <code>Database/update_gestion_v1.sql</code> desde phpMyAdmin para habilitarlos.
        </div>
    </div>
    <?php else: ?>

    <!-- ---------- Tabla: Detalle de Avances por Establecimiento ---------- -->
    <div class="card-body border-top py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Detalle de Avances por Establecimiento</h6>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <span class="stat-mini" id="contadorDetalle"></span>
                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="busquedaDetalle" class="form-control" placeholder="Buscar en la tabla...">
                </div>
                <button type="button" class="btn btn-sm btn-outline-success" id="btnExportarCsv" title="Exportar lo que se muestra a CSV (Excel)">
                    <i class="fas fa-file-csv me-1"></i> Exportar CSV
                </button>
            </div>
        </div>
        <small class="text-muted d-block mb-2">
            <i class="fas fa-layer-group me-1"></i>
            Agrupado por establecimiento
            <?php if ($tieneConsolidado): ?>
                &middot; los registros sin establecimiento se muestran como <strong>Consolidado Zona Sanitaria</strong>
            <?php endif; ?>
        </small>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 cg-table-detalle" id="tablaDetalleEst">
                <thead class="table-light">
                    <tr>
                        <th class="sortable" data-col="0" data-type="str" data-csv="renipress">Renipress</th>
                        <th class="sortable" data-col="1" data-type="str" data-csv="establecimiento">Establecimiento</th>
                        <th class="sortable text-end" data-col="2" data-type="num" data-csv="valor_numerador">Valor Numerador</th>
                        <th class="sortable text-end" data-col="3" data-type="num" data-csv="valor_denominador">Valor Denominador</th>
                        <th class="sortable text-end" data-col="4" data-type="num" data-csv="valor_resultado">Resultado (prom.)</th>
                        <th class="sortable text-end" data-col="5" data-type="num" data-csv="porcentaje_avance">Porcentaje Avance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detalleEst)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                No hay registros de avances para este indicador con los filtros seleccionados.
                                <?php if ($esAdmin): ?>Utilice el modulo <a href="cg_importar_avances.php"><strong>Importar Avances</strong></a> para cargar los datos.<?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detalleEst as $de):
                            $pct = $de['pct'];
                            $av  = clasificarAvanceCG($pct);
                        ?>
                        <tr data-sortable="1">
                            <td data-sort="<?= clean($de['renipress_mostrar']) ?>"><code><?= clean($de['renipress_mostrar']) ?></code></td>
                            <td class="cg-est-name" data-sort="<?= clean($de['nombre_mostrar']) ?>"><?= clean($de['nombre_mostrar']) ?></td>
                            <td class="text-end cg-num" data-sort="<?= (float)($de['sum_num'] ?? 0) ?>" data-csv="<?= $de['sum_num'] !== null ? number_format((float)$de['sum_num'], 2, '.', '') : '' ?>"><?= cgFmtNumero($de['sum_num']) ?></td>
                            <td class="text-end cg-num" data-sort="<?= (float)($de['sum_den'] ?? 0) ?>" data-csv="<?= $de['sum_den'] !== null ? number_format((float)$de['sum_den'], 2, '.', '') : '' ?>"><?= cgFmtNumero($de['sum_den']) ?></td>
                            <td class="text-end cg-num" data-sort="<?= (float)($de['res_prom'] ?? -1) ?>" data-csv="<?= $de['res_prom'] !== null ? number_format((float)$de['res_prom'], 2, '.', '') : '' ?>"><?= cgFmtNumero($de['res_prom']) ?></td>
                            <td class="text-end" data-sort="<?= $pct ?? -1 ?>" data-csv="<?= $pct !== null ? number_format($pct, 2, '.', '') : '' ?>">
                                <?php if ($pct !== null): ?>
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <span class="avance-badge <?= $av['clase'] ?>" style="min-width:64px;"><?= number_format($pct, 1) ?>%</span>
                                        <div class="progress progress-his flex-grow-1 cg-barra">
                                            <div class="progress-bar <?= colorBarraCG($pct) ?>" role="progressbar" style="width: <?= min(100, max(0, $pct)) ?>%;"></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Sin dato</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($detalleEst) && $statsInd !== null): ?>
                <tfoot class="table-light">
                    <tr class="fw-semibold">
                        <td colspan="2"><i class="fas fa-calculator me-1 text-primary"></i>TOTAL (<?= count($detalleEst) ?> establecimientos)</td>
                        <td class="text-end cg-num"><?= cgFmtNumero($statsInd['sum_num']) ?></td>
                        <td class="text-end cg-num"><?= cgFmtNumero($statsInd['sum_den']) ?></td>
                        <td class="text-end cg-num"><?= cgFmtNumero($statsInd['res_prom']) ?></td>
                        <td class="text-end">
                            <?php if ($pctDetalleGlobal !== null): ?>
                                <strong class="<?= $pctDetalleGlobal >= 75 ? 'text-success' : ($pctDetalleGlobal >= 60 ? 'text-warning' : 'text-danger') ?>">
                                    <?= number_format($pctDetalleGlobal, 1) ?>%
                                </strong>
                            <?php else: ?>
                                <span class="text-muted">Sin dato</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ==================================================================
         Fila a 2 columnas:
           IZQUIERDA -> "Avance por Mes" (linea de tendencia con Chart.js,
                        selector de establecimiento por radio-botones)
           DERECHA   -> Ranking de Establecimientos (barras)
         ================================================================== -->
    <div class="card-body border-top py-3">
        <div class="row g-3">

            <!-- ---------- IZQUIERDA: Avance por Mes (linea de tendencia) ---------- -->
            <div class="col-lg-6 d-flex">
                <div class="cg-chart-box w-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-success"></i>Avance por Mes</h6>
                        <span class="stat-mini"><i class="fas fa-calendar me-1"></i>A&ntilde;o <?= clean($fAnio) ?></span>
                    </div>
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-info-circle me-1"></i>
                        L&iacute;nea de tendencia del porcentaje de avance por mes del indicador y a&ntilde;o del panel.
                    </small>

                    <?php if (empty($cgTrendJson)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-line fa-2x d-block mb-2"></i>
                            No hay datos mensuales para graficar en <?= clean($fAnio) ?>.
                        </div>
                    <?php else: ?>
                        <!-- Selector de establecimiento (radio-botones) -->
                        <div class="cg-trend-radios d-flex flex-wrap gap-1 mb-3" id="cgTrendRadios">
                            <?php foreach ($cgTrendJson['series'] as $i => $s): ?>
                                <input type="radio" class="btn-check" name="cgTrendEst" id="cgTrendEst<?= $i ?>"
                                       value="<?= $i ?>" autocomplete="off" <?= $i === 0 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary btn-sm cg-trend-pill" for="cgTrendEst<?= $i ?>"
                                       title="<?= clean($s['nombre']) ?>">
                                    <?php if ($i === 0): ?><i class="fas fa-layer-group me-1"></i><?php endif; ?><?= clean($s['nombre']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Grafico de linea (Chart.js) -->
                        <div class="cg-trend-wrap">
                            <canvas id="cgTrendChart" role="img" aria-label="Grafico de linea: avance por mes"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ---------- DERECHA: Ranking de Establecimientos (grafico de barras) ---------- -->
            <div class="col-lg-6 d-flex">
                <div class="cg-chart-box w-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-ranking-star me-2 text-primary"></i>Ranking de Establecimientos</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($indSel['meta_anual'] !== null): ?>
                                <span class="stat-mini"><i class="fas fa-bullseye me-1"></i>Meta anual: <?= cgFmtPctMeta($indSel['meta_anual']) ?>%</span>
                            <?php endif; ?>
                            <span class="stat-mini"><i class="fas fa-sort-amount-down me-1"></i>Mejor avance primero</span>
                        </div>
                    </div>

                    <?php if (empty($rankingEst)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-simple fa-2x d-block mb-2"></i>
                            Sin datos para graficar con los filtros seleccionados.
                        </div>
                    <?php else: ?>
                        <div class="cg-rank-list">
                            <?php $pos = 0; foreach ($rankingEst as $rk):
                                $pos++;
                                $pctR = $rk['pct'];
                                $colorR = colorBarraCG($pctR);
                                $widthR = $pctR !== null ? min(100, max(0, $pctR)) : 0;
                                $clsPos = $pos <= 3 ? ' cg-pos-' . $pos : '';
                                $goalLeft = ($indSel['meta_anual'] !== null && (float)$indSel['meta_anual'] > 0)
                                    ? min(100, max(0, (float)$indSel['meta_anual'])) : null;
                            ?>
                            <div class="cg-rank-row">
                                <div class="cg-rank-pos<?= $clsPos ?>" title="Puesto <?= $pos ?>">
                                    <?php if ($pos <= 3 && $pctR !== null): ?><i class="fas fa-medal"></i><?php else: ?><?= $pos ?><?php endif; ?>
                                </div>
                                <div class="cg-rank-body">
                                    <div class="cg-rank-head">
                                        <span class="cg-rank-name" title="<?= clean($rk['nombre_mostrar']) ?> (<?= clean($rk['renipress_mostrar']) ?>)">
                                            <?= clean($rk['nombre_mostrar']) ?>
                                            <small class="text-muted fw-normal"><code><?= clean($rk['renipress_mostrar']) ?></code></small>
                                        </span>
                                        <?php if ($pctR !== null): ?>
                                            <span class="cg-rank-val"><?= number_format($pctR, 1) ?>%</span>
                                        <?php else: ?>
                                            <span class="cg-rank-val cg-sin-dato">Sin dato</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cg-rank-track" title="Numerador: <?= cgFmtNumero($rk['sum_num']) ?> | Denominador: <?= cgFmtNumero($rk['sum_den']) ?> | <?= number_format((int)$rk['n_reg']) ?> registro(s)">
                                        <div class="cg-rank-bar <?= $colorR ?>" data-w="<?= $widthR ?>" style="width: <?= $widthR ?>%;"></div>
                                        <?php if ($goalLeft !== null): ?>
                                            <div class="cg-rank-goal" style="left: <?= $goalLeft ?>%;" title="Meta anual: <?= cgFmtPctMeta($indSel['meta_anual']) ?>%"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-3 small text-muted">
                            <span class="d-inline-flex align-items-center gap-1"><span class="cg-legend-dot bg-success"></span>Excelente &ge; 90%</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="cg-legend-dot bg-info"></span>Bueno 75-89%</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="cg-legend-dot bg-warning"></span>Regular 60-74%</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="cg-legend-dot bg-orange"></span>Bajo 40-59%</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="cg-legend-dot bg-danger"></span>Critico &lt; 40%</span>
                            <?php if ($indSel['meta_anual'] !== null && (float)$indSel['meta_anual'] > 0): ?>
                                <span class="d-inline-flex align-items-center gap-1"><span class="cg-legend-goal"></span>L&iacute;nea de meta (<?= cgFmtPctMeta($indSel['meta_anual']) ?>%)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    <?php endif; /* fin $tieneRenipressCG (detalle por establecimiento) */ ?>
</div>

<?php endif; /* fin Panel 02 con indicador seleccionado */ ?>

<!-- Boton Regresar al final del Panel 02 (tras la tabla y el ranking) -->
<div class="text-center mb-4">
    <a href="<?= clean($urlVolverPanel1) ?>" class="btn btn-outline-secondary" title="Vuelve a la Presentacion de Indicadores (Panel 01)">
        <i class="fas fa-arrow-circle-left me-1"></i>Regresar al Panel 01
    </a>
</div>

<?php endif; /* fin vista Panel 02: visible solo con vista=detalle */ ?>

<?php endif; /* fin $sistemaCGok */ ?>

<!-- Chart.js: necesario para el grafico "Avance por Mes" del Panel 02 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/* ============================================================
 * Interactividad de la vista de consulta del Convenio de Gestion (JavaScript puro)
 *  - Tabla del Panel 02: busqueda en vivo, orden por columna y CSV.
 *  - Grafico de ranking: animacion de las barras.
 *  - Grafico "Avance por Mes": linea de tendencia mensual con selector
 *    de establecimiento por radio-botones; al cargar muestra el agregado de
 *    todos los establecimientos y al elegir uno muestra solo su serie.
 *  - Salto automatico al Panel 02 al llegar con ?ind=... o ?vista=detalle sin ancla.
 * ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

    /* ---- Animacion de las barras del ranking ---- */
    document.querySelectorAll('.cg-rank-bar[data-w]').forEach(function (barra) {
        var w = parseFloat(barra.getAttribute('data-w')) || 0;
        barra.style.width = '0%';
        requestAnimationFrame(function () {
            setTimeout(function () { barra.style.width = w + '%'; }, 80);
        });
    });

    /* ------------------------------------------------------------
     * Grafico "Avance por Mes": linea de tendencia mensual.
     * Los datos llegan desde PHP en cgTrendDatos.series:
     *   [0] = agregado de todos los establecimientos (por defecto)
     *   [n] = serie mensual de cada establecimiento
     * El selector (radio-botones) cambia la serie sin recargar.
     * ------------------------------------------------------------ */
    var cgTrendCanvas = document.getElementById('cgTrendChart');
    var cgTrendDatos  = <?= json_encode($cgTrendJson ?: null, JSON_UNESCAPED_UNICODE) ?: 'null' ?>;
    if (cgTrendCanvas && cgTrendDatos && cgTrendDatos.series && cgTrendDatos.series.length) {
        if (!window.Chart) {
            // CDN no disponible (sin internet): aviso en lugar de grafico
            cgTrendCanvas.closest('.cg-trend-wrap').innerHTML =
                '<div class="text-center text-muted py-5"><i class="fas fa-plug fa-2x d-block mb-2"></i>' +
                'No se pudo cargar Chart.js. Verifique la conexion a internet del servidor o del navegador.</div>';
        } else {
            var MESES_CG = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
            var cgTrendIdx   = 0;   // serie visible (0 = Todos)
            var cgTrendChart = null;

            /** Tendencia lineal (regresion lineal simple) de la serie visible */
            function cgTrendRegresion(arr) {
                var pts = [];
                arr.forEach(function (v, i) { if (v !== null && v !== undefined && !isNaN(v)) { pts.push([i, v]); } });
                if (pts.length < 2) { return null; }
                var n = pts.length, sx = 0, sy = 0, sxy = 0, sxx = 0;
                pts.forEach(function (p) { sx += p[0]; sy += p[1]; sxy += p[0] * p[1]; sxx += p[0] * p[0]; });
                var den = n * sxx - sx * sx;
                if (!den) { return null; }
                var m = (n * sxy - sx * sy) / den;
                var b = (sy - m * sx) / n;
                return arr.map(function (_, i) {
                    if (i < pts[0][0] || i > pts[pts.length - 1][0]) { return null; }
                    return Math.max(0, Math.min(100, m * i + b));
                });
            }

            /** Datasets del grafico segun la serie seleccionada */
            function cgTrendDatasets(idx) {
                var s = cgTrendDatos.series[idx] || cgTrendDatos.series[0];
                var ds = [{
                    label: s.nombre,
                    data: s.pct,
                    borderColor: '#2e86c1',
                    backgroundColor: 'rgba(46, 134, 193, .10)',
                    pointBackgroundColor: '#1a5276',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1,
                    pointStyle: 'rect',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 3,
                    tension: 0.3,
                    fill: true,
                    spanGaps: true
                }];
                var reg = cgTrendRegresion(s.pct);
                if (reg) {
                    ds.push({
                        label: 'Tendencia',
                        data: reg,
                        borderColor: '#8395a7',
                        borderDash: [7, 5],
                        borderWidth: 1.6,
                        pointRadius: 0,
                        pointHoverRadius: 0,
                        fill: false,
                        tension: 0,
                        spanGaps: true
                    });
                }
                return ds;
            }

            cgTrendChart = new Chart(cgTrendCanvas, {
                type: 'line',
                data: { labels: MESES_CG, datasets: cgTrendDatasets(0) },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'nearest', intersect: false },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 26, boxHeight: 3, font: { size: 11 }, color: '#2c3e50' }
                        },
                        tooltip: {
                            callbacks: {
                                title: function (items) {
                                    return MESES_CG[items[0].dataIndex] + ' ' + cgTrendDatos.anio;
                                },
                                label: function (item) {
                                    var v = item.parsed.y;
                                    return item.dataset.label + ': ' +
                                        (v === null || isNaN(v) ? '-' : v.toFixed(2)) + '%';
                                },
                                afterLabel: function (item) {
                                    if (item.datasetIndex !== 0) { return ''; }
                                    var s = cgTrendDatos.series[cgTrendIdx] || {};
                                    var num = (s.num || [])[item.dataIndex];
                                    var den = (s.den || [])[item.dataIndex];
                                    if (num === null || num === undefined) { return ''; }
                                    return 'Numerador: ' + Number(num).toLocaleString('es-PE') +
                                           ' | Denominador: ' + Number(den).toLocaleString('es-PE');
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: '#5b6b7b', font: { size: 11 } } },
                        y: {
                            min: 0,
                            max: 100,
                            ticks: { color: '#5b6b7b', font: { size: 11 }, callback: function (v) { return v + '%'; } },
                            grid: { color: '#eef1f5' }
                        }
                    }
                }
            });

            /* Radio-botones: cambiar la serie del grafico (sin recargar) */
            document.querySelectorAll('#cgTrendRadios input[name="cgTrendEst"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (!radio.checked) { return; }
                    cgTrendIdx = parseInt(radio.value, 10) || 0;
                    cgTrendChart.data.datasets = cgTrendDatasets(cgTrendIdx);
                    cgTrendChart.update();
                });
            });
        }
    }

    /* ---- Salto automatico al Panel 02 al llegar con ?ind=... o ?vista=detalle sin ancla ---- */
    if (!location.hash && (/[?&]ind=\d+/.test(location.search) || /[?&]vista=detalle/.test(location.search))) {
        var panel2 = document.getElementById('panel02');
        if (panel2) { panel2.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    }

    /* ---- Tabla de detalle: busqueda en vivo, orden por columna y CSV ---- */
    var tabla = document.getElementById('tablaDetalleEst');
    if (!tabla) return;
    var tbody = tabla.querySelector('tbody');
    var filas = Array.prototype.slice.call(tbody.querySelectorAll('tr[data-sortable="1"]'));
    var input = document.getElementById('busquedaDetalle');
    var contador = document.getElementById('contadorDetalle');
    var estadoSort = { col: -1, dir: 1 };

    function aplicarFiltro() {
        if (!input || !contador) return;
        var q = (input.value || '').toLowerCase().trim();
        var visibles = 0;
        filas.forEach(function (tr) {
            var mostrar = q === '' || tr.textContent.toLowerCase().indexOf(q) !== -1;
            tr.style.display = mostrar ? '' : 'none';
            if (mostrar) visibles++;
        });
        contador.textContent = visibles + ' de ' + filas.length + ' establecimientos';
    }
    if (input) input.addEventListener('input', aplicarFiltro);

    tabla.querySelectorAll('th.sortable').forEach(function (th) {
        th.addEventListener('click', function () {
            var idx = parseInt(th.getAttribute('data-col'), 10);
            var tipo = th.getAttribute('data-type') || 'str';
            var dir = (estadoSort.col === idx) ? -estadoSort.dir : 1;
            estadoSort = { col: idx, dir: dir };
            tabla.querySelectorAll('th.sortable').forEach(function (o) { o.classList.remove('sort-asc', 'sort-desc'); });
            th.classList.add(dir === 1 ? 'sort-asc' : 'sort-desc');
            var ordenadas = filas.slice().sort(function (a, b) {
                var ca = a.children[idx], cb = b.children[idx];
                var va = ca ? (ca.hasAttribute('data-sort') ? ca.getAttribute('data-sort') : ca.textContent.trim()) : '';
                var vb = cb ? (cb.hasAttribute('data-sort') ? cb.getAttribute('data-sort') : cb.textContent.trim()) : '';
                if (tipo === 'num') {
                    va = parseFloat(va); if (isNaN(va)) va = -Infinity;
                    vb = parseFloat(vb); if (isNaN(vb)) vb = -Infinity;
                    return (va - vb) * dir;
                }
                return String(va).localeCompare(String(vb), 'es') * dir;
            });
            ordenadas.forEach(function (tr) { tbody.appendChild(tr); });
        });
    });

    var btnCsv = document.getElementById('btnExportarCsv');
    if (btnCsv) {
        btnCsv.addEventListener('click', function () {
            var lineas = [];
            var heads = Array.prototype.slice.call(tabla.querySelectorAll('thead th')).map(function (th) {
                return th.getAttribute('data-csv') || th.textContent.trim();
            });
            lineas.push(heads.join(';'));
            filas.forEach(function (tr) {
                if (tr.style.display === 'none') return;
                var cells = Array.prototype.slice.call(tr.children).map(function (td) {
                    var v = td.hasAttribute('data-csv') ? td.getAttribute('data-csv') : td.textContent.trim();
                    return '"' + String(v).replace(/"/g, '""') + '"';
                });
                lineas.push(cells.join(';'));
            });
            var blob = new Blob(["\uFEFF" + lineas.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = <?= json_encode('detalle_gestion_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)($indSel['codigo'] ?? 'CG')) . '_' . $fAnio . ($fMes !== '' ? '_' . $fMes : '_todos') . '.csv') ?>;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    }

    aplicarFiltro();
});
</script>

<?php include 'includes/footer.php'; ?>
