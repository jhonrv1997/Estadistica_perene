<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 03: Consulta de Atenciones
 * Sub-paginas:
 *   - general     -> Filtro General de atenciones (toda la data consolidada)
 *   - preventivas -> Filtro de atenciones Preventivas (solo UPS / Codigo_Item de prevencion)
 *
 * Estructura: una sola pagina con tabs internas; el contenido se renderiza segun ?sub=
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();

// Sub-pagina activa
$sub = $_GET['sub'] ?? 'general';
if (!in_array($sub, ['general', 'preventivas'], true)) {
    $sub = 'general';
}

// Titulos por sub-pagina
$subInfo = [
    'general' => [
        'icon' => 'fa-list-alt',
        'titulo' => 'Consulta General de Atenciones',
        'descripcion' => 'Busqueda y consulta del consolidado HIS-MINSA con filtros completos.',
    ],
    'preventivas' => [
        'icon' => 'fa-shield-alt',
        'titulo' => 'Consulta de Atenciones Preventivas',
        'descripcion' => 'Solo atenciones preventivas (UPS / codigos de prevencion y promocion).',
    ],
];

// ============================================================
// FILTROS COMUNES
// ============================================================
$fAnio = trim($_GET['anio'] ?? '');
$fMes = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fGrupoEdad = trim($_GET['grupo_edad'] ?? '');
$fTipoDiagnostico = trim($_GET['tipo_diagnostico'] ?? '');
$fCodigoItem = trim($_GET['codigo_item'] ?? '');
$fDocPaciente = trim($_GET['doc_paciente'] ?? '');
$fDocPersonal = trim($_GET['doc_personal'] ?? '');
$fUps = trim($_GET['ups'] ?? '');
$fDepartamento = trim($_GET['departamento'] ?? '');

// Para la subpagina preventivas, agregamos un filtro prefijado de UPS preventivas
// Las UPS y codigos de preventivas suelen ser los que tienen Fg_Tipo = 'P' (preventivo)
// o cuya Descripcion_Ups contiene 'PREVENTIVO'.

