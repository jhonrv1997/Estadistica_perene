<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Reporte Operacional CANCER (Prevencion y Control del Cancer)
 *
 * REEMPLAZA EL FLUJO MANUAL del Modulo de Cancer:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales"
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion"
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos"
 *   4) Excel:      abrir "Reporte_Actividades_Cancer.xlsx" y refrescar conexion ODBC
 *
 * FLUJO NUEVO (1 click):
 *   Reportes Operacionales -> CANCER -> [Generar Reporte]
 *   (Opcional) [Exportar Excel] -> llena la plantilla oficial
 *   "uploads/Reporte_Actividades_Cancer.xlsx" con los mismos datos (cancer_export.php).
 *
 * El motor de reglas (includes/cancer_data.php) adapta los 9 procedimientos del
 * archivo "03 Creacion de Procedimientos.txt" y los ejecuta contra la tabla
 * consolidada MySQL. El layout replica el Excel "Reporte_Actividades_Cancer.xlsx".
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/cancer_data.php';

$pdo = getDBConnection();

// ==================== FILTROS ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fDetalle         = trim($_GET['detalle'] ?? '1'); // 1 = incluir filas en 0

$anios = cancerGetAniosDisponibles($pdo);
if (empty($anios)) $anios = [date('Y')];
if ($fAnio === '' && !empty($anios)) $fAnio = $anios[0];

$establecimientos = cancerGetEstablecimientosZS($pdo);
$nombreEstablecimiento = $establecimientos[$fEstablecimiento] ?? '';
$renaes = $fEstablecimiento !== '' ? $fEstablecimiento : '';

// Ejecutar SOLO cuando el usuario pulse "Generar Reporte" (como en ESNI:
// evita saturar la base de datos con cada carga de pagina).
$ejecutar = isset($_GET['generar']);
$reporte = null;
$debugSQL = null;

if ($ejecutar) {
    $filtros = [
        'anio'            => $fAnio,
        'mes'             => $fMes,
        'establecimiento' => $fEstablecimiento,
    ];
    $t0 = microtime(true);
    $reporte = cancerEjecutarReporte($pdo, $filtros);
    $reporte['tiempo_ejecucion'] = round(microtime(true) - $t0 + ($reporte['tiempo_ejecucion'] ?? 0), 2);
    if (!empty($reporte['error']) && strpos($reporte['error'], 'Error SQL') === 0) {
        $debugSQL = ['sql' => $reporte['sql_debug'] ?? '', 'params' => $reporte['params_debug'] ?? []];
    }
}

// Texto del periodo (como la cabecera del Excel)
$periodoTxt = [];
if ($fAnio !== '') $periodoTxt[] = $fAnio;
if ($fMes !== '')  $periodoTxt[] = strtoupper(getNombreMes((int)$fMes));
$periodoTxt = $periodoTxt ? implode(' - ', $periodoTxt) : 'TODOS LOS PERIODOS';

