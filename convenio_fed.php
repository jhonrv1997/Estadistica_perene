<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 02: Convenio FED - Fondo de Estimulo al Desempeno
 * Muestra el reporte de avance de los 7 indicadores FED.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();

$fAnio = $_GET['anio'] ?? date('Y');
$fTrim = $_GET['trimestre'] ?? '';

$aniosDisp = $pdo->query("SELECT DISTINCT anio FROM CONVENIO_FED_AVANCE WHERE anio IS NOT NULL ORDER BY anio DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($aniosDisp)) {
    $aniosDisp = [date('Y')];
}
if (!in_array((string)$fAnio, $aniosDisp, true)) {
    $fAnio = $aniosDisp[0];
}

$tablaExiste = $pdo->query("SHOW TABLES LIKE 'CONVENIO_FED_INDICADORES'")->fetchColumn();

$indicadores = [];
$resumen = ['total' => 0, 'con_avance' => 0, 'excelente' => 0, 'bueno' => 0, 'regular' => 0, 'bajo' => 0, 'critico' => 0, 'sin_dato' => 0];
$avancePonderado = 0.0;
$pesoTotal = 0.0;

if ($tablaExiste) {
    $sql = "SELECT i.id_indicador, i.codigo, i.nombre, i.descripcion, i.unidad_medida,
                   i.meta_anual, i.peso_ponderado, i.periodo, i.responsable, i.orden,
                   a.id_avance, a.valor_numerador, a.valor_denominador, a.valor_resultado,
                   a.porcentaje_avance, a.observaciones, a.fecha_registro
            FROM CONVENIO_FED_INDICADORES i
            LEFT JOIN CONVENIO_FED_AVANCE a ON a.id_indicador = i.id_indicador
                AND a.anio = ? " . ($fTrim !== '' ? " AND a.trimestre = ? " : " AND (a.trimestre IS NULL OR a.trimestre = '')") . "
            WHERE i.estado = 1
            ORDER BY i.orden ASC, i.codigo ASC";
    $stmt = $pdo->prepare($sql);
    if ($fTrim !== '') {
        $stmt->execute([$fAnio, $fTrim]);
    } else {
        $stmt->execute([$fAnio]);
    }
    $indicadores = $stmt->fetchAll();

    $resumen['total'] = count($indicadores);
    foreach ($indicadores as $ind) {
        $pct = $ind['porcentaje_avance'] !== null ? (float)$ind['porcentaje_avance'] : null;
        $peso = $ind['peso_ponderado'] !== null ? (float)$ind['peso_ponderado'] : 0;
        if ($pct === null) {
            $resumen['sin_dato']++;
        } else {
            $resumen['con_avance']++;
            $avancePonderado += ($pct * $peso / 100);
            $pesoTotal += $peso;
            if ($pct >= 90) $resumen['excelente']++;
            elseif ($pct >= 75) $resumen['bueno']++;
            elseif ($pct >= 60) $resumen['regular']++;
            elseif ($pct >= 40) $resumen['bajo']++;
            else $resumen['critico']++;
        }
    }
    // Avance ponderado global del FED
    if ($pesoTotal > 0) {
        $avancePonderadoGlobal = ($avancePonderado / $pesoTotal) * 100;
    } else {
        $avancePonderadoGlobal = 0;
    }
}

function clasificarAvanceFED($pct) {
    if ($pct === null) return ['clase' => 'avance-sin-dato', 'texto' => 'Sin dato', 'estado' => ''];
    if ($pct >= 90) return ['clase' => 'avance-excelente', 'texto' => 'Excelente', 'estado' => 'estado-excelente'];
    if ($pct >= 75) return ['clase' => 'avance-bueno', 'texto' => 'Bueno', 'estado' => 'estado-bueno'];
    if ($pct >= 60) return ['clase' => 'avance-regular', 'texto' => 'Regular', 'estado' => 'estado-regular'];
    if ($pct >= 40) return ['clase' => 'avance-bajo', 'texto' => 'Bajo', 'estado' => 'estado-bajo'];
    return ['clase' => 'avance-critico', 'texto' => 'Critico', 'estado' => 'estado-critico'];
}

$pageTitle = 'Convenio FED - Sistema HIS';
include 'includes/header.php';
?>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-medal me-2"></i>Convenio FED - Fondo de Estimulo al Desempeno</h4>
            <p class="subtitle">Reporte de avance de los 7 indicadores del Fondo de Estimulo al Desempeno.</p>
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
                <label class="form-label small fw-semibold mb-0">Trimestre</label>
                <select name="trimestre" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Anual --</option>
                    <option value="T1" <?= $fTrim === 'T1' ? 'selected' : '' ?>>T1 (Ene-Mar)</option>
                    <option value="T2" <?= $fTrim === 'T2' ? 'selected' : '' ?>>T2 (Abr-Jun)</option>
                    <option value="T3" <?= $fTrim === 'T3' ? 'selected' : '' ?>>T3 (Jul-Sep)</option>
                    <option value="T4" <?= $fTrim === 'T4' ? 'selected' : '' ?>>T4 (Oct-Dic)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-his btn-sm"><i class="fas fa-search me-1"></i> Consultar</button>
        </form>
    </div>
</div>

