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

// ====== PROTECCION ANTI HTTP 500 (errores fatales de PHP) ======
// La generacion del reporte es pesada: en hostings compartidos puede
// agotar el memory_limit o el max_execution_time. Esos errores FATALES no
// se capturan con try/catch; sin este handler el navegador muestra la
// pagina en blanco "HTTP ERROR 500". Aqui se capturan y se muestra un
// panel de diagnostico con la causa y las acciones recomendadas.
ob_start();
register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    while (ob_get_level() > 0) { @ob_end_clean(); }
    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
    }
    $msg     = $e['message'];
    $esMem   = (stripos($msg, 'memory') !== false);
    $esTiempo= (stripos($msg, 'execution time') !== false || stripos($msg, 'time limit') !== false);
    $causa   = $esMem ? 'Se agoto la memoria disponible de PHP (memory_limit) al procesar los datos del reporte.'
              : ($esTiempo ? 'Se agoto el tiempo de ejecucion permitido por el hosting (max_execution_time).' : 'Ocurrio un error fatal de PHP durante la generacion del reporte.');
    $ml      = (string)ini_get('memory_limit');
    $met     = (string)ini_get('max_execution_time');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Reporte CANCER - Diagnostico de error</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
       . '<body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">'
       . '<div class="card shadow"><div class="card-header bg-danger text-white"><h5 class="mb-0">El reporte CANCER no se pudo completar</h5></div>'
       . '<div class="card-body"><div class="alert alert-warning mb-3"><strong>Causa probable:</strong> ' . htmlspecialchars($causa) . '</div>'
       . '<p class="mb-2"><strong>Detalle tecnico:</strong></p><pre class="bg-dark text-warning p-2 rounded small" style="white-space:pre-wrap;">' . htmlspecialchars($msg) . '</pre>'
       . '<p class="mb-1"><strong>Que puedo hacer:</strong></p><ol class="mb-3">'
       . '<li>Ejecute una vez <a href="install_cancer.php"><strong>install_cancer.php</strong></a> para crear los indices de la tabla consolidada (acelera la consulta de minutos a segundos).</li>'
       . '<li>Genere el reporte con filtros mas acotados: seleccione <strong>un mes</strong> y/o <strong>un establecimiento</strong> en lugar de "Todos".</li>'
       . '<li>Si el problema persiste, el hosting gratuito (InfinityFree) tiene limites fijos; considere migrar a un plan de pago o descargar el Excel por partes.</li>'
       . '</ol>'
       . '<p class="small text-muted mb-0">Limites actuales de PHP en este servidor &mdash; memory_limit: <code>' . htmlspecialchars($ml) . '</code> &nbsp;|&nbsp; max_execution_time: <code>' . htmlspecialchars($met) . 's</code> &nbsp;|&nbsp; PHP: <code>' . htmlspecialchars(PHP_VERSION) . '</code></p>'
       . '</div><div class="card-footer bg-white"><a href="reporte_cancer.php" class="btn btn-sm btn-his">Volver al reporte</a></div></div>'
       . '</div></div></div></body></html>';
});

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
$verifRpt0603 = null;

