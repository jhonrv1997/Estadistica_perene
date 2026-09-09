<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 06: Reportes Operacionales
 * 17 sub-paginas: Adolescente, Adulto, Adulto mayor, Cancer, ESNI, Joven, Materno,
 * Medicina Alternativa, Metaxenicas, Nino, No transmisibles, Planificacion Familiar,
 * Salud bucal, Salud mental, Salud ocular, TBC, Zoonosis.
 *
 * Cada sub-pagina filtra el consolidado por los UPS / Codigo_Item correspondientes
 * a la estrategia operativa, y muestra volumen de atenciones y atendidos por
 * establecimiento + UPS + grupo de edad, con filtros por anio/mes/departamento.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';

$pdo = getDBConnection();

// Definicion de las 17 estrategias operacionales
$estrategias = [
    'adolescente'           => ['nombre' => 'Adolescente',              'icon' => 'fa-user-graduate',     'color' => 'primary',   'ups_patrones' => ['ADOLESCENTE'], 'item_patrones' => ['Z00%', 'Z01%', 'Z02%', 'Z03%', 'Z04%', 'Z05%', 'Z06%', 'Z07%', 'Z08%', 'Z09%', 'Z11%', 'Z12%', 'Z13%', 'Z14%', 'Z15%', 'Z16%', 'Z17%', 'Z18%', 'Z19%', 'Z7%', 'Z8%'], 'grupos_edad' => ['12 a 17 anos']],
    'adulto'                => ['nombre' => 'Adulto',                   'icon' => 'fa-user',              'color' => 'info',      'ups_patrones' => ['ADULTO', 'ADULTO MAYOR', 'MEDICINA GENERAL'], 'item_patrones' => ['Z00%', 'Z01%', 'I1%', 'I2%', 'E1%', 'E0%', 'E7%', 'E8%', 'E9%'], 'grupos_edad' => ['18 a 29 anos', '30 a 59 anos']],
    'adulto_mayor'          => ['nombre' => 'Adulto Mayor',             'icon' => 'fa-user-tie',          'color' => 'warning',   'ups_patrones' => ['ADULTO MAYOR'], 'item_patrones' => ['Z00%', 'I5%', 'I6%', 'I7%', 'I8%', 'I9%', 'E7%', 'E8%', 'E9%'], 'grupos_edad' => ['60 anos a mas']],
    'cancer'                => ['nombre' => 'Cancer',                   'icon' => 'fa-ribbon',            'color' => 'pink',      'ups_patrones' => ['CANCER', 'ONCOLOGIA'], 'item_patrones' => ['C%', 'D0%', 'D1%', 'D2%', 'D3%', 'D4%', 'Z12%', 'Z80%'], 'grupos_edad' => []],
    'esni'                  => ['nombre' => 'ESNI',                     'icon' => 'fa-syringe',           'color' => 'success',   'ups_patrones' => ['INMUNIZACIONES', 'VACUNACION'], 'item_patrones' => ['Z24%', 'Z25%', 'Z26%', 'Z27%', 'U%', 'J0%', 'J1%'], 'grupos_edad' => ['01 a 29 dias', '01 a 11 meses', '01 a 04 anos']],
    'joven'                 => ['nombre' => 'Joven',                    'icon' => 'fa-walking',           'color' => 'info',      'ups_patrones' => ['JOVEN', 'ADOLESCENTE'], 'item_patrones' => ['Z00%', 'Z01%', 'Z02%', 'Z7%', 'Z8%', 'Z9%'], 'grupos_edad' => ['12 a 17 anos', '18 a 29 anos']],
    'materno'               => ['nombre' => 'Materno',                  'icon' => 'fa-baby',              'color' => 'danger',    'ups_patrones' => ['OBSTETRICIA', 'MATERNO', 'GINECOLOGIA', 'PARTO'], 'item_patrones' => ['O0%', 'O1%', 'O2%', 'O3%', 'O4%', 'O5%', 'O6%', 'O7%', 'O8%', 'O9%', 'Z32%', 'Z33%', 'Z34%', 'Z35%', 'Z36%', 'Z37%'], 'grupos_edad' => []],
    'medicina_alternativa'  => ['nombre' => 'Medicina Alternativa',     'icon' => 'fa-leaf',              'color' => 'success',   'ups_patrones' => ['MEDICINA ALTERNATIVA', 'MEDICINA TRADICIONAL', 'ACUPUNTURA'], 'item_patrones' => [], 'grupos_edad' => []],
    'metaxenicas'           => ['nombre' => 'Metaxenicas',              'icon' => 'fa-virus',             'color' => 'danger',    'ups_patrones' => ['METAXENICAS', 'MALARIA', 'DENGUE'], 'item_patrones' => ['A0%', 'A1%', 'A2%', 'A3%', 'A4%', 'A5%', 'A6%', 'A7%', 'A8%', 'A9%', 'B5%', 'B6%', 'B7%', 'B8%', 'B9%'], 'grupos_edad' => []],
    'nino'                  => ['nombre' => 'Nino',                     'icon' => 'fa-child',             'color' => 'primary',   'ups_patrones' => ['NINO', 'PEDIATRIA', 'CRECIMIENTO'], 'item_patrones' => ['Z00%', 'Z01%', 'A0%', 'A1%', 'A2%', 'A3%', 'A4%', 'A5%', 'A6%', 'A7%', 'A8%', 'A9%', 'J0%', 'J1%'], 'grupos_edad' => ['01 a 29 dias', '01 a 11 meses', '01 a 04 anos', '05 a 11 anos']],
    'no_transmisibles'      => ['nombre' => 'No Transmisibles',         'icon' => 'fa-heartbeat',         'color' => 'danger',    'ups_patrones' => ['NO TRANSMISIBLES', 'CRONICAS', 'DIABETES', 'HIPERTENSION'], 'item_patrones' => ['E1%', 'E2%', 'E3%', 'E4%', 'E5%', 'E6%', 'E7%', 'E8%', 'E9%', 'I1%', 'I2%', 'I3%', 'I4%', 'I5%', 'I6%', 'I7%', 'I8%', 'I9%'], 'grupos_edad' => []],
    'planificacion_familiar'=> ['nombre' => 'Planificacion Familiar',   'icon' => 'fa-people-arrows',     'color' => 'info',      'ups_patrones' => ['PLANIFICACION', 'FAMILIAR'], 'item_patrones' => ['Z30%', 'Z31%'], 'grupos_edad' => []],
    'salud_bucal'           => ['nombre' => 'Salud Bucal',              'icon' => 'fa-tooth',             'color' => 'info',      'ups_patrones' => ['ODONTOLOGIA', 'SALUD BUCAL', 'DENTAL'], 'item_patrones' => ['K0%', 'K1%', 'K2%', 'K3%', 'K4%', 'K5%', 'K6%', 'K7%', 'K8%', 'K9%', 'Z01%'], 'grupos_edad' => []],
    'salud_mental'          => ['nombre' => 'Salud Mental',             'icon' => 'fa-brain',             'color' => 'purple',    'ups_patrones' => ['SALUD MENTAL', 'PSICOLOGIA', 'PSIQUIATRIA'], 'item_patrones' => ['F0%', 'F1%', 'F2%', 'F3%', 'F4%', 'F5%', 'F6%', 'F7%', 'F8%', 'F9%'], 'grupos_edad' => []],
    'salud_ocular'          => ['nombre' => 'Salud Ocular',             'icon' => 'fa-eye',               'color' => 'info',      'ups_patrones' => ['OFTALMOLOGIA', 'SALUD OCULAR'], 'item_patrones' => ['H0%', 'H1%', 'H2%', 'H3%', 'H4%', 'H5%', 'H6%', 'H7%', 'H8%', 'H9%'], 'grupos_edad' => []],
    'tbc'                   => ['nombre' => 'TBC',                      'icon' => 'fa-lungs',             'color' => 'warning',   'ups_patrones' => ['TBC', 'TUBERCULOSIS'], 'item_patrones' => ['A1%', 'A2%', 'A3%', 'A4%', 'A5%', 'A6%', 'A7%', 'A8%', 'A9%', 'J6%', 'J7%', 'J8%', 'J9%'], 'grupos_edad' => []],
    'zoonosis'              => ['nombre' => 'Zoonosis',                 'icon' => 'fa-paw',               'color' => 'warning',   'ups_patrones' => ['ZOONOSIS'], 'item_patrones' => ['A2%', 'A5%', 'A8%', 'A9%', 'B5%', 'B6%', 'B7%', 'B8%', 'B9%'], 'grupos_edad' => []],
];

