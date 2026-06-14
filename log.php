<?php
/**
 * Sistema de Gestion de Datos HIS
 * Log de Auditoria de Importaciones
 */

require_once 'includes/auth.php';
verificarAutenticacion();

require_once 'includes/functions.php';

$pdo = getDBConnection();

// Filtros
$fTipo = $_GET['tipo'] ?? '';
$fEstado = $_GET['estado'] ?? '';
$fFechaDesde = $_GET['fecha_desde'] ?? '';
$fFechaHasta = $_GET['fecha_hasta'] ?? '';

$where = "1=1";
$params = [];

if ($fTipo !== '') {
    $where .= " AND tipo_operacion = :tipo";
    $params[':tipo'] = $fTipo;
}
if ($fEstado !== '') {
    $where .= " AND estado = :estado";
    $params[':estado'] = $fEstado;
}
if ($fFechaDesde !== '') {
    $where .= " AND fecha_operacion >= :fecha_desde";
    $params[':fecha_desde'] = $fFechaDesde . ' 00:00:00';
}
if ($fFechaHasta !== '') {
    $where .= " AND fecha_operacion <= :fecha_hasta";
    $params[':fecha_hasta'] = $fFechaHasta . ' 23:59:59';
}

// Paginacion
$countSql = "SELECT COUNT(*) FROM LOG_IMPORTACION WHERE {$where}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRegistros = $countStmt->fetchColumn();

$porPagina = 30;
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;
$totalPaginas = ceil($totalRegistros / $porPagina);

// Consulta de datos
$dataSql = "SELECT * FROM LOG_IMPORTACION WHERE {$where} ORDER BY fecha_operacion DESC LIMIT {$porPagina} OFFSET {$offset}";
$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute($params);
$logs = $dataStmt->fetchAll();

$pageTitle = 'Log de Auditoria - Sistema HIS';
include 'includes/header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold"><i class="fas fa-clipboard-list me-2 text-primary"></i>Log de Auditoria de Importaciones</h6>
        <a href="log.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i> Limpiar Filtros</a>
    </div>
    
    <!-- Filtros -->
    <div class="card-body border-bottom">
        <form method="GET" action="log.php" class="row g-2 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold">Tipo Operacion</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <option value="IMPORT" <?= $fTipo === 'IMPORT' ? 'selected' : '' ?>>Importacion</option>
                    <option value="PROCESS" <?= $fTipo === 'PROCESS' ? 'selected' : '' ?>>Procesamiento</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small fw-semibold">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <option value="EXITO" <?= $fEstado === 'EXITO' ? 'selected' : '' ?>>Exito</option>
                    <option value="ERROR" <?= $fEstado === 'ERROR' ? 'selected' : '' ?>>Error</option>
                    <option value="PARCIAL" <?= $fEstado === 'PARCIAL' ? 'selected' : '' ?>>Parcial</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold">Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= htmlspecialchars($fFechaDesde) ?>">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small fw-semibold">Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($fFechaHasta) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-his btn-sm w-100"><i class="fas fa-search me-1"></i> Buscar</button>
            </div>
        </form>
    </div>
    
    <!-- Tabla -->
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">No se encontraron registros en el log</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Archivo</th>
                            <th>Tabla Destino</th>
                            <th>Periodo</th>
                            <th>Registros</th>
                            <th>Modo</th>
                            <th>Usuario</th>
                            <th>Estado</th>
                            <th>Duracion</th>
                            <th>Mensaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $i => $log): ?>
                        <tr>
                            <td class="text-muted"><?= $log['id_log'] ?></td>
                            <td><?= formatDateTime($log['fecha_operacion']) ?></td>
                            <td>
                                <?php if ($log['tipo_operacion'] === 'IMPORT'): ?>
                                    <span class="badge bg-primary"><i class="fas fa-file-import me-1"></i>Import</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><i class="fas fa-cogs me-1"></i>Process</span>
                                <?php endif; ?>
                            </td>
                            <td title="<?= clean($log['nombre_archivo']) ?>">
                                <?= clean(mb_strimwidth($log['nombre_archivo'] ?? '-', 0, 25, '...')) ?>
                            </td>
                            <td><code><?= clean($log['tabla_destino'] ?? '-') ?></code></td>
                            <td>
                                <?php if ($log['periodo_anio'] && $log['periodo_mes']): ?>
                                    <?= getNombreMes($log['periodo_mes']) ?> <?= $log['periodo_anio'] ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= number_format($log['registros_procesados']) ?></td>
                            <td>
                                <?php if ($log['modo_importacion'] === 'REEMPLAZO'): ?>
                                    <span class="badge bg-danger">Reemplazo</span>
                                <?php elseif ($log['modo_importacion'] === 'PERIODO'): ?>
                                    <span class="badge bg-warning text-dark">Periodo</span>
                                <?php elseif ($log['modo_importacion'] === 'COMPLETO'): ?>
                                    <span class="badge bg-info text-dark">Completo</span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= clean($log['usuario']) ?></td>
                            <td>
                                <?php if ($log['estado'] === 'EXITO'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Exito</span>
                                <?php elseif ($log['estado'] === 'ERROR'): ?>
                                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Error</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Parcial</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $log['duracion_segundos'] ? $log['duracion_segundos'] . 's' : '-' ?></td>
                            <td title="<?= clean($log['mensaje']) ?>">
                                <?= clean(mb_strimwidth($log['mensaje'] ?? '-', 0, 40, '...')) ?>
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
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?= $pagina - 1 ?>&<?= $queryString ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                $start = max(1, $pagina - 3);
                $end = min($totalPaginas, $pagina + 3);
                for ($p = $start; $p <= $end; $p++):
                ?>
                    <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $p ?>&<?= $queryString ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagina < $totalPaginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?= $pagina + 1 ?>&<?= $queryString ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
