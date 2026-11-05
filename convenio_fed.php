<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 02: Convenio FED - Fondo de Estimulo al Desempeno
 * Consulta y visualizacion de avances (todos los roles).
 *
 * v6 - Navegacion "un panel a la vez" (2026-11-05):
 *  Al cargar la pagina solo es visible el PANEL 01 (Presentacion); el PANEL 02
 *  (Detalle) queda oculto. El boton "Ver detalle (Panel 02)" de cada tarjeta
 *  lleva a convenio_fed.php?vista=detalle&ind=ID... y entonces solo es visible
 *  el PANEL 02, que incluye el boton "Regresar" (junto al titulo y tambien al
 *  final del panel) para volver al PANEL 01 conservando anio/mes/est. Los
 *  enlaces antiguos con ?ind=ID siguen abriendo el Panel 02 (compatibilidad)
 *  y los filtros del Panel 02 conservan vista=detalle al consultar.
 *
 * v8 - Ficha tecnica en PDF por indicador (2026-11-06):
 *  El Panel 02 muestra, junto al codigo del indicador, un icono/boton de PDF
 *  con la ficha tecnica del indicador: al hacer clic abre el PDF en otra
 *  ventana (target=_blank). El archivo se adjunta desde
 *  fed_gestion_indicadores.php, se guarda en uploads/fed_fichas/ del servidor
 *  y su nombre en la columna ficha_tecnica_pdf de CONVENIO_FED_INDICADORES.
 *  Si el indicador no tiene ficha adjunta (o la columna aun no existe), el
 *  icono simplemente no se muestra.
 *
 * v7 - Seccion "Ranking de Establecimientos" dividida en 2 columnas (2026-11-05):
 *  DERECHA: se conserva el ranking de barras tal como estaba.
 *  IZQUIERDA: nuevo grafico "Avance por Mes" (linea de tendencia con Chart.js)
 *  con datos de establecimiento, mes y porcentaje_avance del indicador y anio
 *  del panel (muestra los 12 meses, independiente de los filtros mes/est).
 *  Al cargar la pagina la linea muestra el agregado de TODOS los
 *  establecimientos (numerador y denominador sumados por mes) y un grupo de
 *  radio-botones permite seleccionar un establecimiento para que la linea
 *  muestre unicamente los datos de ese establecimiento (sin recargar). La
 *  linea punteada gris dibuja la tendencia (regresion lineal) de la serie
 *  visible.
 *
 * v5 - Rediseño UI/UX en dos paneles (2026-11-05):
 *  Se conserva el encabezado page-header-section y el aviso
 *  fed-info-import de "Datos actualizados al". El resto de la pagina se
 *  reorganiza en dos paneles enlazados entre si:
 *
 *    PANEL 01 - Presentacion:
 *      Tarjetas de los indicadores activos con: codigo, nombre,
 *      descripcion, meta_anual % y promedio total de porcentaje_avance
 *      (acumulado de todos los periodos). Cada tarjeta incluye un boton
 *      "Ver detalle" que enlaza con el Panel 02 (?ind=ID#panel02)
 *      conservando los filtros elegidos.
 *
 *    PANEL 02 - Detalle del indicador:
 *      Cabecera con codigo, nombre y descripcion del indicador
 *      seleccionado junto a: meta_anual %, promedio total de
 *      porcentaje_avance, suma total de valor_numerador y suma total de
 *      valor_denominador (segun filtros). Incluye filtros de anio, mes
 *      (por defecto "Todos los meses") y establecimiento, la tabla
 *      "Detalle de Avances por Establecimiento" (renipress,
 *      establecimiento, valor_numerador, valor_denominador,
 *      porcentaje_avance; agrupada por establecimiento con numerador y
 *      denominador sumados) y el Ranking de Establecimientos con
 *      grafico de barras por porcentaje_avance.
 *
 * Historial de versiones del modulo:
 *  v3   - Gestion de indicadores + importacion Resultados.txt + visualizacion
 *         interactiva (solo en este archivo, ya separado).
 *  v3.1 - Correccion del ERROR 500: fedTablaExiste()/fedTieneColumna() usan
 *         information_schema (admite placeholders) con respaldo SHOW + literal.
 *  v3.2 - Importador detecta automaticamente el formato REAL del Resultados.txt:
 *         8 columnas con CODIGO de indicador (ej. FED-04;319;...;2026;01;1;1;100)
 *         o 13 columnas (formato antiguo con id numerico). Ver
 *         fed_importar_avances.php.
 *  v4   - Separacion en paginas; codigo comun en includes/fed_common.php.
 *  v4.1 - Con Mes y Establecimiento en "todos", el detalle muestra una fila por
 *         establecimiento y suma numerador y denominador de todos sus meses.
 *         Se oculta Fecha Importacion y se retiran el resumen por
 *         establecimiento y la vista de tarjetas.
 *  v5   - Rediseño en dos paneles enlazados (tarjetas -> detalle). La tabla de
 *         detalle se agrupa SIEMPRE por establecimiento directamente en SQL
 *         (GROUP BY renipress, establecimiento) con el porcentaje recalculado
 *         sobre los totales sumados. Se agrega el ranking con barras.
 *  v6   - Navegacion un panel a la vez: al cargar solo Panel 01; el boton
 *         "Ver detalle" muestra solo el Panel 02; el boton "Regresar" del
 *         Panel 02 vuelve al Panel 01.
 *  v7   - Seccion Ranking a 2 columnas: izquierda "Avance por Mes" (linea de
 *         tendencia con selector de establecimiento por radio-botones; al
 *         cargar agrega todos los establecimientos) y derecha el ranking de
 *         barras.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/fed_common.php';

/* ============================================================================
 * 0. COMPATIBILIDAD CON URL ANTIGUAS (?tab=gestion | ?tab=importar)
 *    Antes el panel de administracion eran pestañas de esta pagina; ahora son
 *    paginas independientes, por lo que se redirige a cada una.
 * ========================================================================== */
$tabLegacy = trim($_GET['tab'] ?? '');
if ($tabLegacy === 'gestion') {
    header('Location: fed_gestion_indicadores.php');
    exit;
}
if ($tabLegacy === 'importar') {
    header('Location: fed_importar_avances.php');
    exit;
}

/* ============================================================================
 * 1. PARAMETROS DE LA VISTA (GET)
 *    ind  (id_indicador del Panel 02; vacio = primer indicador activo)
 *    anio (obligatorio; por defecto el mas reciente con datos)
 *    mes  ('' = Todos los meses, valor por defecto)
 *    est  (renipress; '' = Todos los establecimientos)
 * ========================================================================== */

$fAnio = trim($_GET['anio'] ?? '');
$fMes  = trim($_GET['mes'] ?? '');
$fEst  = trim($_GET['est'] ?? '');
$fInd  = trim($_GET['ind'] ?? '');

if ($fInd !== '' && !ctype_digit($fInd)) {
    $fInd = '';
}
if ($fMes !== '' && !preg_match('/^(0[1-9]|1[0-2])$/', $fMes)) {
    $fMes = '';
}

$aniosDisp = [];
$establecimientosDisp = [];
$indicadoresFiltro = [];   // indicadores activos (selector del Panel 02)

if ($tablaAvances !== null) {
    $aniosDisp = $pdo->query("SELECT DISTINCT anio FROM `{$tablaAvances}` WHERE anio IS NOT NULL ORDER BY anio DESC")
                     ->fetchAll(PDO::FETCH_COLUMN);
}
if (empty($aniosDisp)) {
    $aniosDisp = [date('Y')];
}
if ($fAnio === '' || !in_array($fAnio, $aniosDisp, true)) {
    // Por defecto: el anio mas reciente con datos (o el anio actual)
    $fAnio = in_array((string)date('Y'), array_map('strval', $aniosDisp), true) ? (string)date('Y') : (string)$aniosDisp[0];
}

if ($tablaAvances !== null) {
    $establecimientosDisp = $pdo->query("SELECT renipress, establecimiento FROM `{$tablaAvances}`
                                         GROUP BY renipress, establecimiento ORDER BY establecimiento ASC")
                                  ->fetchAll();
}
if ($tablaIndicadores !== null) {
    $indicadoresFiltro = $pdo->query("SELECT id_indicador, codigo, nombre FROM `{$tablaIndicadores}`
                                      WHERE estado = 1 ORDER BY orden ASC, codigo ASC")->fetchAll();
}

/* ============================================================================
 * 2. PANEL 01: AGREGADO GLOBAL POR INDICADOR (sin filtros; todos los periodos)
 *    Cada tarjeta muestra: codigo, nombre, descripcion, meta_anual % y
 *    promedio total de porcentaje_avance.
 * ========================================================================== */

$panel01 = [];
$avanceGlobalPonderado = null;
$pesoTotal = 0.0;

if ($sistemaOK) {
    // v8: la ficha tecnica PDF solo se consulta si la columna ya existe
    $colFicha = $tieneFichaPdf ? ', i.ficha_tecnica_pdf' : '';
    $sql01 = "SELECT i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                     i.meta_anual, i.peso_ponderado, i.responsable{$colFicha},
                     COUNT(a.id_avance)          AS n_reg,
                     AVG(a.porcentaje_avance)    AS pct_prom,
                     SUM(a.valor_numerador)      AS sum_num,
                     SUM(a.valor_denominador)    AS sum_den,
                     COUNT(DISTINCT a.renipress) AS n_est
              FROM `{$tablaIndicadores}` i
              LEFT JOIN `{$tablaAvances}` a ON a.id_indicador = i.id_indicador
              WHERE i.estado = 1
              GROUP BY i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                       i.meta_anual, i.peso_ponderado, i.responsable{$colFicha}
              ORDER BY i.orden ASC, i.codigo ASC";
    $panel01 = $pdo->query($sql01)->fetchAll();

    // Avance global ponderado del convenio (con los pesos de cada indicador)
    foreach ($panel01 as $ri) {
        $sumNum = $ri['sum_num'] !== null ? (float)$ri['sum_num'] : null;
        $sumDen = $ri['sum_den'] !== null ? (float)$ri['sum_den'] : null;
        $pctCalc = ($sumDen !== null && $sumDen > 0) ? round(($sumNum / $sumDen) * 100, 2) : null;
        $pct = $pctCalc !== null ? $pctCalc : ($ri['pct_prom'] !== null ? (float)$ri['pct_prom'] : null);
        $peso = $ri['peso_ponderado'] !== null ? (float)$ri['peso_ponderado'] : 0;
        if ($pct !== null && (int)$ri['n_reg'] > 0) {
            $avanceGlobalPonderado = ($avanceGlobalPonderado ?? 0) + ($pct * $peso / 100);
            $pesoTotal += $peso;
        }
    }
    if ($pesoTotal > 0 && $avanceGlobalPonderado !== null) {
        $avanceGlobalPonderado = round(($avanceGlobalPonderado / $pesoTotal) * 100, 1);
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
 *    Aplica los filtros anio (obligatorio), mes ('' = todos) y establecimiento.
 *    El detalle se agrupa SIEMPRE por establecimiento: numerador y denominador
 *    se suman (todos los meses cuando mes = todos) y el porcentaje se
 *    recalcula sobre esos totales.
 * ========================================================================== */

$statsInd = null;    // totales de la cabecera del Panel 02
$detalleEst = [];    // filas agrupadas de la tabla de detalle
$rankingEst = [];    // mismo detalle ordenado de mejor a peor avance
$pctDetalleGlobal = null;
$pctCabecera = null;
$fedTrendJson = [];  // v7: datos del grafico "Avance por Mes" (linea de tendencia)

if ($sistemaOK && $indSel !== null) {
    $con = ['a.id_indicador = ?', 'a.anio = ?'];
    $params = [$indId, $fAnio];
    if ($fMes !== '') { $con[] = 'a.mes = ?';       $params[] = $fMes; }
    if ($fEst !== '') { $con[] = 'a.renipress = ?'; $params[] = $fEst; }
    $where = implode(' AND ', $con);

    // --- 4.1 Totales de la cabecera (suma numerador/denominador y promedio) ---
    $st = $pdo->prepare("SELECT COUNT(*) AS n_reg,
                                COUNT(DISTINCT a.renipress) AS n_est,
                                SUM(a.valor_numerador)   AS sum_num,
                                SUM(a.valor_denominador) AS sum_den,
                                AVG(a.porcentaje_avance) AS pct_prom
                         FROM `{$tablaAvances}` a WHERE {$where}");
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
                                AVG(a.porcentaje_avance) AS pct_prom
                         FROM `{$tablaAvances}` a WHERE {$where}
                         GROUP BY a.renipress, a.establecimiento
                         ORDER BY a.establecimiento ASC");
    $st->execute($params);
    $detalleEst = $st->fetchAll();
    foreach ($detalleEst as $k => $de) {
        $num = (float)$de['sum_num'];
        $den = (float)$de['sum_den'];
        if ($den > 0) {
            $detalleEst[$k]['pct'] = round(($num / $den) * 100, 2);
        } elseif ($de['pct_prom'] !== null) {
            $detalleEst[$k]['pct'] = round((float)$de['pct_prom'], 2);
        } else {
            $detalleEst[$k]['pct'] = null;
        }
    }

    // --- 4.3 Ranking de establecimientos: mejor avance primero ---
    $rankingEst = $detalleEst;
    usort($rankingEst, function ($x, $y) {
        $px = $x['pct'];
        $py = $y['pct'];
        if ($px === null && $py === null) { return strcasecmp((string)$x['establecimiento'], (string)$y['establecimiento']); }
        if ($px === null) { return 1; }
        if ($py === null) { return -1; }
        return $py <=> $px;
    });

    // --- 4.4 Porcentaje global del detalle (fila TOTAL de la tabla) ---
    if ($statsInd !== null) {
        $gNum = (float)$statsInd['sum_num'];
        $gDen = (float)$statsInd['sum_den'];
        if ($gDen > 0) {
            $pctDetalleGlobal = round(($gNum / $gDen) * 100, 2);
        } elseif ($statsInd['pct_prom'] !== null) {
            $pctDetalleGlobal = round((float)$statsInd['pct_prom'], 1);
        }
    }

    /* --- 4.5 Serie mensual del grafico "Avance por Mes" (linea de tendencia, v7) ---
     * Datos de establecimiento, mes y porcentaje_avance del indicador y anio
     * del panel (siempre muestra los 12 meses; no depende de los filtros
     * mes/establecimiento del Panel 02). Se generan:
     *   - Serie "__todos__": agregado de TODOS los establecimientos (numerador
     *     y denominador sumados por mes, porcentaje recalculado). Es la serie
     *     seleccionada al cargar la pagina.
     *   - Una serie por establecimiento con su porcentaje mensual.
     * Todo se envia al navegador como JSON y el cambio de serie se hace con
     * radio-botones en JavaScript (sin recargar la pagina). */
    $filasTrend = [];
    try {
        $st = $pdo->prepare("SELECT a.renipress, a.establecimiento, a.mes,
                                    SUM(a.valor_numerador)   AS num,
                                    SUM(a.valor_denominador) AS den,
                                    AVG(a.porcentaje_avance) AS pct_prom
                             FROM `{$tablaAvances}` a
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
            if ((float)$den > 0) { return round(((float)$num / (float)$den) * 100, 2); }
            return $pctProm !== null ? round((float)$pctProm, 2) : null;
        };

        $porEst = [];                                   // serie por establecimiento
        $agg     = array_fill(1, 12, ['num' => 0.0, 'den' => 0.0]); // agregado por mes
        foreach ($filasTrend as $ft) {
            $rp = (string)$ft['renipress'];
            $m  = (int)$ft['mes'];
            if ($m < 1 || $m > 12) { continue; }
            if (!isset($porEst[$rp])) {
                $porEst[$rp] = ['nombre' => (string)$ft['establecimiento'],
                                'pct' => array_fill(0, 12, null),
                                'num' => array_fill(0, 12, null),
                                'den' => array_fill(0, 12, null)];
            }
            $num = (float)$ft['num'];
            $den = (float)$ft['den'];
            $porEst[$rp]['pct'][$m - 1] = $pctTrendMes($num, $den, $ft['pct_prom']);
            $porEst[$rp]['num'][$m - 1] = round($num, 2);
            $porEst[$rp]['den'][$m - 1] = round($den, 2);
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
        foreach ($porEst as $rp => $e) {
            $seriesTrend[] = ['key' => $rp, 'nombre' => $e['nombre'],
                              'pct' => $e['pct'], 'num' => $e['num'], 'den' => $e['den']];
        }

        $fedTrendJson = ['anio' => (string)$fAnio, 'series' => $seriesTrend];
    }
}

/* ============================================================================
 * 5. HELPERS DE LA VISTA Y ENLACES ENTRE PANELES
 * ========================================================================== */

/** Formatea numeros (numerador/denominador) tolerando nulos */
function fedFmtNumero($v, $dec = 2) {
    return $v === null ? '-' : number_format((float)$v, $dec);
}

/** Formatea la meta anual quitando ceros sobrantes (95.00 -> 95; 87.50 -> 87.5) */
function fedFmtPctMeta($v) {
    if ($v === null) { return '-'; }
    $f = rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.');
    return $f === '' ? '0' : $f;
}

/** Nombre del establecimiento filtrado (para la linea de contexto del Panel 02) */
$nombreEstFiltro = '';
if ($fEst !== '') {
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
 * 5.1 VISTA ACTIVA (v6): un solo panel visible a la vez
 *  - Carga por defecto (sin vista y sin ind) -> PANEL 01 visible, PANEL 02 oculto.
 *  - vista=detalle (o enlaces antiguos con ?ind=ID) -> PANEL 02 visible,
 *    PANEL 01 oculto. El boton "Regresar" del Panel 02 vuelve al Panel 01.
 *  - Sin indicadores activos no hay detalle posible: siempre se muestra el
 *    Panel 01 con su aviso.
 * --------------------------------------------------------------------------- */
$verDetalle = (($_GET['vista'] ?? '') === 'detalle')
           || (($_GET['vista'] ?? '') === '' && $fInd !== '');
if (empty($indicadoresFiltro)) {
    $verDetalle = false;
}

/* URL del boton Regresar del Panel 02 -> Panel 01 (conserva anio/mes/est) */
$urlVolverPanel1 = 'convenio_fed.php' . (empty($qsPanel2) ? '' : '?' . http_build_query($qsPanel2));

$pageTitle = 'Convenio FED - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* ---- Estilos especificos de la vista de consulta del modulo Convenio FED (v5) ---- */
html { scroll-behavior: smooth; }
.fed-anchor { scroll-margin-top: 14px; }
.fed-table-detalle th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
.fed-table-detalle th.sortable:hover { background: #e9ecef; }
.fed-table-detalle th.sortable::after { content: "\f0dc"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: .68rem; margin-left: 5px; color: #adb5bd; }
.fed-table-detalle th.sortable.sort-asc::after { content: "\f0de"; color: #0d6efd; }
.fed-table-detalle th.sortable.sort-desc::after { content: "\f0dd"; color: #0d6efd; }
.fed-num { font-variant-numeric: tabular-nums; }
.fed-barra { height: 7px; border-radius: 4px; }
.fed-est-name { font-weight: 600; }
.fed-info-import { background: #eef6ff; border: 1px solid #cfe5ff; border-radius: 8px; }

/* Barra para el rango Bajo (40-59%): complementa colorBarraFED() */
.bg-orange { background-color: #e67e22 !important; }

/* Insignia de numero de panel */
.fed-panel-badge {
    font-size: .72rem; letter-spacing: .5px; text-transform: uppercase; font-weight: 700;
    color: var(--his-primary); background: rgba(26, 82, 118, .08);
    border: 1px solid rgba(26, 82, 118, .18); padding: 3px 12px; border-radius: 20px; white-space: nowrap;
}

/* Panel 01: tarjetas de indicadores */
.fed-desc {
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
    overflow: hidden; font-size: .78rem; line-height: 1.35; margin-bottom: .5rem;
}

/* v8: boton/icono de la ficha tecnica PDF del indicador (Panel 02) */
.fed-ficha-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 12px; border-radius: 20px; font-size: .74rem; font-weight: 600;
    color: #b02a37; background: #fdecec; border: 1px solid #f1b8bd;
    text-decoration: none; white-space: nowrap; transition: all .15s ease;
}
.fed-ficha-btn:hover, .fed-ficha-btn:focus { background: #b02a37; color: #fff; border-color: #b02a37; }
.fed-ficha-btn .fa-file-pdf { font-size: .95rem; }

/* Panel 02: cabecera con datos del indicador */
.fed-stat-tile {
    background: #f6f9fc; border: 1px solid #e3ebf3; border-radius: 10px;
    padding: .6rem .5rem; height: 100%; text-align: center;
}
.fed-tile-label {
    font-size: .66rem; text-transform: uppercase; letter-spacing: .4px;
    color: #6c757d; font-weight: 600; margin-bottom: .15rem; white-space: nowrap;
}
.fed-tile-value { font-size: 1.28rem; font-weight: 700; color: #2c3e50; line-height: 1.15; font-variant-numeric: tabular-nums; }
.fed-tile-sub { font-size: .66rem; color: #98a6b3; margin-top: .1rem; }
.fed-tile-meta .fed-tile-value   { color: var(--his-primary); }
.fed-tile-avance .fed-tile-value { color: #1e8449; }
.fed-tile-num .fed-tile-value    { color: var(--his-info); }
.fed-tile-den .fed-tile-value    { color: #27ae60; }

/* Panel 02: ranking de establecimientos (grafico de barras con CSS) */
.fed-rank-row { display: flex; align-items: center; gap: .65rem; padding: .4rem 0; }
.fed-rank-row + .fed-rank-row { border-top: 1px dashed #eef1f5; }
.fed-rank-pos {
    width: 30px; height: 30px; border-radius: 50%; background: #eef2f7; color: #495057;
    font-weight: 700; font-size: .78rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.fed-pos-1 { background: #ffd43b; color: #7a5c00; }
.fed-pos-2 { background: #ced4da; color: #343a40; }
.fed-pos-3 { background: #f0c29b; color: #7a4300; }
.fed-rank-body { flex: 1 1 auto; min-width: 0; }
.fed-rank-head { display: flex; justify-content: space-between; align-items: baseline; gap: .75rem; margin-bottom: 3px; }
.fed-rank-name { font-weight: 600; font-size: .82rem; color: #2c3e50; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fed-rank-val { font-weight: 700; font-size: .82rem; color: #2c3e50; font-variant-numeric: tabular-nums; white-space: nowrap; }
.fed-rank-val.fed-sin-dato { color: #98a6b3; font-weight: 500; }
.fed-rank-track { position: relative; height: 16px; background: #eef2f7; border-radius: 8px; }
.fed-rank-bar { height: 100%; border-radius: 8px; transition: width .8s ease; }
.fed-rank-goal { position: absolute; top: -3px; bottom: -3px; width: 0; border-left: 2px dashed #8395a7; }
.fed-legend-dot { width: 12px; height: 12px; border-radius: 3px; display: inline-block; flex-shrink: 0; }
.fed-legend-goal { width: 0; height: 12px; border-left: 2px dashed #8395a7; display: inline-block; }

/* v7: fila a 2 columnas - grafico "Avance por Mes" (izquierda) y ranking (derecha) */
.fed-chart-box { background: #fff; border: 1px solid #e3ebf3; border-radius: 10px; padding: .9rem 1rem 1rem; box-shadow: 0 1px 2px rgba(16, 42, 67, .04); }
.fed-trend-radios { max-height: 118px; overflow-y: auto; padding: 2px 4px 2px 2px; }
.fed-trend-radios .fed-trend-pill { border-radius: 20px; padding: .18rem .65rem; font-size: .72rem; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fed-trend-radios .btn-check:checked + .fed-trend-pill { color: #fff; background: var(--his-primary); border-color: var(--his-primary); }
.fed-trend-wrap { position: relative; height: 330px; }
</style>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-medal me-2"></i>Convenio FED - Fondo de Estimulo al Desempeno</h4>
            <p class="subtitle">Zona Sanitaria Perené - Seguimiento del avance de los indicadores FED</p>
        </div>
        <?php if ($esAdmin && $sistemaOK): ?>
        <div class="d-flex gap-2">
            <a href="fed_gestion_indicadores.php" class="btn btn-his btn-sm">
                <i class="fas fa-tasks me-1"></i> Gestion de Indicadores
            </a>
            <a href="fed_importar_avances.php" class="btn btn-warning btn-sm text-dark fw-semibold">
                <i class="fas fa-file-import me-1"></i> Importar Resultados.txt
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$sistemaOK): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php if ($tablaIndicadores === null): ?>
            La tabla <code>CONVENIO_FED_INDICADORES</code> no existe en la base de datos.
        <?php else: ?>
            La tabla <code>CONVENIO_FED_AVANCES</code> no existe en la base de datos.
        <?php endif; ?>
        Ejecute el script <code>Database/update_fed_v3.sql</code> desde phpMyAdmin para crear las tablas
        del Convenio FED y la columna de fecha de importacion.
    </div>
<?php endif; ?>

<?php if ($sistemaOK): ?>

<!-- ===================== AVISO DE ACTUALIZACION DE DATOS ===================== -->
<div class="fed-info-import d-flex align-items-center gap-2 px-3 py-2 mb-4">
    <i class="fas fa-clock text-primary fs-5"></i>
    <div class="small">
        <strong>Datos actualizados al:</strong>
        <?php
        $ultimaActualizacion = null;
        try {
            $colUltima = $tieneFechaImportacion
                ? 'MAX(COALESCE(fecha_importacion, fecha_registro))'
                : 'MAX(fecha_registro)';
            $ultimaActualizacion = $pdo->query("SELECT {$colUltima} FROM `{$tablaAvances}`")->fetchColumn();
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
<!-- PANEL 01 · PRESENTACION: tarjetas de indicadores              -->
<!-- v6: visible solo en la carga por defecto; el Panel 02 queda   -->
<!--     oculto hasta pulsar "Ver detalle" en una tarjeta.         -->
<!-- ============================================================= -->
<?php if (!$verDetalle): ?>
<div class="fed-anchor" id="panel01"></div>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="fed-panel-badge">Panel 01</span>
        <h5 class="mb-0 fw-bold text-dark">Presentacion de Indicadores</h5>
    </div>
    <span class="stat-mini"><i class="fas fa-layer-group me-1"></i><?= count($panel01) ?> indicadores activos &middot; Acumulado de todos los periodos</span>
</div>

<?php if (empty($panel01)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        No hay indicadores activos registrados en el convenio FED.
        <?php if ($esAdmin): ?>Agreguelos en <a href="fed_gestion_indicadores.php"><strong>Gestion de Indicadores</strong></a>.<?php endif; ?>
    </div>
<?php else: ?>

<?php if ($avanceGlobalPonderado !== null): ?>
<!-- Avance global ponderado del convenio -->
<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Avance Global del Convenio FED (ponderado)</h6>
            <span class="avance-badge <?= clasificarAvanceFED($avanceGlobalPonderado)['clase'] ?>">
                <?= number_format($avanceGlobalPonderado, 1) ?>% &middot; <?= clasificarAvanceFED($avanceGlobalPonderado)['texto'] ?>
            </span>
        </div>
        <div class="progress" style="height: 18px; border-radius: 9px;">
            <div class="progress-bar <?= colorBarraFED($avanceGlobalPonderado) ?>"
                 role="progressbar" style="width: <?= min(100, $avanceGlobalPonderado) ?>%;"
                 aria-valuenow="<?= $avanceGlobalPonderado ?>" aria-valuemin="0" aria-valuemax="100">
                <?= number_format($avanceGlobalPonderado, 1) ?>%
            </div>
        </div>
        
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($panel01 as $p):
        $pctCard = $p['pct_prom'] !== null ? round((float)$p['pct_prom'], 1) : null;
        $avCard  = clasificarAvanceFED($pctCard);
        $urlPanel2 = 'convenio_fed.php?' . http_build_query(array_merge($qsPanel2, ['vista' => 'detalle', 'ind' => (int)$p['id_indicador']])) . '#panel02';
        $nombreCorto = mb_strimwidth((string)$p['nombre'], 0, 90, '...');
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
                    <p class="fed-desc text-muted" title="<?= clean($p['descripcion']) ?>"><?= clean($p['descripcion']) ?></p>
                <?php endif; ?>
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted"><i class="fas fa-bullseye me-1"></i>Meta anual</span>
                        <strong><?= $p['meta_anual'] !== null ? fedFmtPctMeta($p['meta_anual']) . '%' : '-' ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mb-1">
                        <span class="text-muted"><i class="fas fa-percent me-1"></i>Promedio de avance</span>
                        <?php if ($pctCard !== null): ?>
                            <strong class="text-primary"><?= number_format($pctCard, 1) ?>%</strong>
                        <?php else: ?>
                            <span class="text-muted">Sin dato</span>
                        <?php endif; ?>
                    </div>
                    <div class="progress progress-his fed-barra mb-2">
                        <?php if ($pctCard !== null): ?>
                            <div class="progress-bar <?= colorBarraFED($pctCard) ?>" role="progressbar" style="width: <?= min(100, $pctCard) ?>%;"></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mb-2">
                        <span><i class="fas fa-database me-1"></i><?= number_format((int)$p['n_reg']) ?> registros</span>
                        <span><i class="fas fa-hospital me-1"></i><?= (int)$p['n_est'] ?> establec.</span>
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
<?php endif; /* fin vista Panel 01 (v6): el Panel 01 se oculta al consultar el detalle */ ?>

<!-- ============================================================= -->
<!-- PANEL 02 · DETALLE DEL INDICADOR SELECCIONADO                 -->
<!-- v6: visible solo al pulsar "Ver detalle" en el Panel 01;      -->
<!--     incluye el boton "Regresar" para volver al Panel 01.      -->
<!-- ============================================================= -->
<?php if ($verDetalle): ?>
<div class="fed-anchor" id="panel02"></div>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2">
        <span class="fed-panel-badge">Panel 02</span>
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
        <?php if ($esAdmin): ?>creado desde <a href="fed_gestion_indicadores.php"><strong>Gestion de Indicadores</strong></a><?php endif; ?>.
    </div>
<?php else: ?>

<div class="card shadow-sm mb-4">
    <!-- ---------- Cabecera del indicador ---------- -->
    <div class="card-header bg-white py-3">
        <div class="row g-3 align-items-center">
            <div class="col-xl-5">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="indicador-codigo"><?= clean($indSel['codigo']) ?></span>
                    <span class="avance-badge <?= clasificarAvanceFED($pctCabecera)['clase'] ?>"><?= clasificarAvanceFED($pctCabecera)['texto'] ?></span>
                    <?php /* v8: icono de la ficha tecnica del indicador (abre el PDF en otra ventana) */ ?>
                    <?php if ($tieneFichaPdf && !empty($indSel['ficha_tecnica_pdf'])): ?>
                        <a href="<?= clean(fedFichaUrl($indSel['ficha_tecnica_pdf'])) ?>" target="_blank" rel="noopener"
                           class="fed-ficha-btn" title="Ficha tecnica del indicador: abre el PDF en otra ventana">
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
                    <?php if ($indSel['peso_ponderado'] !== null): ?>
                        <span><i class="fas fa-balance-scale me-1"></i>Peso: <?= fedFmtPctMeta($indSel['peso_ponderado']) ?>%</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <div class="fed-stat-tile fed-tile-meta">
                            <div class="fed-tile-label"><i class="fas fa-bullseye me-1"></i>Meta Anual</div>
                            <div class="fed-tile-value"><?= $indSel['meta_anual'] !== null ? fedFmtPctMeta($indSel['meta_anual']) . '%' : '-' ?></div>
                            <div class="fed-tile-sub">meta_anual</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fed-stat-tile fed-tile-avance">
                            <div class="fed-tile-label"><i class="fas fa-percent me-1"></i>Promedio Total</div>
                            <div class="fed-tile-value"><?= $pctCabecera !== null ? number_format($pctCabecera, 1) . '%' : 'Sin dato' ?></div>
                            <div class="fed-tile-sub">promedio porcentaje_avance</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fed-stat-tile fed-tile-num">
                            <div class="fed-tile-label"><i class="fas fa-arrow-up me-1"></i>Suma Numerador</div>
                            <div class="fed-tile-value"><?= fedFmtNumero($statsInd['sum_num'] ?? null) ?></div>
                            <div class="fed-tile-sub">suma valor_numerador</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="fed-stat-tile fed-tile-den">
                            <div class="fed-tile-label"><i class="fas fa-arrow-down me-1"></i>Suma Denominador</div>
                            <div class="fed-tile-value"><?= fedFmtNumero($statsInd['sum_den'] ?? null) ?></div>
                            <div class="fed-tile-sub">suma valor_denominador</div>
                        </div>
                    </div>
                </div>
                <div class="small text-muted mt-2 text-center text-xl-end">
                    <i class="fas fa-filter me-1"></i>
                    Periodo consultado: <strong><?= clean($fAnio) ?></strong> &middot;
                    <?= $fMes !== '' ? getNombreMes((int)$fMes) : 'Todos los meses' ?> &middot;
                    <?= $fEst !== '' ? clean($nombreEstFiltro) : 'Todos los establecimientos (' . count($establecimientosDisp) . ')' ?>
                    <?php if ($statsInd !== null): ?>
                        &middot; <?= number_format((int)$statsInd['n_reg']) ?> registros &middot; <?= (int)$statsInd['n_est'] ?> establecimientos
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ---------- Filtros del Panel 02 ---------- -->
    <div class="card-body border-top py-3">
        <form method="GET" action="convenio_fed.php#panel02" class="row g-2 align-items-end" id="formPanel02">
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
                        <option value="<?= date('Y') ?>" <?= (string)$fAnio === (string)date('Y') ? 'selected' : '' ?>><?= date('Y') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label small fw-semibold mb-0">Mes</label>
                <select name="mes" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los meses --</option>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $mPad = str_pad((string)$m, 2, '0', STR_PAD_LEFT); ?>
                        <option value="<?= $mPad ?>" <?= $fMes === $mPad ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-lg-4 col-md-6 col-12">
                <label class="form-label small fw-semibold mb-0">Establecimiento</label>
                <select name="est" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los establecimientos (<?= count($establecimientosDisp) ?>) --</option>
                    <?php foreach ($establecimientosDisp as $e): ?>
                        <option value="<?= clean($e['renipress']) ?>" <?= $fEst === (string)$e['renipress'] ? 'selected' : '' ?>>
                            <?= clean($e['establecimiento']) ?> (<?= clean($e['renipress']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1 col-12">
                <button type="submit" class="btn btn-his btn-sm w-100" title="Consultar"><i class="fas fa-search"></i></button>
            </div>
            <div class="col-12">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    .... <strong>.</strong> .
                </small>
            </div>
        </form>
    </div>

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
        </small>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 fed-table-detalle" id="tablaDetalleEst">
                <thead class="table-light">
                    <tr>
                        <th class="sortable" data-col="0" data-type="str" data-csv="renipress">Renipress</th>
                        <th class="sortable" data-col="1" data-type="str" data-csv="establecimiento">Establecimiento</th>
                        <th class="sortable text-end" data-col="2" data-type="num" data-csv="valor_numerador">Valor Numerador</th>
                        <th class="sortable text-end" data-col="3" data-type="num" data-csv="valor_denominador">Valor Denominador</th>
                        <th class="sortable text-end" data-col="4" data-type="num" data-csv="porcentaje_avance">Porcentaje Avance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detalleEst)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                No hay registros de avances para este indicador con los filtros seleccionados.
                                <?php if ($esAdmin): ?>Utilice el modulo <a href="fed_importar_avances.php"><strong>Importar Resultados.txt</strong></a> para cargar los datos.<?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detalleEst as $de):
                            $pct = $de['pct'];
                            $av  = clasificarAvanceFED($pct);
                        ?>
                        <tr data-sortable="1">
                            <td data-sort="<?= clean($de['renipress']) ?>"><code><?= clean($de['renipress']) ?></code></td>
                            <td class="fed-est-name" data-sort="<?= clean($de['establecimiento']) ?>"><?= clean($de['establecimiento']) ?></td>
                            <td class="text-end fed-num" data-sort="<?= (float)$de['sum_num'] ?>" data-csv="<?= number_format((float)$de['sum_num'], 2, '.', '') ?>"><?= fedFmtNumero($de['sum_num']) ?></td>
                            <td class="text-end fed-num" data-sort="<?= (float)$de['sum_den'] ?>" data-csv="<?= number_format((float)$de['sum_den'], 2, '.', '') ?>"><?= fedFmtNumero($de['sum_den']) ?></td>
                            <td class="text-end" data-sort="<?= $pct ?? -1 ?>" data-csv="<?= $pct !== null ? number_format($pct, 2, '.', '') : '' ?>">
                                <?php if ($pct !== null): ?>
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <span class="avance-badge <?= $av['clase'] ?>" style="min-width:64px;"><?= number_format($pct, 1) ?>%</span>
                                        <div class="progress progress-his flex-grow-1 fed-barra">
                                            <div class="progress-bar <?= colorBarraFED($pct) ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%;"></div>
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
                        <td class="text-end fed-num"><?= fedFmtNumero($statsInd['sum_num']) ?></td>
                        <td class="text-end fed-num"><?= fedFmtNumero($statsInd['sum_den']) ?></td>
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
         Fila a 2 columnas (v7):
           IZQUIERDA -> "Avance por Mes" (linea de tendencia con Chart.js,
                        selector de establecimiento por radio-botones)
           DERECHA   -> Ranking de Establecimientos (barras)
         ================================================================== -->
    <div class="card-body border-top py-3">
        <div class="row g-3">

            <!-- ---------- IZQUIERDA: Avance por Mes (linea de tendencia) ---------- -->
            <div class="col-lg-6 d-flex">
                <div class="fed-chart-box w-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-success"></i>Avance por Mes</h6>
                        <span class="stat-mini"><i class="fas fa-calendar me-1"></i>Año <?= clean($fAnio) ?></span>
                    </div>
                    <small class="text-muted d-block mb-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Línea de tendencia del porcentaje de avance por mes del indicador y año del panel.
                        
                    </small>

                    <?php if (empty($fedTrendJson)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-line fa-2x d-block mb-2"></i>
                            No hay datos mensuales para graficar en <?= clean($fAnio) ?>.
                        </div>
                    <?php else: ?>
                        <!-- Selector de establecimiento (radio-botones) -->
                        <div class="fed-trend-radios d-flex flex-wrap gap-1 mb-3" id="fedTrendRadios">
                            <?php foreach ($fedTrendJson['series'] as $i => $s): ?>
                                <input type="radio" class="btn-check" name="fedTrendEst" id="fedTrendEst<?= $i ?>"
                                       value="<?= $i ?>" autocomplete="off" <?= $i === 0 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary btn-sm fed-trend-pill" for="fedTrendEst<?= $i ?>"
                                       title="<?= clean($s['nombre']) ?>">
                                    <?php if ($i === 0): ?><i class="fas fa-layer-group me-1"></i><?php endif; ?><?= clean($s['nombre']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <!-- Grafico de linea (Chart.js) -->
                        <div class="fed-trend-wrap">
                            <canvas id="fedTrendChart" role="img" aria-label="Grafico de linea: avance por mes"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ---------- DERECHA: Ranking de Establecimientos (grafico de barras) ---------- -->
            <div class="col-lg-6 d-flex">
                <div class="fed-chart-box w-100">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <h6 class="mb-0 fw-bold"><i class="fas fa-ranking-star me-2 text-primary"></i>Ranking de Establecimientos</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($indSel['meta_anual'] !== null): ?>
                                <span class="stat-mini"><i class="fas fa-bullseye me-1"></i>Meta anual: <?= fedFmtPctMeta($indSel['meta_anual']) ?>%</span>
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
                        <div class="fed-rank-list">
                            <?php $pos = 0; foreach ($rankingEst as $rk):
                                $pos++;
                                $pctR = $rk['pct'];
                                $colorR = colorBarraFED($pctR);
                                $widthR = $pctR !== null ? min(100, max(0, $pctR)) : 0;
                                $clsPos = $pos <= 3 ? ' fed-pos-' . $pos : '';
                                $goalLeft = ($indSel['meta_anual'] !== null && (float)$indSel['meta_anual'] > 0)
                                    ? min(100, max(0, (float)$indSel['meta_anual'])) : null;
                            ?>
                            <div class="fed-rank-row">
                                <div class="fed-rank-pos<?= $clsPos ?>" title="Puesto <?= $pos ?>">
                                    <?php if ($pos <= 3 && $pctR !== null): ?><i class="fas fa-medal"></i><?php else: ?><?= $pos ?><?php endif; ?>
                                </div>
                                <div class="fed-rank-body">
                                    <div class="fed-rank-head">
                                        <span class="fed-rank-name" title="<?= clean($rk['establecimiento']) ?> (<?= clean($rk['renipress']) ?>)">
                                            <?= clean($rk['establecimiento']) ?>
                                            <small class="text-muted fw-normal"><code><?= clean($rk['renipress']) ?></code></small>
                                        </span>
                                        <?php if ($pctR !== null): ?>
                                            <span class="fed-rank-val"><?= number_format($pctR, 1) ?>%</span>
                                        <?php else: ?>
                                            <span class="fed-rank-val fed-sin-dato">Sin dato</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fed-rank-track" title="Numerador: <?= fedFmtNumero($rk['sum_num']) ?> | Denominador: <?= fedFmtNumero($rk['sum_den']) ?> | <?= number_format((int)$rk['n_reg']) ?> registro(s)">
                                        <div class="fed-rank-bar <?= $colorR ?>" data-w="<?= $widthR ?>" style="width: <?= $widthR ?>%;"></div>
                                        <?php if ($goalLeft !== null): ?>
                                            <div class="fed-rank-goal" style="left: <?= $goalLeft ?>%;" title="Meta anual: <?= fedFmtPctMeta($indSel['meta_anual']) ?>%"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mt-3 small text-muted">
                            <span class="d-inline-flex align-items-center gap-1"><span class="fed-legend-dot bg-success"></span>Excelente &ge; 90%</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="fed-legend-dot bg-info"></span>Bueno 75-89%</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="fed-legend-dot bg-warning"></span>Regular 60-74%</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="fed-legend-dot bg-orange"></span>Bajo 40-59%</span>
                            <span class="d-inline-flex align-items-center gap-1"><span class="fed-legend-dot bg-danger"></span>Critico &lt; 40%</span>
                            <?php if ($indSel['meta_anual'] !== null && (float)$indSel['meta_anual'] > 0): ?>
                                <span class="d-inline-flex align-items-center gap-1"><span class="fed-legend-goal"></span>Línea de meta (<?= fedFmtPctMeta($indSel['meta_anual']) ?>%)</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php endif; /* fin Panel 02 con indicador seleccionado */ ?>

<!-- Boton Regresar al final del Panel 02 (tras la tabla y el ranking) -->
<div class="text-center mb-4">
    <a href="<?= clean($urlVolverPanel1) ?>" class="btn btn-outline-secondary" title="Vuelve a la Presentacion de Indicadores (Panel 01)">
        <i class="fas fa-arrow-circle-left me-1"></i>Regresar al Panel 01
    </a>
</div>

<?php endif; /* fin vista Panel 02 (v6): visible solo con vista=detalle */ ?>

<?php endif; /* fin $sistemaOK */ ?>

<!-- Chart.js: necesario para el grafico "Avance por Mes" del Panel 02 (v7) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/* ============================================================
 * Interactividad de la vista de consulta FED (JavaScript puro)
 *  - Tabla del Panel 02: busqueda en vivo, orden por columna y CSV.
 *  - Grafico de ranking: animacion de las barras.
 *  - Grafico "Avance por Mes" (v7): linea de tendencia mensual con selector
 *    de establecimiento por radio-botones; al cargar muestra el agregado de
 *    todos los establecimientos y al elegir uno muestra solo su serie.
 *  - Salto automatico al Panel 02 al llegar con ?ind=... o ?vista=detalle sin ancla.
 * ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

    /* ---- Animacion de las barras del ranking ---- */
    document.querySelectorAll('.fed-rank-bar[data-w]').forEach(function (barra) {
        var w = parseFloat(barra.getAttribute('data-w')) || 0;
        barra.style.width = '0%';
        requestAnimationFrame(function () {
            setTimeout(function () { barra.style.width = w + '%'; }, 80);
        });
    });

    /* ------------------------------------------------------------
     * Grafico "Avance por Mes" (v7): linea de tendencia mensual.
     * Los datos llegan desde PHP en fedTrendDatos.series:
     *   [0] = agregado de todos los establecimientos (por defecto)
     *   [n] = serie mensual de cada establecimiento
     * El selector (radio-botones) cambia la serie sin recargar.
     * ------------------------------------------------------------ */
    var fedTrendCanvas = document.getElementById('fedTrendChart');
    var fedTrendDatos  = <?= json_encode($fedTrendJson ?: null, JSON_UNESCAPED_UNICODE) ?: 'null' ?>;
    if (fedTrendCanvas && fedTrendDatos && fedTrendDatos.series && fedTrendDatos.series.length) {
        if (!window.Chart) {
            // CDN no disponible (sin internet): aviso en lugar de grafico
            fedTrendCanvas.closest('.fed-trend-wrap').innerHTML =
                '<div class="text-center text-muted py-5"><i class="fas fa-plug fa-2x d-block mb-2"></i>' +
                'No se pudo cargar Chart.js. Verifique la conexion a internet del servidor o del navegador.</div>';
        } else {
            var MESES_FED = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
            var fedTrendIdx   = 0;   // serie visible (0 = Todos)
            var fedTrendChart = null;

            /** Tendencia lineal (regresion lineal simple) de la serie visible */
            function fedTrendRegresion(arr) {
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
            function fedTrendDatasets(idx) {
                var s = fedTrendDatos.series[idx] || fedTrendDatos.series[0];
                var ds = [{
                    label: s.nombre,
                    data: s.pct,
                    borderColor: '#9acd32',
                    backgroundColor: 'rgba(154, 205, 50, .10)',
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
                var reg = fedTrendRegresion(s.pct);
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

            fedTrendChart = new Chart(fedTrendCanvas, {
                type: 'line',
                data: { labels: MESES_FED, datasets: fedTrendDatasets(0) },
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
                                    return MESES_FED[items[0].dataIndex] + ' ' + fedTrendDatos.anio;
                                },
                                label: function (item) {
                                    var v = item.parsed.y;
                                    return item.dataset.label + ': ' +
                                        (v === null || isNaN(v) ? '-' : v.toFixed(2)) + '%';
                                },
                                afterLabel: function (item) {
                                    if (item.datasetIndex !== 0) { return ''; }
                                    var s = fedTrendDatos.series[fedTrendIdx] || {};
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
            document.querySelectorAll('#fedTrendRadios input[name="fedTrendEst"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (!radio.checked) { return; }
                    fedTrendIdx = parseInt(radio.value, 10) || 0;
                    fedTrendChart.data.datasets = fedTrendDatasets(fedTrendIdx);
                    fedTrendChart.update();
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
            a.download = <?= json_encode('detalle_fed_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)($indSel['codigo'] ?? 'FED')) . '_' . $fAnio . ($fMes !== '' ? '_' . $fMes : '_todos') . '.csv') ?>;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    }

    aplicarFiltro();
});
</script>

<?php include 'includes/footer.php'; ?>
