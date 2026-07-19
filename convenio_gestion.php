<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 01: Convenio de Gestion MINSA-GORE 2026
 * Muestra el reporte de avance de los 34 indicadores del Convenio.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();

// Filtros: anio y mes para seleccionar el reporte de avance
$fAnio = $_GET['anio'] ?? date('Y');
$fMes = $_GET['mes'] ?? '';

// Obtener anios disponibles
$aniosDisp = $pdo->query("SELECT DISTINCT anio FROM CONVENIO_GESTION_AVANCE WHERE anio IS NOT NULL ORDER BY anio DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($aniosDisp)) {
    $aniosDisp = [date('Y')];
}
if (!in_array((string)$fAnio, $aniosDisp, true)) {
    $fAnio = $aniosDisp[0];
}

// Verificar si la tabla existe (por si no se ha corrido el script SQL)
$tablaExiste = $pdo->query("SHOW TABLES LIKE 'CONVENIO_GESTION_INDICADORES'")->fetchColumn();

$indicadores = [];
$resumen = ['total' => 0, 'con_avance' => 0, 'excelente' => 0, 'bueno' => 0, 'regular' => 0, 'bajo' => 0, 'critico' => 0, 'sin_dato' => 0];

if ($tablaExiste) {
    // Obtener todos los indicadores con su avance del periodo seleccionado
    $sql = "SELECT i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                   i.meta_anual, i.periodo, i.responsable, i.orden,
                   a.id_avance, a.valor_numerador, a.valor_denominador, a.valor_resultado,
                   a.porcentaje_avance, a.observaciones, a.fecha_registro
            FROM CONVENIO_GESTION_INDICADORES i
            LEFT JOIN CONVENIO_GESTION_AVANCE a ON a.id_indicador = i.id_indicador
                AND a.anio = ? " . ($fMes !== '' ? " AND a.mes = ? " : " AND (a.mes IS NULL OR a.mes = '')") . "
            WHERE i.estado = 1
            ORDER BY i.orden ASC, i.codigo ASC";
    $stmt = $pdo->prepare($sql);
    if ($fMes !== '') {
        $stmt->execute([$fAnio, $fMes]);
    } else {
        $stmt->execute([$fAnio]);
    }
    $indicadores = $stmt->fetchAll();

    // Calcular resumen
    $resumen['total'] = count($indicadores);
    foreach ($indicadores as $ind) {
        $pct = $ind['porcentaje_avance'] !== null ? (float)$ind['porcentaje_avance'] : null;
        if ($pct === null) {
            $resumen['sin_dato']++;
        } else {
            $resumen['con_avance']++;
            if ($pct >= 90) $resumen['excelente']++;
            elseif ($pct >= 75) $resumen['bueno']++;
            elseif ($pct >= 60) $resumen['regular']++;
            elseif ($pct >= 40) $resumen['bajo']++;
            else $resumen['critico']++;
        }
    }
}

// Funciones helper locales
function clasificarAvance($pct) {
    if ($pct === null) return ['clase' => 'avance-sin-dato', 'texto' => 'Sin dato', 'estado' => ''];
    if ($pct >= 90) return ['clase' => 'avance-excelente', 'texto' => 'Excelente', 'estado' => 'estado-excelente'];
    if ($pct >= 75) return ['clase' => 'avance-bueno', 'texto' => 'Bueno', 'estado' => 'estado-bueno'];
    if ($pct >= 60) return ['clase' => 'avance-regular', 'texto' => 'Regular', 'estado' => 'estado-regular'];
    if ($pct >= 40) return ['clase' => 'avance-bajo', 'texto' => 'Bajo', 'estado' => 'estado-bajo'];
    return ['clase' => 'avance-critico', 'texto' => 'Critico', 'estado' => 'estado-critico'];
}

$pageTitle = 'Convenio de Gestion MINSA-GORE 2026 - Sistema HIS';
include 'includes/header.php';
?>

