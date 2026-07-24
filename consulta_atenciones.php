<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 03: Consulta de Atenciones
 * Sub-paginas:
 *   - general     -> Filtro General de atenciones (toda la data consolidada)
 *   - preventivas -> Filtro de atenciones Preventivas (solo UPS / Codigo_Item de prevencion)
 *
 * Estructura: una sola pagina con tabs internos; el contenido se renderiza segun ?sub=
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
$fZonaSanitaria = trim($_GET['zona_sanitaria'] ?? 'Perene'); // Valor por defecto: Perene
$fEstablecimiento = trim($_GET['establecimiento'] ?? '');
$fGrupoEdad = trim($_GET['grupo_edad'] ?? '');
$fIdGenero = trim($_GET['id_genero'] ?? '');
$fOtraCondicion = trim($_GET['otra_condicion'] ?? '');
$fCodigoItem = trim($_GET['codigo_item'] ?? '');
$fTipoDiagnostico = trim($_GET['tipo_diagnostico'] ?? '');
$fLote = trim($_GET['lote'] ?? '');
$fNumPag = trim($_GET['num_pag'] ?? '');
$fNumReg = trim($_GET['num_reg'] ?? '');
$fDocPaciente = trim($_GET['doc_paciente'] ?? '');
$fDocPersonal = trim($_GET['doc_personal'] ?? '');
$fDocRegistrador = trim($_GET['doc_registrador'] ?? '');
$fUps = trim($_GET['ups'] ?? '');
$fDepartamento = trim($_GET['departamento'] ?? '');

// ============================================================
// OPCIONES DE FILTROS
// ============================================================
$anios = $pdo->query("SELECT DISTINCT Anio FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Anio IS NOT NULL ORDER BY Anio DESC")->fetchAll(PDO::FETCH_COLUMN);

// Zona Sanitaria - basada en tabla ZSPERENE (campo MicroRed)
$zonasSanitarias = $pdo->query("SELECT DISTINCT MicroRed FROM ZSPERENE WHERE MicroRed IS NOT NULL ORDER BY MicroRed")->fetchAll(PDO::FETCH_COLUMN);

// Establecimientos - desde tabla ZSPERENE: descripcion = Nombre_Establecimiento, valor = Codigo_Unico
$establecimientosZona = [];
if ($fZonaSanitaria !== '') {
    $stmtEst = $pdo->prepare("SELECT Codigo_Unico, Nombre_Establecimiento FROM ZSPERENE WHERE MicroRed = :microred ORDER BY Nombre_Establecimiento");
    $stmtEst->execute([':microred' => $fZonaSanitaria]);
    $establecimientosZona = $stmtEst->fetchAll();
} else {
    $establecimientosZona = $pdo->query("SELECT Codigo_Unico, Nombre_Establecimiento FROM ZSPERENE ORDER BY Nombre_Establecimiento")->fetchAll();
}