if ($ejecutar) {
    $filtros = [
        'anio'            => $fAnio,
        'mes'             => $fMes,
        'establecimiento' => $fEstablecimiento,
    ];
    $t0 = microtime(true);
    $reporte = cancerEjecutarReporte($pdo, $filtros);
    $reporte['tiempo_ejecucion'] = round(microtime(true) - $t0 + ($reporte['tiempo_ejecucion'] ?? 0), 2);
    if (!empty($reporte['error']) && !empty($reporte['sql_debug'])) {
        $debugSQL = ['sql' => $reporte['sql_debug'] ?? '', 'params' => $reporte['params_debug'] ?? []];
    }
    // Verificacion de RPT06_03: corre los 8 COUNT SQL directamente en la BD
    // (con y sin filtros) para compararlos en pantalla con los conteos del motor.
    $verifRpt0603 = null;
    if (empty($reporte['error'])) {
        $verifRpt0603 = cancerVerificarRPT0603($pdo, $filtros);
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
    <?php $diag = cancerDiagnosticoEntorno($pdo); ?>
    <hr>
    <h6 class="small fw-bold mb-2"><i class="fas fa-stethoscope me-1"></i>Diagnostico del entorno</h6>
    <ul class="small mb-2 ps-3">
        <li>PHP: <code><?= htmlspecialchars($diag['php']) ?></code> &nbsp;|&nbsp; memory_limit: <code><?= htmlspecialchars($diag['memory_limit']) ?></code> &nbsp;|&nbsp; max_execution_time: <code><?= htmlspecialchars($diag['max_exec_time']) ?>s</code></li>
        <li>Filas aprox. en la tabla consolidada: <code><?= $diag['filas_tabla'] !== null ? number_format((int)$diag['filas_tabla']) : 'n/d' ?></code></li>
        <li>
            Indices en la tabla consolidada:
            <?php if (empty($diag['indices'])): ?>
                <span class="badge bg-danger">NINGUNO</span>
                &rarr; la consulta hace un barrido completo de la tabla. Ejecute una vez
                <a href="install_cancer.php" class="fw-bold">install_cancer.php</a> para crearlos.
            <?php else: ?>
                <span class="badge bg-success"><?= count($diag['indices']) ?></span>
                <code class="small"><?= htmlspecialchars(implode(', ', $diag['indices'])) ?></code>
            <?php endif; ?>
        </li>
    </ul>
    <div class="small text-muted">
        <i class="fas fa-lightbulb me-1"></i>Recomendaciones: ejecute <strong>install_cancer.php</strong> (una sola vez), y genere el reporte con filtros mas acotados
        (seleccione un mes y/o un establecimiento en lugar de "-- Todos --").
    </div>
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
        <?= number_format($reporte['filas_leidas']) ?> filas HIS leidas / <?= $reporte['tiempo_ejecucion'] ?>s<br>
        <span class="badge bg-dark mb-0" title="Version del motor includes/cancer_data.php en ejecucion. Si no coincide con el archivo entregado, el hosting esta sirviendo una copia vieja (OPcache/ruta): vuelva a subirlo.">Motor cancer_data.php v<?= htmlspecialchars($reporte['version'] ?? cancerDataVersion()) ?></span>
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

<?php if ($verifRpt0603 !== null): ?>
<?php
// ---- Panel de verificacion RPT06_03 (8 casos SQL vs motor PHP) ----
$secRpt0603 = null;
foreach ($reporte['secciones'] as $s) { if ($s['codigo'] === 'RPT06_03') { $secRpt0603 = $s; break; } }
$motorCasos = $secRpt0603['rpt0603_casos'] ?? [];
$motorTotal = $secRpt0603 ? $secRpt0603['filas'][0]['total']['atenciones'] : 0;
$sqlCasos   = $verifRpt0603['casos'] ?? [];
$unionF     = $verifRpt0603['union_filtros'] ?? null;
$unionG     = $verifRpt0603['union_global'] ?? null;
$okTotal    = ($unionF !== null && (int)$unionF === (int)$motorTotal);
?>
<div class="cnr-section-card">
    <div class="cnr-section-header" style="background:linear-gradient(90deg,#155a4a 0%,#27ae60 100%);">
        <span><i class="fas fa-clipboard-check me-2"></i>VERIFICACION RPT06_03 &mdash; 8 CASOS SQL (BD) vs MOTOR PHP</span>
        <small>Motor v<?= htmlspecialchars($reporte['version'] ?? cancerDataVersion()) ?> &nbsp;|&nbsp;
            UNION <?= $unionF !== null ? number_format($unionF) : '---' ?> = TOTAL DE ATENCIONES esperado
            <?= $okTotal ? '<span class="badge bg-success">COINCIDE</span>' : '<span class="badge bg-danger">NO COINCIDE</span>' ?>
        </small>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($verifRpt0603['error'])): ?>
            <div class="alert alert-warning mb-0 rounded-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                La verificacion SQL no pudo ejecutarse: <code><?= htmlspecialchars($verifRpt0603['error']) ?></code>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm cnr-table mb-0">
                <thead>
                    <tr>
                        <th class="text-start">Caso</th>
                        <th class="text-start">Condicion SQL (fg_tipo='CX' AND Id_correlativo_Lab=1 + ...)</th>
                        <th class="num">COUNT BD</th>
                        <th class="num">Motor PHP</th>
                        <th class="num">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (cnrRpt0603Casos() as $ci => $caso): ?>
                        <?php
                            $bd  = $sqlCasos[$ci] ?? null;
                            $mot = $motorCasos[$ci] ?? null;
                            $okc = ($bd !== null && $mot !== null && (int)$bd === (int)$mot);
                        ?>
                        <tr>
                            <td class="text-start fw-semibold"><?= htmlspecialchars($caso['titulo']) ?></td>
                            <td class="text-start"><code class="small"><?= htmlspecialchars($caso['sql']) ?></code></td>
                            <td class="num fw-bold"><?= $bd !== null ? number_format($bd) : '---' ?></td>
                            <td class="num"><?= $mot !== null ? number_format($mot) : '---' ?></td>
                            <td class="num"><?= $okc ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="cnr-total-row">
                        <td colspan="2" class="text-end">UNION de los 8 casos (= TOTAL DE ATENCIONES de "Todo tipo de cancer")</td>
                        <td class="num"><?= $unionF !== null ? number_format($unionF) : '---' ?></td>
                        <td class="num"><?= number_format($motorTotal) ?></td>
                        <td class="num"><?= $okTotal ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="text-end text-muted">UNION <strong>sin filtros</strong> (como correr los 8 SQL en phpMyAdmin: todos los anios/meses/establecimientos)</td>
                        <td class="num text-muted"><?= $unionG !== null ? number_format($unionG) : '---' ?></td>
                        <td colspan="2" class="text-muted small">referencia</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="small text-muted px-2 py-2 border-top">
            <i class="fas fa-info-circle me-1"></i>
            <strong>Como leer este panel:</strong> la columna "COUNT BD" ejecuta los 8 SQL directamente en MySQL
            <em>con los filtros actuales</em> (anio/mes/establecimiento) y debe ser identica a "Motor PHP" (los mismos
            casos calculados por el reporte). La UNION con filtros es el valor exacto que la fila
            "Todo tipo de cancer" debe mostrar en <strong>TOTAL DE ATENCIONES</strong>. Si la UNION sin filtros
            difiere de la UNION con filtros, la diferencia viene del <strong>alcance de los filtros</strong>
            (anio/mes/establecimiento seleccionados), no de un error del reporte. Si "COUNT BD" difiere de
            "Motor PHP", revise que el servidor ejecute la version indicada en el badge
            (re-subir <code>includes/cancer_data.php</code> y limpiar cache del hosting).
            Nota: un registro puede cumplir varios casos a la vez (p.ej. C900 adulto cuenta en los Casos 3 y 6),
            por eso el TOTAL es la UNION y no la suma de los COUNT.
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

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