$pageTitle = 'Reporte Operacional Cancer - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* Estilos del modulo Cancer (mismo patron que el modulo ESNI) */
.cnr-section-card { border: 1px solid #dee2e6; border-radius: .5rem; margin-bottom: 1.2rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.cnr-section-header { background: linear-gradient(90deg,#1a5276 0%,#2980b9 100%); color:#fff; padding:.6rem 1rem; font-weight:600; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; }
.cnr-section-header small { opacity:.85; font-weight:400; }
.cnr-table { font-size:.78rem; margin:0; }
.cnr-table thead th { background:#f1f3f5; border-bottom:2px solid #dee2e6; padding:.38rem .45rem; text-align:center; vertical-align:middle; font-weight:600; color:#343a40; }
.cnr-table tbody td { padding:.3rem .45rem; vertical-align:middle; border-bottom:1px solid #f1f3f5; }
.cnr-table tbody tr:hover { background:#f8f9fa; }
.cnr-table td.num, .cnr-table th.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.cnr-total-row { background:#fff3cd !important; font-weight:700; }
.cnr-cero { color:#adb5bd; font-style:italic; }
.cnr-grupo { font-weight:600; color:#1a5276; }
.cnr-secflag { font-size:.7rem; }
.cnr-head-cover { background:linear-gradient(90deg,#27ae60 0%,#1e8449 100%); color:#fff; border-radius:.5rem; padding:.8rem 1.25rem; margin-bottom:1rem; }
.cnr-cols-M { color:#1a5276; }
.cnr-cols-F { color:#8e44ad; }
</style>

<div class="page-header-section">
    <h4><i class="fas fa-ribbon me-2 text-danger"></i>Reporte Operacional CANCER</h4>
    <p class="subtitle">Reporte de Actividades de Prevencion y Control del Cancer - reemplazo web del flujo SQL Server + Excel ODBC (1 click).</p>
</div>

<ul class="nav nav-pills subpage-tabs flex-wrap mb-3">
    <li class="nav-item me-1">
        <a class="nav-link" href="reporte_operacionales.php"><i class="fas fa-arrow-left me-1"></i>Volver</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="#"><i class="fas fa-ribbon me-1"></i>Cancer</a>
    </li>
</ul>

<!-- Cabecera tipo Excel: RENAES / IPRESS / PERIODO -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros del Reporte</h6>
        <div class="d-flex gap-2">
            <a href="cancer_export.php?<?= http_build_query(array_filter([
                'anio' => $fAnio, 'mes' => $fMes, 'establecimiento' => $fEstablecimiento,
                'generar' => $ejecutar ? 1 : null,
            ])) ?>" class="btn btn-sm btn-success" title="Exportar Excel con el layout de la plantilla oficial">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </a>
            <button type="submit" form="filterForm" class="btn btn-sm btn-his"><i class="fas fa-play me-1"></i> Generar Reporte</button>
        </div>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="reporte_cancer.php">
            <input type="hidden" name="generar" value="1">
            <div class="row g-3">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small"><i class="fas fa-calendar me-1"></i>Anio</label>
                    <select name="anio" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= $fAnio === (string)$a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small"><i class="fas fa-calendar-alt me-1"></i>Mes</label>
                    <select name="mes" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $fMes === (string)$m ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label class="form-label fw-semibold small"><i class="fas fa-hospital me-1"></i>Establecimiento (RENAES / IPRESS)</label>
                    <select name="establecimiento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientos as $codUnico => $nombre): ?>
                            <option value="<?= htmlspecialchars($codUnico) ?>" <?= $fEstablecimiento === $codUnico ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="chkDetalle" name="detalle" value="1" <?= $fDetalle === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chkDetalle">Mostrar filas con 0 casos (como la plantilla)</label>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($ejecutar && $reporte): ?>

<?php if (!empty($reporte['error'])): ?>
<div class="alert alert-danger">
    <h6 class="alert-heading"><i class="fas fa-exclamation-circle me-2"></i>Error al ejecutar el reporte</h6>
    <p class="mb-2"><?= htmlspecialchars($reporte['error']) ?></p>
    <?php if ($debugSQL): ?>
    <details>
        <summary class="small">Ver SQL debug</summary>
        <div class="cnr-debug mt-2" style="background:#f8f9fa;border:1px solid #dee2e6;padding:.8rem;font-family:monospace;font-size:.75rem;white-space:pre-wrap;word-break:break-all;"><?= "SQL: " . htmlspecialchars($debugSQL['sql']) . "\n\nPARAMS: " . htmlspecialchars(json_encode($debugSQL['params'], JSON_PRETTY_PRINT)) ?></div>
    </details>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- Resultado global -->
<div class="cnr-head-cover d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <i class="fas fa-check-circle me-2"></i>
        <strong>REPORTE DE ACTIVIDADES DE PREVENCION Y CONTROL DEL CANCER <?= htmlspecialchars($fAnio ?: date('Y')) ?></strong><br>
        <small>
            PERIODO: <?= htmlspecialchars($periodoTxt) ?>
            <?= $renaes ? ' &nbsp;|&nbsp; CODIGO RENAES: ' . htmlspecialchars($renaes) . ' &nbsp;|&nbsp; IPRESS: ' . htmlspecialchars($nombreEstablecimiento) : '' ?>
        </small>
    </div>
    <div class="text-end">
        <strong><?= number_format($reporte['totales']['total_casos']) ?></strong> casos /
        <strong><?= number_format($reporte['totales']['total_personas']) ?></strong> personas /
        <strong><?= number_format($reporte['totales']['total_atenciones']) ?></strong> atenciones /
        <?= number_format($reporte['filas_leidas']) ?> filas HIS leidas / <?= $reporte['tiempo_ejecucion'] ?>s
    </div>
</div>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-ribbon"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($reporte['totales']['total_casos']) ?></span>
                <span class="stat-label">Casos (tamizajes/actividades)</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($reporte['totales']['total_personas']) ?></span>
                <span class="stat-label">Personas</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-notes-medical"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($reporte['totales']['total_atenciones']) ?></span>
                <span class="stat-label">Atenciones por cancer</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $reporte['totales']['secciones_con_datos'] ?>/<?= count($reporte['secciones']) ?></span>
                <span class="stat-label">Secciones con datos</span>
            </div>
        </div>
    </div>
</div>

<?php foreach ($reporte['secciones'] as $sec): ?>
<div class="cnr-section-card">
    <div class="cnr-section-header">
        <span><i class="fas fa-ribbon me-2"></i><?= htmlspecialchars($sec['titulo']) ?></span>
        <small>
            <span class="cnr-secflag badge bg-light text-dark me-1"><?= htmlspecialchars($sec['codigo']) ?></span>
            <?= count($sec['filas']) ?> filas
            <?php if ($sec['medidas'][0] === 'atenciones'): ?>
                | Atenciones: <strong><?= number_format($sec['total']['atenciones']) ?></strong> / Atendidos: <strong><?= number_format($sec['total']['atendidos']) ?></strong>
            <?php else: ?>
                | Casos: <strong><?= number_format($sec['total']['casos']) ?></strong> / Personas: <strong><?= number_format($sec['total']['personas']) ?></strong>
            <?php endif; ?>
        </small>
    </div>
    <div class="card-body p-0">
        <?php cnrRenderSeccion($sec, $fDetalle === '1'); ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Nota sobre secciones de la plantilla oficial sin SP asociado -->
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Secciones de la plantilla oficial "Reporte_Actividades_Cancer.xlsx" no cubiertas por el flujo actual:</strong>
    Telemamografia, Tratamiento de cancer con quimioterapia, radioterapia/braquiterapia, quirurgico,
    cuidados paliativos (5 secciones), estadios y atenciones de cancer infantil.
    El archivo "03 Creacion de Procedimientos" no incluye procedimientos para esas secciones, por lo que
    en el Excel original siempre se muestran en 0 (False). Este reporte replica exactamente las 9 secciones
    con procedimiento asociado; el resto se conserva en la plantilla al exportar.
</div>

<?php endif; ?>

<?php elseif (!$ejecutar): ?>
<div class="alert alert-info d-flex align-items-center">
    <i class="fas fa-info-circle me-3 fa-2x"></i>
    <div>
        <strong>Reporte listo para generar.</strong><br>
        Configure los filtros (anio, mes, establecimiento) y pulse <em>Generar Reporte</em>.
        Se ejecutara el motor data-driven (<code>includes/cancer_data.php</code>) que adapta los 9 procedimientos
        T-SQL del archivo <code>03 Creacion de Procedimientos</code> contra la tabla
        <code>T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO</code>.
        <div class="small text-muted mt-1">
            Ya no es necesario ejecutar los scripts en SQL Server ni refrescar el Excel ODBC:
            el flujo completo se hace desde aqui en 1 click, con la opcion de exportar a Excel con el mismo layout.
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// ====== RENDERIZADO DE SECCIONES (layout tipo Excel) ======

/**
 * Renderiza una seccion del reporte con el layout del Excel oficial:
 * columnas de etiquetas + totales + grupos de edad (con M/F si aplica).
 */
function cnrRenderSeccion(array $sec, bool $mostrarCeros): void {
    $conSexo  = $sec['con_sexo'];
    $esAte    = $sec['medidas'][0] === 'atenciones';
    $m1Label  = $esAte ? 'ATENCIONES' : 'Casos';
    $m2Label  = $esAte ? 'ATENDIDOS'  : 'Personas';
    $gedades  = array_filter($sec['gedades'], fn($g) => true);

    // Cabecera izquierda: 1, 2 o 3 columnas de etiquetas
    $colsEt = [];
    if ($sec['cabe1']) $colsEt[] = $sec['cabe1'];
    if ($sec['cabe2']) $colsEt[] = $sec['cabe2'];
    if ($sec['cabe3']) $colsEt[] = $sec['cabe3'];
    $nEt = count($colsEt);
    $filasVisibles = array_values(array_filter($sec['filas'], fn($f) =>
        $mostrarCeros || (!$f['cero'] && cnrFilaConDatos($f))));
    if (!$filasVisibles && !$mostrarCeros) {
        echo '<div class="text-center text-muted py-3"><i class="fas fa-inbox me-2"></i>Sin datos para esta seccion con los filtros actuales.</div>';
        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover cnr-table mb-0">
            <thead>
                <tr>
                    <?php foreach ($colsEt as $c): ?>
                        <th rowspan="<?= $conSexo ? 3 : 2 ?>" class="text-start" style="min-width:180px;"><?= htmlspecialchars($c) ?></th>
                    <?php endforeach; ?>
                    <th rowspan="<?= $conSexo ? 3 : 2 ?>" class="num"><?= htmlspecialchars($sec['tot_label']) ?></th>
                    <th rowspan="<?= $conSexo ? 3 : 2 ?>" class="num"><?= htmlspecialchars($sec['tot2_label']) ?></th>
                    <?php if (!$conSexo): ?>
                        <?php foreach ($gedades as $g): ?>
                            <th colspan="2" class="num <?= !empty($g['cero']) ? 'text-muted' : '' ?>"><?= htmlspecialchars($g['label']) ?></th>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($gedades as $g): ?>
                            <th colspan="4" class="num <?= !empty($g['cero']) ? 'text-muted' : '' ?>"><?= htmlspecialchars($g['label']) ?></th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
                <?php if ($conSexo): ?>
                <tr>
                    <?php foreach ($gedades as $g): ?>
                        <th colspan="2" class="num cnr-cols-M">M</th>
                        <th colspan="2" class="num cnr-cols-F">F</th>
                    <?php endforeach; ?>
                </tr>
                <?php endif; ?>
                <tr>
                    <?php if (!$conSexo): ?>
                        <?php foreach ($gedades as $g): ?>
                            <th class="num"><?= htmlspecialchars($m1Label) ?></th>
                            <th class="num"><?= htmlspecialchars($m2Label) ?></th>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($gedades as $g): ?>
                            <th class="num"><?= htmlspecialchars($m1Label) ?></th>
                            <th class="num"><?= htmlspecialchars($m2Label) ?></th>
                            <th class="num"><?= htmlspecialchars($m1Label) ?></th>
                            <th class="num"><?= htmlspecialchars($m2Label) ?></th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $grupoAnt = null;
                foreach ($filasVisibles as $f):
                    $esGrupo = ($nEt >= 2) && ($f['c1'] !== $grupoAnt);
                    $grupoAnt = $f['c1'];
                    $t1 = $esAte ? $f['total']['atenciones'] : $f['total']['casos'];
                    $t2 = $esAte ? $f['total']['atendidos'] : $f['total']['personas'];
                ?>
                <tr class="<?= $f['cero'] || $t1 == 0 ? '' : '' ?>">
                    <?php if ($nEt >= 1): ?>
                        <td class="<?= $esGrupo ? 'cnr-grupo' : 'text-muted' ?> <?= $nEt == 1 ? '' : '' ?>" <?= !$esGrupo && $nEt > 1 ? 'style="padding-left:1.4rem;"' : '' ?>>
                            <?= $esGrupo ? '<i class="fas fa-angle-right me-1"></i>' : '' ?><?= htmlspecialchars($f['c1']) ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($nEt >= 2): ?><td><?= htmlspecialchars($f['c2'] ?? '----------') ?></td><?php endif; ?>
                    <?php if ($nEt >= 3): ?><td class="small"><?= htmlspecialchars($f['c3'] ?? '----------') ?></td><?php endif; ?>
                    <td class="num fw-bold <?= $f['cero'] ? 'cnr-cero' : ($t1 > 0 ? 'text-primary' : 'text-muted') ?>"><?= number_format($t1) ?></td>
                    <td class="num <?= $f['cero'] ? 'cnr-cero' : ($t2 > 0 ? 'text-success' : 'text-muted') ?>"><?= number_format($t2) ?></td>
                    <?php if (!$conSexo): ?>
                        <?php foreach ($gedades as $g): ?>
                            <?php $cell = cnrCell($f, null, $g['key'], $esAte); ?>
                            <td class="num <?= $cell['cero'] ? 'cnr-cero' : '' ?>"><?= number_format($cell['m1']) ?></td>
                            <td class="num <?= $cell['cero'] ? 'cnr-cero' : '' ?>"><?= number_format($cell['m2']) ?></td>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($gedades as $g): ?>
                            <?php $cM = cnrCell($f, 'M', $g['key'], $esAte); $cF = cnrCell($f, 'F', $g['key'], $esAte); ?>
                            <td class="num <?= $cM['cero'] ? 'cnr-cero' : '' ?>"><?= number_format($cM['m1']) ?></td>
                            <td class="num <?= $cM['cero'] ? 'cnr-cero' : '' ?>"><?= number_format($cM['m2']) ?></td>
                            <td class="num <?= $cF['cero'] ? 'cnr-cero' : '' ?>"><?= number_format($cF['m1']) ?></td>
                            <td class="num <?= $cF['cero'] ? 'cnr-cero' : '' ?>"><?= number_format($cF['m2']) ?></td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
                <tr class="cnr-total-row">
                    <td colspan="<?= $nEt + 2 ?>" class="text-end">TOTAL SECCION</td>
                    <?php if (!$conSexo): ?>
                        <?php foreach ($gedades as $g): ?>
                            <?php [$a, $b] = cnrTotalGedad($sec['filas'], null, $g['key'], $esAte); ?>
                            <td class="num"><?= number_format($a) ?></td>
                            <td class="num"><?= number_format($b) ?></td>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($gedades as $g): ?>
                            <?php [$aM, $bM] = cnrTotalGedad($sec['filas'], 'M', $g['key'], $esAte); ?>
                            <?php [$aF, $bF] = cnrTotalGedad($sec['filas'], 'F', $g['key'], $esAte); ?>
                            <td class="num"><?= number_format($aM) ?></td>
                            <td class="num"><?= number_format($bM) ?></td>
                            <td class="num"><?= number_format($aF) ?></td>
                            <td class="num"><?= number_format($bF) ?></td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}

/** Devuelve la celda [m1, m2, cero] para (fila, sexo, gedad). */
function cnrCell(array $f, ?string $sexo, $gedad, bool $esAte): array {
    if ($f['cero'] || !empty($f['cero'])) return ['m1' => 0, 'm2' => 0, 'cero' => true];
    $v = $f['valores'];
    if ($sexo !== null) {
        if (!isset($v[$sexo][$gedad])) return ['m1' => 0, 'm2' => 0, 'cero' => false];
        $cell = $v[$sexo][$gedad];
    } else {
        $found = null;
        if (isset($v['F'][$gedad])) $found = $v['F'][$gedad];
        if (isset($v['M'][$gedad])) $found = $found ? $found : null; // secciones sin sexo: solo hay una clave
        if (isset($v['X'][$gedad])) $found = $v['X'][$gedad];
        if ($found === null) {
            // buscar en cualquier sexo (secciones F-only usan la clave 'F')
            foreach ($v as $vx) { if (isset($vx[$gedad])) { $found = $vx[$gedad]; break; } }
        }
        if ($found === null) return ['m1' => 0, 'm2' => 0, 'cero' => false];
        $cell = $found;
    }
    return [
        'm1'   => $esAte ? $cell['atenciones'] : $cell['casos'],
        'm2'   => $esAte ? $cell['atendidos']  : $cell['personas'],
        'cero' => false,
    ];
}

/** Totales por gedad (y sexo) de todas las filas de la seccion. */
function cnrTotalGedad(array $filas, ?string $sexo, $gedad, bool $esAte): array {
    $a = 0; $b = 0;
    foreach ($filas as $f) {
        if (!empty($f['cero'])) continue;
        $cell = cnrCell($f, $sexo, $gedad, $esAte);
        $a += $cell['m1'];
        $b += $cell['m2'];
    }
    return [$a, $b];
}

/** Indica si la fila tiene al menos un valor > 0. */
function cnrFilaConDatos(array $f): bool {
    if (!empty($f['cero'])) return false;
    $t = $f['total'];
    return ($t['casos'] + $t['personas'] + $t['atenciones'] + $t['atendidos']) > 0;
}
?>

<?php include 'includes/footer.php'; ?>