$sub = $_GET['sub'] ?? '';
if (!isset($estrategias[$sub])) {
    // Si no se especifica sub, mostrar la pagina indice con las 17 cards
    $sub = '';
}

$fAnio = trim($_GET['anio'] ?? '');
$fMes = trim($_GET['mes'] ?? '');
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fDepartamento = trim($_GET['departamento'] ?? '');

$anios = $pdo->query("SELECT DISTINCT Anio FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Anio IS NOT NULL ORDER BY Anio DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($anios)) $anios = [date('Y')];
if ($fAnio === '') $fAnio = $anios[0];

$establecimientos = $pdo->query("SELECT DISTINCT Nombre_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Nombre_Establecimiento IS NOT NULL ORDER BY Nombre_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);
$departamentos = $pdo->query("SELECT DISTINCT Departamento_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Departamento_Establecimiento IS NOT NULL ORDER BY Departamento_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);

// Construir clausula WHERE para la estrategia activa
$where = "1=1";
$params = [];
if ($fAnio !== '') { $where .= " AND Anio = :anio"; $params[':anio'] = $fAnio; }
if ($fMes !== '') { $where .= " AND CAST(TRIM(Mes) AS UNSIGNED) = :mes"; $params[':mes'] = intval($fMes); }
if ($fEstablecimiento !== '') { $where .= " AND Nombre_Establecimiento = :est"; $params[':est'] = $fEstablecimiento; }
if ($fDepartamento !== '') { $where .= " AND Departamento_Establecimiento = :dep"; $params[':dep'] = $fDepartamento; }

$datos = [];
$totalAtenciones = 0;
$totalAtendidos = 0;

if ($sub !== '') {
    $est = $estrategias[$sub];

    // Agregar filtro por UPS y Codigo_Item
    $upsConds = [];
    if (!empty($est['ups_patrones'])) {
        foreach ($est['ups_patrones'] as $i => $pat) {
            $k = ':ups_' . $i;
            $upsConds[] = "Descripcion_Ups LIKE " . $k;
            $params[$k] = '%' . $pat . '%';
        }
    }

    $itemConds = [];
    if (!empty($est['item_patrones'])) {
        foreach ($est['item_patrones'] as $i => $pat) {
            $k = ':item_' . $i;
            $itemConds[] = "Codigo_Item LIKE " . $k;
            $params[$k] = $pat;
        }
    }

    $estrCond = '';
    if (!empty($upsConds) && !empty($itemConds)) {
        $estrCond = ' AND (' . implode(' OR ', $upsConds) . ') AND (' . implode(' OR ', $itemConds) . ')';
    } elseif (!empty($upsConds)) {
        $estrCond = ' AND (' . implode(' OR ', $upsConds) . ')';
    } elseif (!empty($itemConds)) {
        $estrCond = ' AND (' . implode(' OR ', $itemConds) . ')';
    }

    // Filtro por grupo de edad si aplica
    if (!empty($est['grupos_edad'])) {
        $geConds = [];
        foreach ($est['grupos_edad'] as $i => $ge) {
            $k = ':ge_' . $i;
            $geConds[] = "Grupo_Edad = " . $k;
            $params[$k] = $ge;
        }
        $estrCond .= ' AND (' . implode(' OR ', $geConds) . ')';
    }

    $sql = "SELECT
                Nombre_Establecimiento,
                Departamento_Establecimiento,
                Descripcion_Ups,
                COUNT(*) as total_atenciones,
                COUNT(DISTINCT Id_Paciente) as total_atendidos,
                COUNT(DISTINCT Codigo_Item) as items_distintos
            FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO
            WHERE {$where} {$estrCond}
            GROUP BY Nombre_Establecimiento, Departamento_Establecimiento, Descripcion_Ups
            ORDER BY total_atenciones DESC
            LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $datos = $stmt->fetchAll();

    foreach ($datos as $r) {
        $totalAtenciones += (int)$r['total_atenciones'];
        $totalAtendidos += (int)$r['total_atendidos'];
    }
}

$pageTitle = 'Reportes Operacionales' . ($sub !== '' ? ' - ' . $estrategias[$sub]['nombre'] : '') . ' - Sistema HIS';
include 'includes/header.php';
?>

<div class="page-header-section">
    <h4><i class="fas fa-chart-line me-2"></i>Reportes Operacionales</h4>
    <p class="subtitle">Reportes por estrategia operativa del MINSA: atenciones y atendidos por establecimiento y UPS.</p>
</div>

<?php if ($sub === ''): ?>
<!-- Pagina indice: 17 tarjetas -->
<div class="row g-3">
    <?php foreach ($estrategias as $key => $est):
        // ESNI, Cancer, Materno y Zoonosis ahora tienen su propio reporte completo
        // data-driven (reporte_esni.php / reporte_cancer.php / reporte_materno.php /
        // reporte_zoonosis.php) - reemplazan el flujo manual SQL Server + Excel ODBC
        // de sus respectivos modulos.
        $esAvanzado = in_array($key, ['esni', 'cancer', 'materno', 'zoonosis']);
        if ($key === 'esni') {
            $href = 'reporte_esni.php';
            $desc = 'Reporte Operacional completo';
        } elseif ($key === 'cancer') {
            $href = 'reporte_cancer.php';
            $desc = 'Reporte de Actividades de Prevencion y Control del Cancer';
        } elseif ($key === 'materno') {
            $href = 'reporte_materno.php';
            $desc = 'Reporte de Actividades de Salud Sexual y Reproductiva';
        } elseif ($key === 'zoonosis') {
            $href = 'reporte_zoonosis.php';
            $desc = 'Informe Mensual de Zoonosis (Ponzoñosos + Rabia Urbana)';
        } else {
            $href = 'reporte_operacionales.php?sub=' . $key;
            $desc = 'Atenciones y atendidos por establecimiento';
        }
        $badge = $esAvanzado ? ' <span class="badge bg-success">NUEVO</span>' : '';
    ?>
    <div class="col-lg-3 col-md-4 col-sm-6">
        <a href="<?= $href ?>" class="subpage-card <?= $esAvanzado ? 'subpage-card-featured' : '' ?>">
            <div class="sub-icon"><i class="fas <?= $est['icon'] ?>"></i></div>
            <h6><?= $est['nombre'] ?><?= $badge ?></h6>
            <small><?= $desc ?></small>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php elseif ($sub === 'esni'):
    // Redirigir a la pagina de reporte ESNI completo (data-driven)
    header('Location: reporte_esni.php');
    exit;
?>

<?php elseif ($sub === 'cancer'):
    // Redirigir al reporte completo de Cancer (data-driven, 1 click)
    header('Location: reporte_cancer.php');
    exit;
?>

<?php elseif ($sub === 'materno'):
    // Redirigir al reporte completo de Materno (data-driven, 1 click)
    header('Location: reporte_materno.php');
    exit;
?>

<?php elseif ($sub === 'zoonosis'):
    // Redirigir al reporte completo de Zoonosis (data-driven, 1 click):
    // Informe Mensual de Zoonosis (ponzoñosos + rabia urbana 1-11)
    header('Location: reporte_zoonosis.php');
    exit;
?>

<?php else:
    $est = $estrategias[$sub];
?>
<!-- Sub-pagina activa -->
<ul class="nav nav-pills subpage-tabs flex-wrap mb-3">
    <li class="nav-item me-1">
        <a class="nav-link" href="reporte_operacionales.php"><i class="fas fa-arrow-left me-1"></i>Volver</a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="#"><i class="fas <?= $est['icon'] ?> me-1"></i><?= $est['nombre'] ?></a>
    </li>
</ul>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-notes-medical"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($totalAtenciones) ?></span>
                <span class="stat-label">Atenciones <?= $est['nombre'] ?></span>
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
            <div class="stat-icon"><i class="fas fa-hospital"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format(count($datos)) ?></span>
                <span class="stat-label">UPS / Establecimientos</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-percentage"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= $totalAtenciones > 0 ? number_format(($totalAtendidos / $totalAtenciones) * 100, 1) : 0 ?>%</span>
                <span class="stat-label">Atendidos / Atenciones</span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros - <?= $est['nombre'] ?></h6>
        <button type="submit" form="filterForm" class="btn btn-sm btn-his"><i class="fas fa-search me-1"></i> Generar</button>
    </div>
    <div class="card-body">
        <form id="filterForm" method="GET" action="reporte_operacionales.php">
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
            </div>
        </form>
    </div>
</div>

<?php if (!empty($est['grupos_edad'])): ?>
<div class="alert alert-info py-2 mb-3">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Filtro por grupo de edad:</strong> <?= implode(', ', $est['grupos_edad']) ?>
</div>
<?php endif; ?>

<!-- Resultado -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Atenciones y Atendidos por Establecimiento</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($datos)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>Sin datos</h5>
                <p>No se encontraron registros de <strong><?= $est['nombre'] ?></strong> para los filtros seleccionados.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Establecimiento</th>
                            <th>Departamento</th>
                            <th>UPS</th>
                            <th class="text-end">Atenciones</th>
                            <th class="text-end">Atendidos</th>
                            <th class="text-end">Items Distintos</th>
                            <th class="text-end">Atenc/Atend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $i => $r): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td title="<?= clean($r['Nombre_Establecimiento']) ?>"><?= clean(mb_strimwidth($r['Nombre_Establecimiento'], 0, 30, '...')) ?></td>
                            <td><small><?= clean($r['Departamento_Establecimiento']) ?></small></td>
                            <td><small><?= clean(mb_strimwidth($r['Descripcion_Ups'] ?? '-', 0, 25, '...')) ?></small></td>
                            <td class="text-end fw-bold"><?= number_format($r['total_atenciones']) ?></td>
                            <td class="text-end text-success"><?= number_format($r['total_atendidos']) ?></td>
                            <td class="text-end"><?= number_format($r['items_distintos']) ?></td>
                            <td class="text-end"><?= $r['total_atendidos'] > 0 ? number_format($r['total_atenciones'] / $r['total_atendidos'], 2) : '0' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">TOTAL</td>
                            <td class="text-end"><?= number_format($totalAtenciones) ?></td>
                            <td class="text-end text-success"><?= number_format($totalAtendidos) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
