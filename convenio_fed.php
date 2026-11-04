<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 02: Convenio FED - Fondo de Estimulo al Desempeno
 * Consulta y visualizacion de avances (todos los roles).
 *
 * v4 - Separacion del modulo en paginas independientes (2026-11-04):
 *  Esta pagina queda como vista de consulta: filtros, resumen rapido, avance
 *  global ponderado, detalle por establecimiento y mes y resumen por indicador.
 *  Las funciones de administracion se trasladaron a paginas propias:
 *    - fed_gestion_indicadores.php -> FED: Gestion de Indicadores (solo admin)
 *    - fed_importar_avances.php    -> FED: Importar Avances (solo admin)
 *  Compatibilidad: las URL antiguas convenio_fed.php?tab=gestion y
 *  convenio_fed.php?tab=importar redirigen automaticamente a las paginas nuevas.
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
 * 1. FILTROS DE LA VISTA (GET)
 *    anio | mes ('' = todos los meses) | est (renipress) | ind (id_indicador)
 * ========================================================================== */

$fAnio = trim($_GET['anio'] ?? '');
$fMes  = trim($_GET['mes'] ?? '');
$fEst  = trim($_GET['est'] ?? '');
$fInd  = trim($_GET['ind'] ?? '');

$aniosDisp = [];
$establecimientosDisp = [];
$indicadoresFiltro = [];

if ($tablaAvances !== null) {
    $aniosDisp = $pdo->query("SELECT DISTINCT anio FROM `{$tablaAvances}` WHERE anio IS NOT NULL ORDER BY anio DESC")
                     ->fetchAll(PDO::FETCH_COLUMN);
}
if (empty($aniosDisp)) {
    $aniosDisp = [date('Y')];
}
if ($fAnio === '' || !in_array($fAnio, $aniosDisp, true)) {
    // Por defecto: el anio mas reciente con datos (o el anio actual)
    $fAnio = in_array((string)date('Y'), $aniosDisp, true) ? (string)date('Y') : (string)$aniosDisp[0];
}