<?php if (!$tablaExiste): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Las tablas del Convenio FED no existen en la base de datos.
        Ejecute el script <code>Database/update_his_v2.sql</code> para crearlas y cargar los 7 indicadores.
    </div>
<?php else: ?>

<!-- Resumen rapido + avance global ponderado -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-list-ol"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($resumen['total']) ?></span>
                <span class="stat-label">Total Indicadores FED</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($avancePonderadoGlobal ?? 0, 1) ?>%</span>
                <span class="stat-label">Avance Global Ponderado</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($resumen['excelente'] + $resumen['bueno']) ?></span>
                <span class="stat-label">En buen nivel (>=75%)</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($resumen['regular'] + $resumen['bajo'] + $resumen['critico']) ?></span>
                <span class="stat-label">Requieren atencion</span>
            </div>
        </div>
    </div>
</div>

<!-- Barra de avance global -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Avance Global del Convenio FED</h6>
            <span class="avance-badge <?= clasificarAvanceFED($avancePonderadoGlobal ?? 0)['clase'] ?>">
                <?= number_format($avancePonderadoGlobal ?? 0, 1) ?>% &middot; <?= clasificarAvanceFED($avancePonderadoGlobal ?? 0)['texto'] ?>
            </span>
        </div>
        <div class="progress" style="height: 22px; border-radius: 11px;">
            <div class="progress-bar <?= ($avancePonderadoGlobal ?? 0) >= 90 ? 'bg-success' : (($avancePonderadoGlobal ?? 0) >= 75 ? 'bg-info' : (($avancePonderadoGlobal ?? 0) >= 60 ? 'bg-warning' : (($avancePonderadoGlobal ?? 0) >= 40 ? 'bg-orange' : 'bg-danger'))) ?>"
                 role="progressbar" style="width: <?= min(100, $avancePonderadoGlobal ?? 0) ?>%;"
                 aria-valuenow="<?= $avancePonderadoGlobal ?? 0 ?>" aria-valuemin="0" aria-valuemax="100">
                <?= number_format($avancePonderadoGlobal ?? 0, 1) ?>%
            </div>
        </div>
        <small class="text-muted">Avance ponderado segun los pesos de cada indicador.</small>
    </div>
</div>

<!-- Tabla de indicadores FED -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Reporte de Avance por Indicador FED</h6>
        <span class="stat-mini">Peso total: <?= number_format($pesoTotal, 1) ?>%</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px;">Codigo</th>
                        <th>Indicador</th>
                        <th style="width:100px;">Unidad</th>
                        <th style="width:80px;" class="text-end">Meta</th>
                        <th style="width:70px;" class="text-end">Peso</th>
                        <th style="width:110px;" class="text-end">Resultado</th>
                        <th style="width:180px;">Avance</th>
                        <th style="width:110px;">Estado</th>
                        <th style="width:130px;">Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($indicadores)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No se encontraron indicadores FED.</td></tr>
                    <?php else: ?>
                        <?php foreach ($indicadores as $ind):
                            $pct = $ind['porcentaje_avance'] !== null ? (float)$ind['porcentaje_avance'] : null;
                            $av = clasificarAvanceFED($pct);
                            $barColor = $pct === null ? 'bg-secondary' :
                                ($pct >= 90 ? 'bg-success' : ($pct >= 75 ? 'bg-info' :
                                ($pct >= 60 ? 'bg-warning' : ($pct >= 40 ? 'bg-orange' : 'bg-danger'))));
                        ?>
                        <tr>
                            <td><code><?= clean($ind['codigo']) ?></code></td>
                            <td>
                                <strong><?= clean($ind['nombre']) ?></strong>
                                <?php if (!empty($ind['descripcion'])): ?>
                                    <small class="d-block text-muted" style="font-size:0.74rem;"><?= clean(mb_strimwidth($ind['descripcion'], 0, 80, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= clean($ind['unidad_medida'] ?? '-') ?></small></td>
                            <td class="text-end"><?= $ind['meta_anual'] !== null ? number_format((float)$ind['meta_anual'], 2) : '-' ?></td>
                            <td class="text-end"><?= $ind['peso_ponderado'] !== null ? number_format((float)$ind['peso_ponderado'], 1) . '%' : '-' ?></td>
                            <td class="text-end">
                                <?php if ($ind['valor_resultado'] !== null): ?>
                                    <strong><?= number_format((float)$ind['valor_resultado'], 2) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pct !== null): ?>
                                    <div class="progress progress-his" style="height:8px;">
                                        <div class="progress-bar <?= $barColor ?>" role="progressbar" style="width: <?= min(100, $pct) ?>%;"></div>
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

<!-- Vista de tarjetas -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-th-large me-2 text-primary"></i>Vista de Tarjetas FED</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($indicadores as $ind):
                $pct = $ind['porcentaje_avance'] !== null ? (float)$ind['porcentaje_avance'] : null;
                $av = clasificarAvanceFED($pct);
            ?>
            <div class="col-lg-4 col-md-6">
                <div class="indicador-card <?= $av['estado'] ?>">
                    <span class="indicador-codigo"><?= clean($ind['codigo']) ?> &middot; Peso <?= number_format((float)$ind['peso_ponderado'], 1) ?>%</span>
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
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
