<?php
/**
 * Sistema de Gestion de Datos HIS (IntelHIS)
 * Pagina: Reporte Operacional ITS/VIH (Hoja de Monitoreo Mensual de la ESN
 * Prevencion y Control de ITS, VIH/SIDA y Hepatitis)
 *
 * REEMPLAZA EL FLUJO MANUAL del Modulo de ITS:
 *   1) SQL Server: ejecutar "01 Creacion tablas iniciales"
 *   2) SQL Server: ejecutar "02 Creacion tablas consolidacion"
 *   3) SQL Server: ejecutar "03 Creacion de Procedimientos"
 *   4) Excel:      abrir "Reporte_Actividades_ITS.xlsx" y refrescar la
 *                  conexion ODBC
 *
 * FLUJO NUEVO (1 click):
 *   Reportes Operacionales -> ITS -> [Generar Reporte]
 *   (Opcional) [Exportar Excel] -> llena la plantilla oficial
 *   "uploads/Reporte_Actividades_ITS.xlsx" con los mismos datos
 *   (its_export.php).
 *
 * El motor de reglas (includes/its_data.php) adapta los 15 procedimientos
 * usp_TRAMA_BASE_ITS_VIH_2022_* del archivo "03 Creacion de Procedimientos"
 * y los ejecuta contra la tabla consolidada MySQL. El layout replica el
 * Excel "Reporte_Actividades_ITS.xlsx" (16 bloques: poblacion general,
 * hepatitis B/C, tamizaje VIH, exposicion, violencia sexual, PVV, gestantes
 * por grupo etareo y trimestre, recien nacidos, poblacion clave HSH/Trans/TS,
 * ITS en poblacion clave, brigadas MCC/BMA y telemedicina).
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/its_data.php';
require_once 'includes/its_render.php';

// ====== PROTECCION ANTI HTTP 500 (errores fatales de PHP) ======
// La generacion del reporte es pesada: en hostings compartidos puede agotar el
// memory_limit o el max_execution_time. Esos errores FATALES no se capturan con
// try/catch; sin este handler el navegador muestra "HTTP ERROR 500".
ob_start();
register_shutdown_function(function () {
    $e = error_get_last();
    if (!$e || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) return;
    while (ob_get_level() > 0) { @ob_end_clean(); }
    if (!headers_sent()) {
        http_response_code(200);
    }
    header('Content-Type: text/html; charset=utf-8');
    $msg     = $e['message'];
    $esMem   = (stripos($msg, 'memory') !== false);
    $esTiempo= (stripos($msg, 'execution time') !== false || stripos($msg, 'time limit') !== false);
    $causa   = $esMem ? 'Se agotó la memoria disponible de PHP (memory_limit) al procesar los datos del reporte.'
              : ($esTiempo ? 'Se agotó el tiempo de ejecución permitido por el hosting (max_execution_time).' : 'Ocurrió un error fatal de PHP durante la generación del reporte.');
    $ml      = (string)ini_get('memory_limit');
    $met     = (string)ini_get('max_execution_time');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Reporte ITS - Error</title>'
       . '<style>body{font-family:system-ui,sans-serif;background:#f4f6f7;padding:2rem}.card{max-width:720px;margin:auto;background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:1.5rem}h1{color:#c0392b;font-size:1.3rem}code{background:#eef1f2;padding:2px 6px;border-radius:4px}</style>'
       . '</head><body><div class="card">'
       . '<h1>⚠ No se pudo generar el reporte ITS</h1>'
       . '<p>' . htmlspecialchars($causa) . '</p>'
       . '<ol><li>Ejecute una vez <strong>install_its.php</strong> (índices de la tabla consolidada) si aún no lo hizo.</li>'
       . '<li>Genere el reporte con filtros más acotados: seleccione <strong>un mes</strong> y/o <strong>un establecimiento</strong> en lugar de "Todos".</li>'
       . '<li>Si el problema persiste, el hosting gratuito (InfinityFree) tiene límites fijos; considere migrar a un plan de pago.</li></ol>'
       . '<p class="small text-muted mb-0">Límites actuales de PHP en este servidor &mdash; memory_limit: <code>' . htmlspecialchars($ml) . '</code> &nbsp;|&nbsp; max_execution_time: <code>' . htmlspecialchars($met) . 's</code> &nbsp;|&nbsp; PHP: <code>' . htmlspecialchars(PHP_VERSION) . '</code></p>'
       . '</div></body></html>';
});

$pdo = getDBConnection();

// ==================== AJAX: FILTROS DEPENDIENTES ====================
// Devuelve las opciones de Anio y Mes recalculadas DENTRO del ambito del
// establecimiento seleccionado (mismo mecanismo que los otros modulos).
if (($_GET['ajax'] ?? '') === 'filtros') {
    header('Content-Type: application/json; charset=utf-8');
    $ajaxAnio = trim($_GET['anio'] ?? '');
    $ajaxEst  = trim($_GET['establecimiento'] ?? '');
    try {
        $aniosAjax = array_map('strval', itsGetAniosDisponibles($pdo, $ajaxEst));
        $mesesAjax = array_map(function ($m) {
            return ['v' => (int)$m, 't' => getNombreMes((int)$m)];
        }, itsGetMesesDisponibles($pdo, $ajaxAnio, $ajaxEst));
        echo json_encode([
            'ok'    => true,
            'anios' => $aniosAjax,
            'meses' => $mesesAjax,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'anios' => [], 'meses' => []]);
    }
    exit; // respuesta JSON: no continuar con el HTML del reporte
}

// ==================== FILTROS ====================
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fDetalle         = trim($_GET['detalle'] ?? '1'); // 1 = incluir filas en 0
$fVerSQL          = trim($_GET['versql'] ?? '0');  // 1 = mostrar panel de condiciones
$fGrupo           = trim($_GET['grupo'] ?? '');    // '' = todos los grupos

// Anios dentro del ambito del establecimiento (lista ZSPERENE con 'Todos')
$anios = itsGetAniosDisponibles($pdo, $fEstablecimiento);
if (empty($anios)) $anios = [date('Y')];
if ($fAnio === '' && !empty($anios)) $fAnio = $anios[0];

$establecimientos = itsGetEstablecimientosZS($pdo);
$nombreEstablecimiento = $establecimientos[$fEstablecimiento] ?? '';
$renaes = $fEstablecimiento !== '' ? $fEstablecimiento : '';

// Meses con datos dentro del mismo ambito (establecimiento + anio)
$mesesDisponibles = itsGetMesesDisponibles($pdo, $fAnio, $fEstablecimiento);
if (empty($mesesDisponibles)) $mesesDisponibles = range(1, 12);

// Ejecutar SOLO cuando el usuario pulse "Generar Reporte" (como en los
// otros modulos: evita saturar la base de datos con cada carga).
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
    $reporte = itsEjecutarReporte($pdo, $filtros);
    $reporte['tiempo_ejecucion'] = round(microtime(true) - $t0 + ($reporte['tiempo_ejecucion'] ?? 0), 2);
    if (!empty($reporte['error']) && !empty($reporte['sql_debug'])) {
        $debugSQL = ['sql' => $reporte['sql_debug'] ?? '', 'params' => $reporte['params_debug'] ?? []];
    }
}

// Texto del periodo (como la cabecera del Excel: K6 AÑO / K8 MES)
$periodoTxt = [];
if ($fAnio !== '') $periodoTxt[] = $fAnio;
if ($fMes !== '')  $periodoTxt[] = strtoupper(getNombreMes((int)$fMes));
$periodoTxt = $periodoTxt ? implode(' - ', $periodoTxt) : 'TODOS LOS PERIODOS';

$gruposMeta = [
    'PG'  => ['nombre' => 'POBLACIÓN GENERAL: ITS, HEPATITIS Y TAMIZAJE VIH',  'icon' => 'fa-users',           'desc' => 'ITS, Hepatitis B/C, Tamizaje VIH, Exposición, Violencia Sexual y PVV'],
    'GES' => ['nombre' => 'GESTANTES Y RECIÉN NACIDOS',                        'icon' => 'fa-baby',            'desc' => 'Transmisión Materno Infantil (Sífilis, VIH, Hepatitis B)'],
    'PC'  => ['nombre' => 'POBLACIÓN CLAVE Y BRIGADAS',                        'icon' => 'fa-people-group',    'desc' => 'HSH/Trans/TS, ITS en Población Clave, Brigadas MCC/BMA'],
    'TM'  => ['nombre' => 'TELEMEDICINA',                                      'icon' => 'fa-tower-broadcast', 'desc' => 'Teleorientaciones, Telemonitoreos y Teleconsultas'],
];

$pageTitle = 'Reporte Operacional ITS/VIH - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* Estilos del modulo ITS (mismo patron que los otros modulos), con la paleta
   de la plantilla oficial (verde-teal MINSA #1F9E8E y gris azulado) */
