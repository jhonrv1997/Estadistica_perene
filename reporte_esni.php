<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Reporte Operacional ESNI (Inmunizaciones) - VERSIÓN COMPLETA
 *
 * Reemplaza al flujo manual anterior:
 *   SQL Server -> 4 archivos .txt de scripts -> Excel con conexion ODBC
 *
 * Ahora TODO se hace desde la web:
 *   1) El usuario aplica filtros (anio, mes, departamento, EE.SS., profesional).
 *   2) El motor de reglas data-driven (includes/esni_data.php) ejecuta el reporte
 *      contra la tabla consolidada MySQL usando las reglas configurables.
 *   3) Se muestran las 14 secciones (A, B, C, H, H2, I, J, K, L, M, N, O, P, VPH)
 *      con el mismo layout que el Excel oficial "ReporteActividadesEsni2019.xlsx".
 *   4) Boton Exportar Excel -> genera el .xlsx con la misma distribucion.
 *
 * El administrador puede agregar/modificar vacunas, dosis, grupos de edad, lineas
 * y reglas en esni_config.php sin tocar codigo SQL ni PHP.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/esni_data.php';

$pdo = getDBConnection();

// Verificar que el esquema ESNI este instalado
$esquemaOK = esniEsquemaInstalado($pdo);

// Resolver columnas de la tabla origen
$cols = esniResolverColumnas($pdo);

// Filtros
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fDepartamento    = trim($_GET['departamento'] ?? '');
$fProfesional     = trim($_GET['profesional'] ?? '');

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
    'departamento'    => $fDepartamento,
    'profesional'     => $fProfesional,
];

// Listas para los selectores
$anios           = esniGetAniosDisponibles($pdo, $cols);
if (empty($anios)) $anios = [date('Y')];
if ($fAnio === '' && !empty($anios)) $fAnio = $anios[0];
$filtros['anio'] = $fAnio;

$establecimientos = esniGetEstablecimientos($pdo, $cols);
$departamentos    = esniGetDepartamentos($pdo, $cols);
$profesionales    = esniGetProfesionales($pdo, $cols);

// Ejecutar reporte solo si se solicita (boton Generar) o si hay filtros
$ejecutar = isset($_GET['generar']) || $fAnio !== '' || $fMes !== '' || $fEstablecimiento !== '' || $fDepartamento !== '' || $fProfesional !== '';
$reporte = null;
$debugSQL = null;

if ($ejecutar && $esquemaOK) {
    $t0 = microtime(true);
    $reporte = esniEjecutarReporte($pdo, $filtros, $cols);
    $tEjec = round(microtime(true) - $t0, 3);
    $reporte['tiempo_ejecucion'] = $tEjec;
    if (!empty($reporte['error']) && strpos($reporte['error'], 'Error SQL') === 0) {
        $debugSQL = ['sql' => $reporte['sql_debug'] ?? '', 'params' => $reporte['params_debug'] ?? []];
    }
}

$pageTitle = 'Reporte Operacional ESNI - Inmunizaciones - Sistema HIS';
include 'includes/header.php';
?>