// Establecimientos desde consolidado (para la subpagina preventivas que no usa zona sanitaria)
$establecimientos = $pdo->query("SELECT DISTINCT Nombre_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Nombre_Establecimiento IS NOT NULL ORDER BY Nombre_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);

// Grupo de Edad dinamico desde la tabla
$gruposEdad = $pdo->query("SELECT DISTINCT Grupo_Edad FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Grupo_Edad IS NOT NULL AND Grupo_Edad != '' ORDER BY Grupo_Edad")->fetchAll(PDO::FETCH_COLUMN);

// Genero
$generos = $pdo->query("SELECT DISTINCT Id_Genero FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Id_Genero IS NOT NULL ORDER BY Id_Genero")->fetchAll(PDO::FETCH_COLUMN);

// Descripcion_Otra_Condicion
$otrasCondiciones = $pdo->query("SELECT DISTINCT Descripcion_Otra_Condicion FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Descripcion_Otra_Condicion IS NOT NULL AND Descripcion_Otra_Condicion != '' ORDER BY Descripcion_Otra_Condicion")->fetchAll(PDO::FETCH_COLUMN);

// Tipos de Diagnostico
$tiposDiagnostico = $pdo->query("SELECT DISTINCT Tipo_Diagnostico FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Tipo_Diagnostico IS NOT NULL ORDER BY Tipo_Diagnostico")->fetchAll(PDO::FETCH_COLUMN);

// Departamentos
$departamentos = $pdo->query("SELECT DISTINCT Departamento_Establecimiento FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Departamento_Establecimiento IS NOT NULL ORDER BY Departamento_Establecimiento")->fetchAll(PDO::FETCH_COLUMN);

// UPS para preventivas
$upsPreventivas = [];
try {
    $upsPreventivas = $pdo->query("SELECT DISTINCT Id_Ups, Descripcion_Ups FROM T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO WHERE Descripcion_Ups LIKE '%PREVENT%' OR Descripcion_Ups LIKE '%PROMOC%' OR Fg_Tipo = 'P' ORDER BY Descripcion_Ups LIMIT 200")->fetchAll();
} catch (Exception $e) {
    $upsPreventivas = [];
}

$hayFiltros = ($fAnio !== '' || $fMes !== '' || $fZonaSanitaria !== '' || $fEstablecimiento !== '' || $fGrupoEdad !== ''
    || $fIdGenero !== '' || $fOtraCondicion !== '' || $fTipoDiagnostico !== '' || $fCodigoItem !== ''
    || $fLote !== '' || $fNumPag !== '' || $fNumReg !== '' || $fDocPaciente !== ''
    || $fDocPersonal !== '' || $fDocRegistrador !== '' || $fUps !== '' || $fDepartamento !== '');

// ============================================================
// CONSTRUCCION DE WHERE
// ============================================================
$where = "1=1";
$params = [];
if ($fAnio !== '') {
    $where .= " AND Anio = :anio"; $params[':anio'] = $fAnio;
}
if ($fMes !== '') {
    $where .= " AND CAST(TRIM(Mes) AS UNSIGNED) = :mes"; $params[':mes'] = intval($fMes);
}
if ($fZonaSanitaria !== '') {
    // Filtrar registros cuyo Codigo_Unico exista en ZSPERENE para la MicroRed seleccionada
    $where .= " AND Codigo_Unico IN (SELECT Codigo_Unico FROM ZSPERENE WHERE MicroRed = :microred)";
    $params[':microred'] = $fZonaSanitaria;
}
if ($fEstablecimiento !== '') {
    // Refinar por establecimiento especifico (Codigo_Unico)
    $where .= " AND Codigo_Unico = :est"; $params[':est'] = $fEstablecimiento;
}
if ($fGrupoEdad !== '') {
    $where .= " AND Grupo_Edad = :gedad"; $params[':gedad'] = $fGrupoEdad;
}
if ($fIdGenero !== '') {
    $where .= " AND Id_Genero = :genero"; $params[':genero'] = $fIdGenero;
}
if ($fOtraCondicion !== '') {
    $where .= " AND Descripcion_Otra_Condicion = :otra_cond"; $params[':otra_cond'] = $fOtraCondicion;
}
if ($fTipoDiagnostico !== '') {
    $where .= " AND Tipo_Diagnostico = :td"; $params[':td'] = $fTipoDiagnostico;
}
if ($fCodigoItem !== '') {
    $where .= " AND Codigo_Item LIKE :citem"; $params[':citem'] = '%' . $fCodigoItem . '%';
}
if ($fLote !== '') {
    $where .= " AND Lote = :lote"; $params[':lote'] = $fLote;
}
if ($fNumPag !== '') {
    $where .= " AND Num_Pag = :numpag"; $params[':numpag'] = intval($fNumPag);
}
if ($fNumReg !== '') {
    $where .= " AND Num_Reg = :numreg"; $params[':numreg'] = intval($fNumReg);
}
if ($fDocPaciente !== '') {
    $where .= " AND Numero_Documento_Paciente LIKE :dpac"; $params[':dpac'] = '%' . $fDocPaciente . '%';
}
if ($fDocPersonal !== '') {
    $where .= " AND Numero_Documento_Personal LIKE :dper"; $params[':dper'] = '%' . $fDocPersonal . '%';
}
if ($fDocRegistrador !== '') {
    $where .= " AND Numero_Documento_Registrador LIKE :dreg"; $params[':dreg'] = '%' . $fDocRegistrador . '%';
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

// ============================================================
// CONSULTA DE DATOS
// ============================================================
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

    // Campos ampliados segun requerimiento para la tabla general
    $campos = "Id_Cita, Anio, Mes, Dia, Fecha_Atencion,
               Lote, Num_Pag, Num_Reg,
               Codigo_Unico, Nombre_Establecimiento,
               Abrev_Tipo_Doc_Paciente, Numero_Documento_Paciente,
               Nombres_Paciente, Apellido_Paterno_Paciente,
               Fecha_Nacimiento_Paciente, Id_Genero, Tipo_Edad, Edad_Reg,
               Grupo_Edad,
               Codigo_Item, Descripcion_Item, Tipo_Diagnostico, Valor_Lab, Fg_Tipo,
               Descripcion_Ups,
               Numero_Documento_Personal, Nombres_Personal, Apellido_Paterno_Personal,
               Descripcion_Profesion,
               Numero_Documento_Registrador, Nombres_Registrador, Apellido_Paterno_Registrador,
               Fecha_Registro, Fecha_Modificacion";

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

            <?php if ($sub === 'general'): ?>
            <!-- ============================================ -->
            <!-- FILTROS SUB-PAGINA GENERAL (mejorado)       -->
            <!-- ============================================ -->
            <div class="row g-3">
                <!-- Fila 1: Anio, Mes, Zona Sanitaria, Establecimiento -->
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
                    <label class="form-label fw-semibold">Zona Sanitaria</label>
                    <select name="zona_sanitaria" id="zonaSanitaria" class="form-select form-select-sm">
                        <?php foreach ($zonasSanitarias as $zs): ?>
                            <option value="<?= htmlspecialchars($zs) ?>" <?= $fZonaSanitaria === $zs ? 'selected' : '' ?>><?= htmlspecialchars($zs) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Establecimiento</label>
                    <select name="establecimiento" id="establecimiento" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($establecimientosZona as $ez): ?>
                            <option value="<?= htmlspecialchars($ez['Codigo_Unico']) ?>" <?= $fEstablecimiento === $ez['Codigo_Unico'] ? 'selected' : '' ?>><?= htmlspecialchars($ez['Nombre_Establecimiento']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Fila 2: Grupo Edad, Genero, Otra Condicion, Codigo Item -->
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
                    <label class="form-label fw-semibold">Genero</label>
                    <select name="id_genero" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($generos as $g): ?>
                            <option value="<?= htmlspecialchars($g) ?>" <?= $fIdGenero === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Otra Condicion</label>
                    <select name="otra_condicion" class="form-select form-select-sm">
                        <option value="">-- Todos --</option>
                        <?php foreach ($otrasCondiciones as $oc): ?>
                            <option value="<?= htmlspecialchars($oc) ?>" <?= $fOtraCondicion === $oc ? 'selected' : '' ?>><?= htmlspecialchars($oc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Codigo Item</label>
                    <input type="text" name="codigo_item" class="form-control form-control-sm" placeholder="Ej: CIE10" value="<?= htmlspecialchars($fCodigoItem) ?>">
                </div>

                <!-- Fila 3: Tipo Diagnostico, Lote, Num Pag, Num Reg -->
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
                    <label class="form-label fw-semibold">Lote</label>
                    <input type="text" name="lote" class="form-control form-control-sm" placeholder="Nro. Lote" value="<?= htmlspecialchars($fLote) ?>">
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Num. Pag</label>
                    <input type="text" name="num_pag" class="form-control form-control-sm" placeholder="Num. Pagina" value="<?= htmlspecialchars($fNumPag) ?>">
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Num. Reg</label>
                    <input type="text" name="num_reg" class="form-control form-control-sm" placeholder="Num. Registro" value="<?= htmlspecialchars($fNumReg) ?>">
                </div>

                <!-- Fila 4: Doc. Paciente, Doc. Personal, Doc. Registrador -->
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Doc. Paciente</label>
                    <input type="text" name="doc_paciente" class="form-control form-control-sm" placeholder="Nro. Documento" value="<?= htmlspecialchars($fDocPaciente) ?>">
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Doc. Personal</label>
                    <input type="text" name="doc_personal" class="form-control form-control-sm" placeholder="Nro. Documento" value="<?= htmlspecialchars($fDocPersonal) ?>">
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label fw-semibold">Doc. Registrador</label>
                    <input type="text" name="doc_registrador" class="form-control form-control-sm" placeholder="Nro. Documento" value="<?= htmlspecialchars($fDocRegistrador) ?>">
                </div>
            </div>

            <?php else: ?>
            <!-- ============================================ -->
            <!-- FILTROS SUB-PAGINA PREVENTIVAS (original)   -->
            <!-- ============================================ -->
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
                <?php if (!empty($upsPreventivas)): ?>
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
            <?php endif; ?>
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
                <table class="table table-hover table-sm mb-0" style="font-size: 0.8rem;">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Fecha Atencion</th>
                            <th>Establecimiento</th>
                            <th>Lote</th>
                            <th>Num. Pag</th>
                            <th>Num. Reg</th>
                            <th>T. Doc.</th>
                            <th>Doc. Paciente</th>
                            <th>Nombres Paciente</th>
                            <th>Ap. Paterno Pac.</th>
                            <th>Genero</th>
                            <th>T. Edad</th>
                            <th>Edad</th>
                            <th>F. Nacimiento</th>
                            <th>Cod. Item</th>
                            <th>Descripcion Item</th>
                            <th>T. Diag.</th>
                            <th>Valor Lab</th>
                            <th>Tipo</th>
                            <th>UPS</th>
                            <th>Nombres Personal</th>
                            <th>Ap. Paterno Per.</th>
                            <th>Profesion</th>
                            <th>Nombres Registrador</th>
                            <th>Ap. Paterno Reg.</th>
                            <th>F. Registro</th>
                            <th>F. Modificacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datos as $i => $row): ?>
                        <tr>
                            <td class="text-muted"><?= $offset + $i + 1 ?></td>
                            <td><?= formatDate($row['Fecha_Atencion']) ?></td>
                            <td title="<?= clean($row['Nombre_Establecimiento']) ?>">
                                <?= clean(mb_strimwidth($row['Nombre_Establecimiento'] ?? '', 0, 25, '...')) ?>
                            </td>
                            <td><?= clean($row['Lote']) ?></td>
                            <td><?= clean($row['Num_Pag']) ?></td>
                            <td><?= clean($row['Num_Reg']) ?></td>
                            <td><small><?= clean($row['Abrev_Tipo_Doc_Paciente']) ?></small></td>
                            <td><?= clean($row['Numero_Documento_Paciente']) ?></td>
                            <td title="<?= clean($row['Nombres_Paciente']) ?>">
                                <?= clean(mb_strimwidth($row['Nombres_Paciente'] ?? '', 0, 20, '...')) ?>
                            </td>
                            <td title="<?= clean($row['Apellido_Paterno_Paciente']) ?>">
                                <?= clean(mb_strimwidth($row['Apellido_Paterno_Paciente'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td><small><?= clean($row['Id_Genero']) ?></small></td>
                            <td><small><?= clean($row['Tipo_Edad']) ?></small></td>
                            <td><?= clean($row['Edad_Reg']) ?></td>
                            <td><?= formatDate($row['Fecha_Nacimiento_Paciente']) ?></td>
                            <td><code><?= clean($row['Codigo_Item']) ?></code></td>
                            <td title="<?= clean($row['Descripcion_Item']) ?>">
                                <?= clean(mb_strimwidth($row['Descripcion_Item'] ?? '', 0, 25, '...')) ?>
                            </td>
                            <td><small><?= clean($row['Tipo_Diagnostico']) ?></small></td>
                            <td><?= clean($row['Valor_Lab']) ?></td>
                            <td>
                                <?php if (($row['Fg_Tipo'] ?? '') === 'P'): ?>
                                    <span class="badge bg-success">P</span>
                                <?php elseif (($row['Fg_Tipo'] ?? '') === 'D'): ?>
                                    <span class="badge bg-info text-dark">D</span>
                                <?php else: ?>
                                    <small class="text-muted"><?= clean($row['Fg_Tipo']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td title="<?= clean($row['Descripcion_Ups'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Descripcion_Ups'] ?? '', 0, 20, '...')) ?>
                            </td>
                            <td title="<?= clean($row['Nombres_Personal'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Nombres_Personal'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td title="<?= clean($row['Apellido_Paterno_Personal'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Apellido_Paterno_Personal'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td title="<?= clean($row['Descripcion_Profesion'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Descripcion_Profesion'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td title="<?= clean($row['Nombres_Registrador'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Nombres_Registrador'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td title="<?= clean($row['Apellido_Paterno_Registrador'] ?? '') ?>">
                                <?= clean(mb_strimwidth($row['Apellido_Paterno_Registrador'] ?? '', 0, 18, '...')) ?>
                            </td>
                            <td><small><?= formatDateTime($row['Fecha_Registro']) ?></small></td>
                            <td><small><?= formatDateTime($row['Fecha_Modificacion']) ?></small></td>
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

<!-- Script para cascada Zona Sanitaria -> Establecimiento -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var zonaSelect = document.getElementById('zonaSanitaria');
    var estaSelect = document.getElementById('establecimiento');

    if (zonaSelect && estaSelect) {
        zonaSelect.addEventListener('change', function() {
            var microred = this.value;
            // Mostrar cargando
            estaSelect.innerHTML = '<option value="">Cargando...</option>';

            fetch('api_establecimientos.php?microred=' + encodeURIComponent(microred))
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    estaSelect.innerHTML = '<option value="">-- Todos --</option>';
                    if (data && data.length > 0) {
                        data.forEach(function(est) {
                            var opt = document.createElement('option');
                            opt.value = est.Codigo_Unico;
                            opt.textContent = est.Nombre_Establecimiento;
                            estaSelect.appendChild(opt);
                        });
                    }
                })
                .catch(function() {
                    estaSelect.innerHTML = '<option value="">-- Error al cargar --</option>';
                });
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
