<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 05: Reporte de Atenciones
 * Sub-paginas:
 *   - atendidos -> Reporte atenciones y atendidos (numero de atenciones vs pacientes unicos)
 *   - diario    -> Reporte produccion atencion diario
 *   - mensual   -> Reporte produccion atencion mensual
 *   - 40a       -> Reporte 40A (formato MINSA: establecimiento x UPS x grupo edad)
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();

$sub = $_GET['sub'] ?? 'atendidos';
if (!in_array($sub, ['atendidos', 'diario', 'mensual', '40a'], true)) {
    $sub = 'atendidos';
}

$subInfo = [
    'atendidos' => ['icon' => 'fa-users',          'titulo' => 'Reporte de Atenciones y Atendidos', 'desc' => 'Comparativo del numero total de atenciones versus pacientes unicos atendidos.'],
    'diario'    => ['icon' => 'fa-calendar-day',   'titulo' => 'Produccion de Atencion Diario',     'desc' => 'Volumen de atenciones por dia del periodo seleccionado.'],
    'mensual'   => ['icon' => 'fa-calendar-alt',   'titulo' => 'Produccion de Atencion Mensual',    'desc' => 'Volumen de atenciones por mes del anio seleccionado.'],
    '40a'       => ['icon' => 'fa-file-invoice',   'titulo' => 'Reporte 40A',                       'desc' => 'Reporte agregado por establecimiento, UPS y grupo de edad (formato MINSA).'],
];

// Filtros
$fAnio = trim($_GET['anio'] ?? '');
$fMes = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fDepartamento = trim($_GET['departamento'] ?? '');

