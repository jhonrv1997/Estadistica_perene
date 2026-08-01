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
 *   4) Boton Exportar Excel -> genera el .xlsx con la misma distribución.
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

// ============================================================================
// FILTRO DE ESTRATEGIA ESNI
// ----------------------------------------------------------------------------
// Se forza el filtro Id_Ups = 301204 sobre la tabla
// T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO para evitar cargar datos de
// otras estrategias (ESNI / VPH / Atencion, etc.) que comparten la misma
// tabla consolidada. De esta manera el reporte ESNI solo trabajara con las
// filas que realmente pertenecen a Inmunizaciones (Id_Ups = 301204).
// ============================================================================
define('ESNI_ID_UPS', '301204');

// Filtros
$fAnio            = trim($_GET['anio'] ?? '');
$fMes             = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fProfesional     = trim($_GET['profesional'] ?? '');

$filtros = [
    'anio'            => $fAnio,
    'mes'             => $fMes,
    'establecimiento' => $fEstablecimiento,
    'profesional'     => $fProfesional,
    // Fijo para el modulo ESNI: solo estrategia Id_Ups = 301204
    'id_ups'          => ESNI_ID_UPS,
];

// Listas para los selectores
$anios           = esniGetAniosDisponibles($pdo, $cols, ESNI_ID_UPS);
if (empty($anios)) $anios = [date('Y')];
if ($fAnio === '' && !empty($anios)) $fAnio = $anios[0];
$filtros['anio'] = $fAnio;

// Establecimientos cargados desde el catalogo ZSPERENE (rapido) en lugar de
// hacer un DISTINCT sobre la gran tabla HIS, lo cual saturaba la base de datos
// al cargar la pagina. La clave es el Codigo_Unico (valor del <option>) y el
// valor el Nombre_Establecimiento.
$establecimientos = esniGetEstablecimientosZS($pdo);
$profesionales    = esniGetProfesionales($pdo, $cols, ESNI_ID_UPS);

// Ejecutar reporte SOLO cuando el usuario pulse "Generar reporte".
// Esto evita saturar la base de datos con la consulta del motor de reglas
// cada vez que se carga la pagina sin haber pedido el reporte explicitamente.
$ejecutar = isset($_GET['generar']);
$reporte = null;
$debugSQL = null;
$diagnostico = null;