.its-section-card { border: 1px solid #dee2e6; border-radius: .5rem; margin-bottom: 1.2rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.its-section-header { background: linear-gradient(90deg,#0e7490 0%,#14a3a8 100%); color:#fff; padding:.6rem 1rem; font-weight:600; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; }
.its-section-header small { opacity:.85; font-weight:400; }
.its-table { font-size:.72rem; margin:0; }
.its-table thead th { background:#f1f3f5; border-bottom:2px solid #dee2e6; padding:.3rem .35rem; text-align:center; vertical-align:middle; font-weight:600; color:#343a40; }
.its-th-label { background:#0e7490 !important; color:#fff !important; border-color:#0b5d73 !important; }
.its-th-total { background:#0b5d73 !important; color:#fff !important; border-color:#094a5c !important; }
.its-th-gedad { background:#e6f4f5 !important; color:#0b5d73 !important; font-weight:500; padding:.15rem .3rem !important; }
.its-th-m, .its-th-f { background:#bfe3e8 !important; color:#0b5d73 !important; font-weight:500; padding:.15rem .3rem !important; }
.its-table tbody td { padding:.22rem .35rem; vertical-align:middle; border-bottom:1px solid #f1f3f5; }
.its-table tbody tr:hover { background:#f8f9fa; }
.its-table td.num, .its-table th.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.its-cero { color:#adb5bd; font-style:italic; }
.its-niv1 { font-weight:500; color:#343a40; }
.its-cond { font-family:monospace; font-size:.7rem; background:#2c3e50; color:#ecf0f1; padding:.5rem .75rem; border-radius:.35rem; white-space:pre-wrap; word-break:break-all; }
.its-head-cover { background:linear-gradient(90deg,#0b5d73 0%,#14a3a8 100%); color:#fff; border-radius:.5rem; padding:.8rem 1.25rem; margin-bottom:1rem; }
.its-group-header { background:linear-gradient(90deg,#094a5c 0%,#0e7490 100%); color:#fff; border-radius:.5rem; padding:.55rem 1rem; margin:1.6rem 0 .8rem 0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; box-shadow:0 2px 4px rgba(9,74,92,.25); }
.its-group-header h6 { margin:0; font-weight:700; letter-spacing:.3px; }
.its-group-header small { opacity:.8; }
.its-secflag { font-size:.68rem; }
</style>

<div class="page-header-section">
    <h4><i class="fas fa-vial-virus me-2 text-info"></i>Reporte Operacional ITS/VIH</h4>
    <p class="subtitle">Hoja de Monitoreo Mensual de la ESN Prevenci&oacute;n y Control de ITS, VIH/SIDA y Hepatitis</p>
</div>

<ul class="nav nav-pills subpage-tabs flex-wrap mb-3">
    <li class="nav-item me-1">
        <a class="nav-link" href="reporte_operacionales.php"><i class="fas fa-arrow-left me-1"></i>Volver</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="#"><i class="fas fa-vial-virus me-1"></i>ITS / VIH / Hepatitis</a>
    </li>
</ul>

<!-- Filtros del reporte -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros del Reporte</h6>
        <div class="d-flex gap-2">
            <a href="its_export.php?<?= http_build_query(array_filter([
                'anio' => $fAnio, 'mes' => $fMes, 'establecimiento' => $fEstablecimiento,
                'generar' => $ejecutar ? 1 : null,
            ])) ?>" class="btn btn-sm btn-success" title="Exportar Excel con el layout de la plantilla oficial Reporte_Actividades_ITS.xlsx">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </a>
            <button type="submit" form="filterForm" class="btn btn-sm btn-his"><i class="fas fa-play me-1"></i> Generar Reporte</button>
        </div>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="reporte_its.php">
            <input type="hidden" name="generar" value="1">
            <div class="row g-3">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small" for="selAnio"><i class="fas fa-calendar me-1"></i>A&ntilde;o</label>
                    <select name="anio" id="selAnio" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= $fAnio === (string)$a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small" for="selMes"><i class="fas fa-calendar-alt me-1"></i>Mes</label>
                    <select name="mes" id="selMes" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($mesesDisponibles as $m): ?>
                            <option value="<?= $m ?>" <?= $fMes === (string)$m ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6 col-sm-12">
                    <label class="form-label fw-semibold small" for="selEst"><i class="fas fa-hospital me-1"></i>Establecimiento (RENAES / IPRESS)</label>
                    <select name="establecimiento" id="selEst" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientos as $codUnico => $nombre): ?>
                            <option value="<?= htmlspecialchars($codUnico) ?>" <?= $fEstablecimiento === $codUnico ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6 col-sm-12">
                    <label class="form-label fw-semibold small" for="selGrupo"><i class="fas fa-layer-group me-1"></i>Grupo del reporte</label>
                    <select name="grupo" id="selGrupo" class="form-select form-select-sm">
                        <option value="">-- Todos los grupos --</option>
                        <?php foreach ($gruposMeta as $gk => $gm): ?>
                            <option value="<?= $gk ?>" <?= $fGrupo === $gk ? 'selected' : '' ?>><?= $gm['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-12 d-flex align-items-end gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="chkDetalle" name="detalle" value="1" <?= $fDetalle === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chkDetalle">Mostrar filas con 0 casos</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="chkSQL" name="versql" value="1" <?= $fVerSQL === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="chkSQL">Ver SQL (auditor&iacute;a)</label>
                    </div>
                </div>
            </div>
        </form>

        <!-- Filtros dependientes: Anio y Mes se recalculan dentro del ambito del
             establecimiento. Cambiar EE.SS/anio NO genera el reporte. -->
        <script>
        (function () {
            var selAnio = document.getElementById('selAnio');
            var selMes  = document.getElementById('selMes');
            var selEst  = document.getElementById('selEst');
            if (!selAnio || !selMes || !selEst) return;

            function pedir(soloMeses) {
                var qs = new URLSearchParams();
                qs.set('ajax', 'filtros');
                qs.set('anio', selAnio.value);
                qs.set('establecimiento', selEst.value);
                fetch('reporte_its.php?' + qs.toString(), { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d || !d.ok) return;
                        if (!soloMeses && d.anios && d.anios.length) {
                            var anioPrevio = selAnio.value;
                            reconstruir(selAnio, d.anios.map(String), true);
                            if (selAnio.value !== anioPrevio) { pedir(true); return; }
                        }
                        if (d.meses) reconstruir(selMes, d.meses, false);
                    })
                    .catch(function () { /* sin AJAX se conservan las opciones del servidor */ });
            }

            /* Reconstruye las opciones de un select conservando "-- Todos --"
               (value='') y la seleccion actual si sigue disponible. */
            function reconstruir(sel, valores, preferirPrimero) {
                var previo = sel.value;
                while (sel.options.length > 1) sel.remove(1);
                valores.forEach(function (v) {
                    var o = document.createElement('option');
                    if (v && typeof v === 'object') {
                        o.value = String(v.v);
                        o.textContent = v.t;
                    } else {
                        o.value = String(v);
                        o.textContent = String(v);
                    }
                    sel.appendChild(o);
                });
                var sigue = Array.prototype.some.call(sel.options, function (o) {
                    return o.value === previo;
                });
                sel.value = sigue ? previo : (preferirPrimero && valores.length ? sel.options[1].value : '');
            }

            selAnio.addEventListener('change', function () { pedir(true); });
            selEst.addEventListener('change', function () { pedir(false); });
        })();
        </script>
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
        <div class="its-cond mt-2"><?= "SQL: " . htmlspecialchars($debugSQL['sql']) . "\n\nPARAMS: " . htmlspecialchars(json_encode($debugSQL['params'], JSON_PRETTY_PRINT)) ?></div>
    </details>
    <?php endif; ?>
    <?php $diag = itsDiagnosticoEntorno($pdo); ?>
    <hr>
    <h6 class="small fw-bold mb-2"><i class="fas fa-stethoscope me-1"></i>Diagn&oacute;stico del entorno</h6>
    <ul class="small mb-2 ps-3">
        <li>PHP: <code><?= htmlspecialchars($diag['php']) ?></code> &nbsp;|&nbsp; memory_limit: <code><?= htmlspecialchars($diag['memory_limit']) ?></code> &nbsp;|&nbsp; max_execution_time: <code><?= htmlspecialchars($diag['max_exec_time']) ?>s</code></li>
        <li>Filas aprox. en la tabla consolidada: <code><?= $diag['filas_tabla'] !== null ? number_format((int)$diag['filas_tabla']) : 'n/d' ?></code></li>
        <li>
            &Iacute;ndices en la tabla consolidada:
            <?php if (empty($diag['indices'])): ?>
                <span class="badge bg-danger">NINGUNO</span>
                &rarr; la consulta hace un barrido completo de la tabla. Ejecute una vez
                <a href="install_its.php" class="fw-bold">install_its.php</a> para crearlos.
            <?php else: ?>
                <span class="badge bg-success"><?= count($diag['indices']) ?></span>
                <code class="small"><?= htmlspecialchars(implode(', ', $diag['indices'])) ?></code>
            <?php endif; ?>
        </li>
    </ul>
    <div class="small text-muted">
        <i class="fas fa-lightbulb me-1"></i>Recomendaciones: ejecute <strong>install_its.php</strong> (una sola vez), y genere el reporte con filtros m&aacute;s acotados
        (seleccione un mes y/o un establecimiento en lugar de "-- Todos --").
    </div>
</div>

<?php else: ?>

<!-- Cabecera tipo Excel: PERIODO / RED / RENAES / IPRESS -->
<div class="its-head-cover d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <i class="fas fa-check-circle me-2"></i>
        <strong>HOJA DE MONITORIZACI&Oacute;N MENSUAL DE LA ESN PREVENCI&Oacute;N Y CONTROL DE ITS, VIH/SIDA Y HEPATITIS</strong><br>
        <small>
            DIRESA: JUNIN &nbsp;|&nbsp; MICRO RED: PERENE &nbsp;|&nbsp; RED: CHANCHAMAYO<br>
            PERIODO: <?= htmlspecialchars($periodoTxt) ?>
            <?= $renaes ? ' &nbsp;|&nbsp; ESTABLECIMIENTO (RENAES): ' . htmlspecialchars($renaes) : '' ?>
            <?= $nombreEstablecimiento ? ' &nbsp;|&nbsp; IPRESS: ' . htmlspecialchars($nombreEstablecimiento) : ' &nbsp;|&nbsp; TODOS LOS ESTABLECIMIENTOS DE LA LISTA (' . count($establecimientos) . ' EE.SS)' ?>
        </small>
    </div>
    <div class="text-end">
        <strong><?= number_format($reporte['totales']['total_casos']) ?></strong> casos/personas totales /
        <?= number_format($reporte['totales']['secciones_con_datos']) ?> de 16 bloques con datos /
        <?= number_format($reporte['filas_leidas']) ?> filas HIS le&iacute;das / <?= $reporte['tiempo_ejecucion'] ?>s<br>
        <span class="badge bg-dark mb-0" title="Versión del motor includes/its_data.php en ejecución.">Motor its_data.php v<?= htmlspecialchars($reporte['version'] ?? itsDataVersion()) ?></span>
    </div>
</div>

<?php if ($fVerSQL === '1'): ?>
<div class="alert alert-warning py-2">
    <i class="fas fa-search me-2"></i>
    <strong>Modo auditor&iacute;a activado:</strong> cada bloque muestra la condici&oacute;n SQL equivalente que ejecuta el motor
    (adaptaci&oacute;n de los 15 procedimientos <code>usp_TRAMA_BASE_ITS_VIH_2022_*</code> del archivo <code>03 Creacion de Procedimientos</code>).
    Compare estas condiciones con su archivo original y aj&uacute;stelas en <code>includes/its_data.php &rarr; itsSecciones()</code> si alguna difiere.
</div>
<?php endif; ?>

<?php
// ====== Render de secciones agrupadas por macro-grupo del Excel ======
$secciones = $reporte['secciones'];
$grupoActual = null;
foreach ($secciones as $sec):
    if ($fGrupo !== '' && $sec['grupo'] !== $fGrupo) continue;
    if ($sec['grupo'] !== $grupoActual):
        $grupoActual = $sec['grupo'];
        $gm = $gruposMeta[$grupoActual] ?? null;
        $totGrupo = $reporte['totales']['grupos'][$grupoActual] ?? 0;
        $nSecsGrupo = count(array_filter($secciones, fn($s) => $s['grupo'] === $grupoActual));
?>
<div class="its-group-header">
    <h6><i class="fas <?= $gm['icon'] ?? 'fa-layer-group' ?> me-2"></i><?= $gm['nombre'] ?? htmlspecialchars($grupoActual) ?></h6>
    <small><?= $gm['desc'] ?? '' ?> &nbsp;|&nbsp; <?= $nSecsGrupo ?> bloques &nbsp;|&nbsp; total: <strong><?= number_format($totGrupo) ?></strong></small>
</div>
<?php endif; ?>
<div class="its-section-card">
    <div class="its-section-header">
        <span><i class="fas fa-file-medical me-2"></i><?= htmlspecialchars($sec['titulo']) ?></span>
        <small>
            <span class="its-secflag badge bg-light text-dark me-1"><?= htmlspecialchars($sec['codigo']) ?></span>
            <?= count($sec['filas']) ?> filas
            | total: <strong><?= number_format($sec['total']) ?></strong>
        </small>
    </div>
    <div class="card-body p-0">
        <?php itsRenderSeccion($sec, $fDetalle === '1', $fVerSQL === '1'); ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Nota sobre equivalencia con el flujo original -->
<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Equivalencia con el flujo original:</strong> este reporte replica los 15 procedimientos
    <code>usp_TRAMA_BASE_ITS_VIH_2022_*</code> (archivos 01/02/03) sobre la tabla consolidada MySQL y el
    layout de la plantilla <code>Reporte_Actividades_ITS.xlsx</code> (16 bloques: Poblaci&oacute;n General, Hepatitis B/C,
    Tamizaje VIH, Exposici&oacute;n, Violencia Sexual, PVV, Gestantes, Reci&eacute;n Nacidos, Poblaci&oacute;n Clave,
    Brigadas MCC/BMA y Telemedicina). Ya no necesita SQL Server ni refrescar la
    conexi&oacute;n ODBC: <strong>1 click y (opcional) Exportar Excel</strong>.<br>
    <strong>Desarrollado por JKRV</strong>
</div>

<?php endif; ?>

<?php elseif (!$ejecutar): ?>
<div class="alert alert-info d-flex align-items-center">
    <i class="fas fa-info-circle me-3 fa-2x"></i>
    <div>
        <strong>Reporte listo para generar.</strong><br>
        <div class="small text-muted mt-1">
            Seleccione el periodo y pulse <strong>Generar Reporte</strong>. El informe incluye los 16 bloques del reporte
            operacional ITS/VIH/Hepatitis (poblaci&oacute;n general, hepatitis B/C, tamizaje VIH, exposiciones, PVV,
            gestantes y reci&eacute;n nacidos, poblaci&oacute;n clave HSH/Trans/TS, brigadas y telemedicina). Desarrollado por JKRV
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