<!-- Encabezado de pagina -->
<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-file-contract me-2"></i>Convenio de Gestion MINSA-GORE 2026</h4>
            <p class="subtitle">Reporte de avance de los 34 indicadores del Convenio de Gestion.</p>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-end">
            <div>
                <label class="form-label small fw-semibold mb-0">Anio</label>
                <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($aniosDisp as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>" <?= (string)$fAnio === (string)$a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                    <?php endforeach; ?>
                    <?php if (!in_array(date('Y'), $aniosDisp, true)): ?>
                        <option value="<?= date('Y') ?>" <?= (string)$fAnio === date('Y') ? 'selected' : '' ?>><?= date('Y') ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-0">Mes</label>
                <select name="mes" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Anual --</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $fMes === (string)$m ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-his btn-sm"><i class="fas fa-search me-1"></i> Consultar</button>
        </form>
    </div>
</div>

<?php if (!$tablaExiste): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Las tablas del Convenio de Gestion no existen en la base de datos.
        Ejecute el script <code>Database/update_his_v2.sql</code> para crearlas y cargar los 34 indicadores.
    </div>
<?php else: ?>

<!-- Resumen rapido -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-list-ol"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($resumen['total']) ?></span>
                <span class="stat-label">Total Indicadores</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($resumen['excelente'] + $resumen['bueno']) ?></span>
                <span class="stat-label">En buen nivel (>=75%)</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($resumen['regular'] + $resumen['bajo']) ?></span>
                <span class="stat-label">Nivel regular/bajo</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($resumen['critico']) ?></span>
                <span class="stat-label">Critico (&lt;40%)</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabla resumen de indicadores -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Reporte de Avance por Indicador</h6>
        <div class="d-flex gap-2">
            <span class="stat-mini"><i class="fas fa-circle sev-excelente"></i> Excelente: <?= $resumen['excelente'] ?></span>
            <span class="stat-mini"><i class="fas fa-circle sev-bueno"></i> Bueno: <?= $resumen['bueno'] ?></span>
            <span class="stat-mini"><i class="fas fa-circle sev-regular"></i> Regular: <?= $resumen['regular'] ?></span>
            <span class="stat-mini"><i class="fas fa-circle sev-bajo"></i> Bajo: <?= $resumen['bajo'] ?></span>
            <span class="stat-mini"><i class="fas fa-circle sev-critica"></i> Critico: <?= $resumen['critico'] ?></span>
            <span class="stat-mini"><i class="fas fa-circle text-muted"></i> Sin dato: <?= $resumen['sin_dato'] ?></span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px;">Codigo</th>
                        <th>Indicador</th>
                        <th style="width:120px;">Unidad</th>
                        <th style="width:100px;" class="text-end">Meta</th>
                        <th style="width:120px;" class="text-end">Resultado</th>
                        <th style="width:180px;">Avance</th>
                        <th style="width:110px;">Estado</th>
                        <th style="width:130px;">Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($indicadores)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron indicadores.</td></tr>
                    <?php else: ?>
                        <?php foreach ($indicadores as $ind):
                            $pct = $ind['porcentaje_avance'] !== null ? (float)$ind['porcentaje_avance'] : null;
                            $av = clasificarAvance($pct);
                            $barColor = $pct === null ? 'bg-secondary' :
                                ($pct >= 90 ? 'bg-success' : ($pct >= 75 ? 'bg-info' :
                                ($pct >= 60 ? 'bg-warning' : ($pct >= 40 ? 'bg-orange' : 'bg-danger'))));
                        ?>
                        <tr>
                            <td><code><?= clean($ind['codigo']) ?></code></td>
                            <td title="<?= clean($ind['descripcion'] ?? '') ?>">
                                <strong><?= clean($ind['nombre']) ?></strong>
                                <?php if (!empty($ind['descripcion'])): ?>
                                    <small class="d-block text-muted" style="font-size:0.74rem;"><?= clean(mb_strimwidth($ind['descripcion'], 0, 80, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= clean($ind['unidad_medida'] ?? '-') ?></small></td>
                            <td class="text-end"><?= $ind['meta_anual'] !== null ? number_format((float)$ind['meta_anual'], 2) : '-' ?></td>
                            <td class="text-end">
                                <?php if ($ind['valor_resultado'] !== null): ?>
                                    <strong><?= number_format((float)$ind['valor_resultado'], 2) ?></strong>
                                    <?php if ($ind['valor_numerador'] !== null && $ind['valor_denominador'] !== null): ?>
                                        <small class="d-block text-muted" style="font-size:0.7rem;">
                                            (<?= number_format((float)$ind['valor_numerador'], 0) ?> / <?= number_format((float)$ind['valor_denominador'], 0) ?>)
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pct !== null): ?>
                                    <div class="progress progress-his" style="height:8px;">
                                        <div class="progress-bar <?= $barColor ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted"><?= number_format($pct, 1) ?>%</small>
                                <?php else: ?>
                                    <span class="text-muted small">Sin registro</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="avance-badge <?= $av['clase'] ?>"><?= $av['texto'] ?></span></td>
                            <td><small><?= clean($ind['responsable'] ?? '-') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Vista tipo cards (vista alternativa) -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-th-large me-2 text-primary"></i>Vista de Tarjetas</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($indicadores as $ind):
                $pct = $ind['porcentaje_avance'] !== null ? (float)$ind['porcentaje_avance'] : null;
                $av = clasificarAvance($pct);
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="indicador-card <?= $av['estado'] ?>">
                    <span class="indicador-codigo"><?= clean($ind['codigo']) ?> &middot; <?= clean($ind['periodo'] ?? '') ?></span>
                    <div class="indicador-nombre"><?= clean($ind['nombre']) ?></div>
                    <div class="indicador-meta">
                        <i class="fas fa-bullseye me-1"></i> Meta: <strong><?= $ind['meta_anual'] !== null ? number_format((float)$ind['meta_anual'], 2) : '-' ?></strong>
                        &middot; <i class="fas fa-ruler me-1"></i> <?= clean($ind['unidad_medida'] ?? '-') ?>
                    </div>
                    <?php if ($pct !== null): ?>
                        <div class="progress progress-his mb-2">
                            <div class="progress-bar <?= $pct >= 90 ? 'bg-success' : ($pct >= 75 ? 'bg-info' : ($pct >= 60 ? 'bg-warning' : ($pct >= 40 ? 'bg-orange' : 'bg-danger'))) ?>"
                                 role="progressbar" style="width: <?= min(100, $pct) ?>%;"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Resultado: <strong><?= number_format((float)$ind['valor_resultado'], 2) ?></strong></small>
                            <span class="avance-badge <?= $av['clase'] ?>"><?= number_format($pct, 1) ?>% &middot; <?= $av['texto'] ?></span>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-2">
                            <span class="avance-badge avance-sin-dato"><i class="fas fa-minus-circle me-1"></i>Sin registro de avance</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($ind['observaciones'])): ?>
                        <div class="mt-2 small text-muted" style="font-size:0.76rem;">
                            <i class="fas fa-comment-dots me-1"></i> <?= clean(mb_strimwidth($ind['observaciones'], 0, 100, '...')) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