if ($ejecutar && $esquemaOK) {
    $t0 = microtime(true);
    $reporte = esniEjecutarReporte($pdo, $filtros, $cols);
    $tEjec = round(microtime(true) - $t0, 3);
    $reporte['tiempo_ejecucion'] = $tEjec;
    if (!empty($reporte['error']) && strpos($reporte['error'], 'Error SQL') === 0) {
        $debugSQL = ['sql' => $reporte['sql_debug'] ?? '', 'params' => $reporte['params_debug'] ?? []];
    }
    // Diagnostico de cobertura de reglas (solo si no hubo error SQL)
    if (empty($reporte['error'])) {
        $diagnostico = esniDiagnosticarReglas($pdo, $cols, $filtros);
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
            <?php if (esAdmin()): ?>
            <a href="install_esni_extra.php" class="btn btn-sm btn-outline-warning" title="Cargar reglas adicionales para codigos de item HIS reales"><i class="fas fa-plus-circle me-1"></i> Reglas extra</a>
            <?php endif; ?>
            <a href="esni_config.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-cog me-1"></i> Configurar</a>
            <a href="esni_export.php?<?= http_build_query($filtros) ?>" class="btn btn-sm btn-success"><i class="fas fa-file-excel me-1"></i> Exportar Excel</a>
            <?php endif; ?>
            <button type="submit" form="filterForm" class="btn btn-sm btn-his"><i class="fas fa-play me-1"></i> Generar Reporte</button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-info py-2 mb-3 small d-flex align-items-center">
            <i class="fas fa-lock me-2 text-info"></i>
            <span>
                <strong>Estrategia fija:</strong>
                Este reporte solo carga datos de la tabla
                <code>T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO</code>
                con <code>Id_Ups = <?= htmlspecialchars(ESNI_ID_UPS) ?></code>
                (Inmunizaciones / ESNI), para evitar cargar datos de otras estrategias.
                La lista de establecimientos se obtiene del catalogo
                <code>ZSPERENE</code>.
            </span>
        </div>
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
                <div class="col-lg-4 col-md-8 col-sm-12">
                    <label class="form-label fw-semibold small"><i class="fas fa-hospital me-1"></i>Establecimiento</label>
                    <select name="establecimiento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientos as $codUnico => $nombre): ?>
                            <option value="<?= htmlspecialchars($codUnico) ?>" <?= $fEstablecimiento === $codUnico ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
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
        $tags[] = 'Id_Ups=' . ESNI_ID_UPS . ' (ESNI)'; // Fijo por modulo
        if ($fAnio)             $tags[] = 'Anio=' . $fAnio;
        if ($fMes)              $tags[] = 'Mes=' . getNombreMes($fMes);
        if ($fEstablecimiento)  $tags[] = 'EESS=' . mb_strimwidth($establecimientos[$fEstablecimiento] ?? $fEstablecimiento, 0, 30, '...');
        if ($fProfesional)      $tags[] = 'Prof=' . $fProfesional;
        if (count($tags) === 1) $tags[] = 'SIN FILTROS (todos los periodos)';
        ?>
        <?php foreach ($tags as $t): ?>
            <span class="badge bg-light text-dark me-1"><?= htmlspecialchars($t) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($diagnostico && !empty($diagnostico['cod_items_sin_reglas'])): ?>
<!-- Panel de diagnostico de cobertura -->
<div class="card border-warning mb-3">
    <div class="card-header bg-warning bg-opacity-25 d-flex align-items-center">
        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
        <strong class="me-3">Diagnostico de cobertura de reglas</strong>
        <span class="text-muted small">
            Cobertura actual:
            <strong class="<?= $diagnostico['porcentaje_cobertura'] < 50 ? 'text-danger' : 'text-success' ?>">
                <?= $diagnostico['porcentaje_cobertura'] ?>%
            </strong>
            (<?= number_format($diagnostico['filas_cubiertas']) ?> de <?= number_format($diagnostico['total_filas_datos']) ?> filas HIS)
        </span>
    </div>
    <div class="card-body">
        <p class="mb-2 small">
            Se encontraron <strong><?= count($diagnostico['cod_items_sin_reglas']) ?> codigos de item</strong>
            en los datos HIS que <strong>no tienen reglas ESNI configuradas</strong>.
            Estas filas no se estan contando en el reporte. Ejecute
            <a href="install_esni_extra.php" class="alert-link"><code>install_esni_extra.php</code></a>
            o agregue las reglas manualmente desde <a href="esni_config.php?tab=reglas" class="alert-link">Configurar ESNI &raquo; Reglas</a>.
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.8rem">
                <thead>
                    <tr>
                        <th style="width:120px;">Codigo Item</th>
                        <th class="text-end" style="width:90px;">Filas HIS</th>
                        <th>Acción recomendada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diagnostico['cod_items_sin_reglas'] as $info): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($info['cod']) ?></code></td>
                        <td class="text-end fw-bold text-danger"><?= number_format($info['n']) ?></td>
                        <td class="text-muted small">
                            Crear regla en <a href="esni_config.php?tab=reglas">esni_config.php</a> con cod_item=<code><?= htmlspecialchars($info['cod']) ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($diagnostico['reglas_sin_datos'])): ?>
        <p class="mt-3 mb-0 small text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Adicionalmente, hay <strong><?= count($diagnostico['reglas_sin_datos']) ?> reglas</strong> configuradas
            para cod_items que <em>no aparecen en los datos actuales</em>:
            <code><?= htmlspecialchars(implode(', ', $diagnostico['reglas_sin_datos'])) ?></code>.
            Estas reglas son inofensivas pero pueden eliminarse para limpiar la configuracion.
        </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($reporte['totales']['total_dosis'] === 0): ?>