// Opciones de filtros
$anios = $pdo->query("SELECT DISTINCT Anio FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Anio IS NOT NULL ORDER BY Anio DESC")->fetchAll(PDO::FETCH_COLUMN);
$establecimientos = $pdo->query("SELECT DISTINCT Nombre_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Nombre_Establecimiento IS NOT NULL ORDER BY Nombre_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);
$tiposDiagnostico = $pdo->query("SELECT DISTINCT Tipo_Diagnostico FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Tipo_Diagnostico IS NOT NULL ORDER BY Tipo_Diagnostico")->fetchAll(PDO::FETCH_COLUMN);
$departamentos = $pdo->query("SELECT DISTINCT Departamento_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Departamento_Establecimiento IS NOT NULL ORDER BY Departamento_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);
$gruposEdad = ['01 a 29 dias', '01 a 11 meses', '01 a 04 anos', '05 a 11 anos', '12 a 17 anos', '18 a 29 anos', '30 a 59 anos', '60 anos a mas'];

// UPS para preventivas
$upsPreventivas = [];
try {
    // Buscamos UPS que tengan descripcion relacionada a preventivas/promocion
    $upsPreventivas = $pdo->query("SELECT DISTINCT Id_Ups, Descripcion_Ups FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%' OR Fg_Tipo = 'P' ORDER BY Descripcion_Ups LIMIT 200")->fetchAll();
} catch (Exception $e) {
    $upsPreventivas = [];
}

$hayFiltros = ($fAnio !== '' || $fMes !== '' || $fEstablecimiento !== '' || $fGrupoEdad !== ''
    || $fTipoDiagnostico !== '' || $fCodigoItem !== '' || $fDocPaciente !== ''
    || $fDocPersonal !== '' || $fUps !== '' || $fDepartamento !== '');

// En subpagina preventivas forzamos el filtro de UPS preventivas o Fg_Tipo='P'
$where = "1=1";
$params = [];
if ($fAnio !== '') {
    $where .= " AND Anio = :anio"; $params[':anio'] = $fAnio;
}
if ($fMes !== '') {
    $where .= " AND CAST(TRIM(Mes) AS UNSIGNED) = :mes"; $params[':mes'] = intval($fMes);
}
if ($fEstablecimiento !== '') {
    $where .= " AND Nombre_Establecimiento = :est"; $params[':est'] = $fEstablecimiento;
}
if ($fGrupoEdad !== '') {
    $where .= " AND Grupo_Edad = :gedad"; $params[':gedad'] = $fGrupoEdad;
}
if ($fTipoDiagnostico !== '') {
    $where .= " AND Tipo_Diagnostico = :td"; $params[':td'] = $fTipoDiagnostico;
}
if ($fCodigoItem !== '') {
    $where .= " AND Codigo_Item LIKE :citem"; $params[':citem'] = '%' . $fCodigoItem . '%';
}
if ($fDocPaciente !== '') {
    $where .= " AND Numero_Documento_Paciente LIKE :dpac"; $params[':dpac'] = '%' . $fDocPaciente . '%';
}
if ($fDocPersonal !== '') {
    $where .= " AND Numero_Documento_Personal LIKE :dper"; $params[':dper'] = '%' . $fDocPersonal . '%';
}
if ($fDepartamento !== '') {
    $where .= " AND Departamento_Establecimiento = :dep"; $params[':dep'] = $fDepartamento;
}
if ($fUps !== '') {
    $where .= " AND Id_Ups = :ups"; $params[':ups'] = $fUps;
}

// Para subpagina preventivas: filtrar por UPS preventivas o Fg_Tipo='P'
if ($sub === 'preventivas') {
    if (!empty($upsPreventivas)) {
        $upsIds = array_column($upsPreventivas, 'Id_Ups');
        // construir lista segura
        $placeholders = [];
        foreach ($upsIds as $i => $uid) {
            if ($uid === null || $uid === '') continue;
            $k = ':pu' . $i;
            $placeholders[] = $k;
            $params[$k] = $uid;
        }
        if (!empty($placeholders)) {
            $where .= " AND (Id_Ups IN (" . implode(',', $placeholders) . ") OR Fg_Tipo = 'P' OR Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%')";
        } else {
            $where .= " AND (Fg_Tipo = 'P' OR Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%')";
        }
    } else {
        $where .= " AND (Fg_Tipo = 'P' OR Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%')";
    }
}

$totalRegistros = 0;
$datos = [];
$stats = ['total_pacientes' => 0, 'total_personal' => 0, 'total_establecimientos' => 0, 'total_items' => 0];
$totalPaginas = 0;
$pagina = 1;
$offset = 0;
$porPagina = 50;

if ($hayFiltros || $sub === 'preventivas') {
    $countSql = "SELECT COUNT(*) FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE " . $where;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRegistros = (int)$countStmt->fetchColumn();

    $pagina = max(1, intval($_GET['pagina'] ?? 1));
    $offset = ($pagina - 1) * $porPagina;
    $totalPaginas = ceil($totalRegistros / $porPagina);

    $campos = "Id_Cita, Anio, Mes, Dia, Fecha_Atencion, Descripcion_Ups, Nombre_Establecimiento,
               Numero_Documento_Paciente, Apellido_Paterno_Paciente, Apellido_Materno_Paciente,
               Nombres_Paciente, Fecha_Nacimiento_Paciente, Id_Genero, Grupo_Edad,
               Numero_Documento_Personal, Apellido_Paterno_Personal, Nombres_Personal,
               Descripcion_Profesion, Codigo_Item, Descripcion_Item, Tipo_Diagnostico,
               Valor_Lab, Descripcion_Financiador, Descripcion_Etnia, Fg_Tipo,
               Departamento_Establecimiento, Provincia_Establecimiento";

    $dataSql = "SELECT {$campos} FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE {$where} ORDER BY Fecha_Atencion DESC LIMIT {$porPagina} OFFSET {$offset}";
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $datos = $dataStmt->fetchAll();

    $statsSql = "SELECT
        COUNT(DISTINCT Id_Paciente) as total_pacientes,
        COUNT(DISTINCT Id_Personal) as total_personal,
        COUNT(DISTINCT Id_Establecimiento) as total_establecimientos,
        COUNT(DISTINCT Codigo_Item) as total_items
        FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE " . $where;
    $statsStmt = $pdo->prepare($statsSql);
    $statsStmt->execute($params);
    $stats = $statsStmt->fetch();
}

$pageTitle = $subInfo[$sub]['titulo'] . ' - Sistema HIS';
include 'includes/header.php';
?>

<!-- Encabezado -->
<div class="page-header-section">
    <h4><i class="fas <?= $subInfo[$sub]['icon'] ?> me-2"></i><?= $subInfo[$sub]['titulo'] ?></h4>
    <p class="subtitle"><?= $subInfo[$sub]['descripcion'] ?></p>
</div>

<!-- Tabs de sub-paginas -->
<ul class="nav nav-pills subpage-tabs flex-wrap">
    <li class="nav-item me-1">
        <a class="nav-link <?= $sub === 'general' ? 'active' : '' ?>" href="consulta_atenciones.php?sub=general">
            <i class="fas fa-list-alt me-1"></i> Filtro General
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $sub === 'preventivas' ? 'active' : '' ?>" href="consulta_atenciones.php?sub=preventivas">
            <i class="fas fa-shield-alt me-1"></i> Filtro Preventivas
        </a>
    </li>
</ul>

<!-- Stats -->
<?php if ($hayFiltros || $sub === 'preventivas'): ?>
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-notes-medical"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($totalRegistros) ?></span>
                <span class="stat-label">Atenciones</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-user-injured"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['total_pacientes'] ?? 0) ?></span>
                <span class="stat-label">Pacientes</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-user-md"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['total_personal'] ?? 0) ?></span>
                <span class="stat-label">Personal</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-hospital"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($stats['total_establecimientos'] ?? 0) ?></span>
                <span class="stat-label">Establecimientos</span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros de Busqueda</h6>
        <div>
            <a href="consulta_atenciones.php?sub=<?= $sub ?>" class="btn btn-sm btn-outline-secondary me-1">
                <i class="fas fa-times me-1"></i> Limpiar
            </a>
            <button type="submit" form="filterForm" class="btn btn-sm btn-his">
                <i class="fas fa-search me-1"></i> Buscar
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="consulta_atenciones.php">
            <input type="hidden" name="sub" value="<?= htmlspecialchars($sub) ?>">
            <div class="row g-3">
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Anio</label>
                    <select name="anio" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($anios as $a): ?>
                            <option value="<?= htmlspecialchars($a) ?>" <?= $fAnio === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Mes</label>
                    <select name="mes" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $fMes === (string)$m ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Departamento</label>
                    <select name="departamento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($departamentos as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $fDepartamento === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Establecimiento</label>
                    <select name="establecimiento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientos as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>" <?= $fEstablecimiento === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Grupo de Edad</label>
                    <select name="grupo_edad" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($gruposEdad as $ge): ?>
                            <option value="<?= htmlspecialchars($ge) ?>" <?= $fGrupoEdad === $ge ? 'selected' : '' ?>><?= htmlspecialchars($ge) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Tipo Diagnostico</label>
                    <select name="tipo_diagnostico" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($tiposDiagnostico as $td): ?>
                            <option value="<?= htmlspecialchars($td) ?>" <?= $fTipoDiagnostico === $td ? 'selected' : '' ?>><?= htmlspecialchars($td) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Codigo Item</label>
                    <input type="text" name="codigo_item" class="form-control form-control-sm" placeholder="Ej: CIE10" value="<?= htmlspecialchars($fCodigoItem) ?>">
                </div>
                <?php if ($sub === 'preventivas' && !empty($upsPreventivas)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">UPS Preventiva</label>
                    <select name="ups" class="form-select form-select-sm">
                        <option value="">-- Todas --</option>
                        <?php foreach ($upsPreventivas as $u): ?>
                            <option value="<?= htmlspecialchars($u['Id_Ups'] ?? '') ?>" <?= $fUps === ($u['Id_Ups'] ?? '') ? 'selected' : '' ?>>
                                <?= clean($u['Descripcion_Ups'] ?? '') ?> (<?= clean($u['Id_Ups'] ?? '') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Doc. Paciente</label>
                    <input type="text" name="doc_paciente" class="form-control form-control-sm" placeholder="Nro. Documento" value="<?= htmlspecialchars($fDocPaciente) ?>">
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Doc. Personal</label>
                    <input type="text" name="doc_personal" class="form-control form-control-sm" placeholder="Nro. Documento" value="<?= htmlspecialchars($fDocPersonal) ?>">
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($sub === 'preventivas'): ?>
<div class="alert alert-info py-2 mb-3">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Modo Preventivas:</strong> Se filtran automaticamente las atenciones cuyo UPS o tipo de item corresponde a actividades preventivo-promocionales.
</div>
<?php endif; ?>

<!-- Resultados -->
<?php if (!$hayFiltros && $sub !== 'preventivas'): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="fas fa-filter fa-3x text-muted mb-3"></i>
        <h5 class="text-muted mb-2">Seleccione al menos un filtro para consultar</h5>
        <p class="text-muted small mb-0">Use los filtros de arriba para cargar los datos del consolidado.</p>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0 fw-bold">
            <i class="fas fa-table me-2 text-primary"></i>
            Resultados: <?= number_format($totalRegistros) ?> registros
        </h6>
        <?php if ($totalRegistros > 0): ?>
        <form method="POST" action="export_excel.php" target="_blank">
            <?php foreach ($_GET as $key => $val): ?>
                <input type="hidden" name="filter_<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars(is_array($val) ? '' : $val) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn btn-sm btn-success">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </button>
        </form>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($datos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">No se encontraron registros con los filtros seleccionados</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>UPS</th>
                            <th>Establecimiento</th>
                            <th>Departamento</th>
                            <th>Paciente</th>
                            <th>Doc. Pac.</th>
                            <th>Edad</th>
                            <th>Personal</th>
                            <th>Codigo Item</th>
                            <th>Diagnostico</th>
                            <th>T.D.</th>
                            <th>Tipo</th>
                            <th>Financiador</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $i => $row): ?>
                        <tr>
                            <td class="text-muted"><?= $offset + $i + 1 ?></td>
                            <td><?= formatDate($row['Fecha_Atencion']) ?></td>
                            <td><?= clean($row['Descripcion_Ups']) ?></td>
                            <td title="<?= clean($row['Nombre_Establecimiento']) ?>">
                                <?= clean(mb_strimwidth($row['Nombre_Establecimiento'], 0, 30, '...')) ?>
                            </td>
                            <td><small><?= clean($row['Departamento_Establecimiento']) ?></small></td>
                            <td title="<?= clean($row['Apellido_Paterno_Paciente'] . ' ' . $row['Apellido_Materno_Paciente'] . ', ' . $row['Nombres_Paciente']) ?>">
                                <?= clean(mb_strimwidth($row['Apellido_Paterno_Paciente'] . ' ' . $row['Apellido_Materno_Paciente'] . ', ' . $row['Nombres_Paciente'], 0, 25, '...')) ?>
                            </td>
                            <td><?= clean($row['Numero_Documento_Paciente']) ?></td>
                            <td><?= clean($row['Grupo_Edad']) ?></td>
                            <td title="<?= clean($row['Apellido_Paterno_Personal'] . ', ' . $row['Nombres_Personal']) ?>">
                                <?= clean(mb_strimwidth($row['Apellido_Paterno_Personal'] . ', ' . $row['Nombres_Personal'], 0, 20, '...')) ?>
                            </td>
                            <td><code><?= clean($row['Codigo_Item']) ?></code></td>
                            <td title="<?= clean($row['Descripcion_Item']) ?>">
                                <?= clean(mb_strimwidth($row['Descripcion_Item'], 0, 30, '...')) ?>
                            </td>
                            <td><?= clean($row['Tipo_Diagnostico']) ?></td>
                            <td>
                                <?php if (($row['Fg_Tipo'] ?? '') === 'P'): ?>
                                    <span class="badge bg-success">Preventiva</span>
                                <?php elseif (($row['Fg_Tipo'] ?? '') === 'D'): ?>
                                    <span class="badge bg-info text-dark">Curativa</span>
                                <?php else: ?>
                                    <small class="text-muted"><?= clean($row['Fg_Tipo']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= clean($row['Descripcion_Financiador']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPaginas > 1): ?>
    <div class="card-footer bg-white">
        <nav aria-label="Paginacion">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php
                $queryParams = $_GET;
                unset($queryParams['pagina']);
                $queryString = http_build_query($queryParams);
                if ($pagina > 1): ?>
                    <li class="page-item"><a class="page-link" href="?pagina=1&<?= $queryString ?>"><i class="fas fa-angle-double-left"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina - 1 ?>&<?= $queryString ?>"><i class="fas fa-angle-left"></i></a></li>
                <?php endif; ?>
                <?php
                $start = max(1, $pagina - 2);
                $end = min($totalPaginas, $pagina + 2);
                for ($p = $start; $p <= $end; $p++):
                ?>
                    <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $p ?>&<?= $queryString ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($pagina < $totalPaginas): ?>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $pagina + 1 ?>&<?= $queryString ?>"><i class="fas fa-angle-right"></i></a></li>
                    <li class="page-item"><a class="page-link" href="?pagina=<?= $totalPaginas ?>&<?= $queryString ?>"><i class="fas fa-angle-double-right"></i></a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <div class="text-center mt-2">
            <small class="text-muted">
                Mostrando <?= number_format($offset + 1) ?> - <?= number_format(min($offset + $porPagina, $totalRegistros)) ?>
                de <?= number_format($totalRegistros) ?> registros
                | Pagina <?= $pagina ?> de <?= $totalPaginas ?>
            </small>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