<style>
.esni-section-card { border: 1px solid #dee2e6; border-radius: .5rem; margin-bottom: 1.2rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
.esni-section-header { background: linear-gradient(90deg,#0d6efd 0%,#0b5ed7 100%); color:#fff; padding:.6rem 1rem; font-weight:600; display:flex; justify-content:space-between; align-items:center; }
.esni-section-header small { opacity:.85; font-weight:400; }
.esni-table { font-size:.82rem; margin:0; }
.esni-table thead th { background:#f1f3f5; border-bottom:2px solid #dee2e6; padding:.4rem .5rem; text-align:center; vertical-align:middle; font-weight:600; color:#343a40; }
.esni-table tbody td { padding:.35rem .5rem; vertical-align:middle; border-bottom:1px solid #f1f3f5; }
.esni-table tbody tr:hover { background:#f8f9fa; }
.esni-cod-pill { display:inline-block; background:#e9ecef; color:#495057; padding:.05rem .35rem; border-radius:.3rem; font-size:.7rem; font-weight:600; margin-right:.25rem; }
.esni-total-row { background:#fff3cd !important; font-weight:700; }
.esni-bigtotal { background:linear-gradient(90deg,#198754 0%,#157347 100%); color:#fff; padding:1rem 1.5rem; border-radius:.5rem; margin-bottom:1rem; }
.esni-vac-dot { width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:.4rem; vertical-align:middle; }
.esni-debug { background:#f8f9fa; border:1px solid #dee2e6; padding:.8rem; font-family:monospace; font-size:.75rem; white-space:pre-wrap; word-break:break-all; }
.esni-stat-card { padding:.8rem; border-radius:.5rem; color:#fff; }
.esni-stat-num { font-size:1.6rem; font-weight:700; line-height:1; }
.esni-stat-lbl { font-size:.78rem; opacity:.9; margin-top:.2rem; }
</style>

<div class="page-header-section">
    <h4><i class="fas fa-syringe me-2 text-success"></i>Reporte Operacional ESNI - Inmunizaciones</h4>
    <p class="subtitle">Informe analitico de inmunizaciones por vacuna, dosis y grupo de edad. Configuracion data-driven.</p>
</div>

<?php if (!$esquemaOK): ?>
<div class="alert alert-warning d-flex align-items-center">
    <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
    <div>
        <h6 class="alert-heading">Esquema ESNI no instalado</h6>
        <p class="mb-2">Para habilitar este reporte debe ejecutar el script <code>Database/install_esni.sql</code> en su base de datos MySQL.</p>
        <p class="mb-0 small">Este script crea las 7 tablas de configuracion (<code>ESNI_VACUNA</code>, <code>ESNI_DOSIS</code>, <code>ESNI_GRUPO_EDAD</code>, <code>ESNI_SECCION_REPORTE</code>, <code>ESNI_LINEA_REPORTE</code>, <code>ESNI_REGLA</code>, <code>ESNI_PARAMETRO</code>) con datos semilla para las secciones A, B, C, H, H2, I y VPH.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($esquemaOK):
    $resumen = esniGetResumenConfig($pdo);
?>
<!-- Stats de configuracion -->
<div class="row g-2 mb-3">
    <div class="col-md-2 col-6"><div class="esni-stat-card bg-primary"><div class="esni-stat-num"><?= $resumen['vacunas'] ?></div><div class="esni-stat-lbl"><i class="fas fa-prescription-bottle me-1"></i>Vacunas</div></div></div>
    <div class="col-md-2 col-6"><div class="esni-stat-card bg-info"><div class="esni-stat-num"><?= $resumen['dosis'] ?></div><div class="esni-stat-lbl"><i class="fas fa-list-ol me-1"></i>Dosis</div></div></div>
    <div class="col-md-2 col-6"><div class="esni-stat-card bg-warning text-dark"><div class="esni-stat-num"><?= $resumen['grupos_edad'] ?></div><div class="esni-stat-lbl"><i class="fas fa-users me-1"></i>Grupos Edad</div></div></div>
    <div class="col-md-2 col-6"><div class="esni-stat-card bg-success"><div class="esni-stat-num"><?= $resumen['secciones'] ?></div><div class="esni-stat-lbl"><i class="fas fa-layer-group me-1"></i>Secciones</div></div></div>
    <div class="col-md-2 col-6"><div class="esni-stat-card bg-secondary"><div class="esni-stat-num"><?= $resumen['lineas'] ?></div><div class="esni-stat-lbl"><i class="fas fa-bars me-1"></i>Lineas</div></div></div>
    <div class="col-md-2 col-6"><div class="esni-stat-card bg-danger"><div class="esni-stat-num"><?= $resumen['reglas'] ?></div><div class="esni-stat-lbl"><i class="fas fa-project-diagram me-1"></i>Reglas</div></div></div>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros del Reporte</h6>
        <div class="d-flex gap-2">
            <?php if ($esquemaOK): ?>
            <a href="esni_config.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-cog me-1"></i> Configurar</a>
            <a href="esni_export.php?<?= http_build_query($filtros) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel me-1"></i> Exportar Excel</a>
            <?php endif; ?>
            <button type="submit" form="filterForm" class="btn btn-sm btn-his"><i class="fas fa-play me-1"></i> Generar Reporte</button>
        </div>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="reporte_esni.php">
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
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <label class="form-label fw-semibold small"><i class="fas fa-map-marker-alt me-1"></i>Departamento</label>
                    <select name="departamento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($departamentos as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $fDepartamento === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-12">
                    <label class="form-label fw-semibold small"><i class="fas fa-hospital me-1"></i>Establecimiento</label>
                    <select name="establecimiento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientos as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>" <?= $fEstablecimiento === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold small"><i class="fas fa-user-md me-1"></i>Profesional</label>
                    <select name="profesional" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($profesionales as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>" <?= $fProfesional === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($ejecutar && $esquemaOK && $reporte): ?>

<?php if (!empty($reporte['error'])): ?>
<div class="alert alert-danger">
    <h6 class="alert-heading"><i class="fas fa-exclamation-circle me-2"></i>Error al ejecutar el reporte</h6>
    <p class="mb-2"><?= htmlspecialchars($reporte['error']) ?></p>
    <?php if ($debugSQL): ?>
    <details>
        <summary class="small">Ver SQL debug</summary>
        <div class="esni-debug mt-2"><?= "SQL: " . htmlspecialchars($debugSQL['sql']) . "\n\nPARAMS: " . htmlspecialchars(json_encode($debugSQL['params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></div>
    </details>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- Resultado global -->
<div class="esni-bigtotal d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <i class="fas fa-check-circle me-2"></i>
        <strong>Reporte generado:</strong>
        <?= number_format($reporte['totales']['total_dosis']) ?> dosis aplicadas /
        <?= $reporte['totales']['total_lineas_con_datos'] ?> lineas con datos /
        <?= number_format($reporte['filas_leidas']) ?> registros HIS leidos
        / Tiempo: <?= $reporte['tiempo_ejecucion'] ?? '?' ?>s
    </div>
    <div class="mt-2 mt-md-0">
        <small>Filtros:</small>
        <?php
        $tags = [];
        if ($fAnio)            $tags[] = 'Anio=' . $fAnio;
        if ($fMes)             $tags[] = 'Mes=' . getNombreMes($fMes);
        if ($fDepartamento)    $tags[] = 'Dep=' . $fDepartamento;
        if ($fEstablecimiento) $tags[] = 'EESS=' . mb_strimwidth($fEstablecimiento, 0, 25, '...');
        if ($fProfesional)     $tags[] = 'Prof=' . $fProfesional;
        if (empty($tags)) $tags[] = 'SIN FILTROS (todos los periodos)';
        ?>
        <?php foreach ($tags as $t): ?>
            <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($t) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<?php foreach ($reporte['secciones'] as $sec): ?>
<div class="esni-section-card">
    <div class="esni-section-header">
        <span><i class="fas fa-layer-group me-2"></i><?= htmlspecialchars($sec['titulo']) ?></span>
        <small><?= count($sec['lineas']) ?> lineas | Total: <strong><?= number_format($sec['total']) ?></strong></small>
    </div>
    <div class="card-body p-0">
        <?php if (empty($sec['lineas'])): ?>
            <div class="text-center text-muted py-3"><i class="fas fa-inbox me-2"></i>Esta seccion no tiene lineas configuradas. Agregalas desde <a href="esni_config.php">Configurar ESNI</a>.</div>
        <?php else: ?>
            <?php
            // Render segun layout
            switch ($sec['layout']) {
                case 'matriz_dosis':   echo renderMatrizDosis($sec); break;
                case 'matriz_edad':    echo renderMatrizEdad($sec);  break;
                case 'total_uno':      echo renderTotalUno($sec);    break;
                case 'matriz_sexo':    echo renderMatrizSexo($sec);  break;
                default:               echo renderLista($sec);       break;
            }
            ?>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php elseif ($esquemaOK && !$ejecutar): ?>
<div class="alert alert-info d-flex align-items-center">
    <i class="fas fa-info-circle me-3 fa-2x"></i>
    <div>
        <strong>Reporte listo para generar.</strong><br>
        Configure los filtros y pulse <em>Generar Reporte</em>. Se ejecutara el motor de reglas data-driven contra la tabla <code><?= htmlspecialchars($cols['_tabla']) ?></code>.
    </div>
</div>
<?php endif; ?>

<?php
// ====== FUNCIONES DE RENDERIZADO POR LAYOUT ======

function renderLista(array $sec): string {
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover esni-table mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th class="text-start">Tipo de Vacuna / Dosis</th>
                    <th style="width:90px;">Vacuna</th>
                    <th style="width:90px;">Dosis</th>
                    <th style="width:130px;">Grupo Edad</th>
                    <th style="width:60px;">Sexo</th>
                    <th class="text-end" style="width:110px;">Casos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sec['lineas'] as $i => $lin): ?>
                <tr class="<?= $lin['cantidad'] > 0 ? '' : 'text-muted' ?>">
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td>
                        <?php if ($lin['vacuna_color']): ?>
                            <span class="esni-vac-dot" style="background:<?= htmlspecialchars($lin['vacuna_color']) ?>"></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($lin['etiqueta']) ?>
                    </td>
                    <td><span class="esni-cod-pill"><?= htmlspecialchars($lin['vacuna_codigo'] ?: '-') ?></span></td>
                    <td><span class="esni-cod-pill"><?= htmlspecialchars($lin['dosis_codigo'] ?: '-') ?></span></td>
                    <td><small><?= htmlspecialchars($lin['grupo_edad_codigo'] ?: '-') ?></small></td>
                    <td class="text-center">
                        <?php if ($lin['sexo'] === 'M'): ?><span class="badge bg-info">M</span>
                        <?php elseif ($lin['sexo'] === 'F'): ?><span class="badge bg-warning text-dark">V</span>
                        <?php else: ?><span class="text-muted">A</span><?php endif; ?>
                    </td>
                    <td class="text-end fw-bold <?= $lin['cantidad'] > 0 ? 'text-success' : 'text-muted' ?>"><?= number_format($lin['cantidad']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="esni-total-row">
                    <td colspan="6" class="text-end">TOTAL SECCION <?= htmlspecialchars($sec['codigo']) ?></td>
                    <td class="text-end"><?= number_format($sec['total']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

function renderMatrizDosis(array $sec): string {
    // Agrupar lineas por vacuna + grupo edad, columnas = dosis
    $grupos = [];
    foreach ($sec['lineas'] as $lin) {
        $key = ($lin['vacuna_codigo'] ?: '-') . '|' . ($lin['grupo_edad_codigo'] ?: '-');
        if (!isset($grupos[$key])) {
            $grupos[$key] = [
                'etiqueta' => $lin['etiqueta'],
                'vacuna'   => $lin['vacuna_codigo'] ?: '-',
                'edad'     => $lin['grupo_edad_codigo'] ?: '-',
                'dosis'    => [],
            ];
        }
        $grupos[$key]['dosis'][$lin['dosis_codigo']] = $lin['cantidad'];
    }
    $dosisCols = ['D1', 'D2', 'D3', 'D4', 'REF1', 'REF2', 'DU'];
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover esni-table mb-0">
            <thead>
                <tr>
                    <th class="text-start">Grupo Edad</th>
                    <th>Vacuna</th>
                    <?php foreach ($dosisCols as $dc): ?><th><?= $dc ?></th><?php endforeach; ?>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grupos as $g):
                    $tot = array_sum($g['dosis']);
                ?>
                <tr class="<?= $tot > 0 ? '' : 'text-muted' ?>">
                    <td><?= htmlspecialchars($g['edad']) ?></td>
                    <td><span class="esni-cod-pill"><?= htmlspecialchars($g['vacuna']) ?></span></td>
                    <?php foreach ($dosisCols as $dc): ?>
                        <td class="text-end"><?= isset($g['dosis'][$dc]) ? number_format($g['dosis'][$dc]) : '<span class="text-muted">-</span>' ?></td>
                    <?php endforeach; ?>
                    <td class="text-end fw-bold text-success"><?= number_format($tot) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="esni-total-row">
                    <td colspan="<?= 2 + count($dosisCols) ?>" class="text-end">TOTAL SECCION <?= htmlspecialchars($sec['codigo']) ?></td>
                    <td class="text-end"><?= number_format($sec['total']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

function renderMatrizEdad(array $sec): string {
    // Columnas = edades (02, 03, 04 anos), filas = dosis
    $edades = ['02A' => '02 Anios', '03A' => '03 Anios', '04A' => '04 Anios'];
    $dosisFilas = ['D1' => '1ra Dosis', 'D2' => '2da Dosis', 'D3' => '3ra Dosis'];
    $mat = [];
    foreach ($sec['lineas'] as $lin) {
        $dc = $lin['dosis_codigo'];
        $ec = $lin['grupo_edad_codigo'];
        if (isset($dosisFilas[$dc]) && isset($edades[$ec])) {
            $mat[$dc][$ec] = $lin['cantidad'];
        }
    }
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover esni-table mb-0">
            <thead>
                <tr><th class="text-start">Dosis</th>
                    <?php foreach ($edades as $ec => $en): ?><th><?= htmlspecialchars($en) ?></th><?php endforeach; ?>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dosisFilas as $dc => $dn):
                    $tot = 0;
                    foreach ($edades as $ec => $_) $tot += $mat[$dc][$ec] ?? 0;
                ?>
                <tr class="<?= $tot > 0 ? '' : 'text-muted' ?>">
                    <td><?= htmlspecialchars($dn) ?></td>
                    <?php foreach ($edades as $ec => $_): ?>
                        <td class="text-end"><?= isset($mat[$dc][$ec]) ? number_format($mat[$dc][$ec]) : '<span class="text-muted">-</span>' ?></td>
                    <?php endforeach; ?>
                    <td class="text-end fw-bold text-success"><?= number_format($tot) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="esni-total-row"><td colspan="<?= count($edades) + 1 ?>" class="text-end">TOTAL SECCION <?= htmlspecialchars($sec['codigo']) ?></td><td class="text-end"><?= number_format($sec['total']) ?></td></tr>
            </tfoot>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

function renderTotalUno(array $sec): string {
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover esni-table mb-0">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th class="text-start">Grupo de Edad / Riesgo</th>
                    <th style="width:80px;">Vacuna</th>
                    <th class="text-end" style="width:120px;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sec['lineas'] as $i => $lin): ?>
                <tr class="<?= $lin['cantidad'] > 0 ? '' : 'text-muted' ?>">
                    <td class="text-muted small"><?= $i + 1 ?></td>
                    <td>
                        <?php if ($lin['vacuna_color']): ?>
                            <span class="esni-vac-dot" style="background:<?= htmlspecialchars($lin['vacuna_color']) ?>"></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($lin['etiqueta']) ?>
                    </td>
                    <td><span class="esni-cod-pill"><?= htmlspecialchars($lin['vacuna_codigo'] ?: '-') ?></span></td>
                    <td class="text-end fw-bold <?= $lin['cantidad'] > 0 ? 'text-success' : 'text-muted' ?>"><?= number_format($lin['cantidad']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="esni-total-row"><td colspan="3" class="text-end">TOTAL SECCION <?= htmlspecialchars($sec['codigo']) ?></td><td class="text-end"><?= number_format($sec['total']) ?></td></tr>
            </tfoot>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

function renderMatrizSexo(array $sec): string {
    // Columnas = sexo (Femenino 1ra, Femenino 2da, Masculino Unica)
    $cols = [
        'F_D1' => 'Femenino 1ra',
        'F_D2' => 'Femenino 2da',
        'M_DU' => 'Masculino Unica',
    ];
    $rowMap = [];
    foreach ($sec['lineas'] as $lin) {
        if ($lin['sexo'] === 'M') {
            $k = 'F_' . $lin['dosis_codigo'];
        } elseif ($lin['sexo'] === 'F') {
            $k = 'M_' . $lin['dosis_codigo'];
        } else continue;
        if (isset($cols[$k])) $rowMap[$k] = $lin;
    }
    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover esni-table mb-0">
            <thead>
                <tr><th class="text-start">Grupo Edad</th>
                    <?php foreach ($cols as $cn): ?><th><?= htmlspecialchars($cn) ?></th><?php endforeach; ?>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $tot = 0;
                foreach ($rowMap as $k => $lin) $tot += $lin['cantidad'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($rowMap['F_D1']['grupo_edad_codigo'] ?? $rowMap['M_DU']['grupo_edad_codigo'] ?? '-') ?></td>
                    <?php foreach (array_keys($cols) as $k): ?>
                        <td class="text-end fw-bold <?= ($rowMap[$k]['cantidad'] ?? 0) > 0 ? 'text-success' : 'text-muted' ?>"><?= isset($rowMap[$k]) ? number_format($rowMap[$k]['cantidad']) : '<span class="text-muted">-</span>' ?></td>
                    <?php endforeach; ?>
                    <td class="text-end fw-bold"><?= number_format($tot) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="esni-total-row"><td colspan="<?= count($cols) + 1 ?>" class="text-end">TOTAL SECCION <?= htmlspecialchars($sec['codigo']) ?></td><td class="text-end"><?= number_format($sec['total']) ?></td></tr>
            </tfoot>
        </table>
    </div>
    <?php
    return ob_get_clean();
}
?>

<div class="card mt-4">
    <div class="card-body small text-muted">
        <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Como funciona este reporte</h6>
        <p class="mb-1">El motor data-driven ejecuta las siguientes etapas:</p>
        <ol class="mb-2 small">
            <li>Carga las reglas activas desde la tabla <code>ESNI_REGLA</code> (mapeo <code>cod_item</code> + <code>valor_lab</code> + grupo edad -&gt; linea del reporte).</li>
            <li>Construye una sola consulta SQL contra la tabla origen <code><?= htmlspecialchars($cols['_tabla'] ?? '?') ?></code> trayendo solo filas con los <code>cod_item</code> relevantes, aplicando los filtros comunes.</li>
            <li>Para cada fila HIS, evalua las reglas asociadas al <code>cod_item</code> y cuenta la primera regla que encaja (valor_lab + sexo + edad + aniomes + riesgo).</li>
            <li>Suma los contadores por linea y por seccion, generando el mismo layout que el Excel oficial MINSA.</li>
        </ol>
        <p class="mb-0 small text-muted">
            Para agregar una nueva vacuna o dosis: vaya a <a href="esni_config.php"><i class="fas fa-cog me-1"></i>Configurar ESNI</a> y agregue los registros correspondientes.
            <strong>No requiere editar codigo SQL ni PHP.</strong>
        </p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