// Anios y establecimientos disponibles
$anios = $pdo->query("SELECT DISTINCT Anio FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Anio IS NOT NULL ORDER BY Anio DESC")->fetchAll(PDO::FETCH_COLUMN);
$establecimientos = $pdo->query("SELECT DISTINCT Nombre_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Nombre_Establecimiento IS NOT NULL ORDER BY Nombre_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);
$departamentos = $pdo->query("SELECT DISTINCT Departamento_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Departamento_Establecimiento IS NOT NULL ORDER BY Departamento_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);

// Si no hay anio seleccionado, tomar el mas reciente
if ($fAnio === '' && !empty($anios)) {
    $fAnio = $anios[0];
}

$datos = [];
$totalAtenciones = 0;
$totalAtendidos = 0;

// ============================================================
// SUB-PAGINA: ATENDIDOS
// ============================================================
if ($sub === 'atendidos') {
    $where = "1=1";
    $params = [];
    if ($fAnio !== '') { $where .= " AND Anio = :anio"; $params[':anio'] = $fAnio; }
    if ($fMes !== '') { $where .= " AND CAST(TRIM(Mes) AS UNSIGNED) = :mes"; $params[':mes'] = intval($fMes); }
    if ($fEstablecimiento !== '') { $where .= " AND Nombre_Establecimiento = :est"; $params[':est'] = $fEstablecimiento; }
    if ($fDepartamento !== '') { $where .= " AND Departamento_Establecimiento = :dep"; $params[':dep'] = $fDepartamento; }

    $sql = "SELECT
                Nombre_Establecimiento,
                Departamento_Establecimiento,
                COUNT(*) as total_atenciones,
                COUNT(DISTINCT Id_Paciente) as total_atendidos,
                COUNT(DISTINCT Codigo_Item) as items_distintos,
                COUNT(DISTINCT Id_Personal) as personal_distinto
            FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
            WHERE {$where}
            GROUP BY Nombre_Establecimiento, Departamento_Establecimiento
            ORDER BY total_atenciones DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll();

    foreach ($datos as $row) {
        $totalAtenciones += (int)$row['total_atenciones'];
        $totalAtendidos += (int)$row['total_atendidos'];
    }
}

// ============================================================
// SUB-PAGINA: DIARIO
// ============================================================
elseif ($sub === 'diario') {
    $where = "1=1";
    $params = [];
    if ($fAnio !== '') { $where .= " AND Anio = :anio"; $params[':anio'] = $fAnio; }
    if ($fMes !== '') { $where .= " AND CAST(TRIM(Mes) AS UNSIGNED) = :mes"; $params[':mes'] = intval($fMes); }
    if ($fEstablecimiento !== '') { $where .= " AND Nombre_Establecimiento = :est"; $params[':est'] = $fEstablecimiento; }

    $sql = "SELECT
                Fecha_Atencion,
                Anio, Mes, Dia,
                COUNT(*) as total_atenciones,
                COUNT(DISTINCT Id_Paciente) as total_atendidos,
                COUNT(DISTINCT Id_Establecimiento) as establecimientos_activos
            FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
            WHERE {$where}
            GROUP BY Fecha_Atencion, Anio, Mes, Dia
            ORDER BY Fecha_Atencion ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll();

    foreach ($datos as $row) {
        $totalAtenciones += (int)$row['total_atenciones'];
        $totalAtendidos += (int)$row['total_atendidos'];
    }
}

// ============================================================
// SUB-PAGINA: MENSUAL
// ============================================================
elseif ($sub === 'mensual') {
    $where = "1=1";
    $params = [];
    if ($fAnio !== '') { $where .= " AND Anio = :anio"; $params[':anio'] = $fAnio; }
    if ($fEstablecimiento !== '') { $where .= " AND Nombre_Establecimiento = :est"; $params[':est'] = $fEstablecimiento; }

    $sql = "SELECT
                Anio, Mes,
                COUNT(*) as total_atenciones,
                COUNT(DISTINCT Id_Paciente) as total_atendidos,
                COUNT(DISTINCT Id_Establecimiento) as establecimientos_activos,
                COUNT(DISTINCT Codigo_Item) as items_distintos
            FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
            WHERE {$where}
            GROUP BY Anio, Mes
            ORDER BY Anio ASC, CAST(TRIM(Mes) AS UNSIGNED) ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll();

    foreach ($datos as $row) {
        $totalAtenciones += (int)$row['total_atenciones'];
        $totalAtendidos += (int)$row['total_atendidos'];
    }
}

// ============================================================
// SUB-PAGINA: 40A (Reporte MINSA - establecimiento x UPS x grupo edad)
// ============================================================
elseif ($sub === '40a') {
    $where = "1=1";
    $params = [];
    if ($fAnio !== '') { $where .= " AND Anio = :anio"; $params[':anio'] = $fAnio; }
    if ($fMes !== '') { $where .= " AND CAST(TRIM(Mes) AS UNSIGNED) = :mes"; $params[':mes'] = intval($fMes); }
    if ($fEstablecimiento !== '') { $where .= " AND Nombre_Establecimiento = :est"; $params[':est'] = $fEstablecimiento; }
    if ($fDepartamento !== '') { $where .= " AND Departamento_Establecimiento = :dep"; $params[':dep'] = $fDepartamento; }

    $gruposEdad = ['01 a 29 dias', '01 a 11 meses', '01 a 04 anos', '05 a 11 anos', '12 a 17 anos', '18 a 29 anos', '30 a 59 anos', '60 anos a mas'];

    $sql = "SELECT
                Nombre_Establecimiento,
                Descripcion_Ups,
                Grupo_Edad,
                COUNT(*) as total_atenciones,
                COUNT(DISTINCT Id_Paciente) as total_atendidos
            FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
            WHERE {$where}
            GROUP BY Nombre_Establecimiento, Descripcion_Ups, Grupo_Edad
            ORDER BY Nombre_Establecimiento, Descripcion_Ups, Grupo_Edad";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $raw = $stmt->fetchAll();

    // Pivotar datos: agrupar por establecimiento + UPS, con columnas por grupo de edad
    $pivot = [];
    foreach ($raw as $r) {
        $key = $r['Nombre_Establecimiento'] . '||' . $r['Descripcion_Ups'];
        if (!isset($pivot[$key])) {
            $pivot[$key] = [
                'establecimiento' => $r['Nombre_Establecimiento'],
                'ups' => $r['Descripcion_Ups'],
                'grupos' => array_fill_keys($gruposEdad, 0),
                'total' => 0,
                'atendidos' => 0,
            ];
        }
        if (isset($pivot[$key]['grupos'][$r['Grupo_Edad']])) {
            $pivot[$key]['grupos'][$r['Grupo_Edad']] += (int)$r['total_atenciones'];
        } else {
            $pivot[$key]['grupos'][$r['Grupo_Edad']] = (int)$r['total_atenciones'];
        }
        $pivot[$key]['total'] += (int)$r['total_atenciones'];
        $pivot[$key]['atendidos'] += (int)$r['total_atendidos'];
    }
    $datos = array_values($pivot);
    foreach ($datos as $row) {
        $totalAtenciones += $row['total'];
        $totalAtendidos += $row['atendidos'];
    }
}

$pageTitle = $subInfo[$sub]['titulo'] . ' - Sistema HIS';
include 'includes/header.php';
?>

<div class="page-header-section">
    <h4><i class="fas <?= $subInfo[$sub]['icon'] ?> me-2"></i><?= $subInfo[$sub]['titulo'] ?></h4>
    <p class="subtitle"><?= $subInfo[$sub]['desc'] ?></p>
</div>

<!-- Tabs de sub-paginas -->
<ul class="nav nav-pills subpage-tabs flex-wrap">
    <li class="nav-item me-1"><a class="nav-link <?= $sub === 'atendidos' ? 'active' : '' ?>" href="reporte_atenciones.php?sub=atendidos"><i class="fas fa-users me-1"></i>Atenciones y Atendidos</a></li>
    <li class="nav-item me-1"><a class="nav-link <?= $sub === 'diario' ? 'active' : '' ?>" href="reporte_atenciones.php?sub=diario"><i class="fas fa-calendar-day me-1"></i>Produccion Diario</a></li>
    <li class="nav-item me-1"><a class="nav-link <?= $sub === 'mensual' ? 'active' : '' ?>" href="reporte_atenciones.php?sub=mensual"><i class="fas fa-calendar-alt me-1"></i>Produccion Mensual</a></li>
    <li class="nav-item"><a class="nav-link <?= $sub === '40a' ? 'active' : '' ?>" href="reporte_atenciones.php?sub=40a"><i class="fas fa-file-invoice me-1"></i>Reporte 40A</a></li>
</ul>

<!-- Stats resumen -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-notes-medical"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($totalAtenciones) ?></span>
                <span class="stat-label">Total Atenciones</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-user-injured"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($totalAtendidos) ?></span>
                <span class="stat-label">Pacientes Atendidos</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalAtenciones > 0 ? number_format(($totalAtendidos / $totalAtenciones) * 100, 1) : 0 ?>%</span>
                <span class="stat-label">Atendidos / Atenciones</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-list-ol"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($totalAtenciones > 0 && $totalAtendidos > 0 ? $totalAtenciones / $totalAtendidos : 0, 2) ?></span>
                <span class="stat-label">Atenciones por Paciente</span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros del Reporte</h6>
        <button type="submit" form="filterForm" class="btn btn-sm btn-his"><i class="fas fa-search me-1"></i> Generar</button>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="reporte_atenciones.php">
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
                <?php if (in_array($sub, ['atendidos', 'diario', '40a'], true)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Mes</label>
                    <select name="mes" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $fMes === (string)$m ? 'selected' : '' ?>><?= getNombreMes($m) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php endif; ?>
                <?php if (in_array($sub, ['atendidos', '40a'], true)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Departamento</label>
                    <select name="departamento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($departamentos as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $fDepartamento === $d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Establecimiento</label>
                    <select name="establecimiento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientos as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>" <?= $fEstablecimiento === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Resultado del reporte -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i><?= $subInfo[$sub]['titulo'] ?></h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($datos)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>Sin datos</h5>
                <p>No se encontraron registros para los filtros seleccionados.</p>
            </div>
        <?php elseif ($sub === 'atendidos'): ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Establecimiento</th>
                            <th>Departamento</th>
                            <th class="text-end">Atenciones</th>
                            <th class="text-end">Atendidos</th>
                            <th class="text-end">Items Distintos</th>
                            <th class="text-end">Personal</th>
                            <th class="text-end">Atenc/Atend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $i => $r): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><?= clean($r['Nombre_Establecimiento']) ?></td>
                            <td><small><?= clean($r['Departamento_Establecimiento']) ?></small></td>
                            <td class="text-end fw-bold"><?= number_format($r['total_atenciones']) ?></td>
                            <td class="text-end text-success fw-bold"><?= number_format($r['total_atendidos']) ?></td>
                            <td class="text-end"><?= number_format($r['items_distintos']) ?></td>
                            <td class="text-end"><?= number_format($r['personal_distinto']) ?></td>
                            <td class="text-end"><?= $r['total_atendidos'] > 0 ? number_format($r['total_atenciones'] / $r['total_atendidos'], 2) : '0' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td class="text-end"><?= number_format($totalAtenciones) ?></td>
                            <td class="text-end text-success"><?= number_format($totalAtendidos) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php elseif ($sub === 'diario'): ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Fecha</th>
                            <th>Anio</th>
                            <th>Mes</th>
                            <th>Dia</th>
                            <th class="text-end">Atenciones</th>
                            <th class="text-end">Atendidos</th>
                            <th class="text-end">Establecimientos</th>
                            <th class="text-end">Atenc/Atend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $i => $r): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><?= formatDate($r['Fecha_Atencion']) ?></td>
                            <td><?= clean($r['Anio']) ?></td>
                            <td><?= getNombreMes((int)$r['Mes']) ?></td>
                            <td><?= clean($r['Dia']) ?></td>
                            <td class="text-end fw-bold"><?= number_format($r['total_atenciones']) ?></td>
                            <td class="text-end text-success"><?= number_format($r['total_atendidos']) ?></td>
                            <td class="text-end"><?= number_format($r['establecimientos_activos']) ?></td>
                            <td class="text-end"><?= $r['total_atendidos'] > 0 ? number_format($r['total_atenciones'] / $r['total_atendidos'], 2) : '0' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">TOTAL</td>
                            <td class="text-end"><?= number_format($totalAtenciones) ?></td>
                            <td class="text-end text-success"><?= number_format($totalAtendidos) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php elseif ($sub === 'mensual'): ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Anio</th>
                            <th>Mes</th>
                            <th class="text-end">Atenciones</th>
                            <th class="text-end">Atendidos</th>
                            <th class="text-end">Establecimientos Activos</th>
                            <th class="text-end">Items Distintos</th>
                            <th class="text-end">Atenc/Atend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $i => $r): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><?= clean($r['Anio']) ?></td>
                            <td><?= getNombreMes((int)$r['Mes']) ?></td>
                            <td class="text-end fw-bold"><?= number_format($r['total_atenciones']) ?></td>
                            <td class="text-end text-success"><?= number_format($r['total_atendidos']) ?></td>
                            <td class="text-end"><?= number_format($r['establecimientos_activos']) ?></td>
                            <td class="text-end"><?= number_format($r['items_distintos']) ?></td>
                            <td class="text-end"><?= $r['total_atendidos'] > 0 ? number_format($r['total_atenciones'] / $r['total_atendidos'], 2) : '0' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td class="text-end"><?= number_format($totalAtenciones) ?></td>
                            <td class="text-end text-success"><?= number_format($totalAtendidos) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php elseif ($sub === '40a'): ?>
            <?php $grupos = ['01 a 29 dias', '01 a 11 meses', '01 a 04 anos', '05 a 11 anos', '12 a 17 anos', '18 a 29 anos', '30 a 59 anos', '60 anos a mas']; ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2">Establecimiento</th>
                            <th rowspan="2">UPS</th>
                            <?php foreach ($grupos as $g): ?>
                                <th class="text-end" title="<?= htmlspecialchars($g) ?>"><?= htmlspecialchars(mb_strimwidth($g, 0, 12, '...')) ?></th>
                            <?php endforeach; ?>
                            <th class="text-end">Total</th>
                            <th class="text-end">Atendidos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $r): ?>
                        <tr>
                            <td title="<?= clean($r['establecimiento']) ?>"><?= clean(mb_strimwidth($r['establecimiento'], 0, 25, '...')) ?></td>
                            <td><small><?= clean(mb_strimwidth($r['ups'] ?? '-', 0, 25, '...')) ?></small></td>
                            <?php foreach ($grupos as $g): ?>
                                <td class="text-end"><?= isset($r['grupos'][$g]) && $r['grupos'][$g] > 0 ? number_format($r['grupos'][$g]) : '<span class="text-muted">-</span>' ?></td>
                            <?php endforeach; ?>
                            <td class="text-end fw-bold"><?= number_format($r['total']) ?></td>
                            <td class="text-end text-success"><?= number_format($r['atendidos']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">TOTAL</td>
                            <?php foreach ($grupos as $g): $gt = 0; foreach ($datos as $r) { $gt += $r['grupos'][$g] ?? 0; } ?>
                                <td class="text-end"><?= number_format($gt) ?></td>
                            <?php endforeach; ?>
                            <td class="text-end"><?= number_format($totalAtenciones) ?></td>
                            <td class="text-end text-success"><?= number_format($totalAtendidos) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