if ($tablaAvances !== null) {
    $establecimientosDisp = $pdo->query("SELECT renipress, establecimiento FROM `{$tablaAvances}`
                                         GROUP BY renipress, establecimiento ORDER BY establecimiento ASC")
                                  ->fetchAll();
}
if ($tablaIndicadores !== null) {
    $indicadoresFiltro = $pdo->query("SELECT id_indicador, codigo, nombre, estado FROM `{$tablaIndicadores}`
                                      ORDER BY orden ASC, codigo ASC")->fetchAll();
}
// Validar id de indicador recibido
if ($fInd !== '' && !ctype_digit($fInd)) {
    $fInd = '';
}

/* ============================================================================
 * 2. CONSULTAS DE DATOS PARA LA VISUALIZACION
 * ========================================================================== */

$avances = [];            // detalle (filas de la tabla principal)
$resumenIndicadores = []; // agregado por indicador
$resumenEstablecimientos = []; // agregado por establecimiento (calculado en PHP)
$ultimaActualizacion = null;
$avanceGlobalPonderado = null;
$pesoTotal = 0.0;

if ($sistemaOK) {
    // --- 2.1 Ultima actualizacion de datos (fecha/hora de importacion) ---
    $colUltima = $tieneFechaImportacion
        ? 'MAX(COALESCE(fecha_importacion, fecha_registro))'
        : 'MAX(fecha_registro)';
    $ultimaActualizacion = $pdo->query("SELECT {$colUltima} FROM `{$tablaAvances}`")->fetchColumn();

    // --- 2.2 Detalle de avances con filtros ---
    $colFI = $tieneFechaImportacion ? ', a.fecha_importacion' : ', NULL AS fecha_importacion';
    $sql = "SELECT a.id_avance, a.id_indicador, a.renipress, a.establecimiento, a.anio, a.mes,
                   a.valor_numerador, a.valor_denominador, a.porcentaje_avance, a.observaciones,
                   a.fecha_registro {$colFI},
                   i.codigo, i.nombre AS indicador_nombre
            FROM `{$tablaAvances}` a
            LEFT JOIN `{$tablaIndicadores}` i ON i.id_indicador = a.id_indicador
            WHERE 1=1";
    $params = [];
    if ($fAnio !== '') { $sql .= " AND a.anio = ?";            $params[] = $fAnio; }
    if ($fMes  !== '') { $sql .= " AND a.mes = ?";             $params[] = $fMes; }
    if ($fEst  !== '') { $sql .= " AND a.renipress = ?";       $params[] = $fEst; }
    if ($fInd  !== '') { $sql .= " AND a.id_indicador = ?";    $params[] = (int)$fInd; }
    $sql .= " ORDER BY a.establecimiento ASC, i.codigo ASC, a.mes ASC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $avances = $st->fetchAll();

    // --- 2.3 Resumen agregado por indicador (mismos filtros) ---
    $conInd = [];
    $paramsInd = [];
    if ($fAnio !== '') { $conInd[] = "a.anio = ?";         $paramsInd[] = $fAnio; }
    if ($fMes  !== '') { $conInd[] = "a.mes = ?";          $paramsInd[] = $fMes; }
    if ($fEst  !== '') { $conInd[] = "a.renipress = ?";    $paramsInd[] = $fEst; }
    if ($fInd  !== '') { $conInd[] = "a.id_indicador = ?"; $paramsInd[] = (int)$fInd; }

    $sqlInd = "SELECT i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                      i.meta_anual, i.peso_ponderado, i.periodo, i.responsable, i.estado, i.orden,
                      COUNT(a.id_avance) AS n_reg,
                      SUM(a.valor_numerador) AS sum_num,
                      SUM(a.valor_denominador) AS sum_den,
                      AVG(a.porcentaje_avance) AS pct_prom
               FROM `{$tablaIndicadores}` i
               LEFT JOIN `{$tablaAvances}` a ON a.id_indicador = i.id_indicador"
               . (!empty($conInd) ? ' AND ' . implode(' AND ', $conInd) : '') . "
               WHERE i.estado = 1" . ($fInd !== '' ? " AND i.id_indicador = ?" : "") . "
               GROUP BY i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                        i.meta_anual, i.peso_ponderado, i.periodo, i.responsable, i.estado, i.orden
               ORDER BY i.orden ASC, i.codigo ASC";
    $stInd = $pdo->prepare($sqlInd);
    $stInd->execute($fInd !== '' ? array_merge($paramsInd, [(int)$fInd]) : $paramsInd);
    $resumenIndicadores = $stInd->fetchAll();

    // Avance global ponderado del FED (según filtros aplicados)
    foreach ($resumenIndicadores as $ri) {
        $pctCalc = ((float)$ri['sum_den'] > 0) ? round(((float)$ri['sum_num'] / (float)$ri['sum_den']) * 100, 2) : null;
        $pct = $pctCalc !== null ? $pctCalc : ($ri['pct_prom'] !== null ? (float)$ri['pct_prom'] : null);
        $peso = $ri['peso_ponderado'] !== null ? (float)$ri['peso_ponderado'] : 0;
        if ($pct !== null && $ri['n_reg'] > 0) {
            $avanceGlobalPonderado = ($avanceGlobalPonderado ?? 0) + ($pct * $peso / 100);
            $pesoTotal += $peso;
        }
    }
    if ($pesoTotal > 0 && $avanceGlobalPonderado !== null) {
        $avanceGlobalPonderado = round(($avanceGlobalPonderado / $pesoTotal) * 100, 1);
    } else {
        $avanceGlobalPonderado = null;
    }

    // --- 2.4 Resumen agregado por establecimiento (PHP, a partir del detalle) ---
    $tmpEst = [];
    foreach ($avances as $a) {
        $key = $a['renipress'];
        if (!isset($tmpEst[$key])) {
            $tmpEst[$key] = ['renipress' => $a['renipress'], 'establecimiento' => $a['establecimiento'],
                             'n' => 0, 'suma' => 0.0];
        }
        $tmpEst[$key]['n']++;
        $tmpEst[$key]['suma'] += (float)$a['porcentaje_avance'];
    }
    foreach ($tmpEst as $t) {
        $t['pct_prom'] = $t['n'] > 0 ? round($t['suma'] / $t['n'], 2) : 0;
        $resumenEstablecimientos[] = $t;
    }
    // Peores primero: para el seguimiento diario lo urgente va arriba
    usort($resumenEstablecimientos, function ($x, $y) { return $x['pct_prom'] <=> $y['pct_prom']; });
}

/* ============================================================================
 * 2.5 VISTA DEL DETALLE
 *     Con Mes = todos y Establecimiento = todos, cada establecimiento aparece
 *     una sola vez: se suman numerador y denominador de todos sus meses
 *     (y de los indicadores que entren en el filtro). El porcentaje se
 *     recalcula como numerador / denominador.
 *     Si se elige un mes o un establecimiento, se mantiene el detalle por fila.
 * ========================================================================== */
$agruparPorEstablecimiento = ($fMes === '' && $fEst === '');
$avancesVista = $avances;
if ($agruparPorEstablecimiento && !empty($avances)) {
    $ordenEst = [];
    $grupos = [];
    foreach ($avances as $a) {
        $key = (string)$a['renipress'];
        if (!isset($grupos[$key])) {
            $ordenEst[] = $key;
            $grupos[$key] = [
                'renipress'         => $a['renipress'],
                'establecimiento'   => $a['establecimiento'],
                'anio'              => $a['anio'],
                'mes'               => $a['mes'],
                'valor_numerador'   => 0.0,
                'valor_denominador' => 0.0,
                'porcentaje_avance' => null,
                'observaciones'     => '',
                'codigo'            => '',
                'indicador_nombre'  => '',
                '_inds'             => [],
                '_obs'              => [],
                '_pcts'             => [],
                '_meses'            => [],
                '_n_obs'            => 0,
            ];
        }
        $grupos[$key]['valor_numerador']   += (float)$a['valor_numerador'];
        $grupos[$key]['valor_denominador'] += (float)$a['valor_denominador'];

        $mes = trim((string)($a['mes'] ?? ''));
        if ($mes !== '' && !in_array($mes, $grupos[$key]['_meses'], true)) {
            $grupos[$key]['_meses'][] = $mes;
        }

        $cod = trim((string)($a['codigo'] ?? ''));
        $indKey = $cod !== '' ? $cod : ('id:' . (string)($a['id_indicador'] ?? ''));
        $grupos[$key]['_inds'][$indKey] = (string)($a['indicador_nombre'] ?? '');

        $obs = trim((string)($a['observaciones'] ?? ''));
        if ($obs !== '' && !in_array($obs, $grupos[$key]['_obs'], true)) {
            $grupos[$key]['_obs'][] = $obs;
        }
        if ($a['porcentaje_avance'] !== null && $a['porcentaje_avance'] !== '') {
            $grupos[$key]['_pcts'][] = (float)$a['porcentaje_avance'];
        }
    }

    $avancesVista = [];
    foreach ($ordenEst as $key) {
        $g = $grupos[$key];
        usort($g['_meses'], function ($x, $y) { return ((int)$x) <=> ((int)$y); });

        $den = (float)$g['valor_denominador'];
        $num = (float)$g['valor_numerador'];
        if ($den > 0) {
            $g['porcentaje_avance'] = round(($num / $den) * 100, 2);
        } elseif (count($g['_pcts']) > 0) {
            $g['porcentaje_avance'] = round(array_sum($g['_pcts']) / count($g['_pcts']), 2);
        }

        if (count($g['_meses']) === 1) {
            $g['mes'] = $g['_meses'][0];
        }

        $labels = [];
        $nombreUnico = '';
        $codigoUnico = '';
        foreach ($g['_inds'] as $k => $nom) {
            if (strpos((string)$k, 'id:') === 0) {
                $labels[] = $nom !== '' ? $nom : 'N/D';
                $codigoUnico = '';
            } else {
                $labels[] = (string)$k;
                $codigoUnico = (string)$k;
            }
            $nombreUnico = $nom;
        }
        $nInd = count($labels);
        if ($nInd === 1) {
            $g['codigo'] = $codigoUnico;
            $g['indicador_nombre'] = $nombreUnico;
        } elseif ($nInd > 1) {
            $g['codigo'] = $nInd . ' ind.';
            $g['indicador_nombre'] = implode(', ', $labels);
        }

        $g['_n_obs'] = count($g['_obs']);
        if ($g['_n_obs'] > 0) {
            $g['observaciones'] = implode(' | ', $g['_obs']);
        }

        unset($g['_inds'], $g['_obs'], $g['_pcts']);
        $avancesVista[] = $g;
    }
}

$csrfToken = generateCSRFToken();
$pageTitle = 'Convenio FED - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* ---- Estilos especificos de la vista de consulta del modulo Convenio FED ---- */
.fed-table-detalle th.sortable { cursor: pointer; user-select: none; white-space: nowrap; }
.fed-table-detalle th.sortable:hover { background: #e9ecef; }
.fed-table-detalle th.sortable::after { content: "\f0dc"; font-family: "Font Awesome 6 Free"; font-weight: 900; font-size: .68rem; margin-left: 5px; color: #adb5bd; }
.fed-table-detalle th.sortable.sort-asc::after { content: "\f0de"; color: #0d6efd; }
.fed-table-detalle th.sortable.sort-desc::after { content: "\f0dd"; color: #0d6efd; }
.fed-num { font-variant-numeric: tabular-nums; }
.fed-barra { height: 7px; border-radius: 4px; }
.fed-est-name { font-weight: 600; }
.fed-info-import { background: #eef6ff; border: 1px solid #cfe5ff; border-radius: 8px; }
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

<!-- ===================== FILTROS ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros de Consulta</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="convenio_fed.php" class="row g-2 align-items-end" id="formFiltros">
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
            <div class="col-lg-3 col-md-6 col-12">
                <label class="form-label small fw-semibold mb-0">Indicador</label>
                <select name="ind" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Todos los indicadores --</option>
                    <?php foreach ($indicadoresFiltro as $if): ?>
                        <option value="<?= (int)$if['id_indicador'] ?>" <?= $fInd === (string)$if['id_indicador'] ? 'selected' : '' ?>>
                            <?= clean($if['codigo']) ?> - <?= clean(mb_strimwidth($if['nombre'], 0, 55, '...')) ?><?= (int)$if['estado'] === 0 ? ' (inactivo)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1 col-12">
                <button type="submit" class="btn btn-his btn-sm w-100" title="Consultar"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== AVISO DE ACTUALIZACION DE DATOS ===================== -->
<div class="fed-info-import d-flex align-items-center gap-2 px-3 py-2 mb-4">
    <i class="fas fa-clock text-primary fs-5"></i>
    <div class="small">
        <strong>Datos actualizados al:</strong>
        <?php if ($ultimaActualizacion): ?>
            <strong class="text-primary"><?= formatDateTime($ultimaActualizacion) ?></strong>
            
        <?php else: ?>
            <span class="text-muted">Aun no se ha importado informacion de avances.</span>
        <?php endif; ?>
        <span class="text-muted d-block d-md-inline">Fuente: BD Local</span>
    </div>
</div>

<!-- ===================== RESUMEN RAPIDO ===================== -->
<?php
$nRegistros = count($avances);
$nFilasDetalle = count($avancesVista);
$nEstablecimientos = count($resumenEstablecimientos);
$nIndicadoresConDato = count(array_unique(array_map(function ($a) { return $a['id_indicador']; }, $avances)));
$pctPromedioGeneral = $nRegistros > 0 ? round(array_sum(array_map(function ($a) { return (float)$a['porcentaje_avance']; }, $avances)) / $nRegistros, 1) : null;
$avClasifProm = clasificarAvanceFED($pctPromedioGeneral);
?>
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-list-ol"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($nFilasDetalle) ?></span>
                <span class="stat-label"><?= $agruparPorEstablecimiento ? 'Establecimientos (agrupado)' : 'Registros visibles' ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-hospital"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($nEstablecimientos) ?></span>
                <span class="stat-label">Establecimientos</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-bullseye"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($nIndicadoresConDato) ?></span>
                <span class="stat-label">Indicadores con datos</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card <?= $pctPromedioGeneral === null ? 'stat-warning' : ($pctPromedioGeneral >= 75 ? 'stat-success' : 'stat-warning') ?>">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $pctPromedioGeneral !== null ? number_format($pctPromedioGeneral, 1) . '%' : '-' ?></span>
                <span class="stat-label">Avance promedio (filtros)</span>
            </div>
        </div>
    </div>
</div>

<!-- ===================== AVANCE GLOBAL PONDERADO ===================== -->
<?php if ($avanceGlobalPonderado !== null): ?>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Avance Global del Convenio FED (ponderado)</h6>
            <span class="avance-badge <?= clasificarAvanceFED($avanceGlobalPonderado)['clase'] ?>">
                <?= number_format($avanceGlobalPonderado, 1) ?>% &middot; <?= clasificarAvanceFED($avanceGlobalPonderado)['texto'] ?>
            </span>
        </div>
        <div class="progress" style="height: 22px; border-radius: 11px;">
            <div class="progress-bar <?= colorBarraFED($avanceGlobalPonderado) ?>"
                 role="progressbar" style="width: <?= min(100, $avanceGlobalPonderado) ?>%;"
                 aria-valuenow="<?= $avanceGlobalPonderado ?>" aria-valuemin="0" aria-valuemax="100">
                <?= number_format($avanceGlobalPonderado, 1) ?>%
            </div>
        </div>
        <small class="text-muted">Calculado con los pesos de cada indicador y los filtros seleccionados (<?= number_format($pesoTotal, 1) ?>% de peso con datos).</small>
    </div>
</div>
<?php endif; ?>

<!-- ===================== DETALLE DE AVANCES (tabla interactiva) ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Detalle de Avances por Establecimiento y Mes</h6>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <?php if ($agruparPorEstablecimiento): ?>
                <span class="badge rounded-pill bg-light text-primary border">1 fila por establecimiento</span>
            <?php endif; ?>
            <span class="stat-mini" id="contadorDetalle"></span>
            <div class="input-group input-group-sm" style="width: 230px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="busquedaDetalle" class="form-control" placeholder="Buscar en la tabla...">
            </div>
            <button type="button" class="btn btn-sm btn-outline-success" id="btnExportarCsv" title="Exportar lo que se muestra a CSV (Excel)">
                <i class="fas fa-file-csv me-1"></i> Exportar CSV
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="px-3 pt-2">
            <?php if ($agruparPorEstablecimiento): ?>
                <small class="text-muted"><i class="fas fa-layer-group me-1"></i>Cada establecimiento aparece una sola vez. El numerador y el denominador suman todos sus meses y el porcentaje se recalcula sobre esos totales. Elija un mes o un establecimiento para ver el detalle.</small>
            <?php else: ?>
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Haga clic en el encabezado de una columna para ordenar. La busqueda filtra en vivo.</small>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 fed-table-detalle" id="tablaDetalle">
                <thead class="table-light">
                    <tr>
                        <th class="sortable" data-col="0" data-type="num" data-csv="renipress">Renipress</th>
                        <th class="sortable" data-col="1" data-type="str" data-csv="establecimiento">Establecimiento</th>
                        <th class="sortable" data-col="2" data-type="num" data-csv="anio">Anio</th>
                        <th class="sortable" data-col="3" data-type="str" data-csv="mes">Mes</th>
                        <th class="sortable text-end" data-col="4" data-type="num" data-csv="valor_numerador">Valor Numerador</th>
                        <th class="sortable text-end" data-col="5" data-type="num" data-csv="valor_denominador">Valor Denominador</th>
                        <th class="sortable" data-col="6" data-type="num" data-csv="porcentaje_avance">Porcentaje Avance</th>
                        <th class="sortable" data-col="7" data-type="str" data-csv="observaciones">Observaciones</th>
                        <th class="sortable" data-col="8" data-type="str" data-csv="indicador">Indicador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avancesVista)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                No hay registros de avances para los filtros seleccionados.
                                <?php if ($esAdmin): ?>Utilice el modulo <a href="fed_importar_avances.php"><strong>Importar Resultados.txt</strong></a> para cargar los datos.<?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avancesVista as $a):
                            $pct  = $a['porcentaje_avance'] !== null ? (float)$a['porcentaje_avance'] : null;
                            $av   = clasificarAvanceFED($pct);
                            $mesesGrupo = (isset($a['_meses']) && is_array($a['_meses'])) ? $a['_meses'] : [];
                            $nMesesGrupo = count($mesesGrupo);
                            if ($nMesesGrupo > 1) {
                                $partesMes = [];
                                foreach ($mesesGrupo as $mm) {
                                    $partesMes[] = $mm . ' - ' . getNombreMes((int)$mm);
                                }
                                $mesSort = $mesesGrupo[0];
                                $mesCsv = implode(', ', $partesMes);
                                $mesTitle = implode(', ', $partesMes);
                                $mesTxt = '';
                            } else {
                                $mesN = $a['mes'] !== null ? (int)$a['mes'] : null;
                                $mesTxt = $a['mes'] !== null ? $a['mes'] . ' &middot; ' . getNombreMes($mesN) : '-';
                                $mesSort = (string)($a['mes'] ?? '');
                                $mesCsv = $a['mes'] !== null ? $a['mes'] . ' - ' . getNombreMes($mesN) : '';
                                $mesTitle = '';
                            }
                            $nObs = isset($a['_n_obs']) ? (int)$a['_n_obs'] : 0;
                        ?>
                        <tr data-sortable="1">
                            <td data-sort="<?= clean($a['renipress']) ?>"><code><?= clean($a['renipress']) ?></code></td>
                            <td class="fed-est-name" data-sort="<?= clean($a['establecimiento']) ?>"><?= clean($a['establecimiento']) ?></td>
                            <td data-sort="<?= clean($a['anio']) ?>"><?= clean($a['anio']) ?></td>
                            <td data-sort="<?= clean($mesSort) ?>" data-csv="<?= clean($mesCsv) ?>"<?= $mesTitle !== '' ? ' title="' . clean($mesTitle) . '"' : '' ?>>
                                <?php if ($nMesesGrupo > 1): ?>
                                    <span class="badge bg-light text-dark border"><?= (int)$nMesesGrupo ?> meses</span>
                                <?php else: ?>
                                    <?= $mesTxt ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fed-num" data-sort="<?= (float)$a['valor_numerador'] ?>" data-csv="<?= number_format((float)$a['valor_numerador'], 2, '.', '') ?>"><?= number_format((float)$a['valor_numerador'], 2) ?></td>
                            <td class="text-end fed-num" data-sort="<?= (float)$a['valor_denominador'] ?>" data-csv="<?= number_format((float)$a['valor_denominador'], 2, '.', '') ?>"><?= number_format((float)$a['valor_denominador'], 2) ?></td>
                            <td data-sort="<?= $pct ?? -1 ?>" data-csv="<?= $pct !== null ? number_format($pct, 2, '.', '') : '' ?>">
                                <?php if ($pct !== null): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avance-badge <?= $av['clase'] ?>" style="min-width:64px;"><?= number_format($pct, 1) ?>%</span>
                                        <div class="progress progress-his flex-grow-1 fed-barra">
                                            <div class="progress-bar <?= colorBarraFED($pct) ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%;"></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Sin dato</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= clean($a['observaciones'] ?? '') ?>" data-csv="<?= clean($a['observaciones'] ?? '') ?>">
                                <?php if (!empty($a['observaciones'])): ?>
                                    <?php if ($nObs > 1): ?>
                                        <small title="<?= clean($a['observaciones']) ?>"><?= (int)$nObs ?> observaciones</small>
                                    <?php else: ?>
                                        <small title="<?= clean($a['observaciones']) ?>"><?= clean(mb_strimwidth($a['observaciones'], 0, 40, '...')) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= clean($a['codigo'] ?? '') ?>" data-csv="<?= clean(($a['codigo'] ?? '')) ?> <?= clean($a['indicador_nombre'] ?? '') ?>">
                                <?php if (!empty($a['codigo'])): ?>
                                    <span class="badge bg-light text-dark border" title="<?= clean($a['indicador_nombre'] ?? '') ?>"><?= clean($a['codigo']) ?></span>
                                    <small class="text-muted d-none d-xl-inline"><?= clean(mb_strimwidth($a['indicador_nombre'] ?? '', 0, 48, '...')) ?></small>
                                <?php else: ?>
                                    <small class="text-muted">N/D</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== RESUMEN POR INDICADOR ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-bullseye me-2 text-primary"></i>Reporte de Avance por Indicador FED</h6>
        <span class="stat-mini">Acumulado segun los filtros seleccionados</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px;">Codigo</th>
                        <th>Indicador</th>
                        <th style="width:90px;">Unidad</th>
                        <th style="width:90px;" class="text-end">Meta</th>
                        <th style="width:80px;" class="text-end">Registros</th>
                        <th style="width:110px;" class="text-end">Numerador</th>
                        <th style="width:110px;" class="text-end">Denominador</th>
                        <th style="width:100px;" class="text-end">Avance</th>
                        <th style="width:170px;">Barra de Avance</th>
                        <th style="width:100px;">Estado</th>
                        <th style="width:130px;">Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($resumenIndicadores)): ?>
                        <tr><td colspan="11" class="text-center text-muted py-4">No hay indicadores activos registrados. <?php if ($esAdmin): ?>Agreguelos en <a href="fed_gestion_indicadores.php"><strong>Gestion de Indicadores</strong></a>.<?php endif; ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($resumenIndicadores as $ri):
                            $nReg = (int)$ri['n_reg'];
                            $sumNum = $ri['sum_num'] !== null ? (float)$ri['sum_num'] : null;
                            $sumDen = $ri['sum_den'] !== null ? (float)$ri['sum_den'] : null;
                            $pctCalc = ($sumDen !== null && $sumDen > 0) ? round(($sumNum / $sumDen) * 100, 1) : null;
                            $pct = $pctCalc !== null ? $pctCalc : ($ri['pct_prom'] !== null ? round((float)$ri['pct_prom'], 1) : null);
                            $av = clasificarAvanceFED($pct);
                        ?>
                        <tr>
                            <td><code><?= clean($ri['codigo']) ?></code></td>
                            <td title="<?= clean($ri['descripcion'] ?? '') ?>">
                                <strong><?= clean($ri['nombre']) ?></strong>
                                <?php if (!empty($ri['descripcion'])): ?>
                                    <small class="d-block text-muted" style="font-size:0.74rem;"><?= clean(mb_strimwidth($ri['descripcion'], 0, 80, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= clean($ri['unidad_medida'] ?? '-') ?></small></td>
                            <td class="text-end"><?= $ri['meta_anual'] !== null ? number_format((float)$ri['meta_anual'], 2) : '-' ?></td>
                            <td class="text-end"><?= $nReg ?></td>
                            <td class="text-end fed-num"><?= $sumNum !== null ? number_format($sumNum, 2) : '-' ?></td>
                            <td class="text-end fed-num"><?= $sumDen !== null ? number_format($sumDen, 2) : '-' ?></td>
                            <td class="text-end">
                                <?php if ($pct !== null): ?><strong><?= number_format($pct, 1) ?>%</strong>
                                <?php else: ?><span class="text-muted">Sin dato</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pct !== null): ?>
                                    <div class="progress progress-his" style="height:8px;">
                                        <div class="progress-bar <?= colorBarraFED($pct) ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%;"></div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="avance-badge <?= $av['clase'] ?>"><?= $av['texto'] ?></span></td>
                            <td><small><?= clean($ri['responsable'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; /* fin $sistemaOK */ ?>

<script>
/* ============================================================
 * Interactividad de la vista de consulta FED (JavaScript puro)
 * La gestion de indicadores y la importacion tienen su propia
 * pagina (fed_gestion_indicadores.php / fed_importar_avances.php).
 * ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

    /* ---- Tabla de detalle: busqueda en vivo, orden por columna y CSV ---- */
    var tabla = document.getElementById('tablaDetalle');
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
        contador.textContent = visibles + ' de ' + filas.length + ' registros';
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
            a.download = <?= json_encode('avances_fed_' . $fAnio . ($fMes !== '' ? '_' . $fMes : 's_todos') . ($fEst !== '' ? '_est' . $fEst : '') . '.csv') ?>;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    }

    aplicarFiltro();
});
</script>

<?php include 'includes/footer.php'; ?>
