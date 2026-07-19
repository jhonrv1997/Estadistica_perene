<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 04: Control de Calidad
 * Reporte con todas las observaciones encontradas en los datos consolidados.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();

$tablaExiste = $pdo->query("SHOW TABLES LIKE 'CONTROL_CALIDAD_OBSERVACIONES'")->fetchColumn();

// Filtros
$fAnio = trim($_GET['anio'] ?? '');
$fMes = trim($_GET['mes'] ?? '');
$fTipo = trim($_GET['tipo'] ?? '');
$fSeveridad = trim($_GET['severidad'] ?? '');
$fEstado = trim($_GET['estado'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');

$where = "1=1";
$params = [];
if ($fAnio !== '') { $where .= " AND anio = :anio"; $params[':anio'] = $fAnio; }
if ($fMes !== '') { $where .= " AND mes = :mes"; $params[':mes'] = $fMes; }
if ($fTipo !== '') { $where .= " AND tipo_observacion = :tipo"; $params[':tipo'] = $fTipo; }
if ($fSeveridad !== '') { $where .= " AND severidad = :sev"; $params[':sev'] = $fSeveridad; }
if ($fEstado !== '') { $where .= " AND estado = :est"; $params[':est'] = $fEstado; }
if ($fEstablecimiento !== '') { $where .= " AND nombre_establecimiento LIKE :estab"; $params[':estab'] = '%' . $fEstablecimiento . '%'; }

$tiposObs = [];
$estadosObs = ['PENDIENTE', 'EN_PROCESO', 'ATENDIDA', 'CERRADA'];
$severidades = ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'];
$aniosObs = [];

$observaciones = [];
$stats = ['total' => 0, 'pendientes' => 0, 'en_proceso' => 0, 'atendidas' => 0, 'cerradas' => 0, 'criticas' => 0, 'altas' => 0];
$totalPaginas = 0;
$pagina = 1;
$offset = 0;
$porPagina = 50;

if ($tablaExiste) {
    $tiposObs = $pdo->query("SELECT DISTINCT tipo_observacion FROM CONTROL_CALIDAD_OBSERVACIONES WHERE tipo_observacion IS NOT NULL ORDER BY tipo_observacion")->fetchAll(PDO::FETCH_COLUMN);
    $aniosObs = $pdo->query("SELECT DISTINCT anio FROM CONTROL_CALIDAD_OBSERVACIONES WHERE anio IS NOT NULL ORDER BY anio DESC")->fetchAll(PDO::FETCH_COLUMN);

    // Stats globales (sin filtros)
    $statsRow = $pdo->query("SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado='PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado='EN_PROCESO' THEN 1 ELSE 0 END) as en_proceso,
        SUM(CASE WHEN estado='ATENDIDA' THEN 1 ELSE 0 END) as atendidas,
        SUM(CASE WHEN estado='CERRADA' THEN 1 ELSE 0 END) as cerradas,
        SUM(CASE WHEN severidad='CRITICA' THEN 1 ELSE 0 END) as criticas,
        SUM(CASE WHEN severidad='ALTA' THEN 1 ELSE 0 END) as altas
        FROM CONTROL_CALIDAD_OBSERVACIONES")->fetch();
    if ($statsRow) {
        $stats = $statsRow;
    }

    // Conteo filtrado
    $countSql = "SELECT COUNT(*) FROM CONTROL_CALIDAD_OBSERVACIONES WHERE " . $where;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRegistros = (int)$countStmt->fetchColumn();

    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $offset = ($pagina - 1) * $porPagina;
    $totalPaginas = ceil($totalRegistros / $porPagina);

    $dataSql = "SELECT * FROM CONTROL_CALIDAD_OBSERVACIONES WHERE {$where} ORDER BY fecha_creacion DESC, id_observacion DESC LIMIT {$porPagina} OFFSET {$offset}";
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $observaciones = $dataStmt->fetchAll();
}

$pageTitle = 'Control de Calidad - Sistema HIS';
include 'includes/header.php';
?>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-clipboard-check me-2"></i>Control de Calidad</h4>
            <p class="subtitle">Reporte de todas las observaciones encontradas en los datos consolidados HIS-MINSA.</p>
        </div>
        <?php if (esAdmin()): ?>
        <a href="control_calidad.php?accion=nueva" class="btn btn-his btn-sm" data-bs-toggle="modal" data-bs-target="#modalObservacion">
            <i class="fas fa-plus me-1"></i> Nueva Observacion
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$tablaExiste): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        La tabla <code>CONTROL_CALIDAD_OBSERVACIONES</code> no existe en la base de datos.
        Ejecute el script <code>Database/update_his_v2.sql</code> para crearla.
    </div>
<?php else: ?>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-2 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-list"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['total'] ?? 0) ?></span>
                <span class="stat-label">Total</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['pendientes'] ?? 0) ?></span>
                <span class="stat-label">Pendientes</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-cogs"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['en_proceso'] ?? 0) ?></span>
                <span class="stat-label">En Proceso</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-check"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['atendidas'] ?? 0) ?></span>
                <span class="stat-label">Atendidas</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-flag-checkered"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['cerradas'] ?? 0) ?></span>
                <span class="stat-label">Cerradas</span>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6 mb-3">
        <div class="stat-card stat-danger">
            <div class="stat-icon"><i class="fas fa-exclamation"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format(($stats['criticas'] ?? 0) + ($stats['altas'] ?? 0)) ?></span>
                <span class="stat-label">Criticas+Altas</span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros</h6>
        <a href="control_calidad.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i> Limpiar</a>
    </div>
    <div class="card-body">
        <form method="GET" action="control_calidad.php" class="row g-2 align-items-end">
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold">Anio</label>
                <select name="anio" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($aniosObs as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>" <?= $fAnio === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold">Mes</label>
                <select name="mes" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $fMes === str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($tiposObs as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $fTipo === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold">Severidad</label>
                <select name="severidad" class="form-select form-select-sm">
                    <option value="">-- Todas --</option>
                    <?php foreach ($severidades as $s): ?>
                        <option value="<?= $s ?>" <?= $fSeveridad === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <?php foreach ($estadosObs as $e): ?>
                        <option value="<?= $e ?>" <?= $fEstado === $e ? 'selected' : '' ?>><?= ucfirst(strtolower(str_replace('_', ' ', $e))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-his btn-sm w-100"><i class="fas fa-search me-1"></i> Buscar</button>
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label small fw-semibold">Establecimiento</label>
                <input type="text" name="establecimiento" class="form-control form-control-sm" placeholder="Nombre del establecimiento" value="<?= htmlspecialchars($fEstablecimiento) ?>">
            </div>
        </form>
    </div>
</div>

<!-- Tabla de observaciones -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Observaciones (<?= number_format($totalRegistros ?? 0) ?>)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($observaciones)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>Sin observaciones</h5>
                <p>No se encontraron observaciones con los filtros seleccionados.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Periodo</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Severidad</th>
                            <th>Campo</th>
                            <th>Descripcion</th>
                            <th>Establecimiento</th>
                            <th>Estado</th>
                            <th>Usuario</th>
                            <th width="1%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($observaciones as $i => $o): ?>
                        <tr>
                            <td class="text-muted"><?= $o['id_observacion'] ?></td>
                            <td><?= clean($o['mes'] ?? '') ?>/<?= clean($o['anio'] ?? '') ?></td>
                            <td><?= formatDate($o['fecha_observacion'] ?? '') ?></td>
                            <td><span class="badge bg-secondary"><?= clean($o['tipo_observacion']) ?></span></td>
                            <td>
                                <?php $sev = strtolower($o['severidad'] ?? 'media'); ?>
                                <span class="badge badge-sev-<?= $sev ?>">
                                    <i class="fas fa-exclamation-triangle me-1"></i><?= clean($o['severidad']) ?>
                                </span>
                            </td>
                            <td><code><?= clean($o['campo_afectado'] ?? '-') ?></code></td>
                            <td title="<?= clean($o['descripcion']) ?>">
                                <?= clean(mb_strimwidth($o['descripcion'], 0, 50, '...')) ?>
                            </td>
                            <td title="<?= clean($o['nombre_establecimiento'] ?? '') ?>">
                                <small><?= clean(mb_strimwidth($o['nombre_establecimiento'] ?? '-', 0, 25, '...')) ?></small>
                            </td>
                            <td>
                                <?php $est = strtolower(str_replace(' ', '_', $o['estado'] ?? '')); ?>
                                <span class="badge badge-estado-<?= $est ?>"><?= clean($o['estado']) ?></span>
                            </td>
                            <td><small><?= clean($o['usuario_registro'] ?? '-') ?></small></td>
                            <td>
                                <button type="button" class="btn btn-xs btn-outline-primary"
                                        onclick="verObservacion(<?= (int)$o['id_observacion'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($totalPaginas > 1): ?>
    <div class="card-footer bg-white">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php
                $queryParams = $_GET;
                unset($queryParams['pagina']);
                $queryString = http_build_query($queryParams);
                if ($pagina > 1): ?>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina - 1 ?>&<?= $queryString ?>"><i class="fas fa-angle-left"></i></a></li>
                <?php endif;
                $start = max(1, $pagina - 3);
                $end = min($totalPaginas, $pagina + 3);
                for ($p = $start; $p <= $end; $p++): ?>
                    <li class="page-item <?= $p === $pagina ? 'active' : '' ?>"><a class="page-link" href="?pagina=<?= $p ?>&<?= $queryString ?>"><?= $p ?></a></li>
                <?php endfor;
                if ($pagina < $totalPaginas): ?>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina + 1 ?>&<?= $queryString ?>"><i class="fas fa-angle-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- Modal ver observacion (simplificado - carga datos via JS inline) -->
<script>
const OBS_DATA = <?= json_encode(array_values(array_map(function($o) {
    return [
        'id' => (int)$o['id_observacion'],
        'periodo' => ($o['mes'] ?? '') . '/' . ($o['anio'] ?? ''),
        'fecha' => '<?= "" ?>' + '<?= "" ?>',
        'tipo' => $o['tipo_observacion'] ?? '',
        'severidad' => $o['severidad'] ?? '',
        'campo' => $o['campo_afectado'] ?? '',
        'descripcion' => $o['descripcion'] ?? '',
        'establecimiento' => $o['nombre_establecimiento'] ?? '',
        'estado' => $o['estado'] ?? '',
        'solucion' => $o['solucion'] ?? '',
        'usuario_registro' => $o['usuario_registro'] ?? '',
        'usuario_cierre' => $o['usuario_cierre'] ?? '',
        'fecha_creacion' => $o['fecha_creacion'] ?? '',
        'fecha_cierre' => $o['fecha_cierre'] ?? '',
        'id_cita' => $o['id_cita'] ?? '',
        'codigo_item' => $o['codigo_item'] ?? '',
        'numero_documento_paciente' => $o['numero_documento_paciente'] ?? '',
        'numero_documento_personal' => $o['numero_documento_personal'] ?? '',
    ];
}, $observaciones))) ?>;

function verObservacion(id) {
    const o = OBS_DATA.find(x => x.id === id);
    if (!o) return;
    let html = '<dl class="row mb-0 small">';
    html += '<dt class="col-sm-4">ID Observacion</dt><dd class="col-sm-8">#' + o.id + '</dd>';
    html += '<dt class="col-sm-4">Periodo</dt><dd class="col-sm-8">' + (o.periodo || '-') + '</dd>';
    html += '<dt class="col-sm-4">Tipo</dt><dd class="col-sm-8"><span class="badge bg-secondary">' + o.tipo + '</span></dd>';
    html += '<dt class="col-sm-4">Severidad</dt><dd class="col-sm-8"><span class="badge badge-sev-' + o.severidad.toLowerCase() + '">' + o.severidad + '</span></dd>';
    html += '<dt class="col-sm-4">Campo Afectado</dt><dd class="col-sm-8"><code>' + (o.campo || '-') + '</code></dd>';
    html += '<dt class="col-sm-4">Estado</dt><dd class="col-sm-8"><span class="badge badge-estado-' + o.estado.toLowerCase().replace(/ /g,'_') + '">' + o.estado + '</span></dd>';
    html += '<dt class="col-sm-4">Establecimiento</dt><dd class="col-sm-8">' + (o.establecimiento || '-') + '</dd>';
    html += '<dt class="col-sm-4">Id Cita</dt><dd class="col-sm-8">' + (o.id_cita || '-') + '</dd>';
    html += '<dt class="col-sm-4">Codigo Item</dt><dd class="col-sm-8">' + (o.codigo_item || '-') + '</dd>';
    html += '<dt class="col-sm-4">Doc. Paciente</dt><dd class="col-sm-8">' + (o.numero_documento_paciente || '-') + '</dd>';
    html += '<dt class="col-sm-4">Doc. Personal</dt><dd class="col-sm-8">' + (o.numero_documento_personal || '-') + '</dd>';
    html += '<dt class="col-sm-4">Usuario Registro</dt><dd class="col-sm-8">' + (o.usuario_registro || '-') + '</dd>';
    html += '<dt class="col-sm-4">Fecha Registro</dt><dd class="col-sm-8">' + (o.fecha_creacion || '-') + '</dd>';
    html += '<dt class="col-sm-4">Usuario Cierre</dt><dd class="col-sm-8">' + (o.usuario_cierre || '-') + '</dd>';
    html += '<dt class="col-sm-4">Fecha Cierre</dt><dd class="col-sm-8">' + (o.fecha_cierre || '-') + '</dd>';
    html += '<dt class="col-sm-12">Descripcion</dt><dd class="col-sm-12">' + (o.descripcion || '-') + '</dd>';
    if (o.solucion) {
        html += '<dt class="col-sm-12">Solucion</dt><dd class="col-sm-12">' + o.solucion + '</dd>';
    }
    html += '</dl>';
    document.getElementById('modalObservacionBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('modalVerObservacion')).show();
}
</script>

<!-- Modal ver observacion -->
<div class="modal fade" id="modalVerObservacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Detalle de Observacion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalObservacionBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php if (esAdmin() && $tablaExiste): ?>
<!-- Modal nueva observacion (placeholder - el admin puede ampliar con CRUD completo) -->
<div class="modal fade" id="modalObservacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="control_calidad_guardar.php">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nueva Observacion de Control de Calidad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        El registro manual de observaciones es opcional. La mayoria de observaciones se generan
                        automaticamente al ejecutar el procesamiento del consolidado.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Anio *</label>
                            <input type="text" name="anio" class="form-control form-control-sm" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Mes *</label>
                            <select name="mes" class="form-select form-select-sm" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= (int)date('m') === $m ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Fecha Observacion</label>
                            <input type="date" name="fecha_observacion" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Severidad</label>
                            <select name="severidad" class="form-select form-select-sm">
                                <option value="BAJA">BAJA</option>
                                <option value="MEDIA" selected>MEDIA</option>
                                <option value="ALTA">ALTA</option>
                                <option value="CRITICA">CRITICA</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo</label>
                            <input type="text" name="tipo_observacion" class="form-control form-control-sm" placeholder="Ej: Dato Faltante" list="tiposList">
                            <datalist id="tiposList">
                                <option value="Dato Faltante">
                                <option value="Codigo Invalido">
                                <option value="Duplicidad">
                                <option value="Inconsistencia">
                                <option value="Formato">
                                <option value="Fuera de Rango">
                            </datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Campo Afectado</label>
                            <input type="text" name="campo_afectado" class="form-control form-control-sm" placeholder="Ej: Fecha_Nacimiento_Paciente">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Establecimiento</label>
                            <input type="text" name="nombre_establecimiento" class="form-control form-control-sm" placeholder="Nombre">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Id Cita</label>
                            <input type="text" name="id_cita" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Codigo Item</label>
                            <input type="text" name="codigo_item" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Doc. Paciente</label>
                            <input type="text" name="numero_documento_paciente" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Descripcion *</label>
                            <textarea name="descripcion" class="form-control form-control-sm" rows="3" required placeholder="Descripcion detallada de la observacion"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-his"><i class="fas fa-save me-1"></i> Guardar Observacion</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