<!-- Aviso de 0 resultados -->
<div class="alert alert-info d-flex align-items-center">
    <i class="fas fa-info-circle me-3 fa-2x"></i>
    <div>
        <strong>El reporte se ejecutó correctamente pero no se encontro ninguna dosis que coincida con las reglas configuradas.</strong><br>
        Posibles causas:
        <ul class="mb-0 mt-1 small">
            <li>Los codigos de item HIS en los datos no coinciden con los codigos configurados en las reglas ESNI (ver diagnostico de cobertura arriba).</li>
            <li>Los <code>Valor_Lab</code> de las filas HIS no coinciden con los valores esperados por las reglas (por ejemplo: la regla espera <code>'1'</code> pero los datos tienen <code>'DU'</code>).</li>
            <li>Los grupos de edad de las filas HIS no encajan con los grupos configurados en las reglas.</li>
            <li>No hay datos HIS para el periodo/establecimiento seleccionado en los filtros.</li>
        </ul>
    </div>
</div>
<?php endif; ?>

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
                        <?php if ($lin['sexo'] === 'F'): ?><span class="badge bg-info">F</span>
                        <?php elseif ($lin['sexo'] === 'M'): ?><span class="badge bg-warning text-dark">V</span>
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

    // ====== CORRECCION: columnas dinamicas segun las dosis reales de la seccion ======
    // Antes: $dosisCols = ['D1', 'D2', 'D3', 'D4', 'REF1', 'REF2', 'DU'];  <-- siempre mostraba 7 columnas
    //
    // Ahora: se construye dinamicamente a partir de las dosis realmente configuradas
    // en las lineas de esta seccion (tabla ESNI_LINEA_REPORTE -> ESNI_DOSIS).
    // De esta manera, si la seccion solo tiene D1/D2/D3, la matriz mostrara
    // exclusivamente esas 3 columnas. Si otra seccion tiene DU o REF, se
    // incluiran automaticamente. Respeta el principio data-driven del modulo.
    //
    // Orden logico MINSA aplicado a las dosis presentes:
    $ordenDosis = ['D1' => 1, 'D2' => 2, 'D3' => 3, 'D4' => 4, 'DU' => 5,
                   'REF1' => 6, 'REF2' => 7, 'REF3' => 8, 'TOT' => 9];
    $presentes = [];
    foreach ($sec['lineas'] as $lin) {
        if (!empty($lin['dosis_codigo']) && !in_array($lin['dosis_codigo'], $presentes, true)) {
            $presentes[] = $lin['dosis_codigo'];
        }
    }
    // Ordenar las dosis presentes segun el orden logico definido arriba.
    // Las dosis no contempladas en $ordenDosis se colocan al final, en orden alfabetico.
    usort($presentes, function ($a, $b) use ($ordenDosis) {
        $ia = $ordenDosis[$a] ?? 999;
        $ib = $ordenDosis[$b] ?? 999;
        if ($ia === $ib) return strcmp($a, $b);
        return $ia <=> $ib;
    });

    // Fallback: si por algun motivo no se detectaron dosis (lineas sin id_dosis),
    // se usa el default oficial MINSA: D1, D2, D3.
    $dosisCols = !empty($presentes) ? $presentes : ['D1', 'D2', 'D3'];
    // ================================================================================

    ob_start();
    ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover esni-table mb-0">
            <thead>
                <tr>
                    <th class="text-start">Grupo Edad</th>
                    <th class="text-start">Vacuna</th>
                    <?php foreach ($dosisCols as $dc): ?><th class="text-end"><?= $dc ?></th><?php endforeach; ?>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grupos as $g):
                    $tot = array_sum($g['dosis']);
                ?>
                <tr class="<?= $tot > 0 ? '' : 'text-muted' ?>">
                    <td class="text-start"><?= htmlspecialchars($g['edad']) ?></td>
                    <td class="text-start"><span class="esni-cod-pill"><?= htmlspecialchars($g['vacuna']) ?></span></td>
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
        if ($lin['sexo'] === 'F') {
            $k = 'F_' . $lin['dosis_codigo'];
        } elseif ($lin['sexo'] === 'M') {
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

<?php include 'includes/footer.php';
