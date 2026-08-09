<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: Configuracion ESNI (Admin)
 *
 * Permite gestionar de forma dinamica (sin tocar codigo) todas las entidades
 * que antes estaban hard-codeadas en los Stored Procedures T-SQL:
 *   - Vacunas (BCG, Hepatitis B, Pentavalente, ...)
 *   - Dosis (1ra, 2da, 3ra, Unica, Refuerzo, ...)
 *   - Grupos de Edad (24H, 28D, 01-11M, 02-04A, Gestantes, Riesgo, ...)
 *   - Secciones del Reporte (A, B, C, H, H2, I, J, K, L, M, N, O, P, VPH)
 *   - Lineas dentro de cada seccion (vacuna + dosis + grupo edad + texto)
 *   - Reglas de mapeo (cod_item + valor_lab + edad -> linea)
 *
 * Solo accesible para rol admin.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
verificarAdmin();
require_once 'includes/functions.php';
require_once 'includes/esni_data.php';

$pdo = getDBConnection();
$esquemaOK = esniEsquemaInstalado($pdo);

$tab = $_GET['tab'] ?? 'vacunas';
$tab = in_array($tab, ['vacunas','dosis','grupos','secciones','lineas','reglas','parametros']) ? $tab : 'vacunas';

$mensaje = '';
$mensajeTipo = '';

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $esquemaOK) {
    $accion = $_POST['accion'] ?? '';
    try {
        switch ($accion) {
            case 'crear_vacuna':
                esniCrearVacuna($pdo, $_POST['codigo'], $_POST['nombre'], $_POST['descripcion'] ?? '', $_POST['color'] ?? '#0d6efd');
                $mensaje = "Vacuna '{$_POST['nombre']}' creada correctamente.";
                $mensajeTipo = 'success';
                break;
            case 'editar_vacuna':
                $stmt = $pdo->prepare("UPDATE ESNI_VACUNA SET codigo=?, nombre=?, descripcion=?, color=?, activo=? WHERE id_vacuna=?");
                $stmt->execute([$_POST['codigo'], $_POST['nombre'], $_POST['descripcion'], $_POST['color'], isset($_POST['activo'])?1:0, $_POST['id_vacuna']]);
                $mensaje = "Vacuna actualizada.";
                $mensajeTipo = 'success';
                break;
            case 'eliminar_vacuna':
                $stmt = $pdo->prepare("DELETE FROM ESNI_VACUNA WHERE id_vacuna=?");
                $stmt->execute([$_POST['id_vacuna']]);
                $mensaje = "Vacuna eliminada.";
                $mensajeTipo = 'success';
                break;

            case 'crear_dosis':
                esniCrearDosis($pdo, $_POST['codigo'], $_POST['nombre'], (int)($_POST['orden'] ?? 0));
                $mensaje = "Dosis creada.";
                $mensajeTipo = 'success';
                break;
            case 'editar_dosis':
                $stmt = $pdo->prepare("UPDATE ESNI_DOSIS SET codigo=?, nombre=?, orden=?, activo=? WHERE id_dosis=?");
                $stmt->execute([$_POST['codigo'], $_POST['nombre'], (int)$_POST['orden'], isset($_POST['activo'])?1:0, $_POST['id_dosis']]);
                $mensaje = "Dosis actualizada.";
                $mensajeTipo = 'success';
                break;
            case 'eliminar_dosis':
                $stmt = $pdo->prepare("DELETE FROM ESNI_DOSIS WHERE id_dosis=?");
                $stmt->execute([$_POST['id_dosis']]);
                $mensaje = "Dosis eliminada.";
                $mensajeTipo = 'success';
                break;

            case 'crear_grupo':
                esniCrearGrupoEdad($pdo, $_POST['codigo'], $_POST['nombre'], $_POST['tipo_edad'],
                    $_POST['edad_min'] !== '' ? (int)$_POST['edad_min'] : null,
                    $_POST['edad_max'] !== '' ? (int)$_POST['edad_max'] : null);
                $mensaje = "Grupo de edad creado.";
                $mensajeTipo = 'success';
                break;
            case 'editar_grupo':
                $stmt = $pdo->prepare("UPDATE ESNI_GRUPO_EDAD SET codigo=?, nombre=?, tipo_edad=?, edad_min=?, edad_max=?, activo=? WHERE id_grupo_edad=?");
                $stmt->execute([$_POST['codigo'], $_POST['nombre'], $_POST['tipo_edad'],
                    $_POST['edad_min'] !== '' ? (int)$_POST['edad_min'] : null,
                    $_POST['edad_max'] !== '' ? (int)$_POST['edad_max'] : null,
                    isset($_POST['activo'])?1:0, $_POST['id_grupo_edad']]);
                $mensaje = "Grupo de edad actualizado.";
                $mensajeTipo = 'success';
                break;
            case 'eliminar_grupo':
                $stmt = $pdo->prepare("DELETE FROM ESNI_GRUPO_EDAD WHERE id_grupo_edad=?");
                $stmt->execute([$_POST['id_grupo_edad']]);
                $mensaje = "Grupo de edad eliminado.";
                $mensajeTipo = 'success';
                break;

            case 'crear_seccion':
                esniCrearSeccion($pdo, $_POST['codigo'], $_POST['titulo'], $_POST['descripcion'] ?? '', $_POST['layout'], (int)($_POST['orden'] ?? 99));
                $mensaje = "Seccion creada.";
                $mensajeTipo = 'success';
                break;
            case 'editar_seccion':
                $stmt = $pdo->prepare("UPDATE ESNI_SECCION_REPORTE SET codigo=?, titulo=?, descripcion=?, layout=?, orden=?, activo=? WHERE id_seccion=?");
                $stmt->execute([$_POST['codigo'], $_POST['titulo'], $_POST['descripcion'], $_POST['layout'], (int)$_POST['orden'], isset($_POST['activo'])?1:0, $_POST['id_seccion']]);
                $mensaje = "Seccion actualizada.";
                $mensajeTipo = 'success';
                break;
            case 'eliminar_seccion':
                $stmt = $pdo->prepare("DELETE FROM ESNI_SECCION_REPORTE WHERE id_seccion=?");
                $stmt->execute([$_POST['id_seccion']]);
                $mensaje = "Seccion eliminada (y sus lineas en cascada).";
                $mensajeTipo = 'success';
                break;

            case 'crear_linea':
                esniCrearLinea($pdo, (int)$_POST['id_seccion'], $_POST['etiqueta'],
                    $_POST['id_vacuna'] !== '' ? (int)$_POST['id_vacuna'] : null,
                    $_POST['id_dosis'] !== '' ? (int)$_POST['id_dosis'] : null,
                    $_POST['id_grupo_edad'] !== '' ? (int)$_POST['id_grupo_edad'] : null,
                    $_POST['sexo'] ?? 'A', (int)($_POST['orden'] ?? 99));
                $mensaje = "Linea creada.";
                $mensajeTipo = 'success';
                break;
            case 'editar_linea':
                $stmt = $pdo->prepare("UPDATE ESNI_LINEA_REPORTE SET id_seccion=?, orden=?, etiqueta=?, id_vacuna=?, id_dosis=?, id_grupo_edad=?, sexo=?, activo=? WHERE id_linea=?");
                $stmt->execute([(int)$_POST['id_seccion'], (int)$_POST['orden'], $_POST['etiqueta'],
                    $_POST['id_vacuna'] !== '' ? (int)$_POST['id_vacuna'] : null,
                    $_POST['id_dosis'] !== '' ? (int)$_POST['id_dosis'] : null,
                    $_POST['id_grupo_edad'] !== '' ? (int)$_POST['id_grupo_edad'] : null,
                    $_POST['sexo'], isset($_POST['activo'])?1:0, $_POST['id_linea']]);
                $mensaje = "Linea actualizada.";
                $mensajeTipo = 'success';
                break;
            case 'eliminar_linea':
                $stmt = $pdo->prepare("DELETE FROM ESNI_LINEA_REPORTE WHERE id_linea=?");
                $stmt->execute([$_POST['id_linea']]);
                $mensaje = "Linea eliminada (y sus reglas en cascada).";
                $mensajeTipo = 'success';
                break;

            case 'crear_regla':
                esniCrearRegla($pdo, (int)$_POST['id_linea'], $_POST['cod_item'],
                    $_POST['valor_lab'] !== '' ? $_POST['valor_lab'] : null,
                    $_POST['id_grupo_edad'] !== '' ? (int)$_POST['id_grupo_edad'] : null,
                    $_POST['sexo'] ?? 'A',
                    $_POST['aniomes_min'] !== '' ? $_POST['aniomes_min'] : null,
                    $_POST['aniomes_max'] !== '' ? $_POST['aniomes_max'] : null,
                    isset($_POST['requiere_riesgo'])?1:0, isset($_POST['excluye_riesgo'])?1:0,
                    isset($_POST['requiere_comorbilidad'])?1:0, isset($_POST['excluye_comorbilidad'])?1:0,
                    $_POST['requiere_valor_lab_cita'] !== '' ? $_POST['requiere_valor_lab_cita'] : null,
                    $_POST['excluye_valor_lab_cita'] !== '' ? $_POST['excluye_valor_lab_cita'] : null);
                $mensaje = "Regla creada.";
                $mensajeTipo = 'success';
                break;
            case 'editar_regla':
                // Detectar dinamicamente si las columnas nuevas existen.
                $tieneColsCita = esniColumnasReglaExisten($pdo, ['requiere_valor_lab_cita', 'excluye_valor_lab_cita']);
                if ($tieneColsCita) {
                    $stmt = $pdo->prepare("UPDATE ESNI_REGLA SET id_linea=?, cod_item=?, valor_lab=?, id_grupo_edad=?, sexo=?, aniomes_min=?, aniomes_max=?, requiere_riesgo=?, excluye_riesgo=?, requiere_comorbilidad=?, excluye_comorbilidad=?, requiere_valor_lab_cita=?, excluye_valor_lab_cita=?, activo=? WHERE id_regla=?");
                    $stmt->execute([(int)$_POST['id_linea'], $_POST['cod_item'],
                        $_POST['valor_lab'] !== '' ? $_POST['valor_lab'] : null,
                        $_POST['id_grupo_edad'] !== '' ? (int)$_POST['id_grupo_edad'] : null,
                        $_POST['sexo'],
                        $_POST['aniomes_min'] !== '' ? $_POST['aniomes_min'] : null,
                        $_POST['aniomes_max'] !== '' ? $_POST['aniomes_max'] : null,
                        isset($_POST['requiere_riesgo'])?1:0, isset($_POST['excluye_riesgo'])?1:0,
                        isset($_POST['requiere_comorbilidad'])?1:0, isset($_POST['excluye_comorbilidad'])?1:0,
                        $_POST['requiere_valor_lab_cita'] !== '' ? $_POST['requiere_valor_lab_cita'] : null,
                        $_POST['excluye_valor_lab_cita'] !== '' ? $_POST['excluye_valor_lab_cita'] : null,
                        isset($_POST['activo'])?1:0, $_POST['id_regla']]);
                } else {
                    // Migracion no aplicada: actualizar sin las columnas nuevas.
                    $stmt = $pdo->prepare("UPDATE ESNI_REGLA SET id_linea=?, cod_item=?, valor_lab=?, id_grupo_edad=?, sexo=?, aniomes_min=?, aniomes_max=?, requiere_riesgo=?, excluye_riesgo=?, requiere_comorbilidad=?, excluye_comorbilidad=?, activo=? WHERE id_regla=?");
                    $stmt->execute([(int)$_POST['id_linea'], $_POST['cod_item'],
                        $_POST['valor_lab'] !== '' ? $_POST['valor_lab'] : null,
                        $_POST['id_grupo_edad'] !== '' ? (int)$_POST['id_grupo_edad'] : null,
                        $_POST['sexo'],
                        $_POST['aniomes_min'] !== '' ? $_POST['aniomes_min'] : null,
                        $_POST['aniomes_max'] !== '' ? $_POST['aniomes_max'] : null,
                        isset($_POST['requiere_riesgo'])?1:0, isset($_POST['excluye_riesgo'])?1:0,
                        isset($_POST['requiere_comorbilidad'])?1:0, isset($_POST['excluye_comorbilidad'])?1:0,
                        isset($_POST['activo'])?1:0, $_POST['id_regla']]);
                }
                $mensaje = "Regla actualizada.";
                $mensajeTipo = 'success';
                break;
            case 'eliminar_regla':
                $stmt = $pdo->prepare("DELETE FROM ESNI_REGLA WHERE id_regla=?");
                $stmt->execute([$_POST['id_regla']]);
                $mensaje = "Regla eliminada.";
                $mensajeTipo = 'success';
                break;

            case 'guardar_parametro':
                $stmt = $pdo->prepare("INSERT INTO ESNI_PARAMETRO (clave, valor, descripcion) VALUES (?, ?, ?)
                                       ON DUPLICATE KEY UPDATE valor = VALUES(valor), descripcion = VALUES(descripcion), fecha_actualizado = NOW()");
                $stmt->execute([$_POST['clave'], $_POST['valor'], $_POST['descripcion'] ?? '']);
                $mensaje = "Parametro guardado.";
                $mensajeTipo = 'success';
                break;
            case 'eliminar_parametro':
                $stmt = $pdo->prepare("DELETE FROM ESNI_PARAMETRO WHERE clave=?");
                $stmt->execute([$_POST['clave']]);
                $mensaje = "Parametro eliminado.";
                $mensajeTipo = 'success';
                break;
        }
    } catch (Throwable $e) {
        $mensaje = "Error: " . $e->getMessage();
        $mensajeTipo = 'danger';
    }
}

$pageTitle = 'Configuracion ESNI - Sistema HIS';
include 'includes/header.php';
?>

<style>
.config-tab { color:#fff !important; border-bottom:3px solid transparent; }
.config-tab.active { border-bottom-color:#fff; background:rgba(255,255,255,.1); }
.config-tab:hover { background:rgba(255,255,255,.08); }
.config-card { border: 1px solid #dee2e6; border-radius:.4rem; margin-bottom:.8rem; }
.config-card-header { background:#f8f9fa; padding:.5rem .8rem; font-weight:600; border-bottom:1px solid #dee2e6; display:flex; justify-content:space-between; align-items:center; }
.color-dot { width:14px; height:14px; border-radius:50%; display:inline-block; vertical-align:middle; margin-right:.4rem; border:1px solid rgba(0,0,0,.15); }
.mono-pill { font-family:'Courier New', monospace; background:#e9ecef; padding:.05rem .35rem; border-radius:.25rem; font-size:.75rem; }
</style>

<div class="page-header-section">
    <h4><i class="fas fa-cog me-2 text-primary"></i>Configuracion ESNI</h4>
    <p class="subtitle">Gestion data-driven de vacunas, dosis, grupos de edad, secciones y reglas de mapeo.</p>
</div>

<?php if (!$esquemaOK): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Esquema ESNI no instalado.</strong> Ejecute el script <code>Database/install_esni.sql</code> en su base de datos MySQL para crear las 7 tablas requeridas.
</div>
<?php include 'includes/footer.php'; exit; endif; ?>

<?php if ($mensaje): ?>
<div class="alert alert-<?= $mensajeTipo ?> alert-dismissible fade show">
    <i class="fas fa-<?= $mensajeTipo === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
    <?= htmlspecialchars($mensaje) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="reporte_esni.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Volver al Reporte</a>
    <div class="btn-group btn-group-sm">
        <a href="?tab=vacunas"     class="btn btn-<?= $tab==='vacunas'?'primary':'outline-primary' ?>    config-tab">Vacunas</a>
        <a href="?tab=dosis"       class="btn btn-<?= $tab==='dosis'?'primary':'outline-primary' ?>      config-tab">Dosis</a>
        <a href="?tab=grupos"      class="btn btn-<?= $tab==='grupos'?'primary':'outline-primary' ?>     config-tab">Grupos Edad</a>
        <a href="?tab=secciones"   class="btn btn-<?= $tab==='secciones'?'primary':'outline-primary' ?>  config-tab">Secciones</a>
        <a href="?tab=lineas"      class="btn btn-<?= $tab==='lineas'?'primary':'outline-primary' ?>     config-tab">Lineas</a>
        <a href="?tab=reglas"      class="btn btn-<?= $tab==='reglas'?'primary':'outline-primary' ?>     config-tab">Reglas</a>
        <a href="?tab=parametros"  class="btn btn-<?= $tab==='parametros'?'primary':'outline-primary' ?> config-tab">Parametros</a>
    </div>
</div>

<?php
// ====== TAB: VACUNAS ======
if ($tab === 'vacunas'):
    $vacunas = $pdo->query("SELECT * FROM ESNI_VACUNA ORDER BY codigo")->fetchAll();
?>
<div class="row">
    <div class="col-lg-4">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-plus me-2"></i>Nueva Vacuna</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_vacuna">
                    <div class="mb-2"><label class="form-label small fw-semibold">Codigo</label><input type="text" name="codigo" class="form-control form-control-sm" required placeholder="ej: BCG, HVB, PENTA"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" required placeholder="ej: BCG, Hepatitis Viral B"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Descripcion</label><input type="text" name="descripcion" class="form-control form-control-sm" placeholder="Descripcion opcional"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Color (UI)</label><input type="color" name="color" class="form-control form-control-color" value="#0d6efd"></div>
                    <button class="btn btn-sm btn-his w-100"><i class="fas fa-save me-1"></i>Crear Vacuna</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-list me-2"></i>Vacunas Configuradas (<?= count($vacunas) ?>)</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>ID</th><th>Codigo</th><th>Nombre</th><th>Color</th><th>Estado</th><th class="text-end">Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vacunas as $v): ?>
                        <tr>
                            <td class="text-muted small"><?= $v['id_vacuna'] ?></td>
                            <td><span class="mono-pill"><?= clean($v['codigo']) ?></span></td>
                            <td>
                                <span class="color-dot" style="background:<?= clean($v['color']) ?>"></span>
                                <?= clean($v['nombre']) ?>
                                <?php if ($v['descripcion']): ?><small class="text-muted d-block"><?= clean($v['descripcion']) ?></small><?php endif; ?>
                            </td>
                            <td><code><?= clean($v['color']) ?></code></td>
                            <td><?= $v['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-tipo="vacuna" data-id="<?= $v['id_vacuna'] ?>"
                                    data-codigo="<?= clean($v['codigo']) ?>" data-nombre="<?= clean($v['nombre']) ?>"
                                    data-descripcion="<?= clean($v['descripcion']) ?>" data-color="<?= clean($v['color']) ?>"
                                    data-activo="<?= $v['activo'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar vacuna? Tambien se eliminaran sus lineas y reglas asociadas.')">
                                    <input type="hidden" name="accion" value="eliminar_vacuna">
                                    <input type="hidden" name="id_vacuna" value="<?= $v['id_vacuna'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// ====== TAB: DOSIS ======
elseif ($tab === 'dosis'):
    $dosis = $pdo->query("SELECT * FROM ESNI_DOSIS ORDER BY orden, codigo")->fetchAll();
?>
<div class="row">
    <div class="col-lg-4">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-plus me-2"></i>Nueva Dosis</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_dosis">
                    <div class="mb-2"><label class="form-label small fw-semibold">Codigo</label><input type="text" name="codigo" class="form-control form-control-sm" required placeholder="ej: D1, D2, D3, DU, REF1"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" required placeholder="ej: 1ra Dosis, Dosis Unica"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Orden</label><input type="number" name="orden" class="form-control form-control-sm" value="0"></div>
                    <button class="btn btn-sm btn-his w-100"><i class="fas fa-save me-1"></i>Crear Dosis</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-list me-2"></i>Dosis Configuradas (<?= count($dosis) ?>)</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Codigo</th><th>Nombre</th><th>Orden</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($dosis as $d): ?>
                        <tr>
                            <td class="text-muted small"><?= $d['id_dosis'] ?></td>
                            <td><span class="mono-pill"><?= clean($d['codigo']) ?></span></td>
                            <td><?= clean($d['nombre']) ?></td>
                            <td><?= $d['orden'] ?></td>
                            <td><?= $d['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-tipo="dosis" data-id="<?= $d['id_dosis'] ?>" data-codigo="<?= clean($d['codigo']) ?>"
                                    data-nombre="<?= clean($d['nombre']) ?>" data-orden="<?= $d['orden'] ?>" data-activo="<?= $d['activo'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar dosis?')">
                                    <input type="hidden" name="accion" value="eliminar_dosis">
                                    <input type="hidden" name="id_dosis" value="<?= $d['id_dosis'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// ====== TAB: GRUPOS EDAD ======
elseif ($tab === 'grupos'):
    $grupos = $pdo->query("SELECT * FROM ESNI_GRUPO_EDAD ORDER BY tipo_edad, edad_min, codigo")->fetchAll();
    $tipos = ['D'=>'Dias','M'=>'Meses','A'=>'Anios','R'=>'Rango especial'];
?>
<div class="row">
    <div class="col-lg-4">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-plus me-2"></i>Nuevo Grupo de Edad</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_grupo">
                    <div class="mb-2"><label class="form-label small fw-semibold">Codigo</label><input type="text" name="codigo" class="form-control form-control-sm" required placeholder="ej: 24H, 01_11M, 02_04A, GEST"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" required placeholder="ej: 24 horas, 01 a 11 meses"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Tipo edad</label>
                        <select name="tipo_edad" class="form-select form-select-sm">
                            <?php foreach ($tipos as $k => $t): ?><option value="<?= $k ?>"><?= $t ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2"><label class="form-label small fw-semibold">Edad min</label><input type="number" name="edad_min" class="form-control form-control-sm"></div>
                        <div class="col-6 mb-2"><label class="form-label small fw-semibold">Edad max</label><input type="number" name="edad_max" class="form-control form-control-sm"></div>
                    </div>
                    <button class="btn btn-sm btn-his w-100"><i class="fas fa-save me-1"></i>Crear Grupo</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-list me-2"></i>Grupos de Edad (<?= count($grupos) ?>)</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Codigo</th><th>Nombre</th><th>Tipo</th><th>Rango</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($grupos as $g): ?>
                        <tr>
                            <td class="text-muted small"><?= $g['id_grupo_edad'] ?></td>
                            <td><span class="mono-pill"><?= clean($g['codigo']) ?></span></td>
                            <td><?= clean($g['nombre']) ?></td>
                            <td><small><?= $tipos[$g['tipo_edad']] ?? $g['tipo_edad'] ?></small></td>
                            <td><small><?= $g['edad_min'] !== null ? $g['edad_min'] : '-' ?> - <?= $g['edad_max'] !== null ? $g['edad_max'] : '-' ?></small></td>
                            <td><?= $g['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-tipo="grupo" data-id="<?= $g['id_grupo_edad'] ?>" data-codigo="<?= clean($g['codigo']) ?>"
                                    data-nombre="<?= clean($g['nombre']) ?>" data-tipo-edad="<?= $g['tipo_edad'] ?>"
                                    data-edad-min="<?= $g['edad_min'] ?>" data-edad-max="<?= $g['edad_max'] ?>" data-activo="<?= $g['activo'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar grupo de edad?')">
                                    <input type="hidden" name="accion" value="eliminar_grupo">
                                    <input type="hidden" name="id_grupo_edad" value="<?= $g['id_grupo_edad'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// ====== TAB: SECCIONES ======
elseif ($tab === 'secciones'):
    $secciones = $pdo->query("SELECT s.*, COUNT(l.id_linea) AS num_lineas FROM ESNI_SECCION_REPORTE s LEFT JOIN ESNI_LINEA_REPORTE l ON l.id_seccion=s.id_seccion GROUP BY s.id_seccion ORDER BY s.orden, s.codigo")->fetchAll();
    $layouts = ['lista'=>'Lista simple','matriz_dosis'=>'Matriz por dosis','matriz_edad'=>'Matriz por edad','total_uno'=>'Total unico','matriz_sexo'=>'Matriz por sexo'];
?>
<div class="row">
    <div class="col-lg-4">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-plus me-2"></i>Nueva Seccion</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_seccion">
                    <div class="mb-2"><label class="form-label small fw-semibold">Codigo</label><input type="text" name="codigo" class="form-control form-control-sm" required placeholder="ej: A, B, H, VPH, o nuevo: Q2"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Titulo</label><input type="text" name="titulo" class="form-control form-control-sm" required placeholder="ej: A. - MENORES DE 01 ANIO"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Descripcion</label><textarea name="descripcion" class="form-control form-control-sm" rows="2"></textarea></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Layout</label>
                        <select name="layout" class="form-select form-select-sm">
                            <?php foreach ($layouts as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Orden</label><input type="number" name="orden" class="form-control form-control-sm" value="99"></div>
                    <button class="btn btn-sm btn-his w-100"><i class="fas fa-save me-1"></i>Crear Seccion</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-list me-2"></i>Secciones del Reporte (<?= count($secciones) ?>)</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Cod</th><th>Titulo</th><th>Layout</th><th>Orden</th><th>Lineas</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($secciones as $s): ?>
                        <tr>
                            <td class="text-muted small"><?= $s['id_seccion'] ?></td>
                            <td><span class="mono-pill"><?= clean($s['codigo']) ?></span></td>
                            <td><?= clean($s['titulo']) ?><br><small class="text-muted"><?= clean($s['descripcion']) ?></small></td>
                            <td><small><?= $layouts[$s['layout']] ?? $s['layout'] ?></small></td>
                            <td><?= $s['orden'] ?></td>
                            <td><a href="?tab=lineas&filter_seccion=<?= $s['id_seccion'] ?>" class="badge bg-info"><?= $s['num_lineas'] ?></a></td>
                            <td><?= $s['activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-tipo="seccion" data-id="<?= $s['id_seccion'] ?>" data-codigo="<?= clean($s['codigo']) ?>"
                                    data-titulo="<?= clean($s['titulo']) ?>" data-descricao="<?= clean($s['descripcion']) ?>"
                                    data-layout="<?= $s['layout'] ?>" data-orden="<?= $s['orden'] ?>" data-activo="<?= $s['activo'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar seccion? Se eliminaran todas sus lineas y reglas en cascada.')">
                                    <input type="hidden" name="accion" value="eliminar_seccion">
                                    <input type="hidden" name="id_seccion" value="<?= $s['id_seccion'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// ====== TAB: LINEAS ======
elseif ($tab === 'lineas'):
    $vacunas = $pdo->query("SELECT id_vacuna, codigo, nombre FROM ESNI_VACUNA ORDER BY codigo")->fetchAll();
    $dosis = $pdo->query("SELECT id_dosis, codigo, nombre FROM ESNI_DOSIS ORDER BY orden")->fetchAll();
    $grupos = $pdo->query("SELECT id_grupo_edad, codigo, nombre FROM ESNI_GRUPO_EDAD ORDER BY codigo")->fetchAll();
    $secciones = $pdo->query("SELECT id_seccion, codigo, titulo FROM ESNI_SECCION_REPORTE ORDER BY orden")->fetchAll();
    $fSec = $_GET['filter_seccion'] ?? '';
    $where = $fSec !== '' ? "WHERE l.id_seccion = " . (int)$fSec : "";
    $lineas = $pdo->query("SELECT l.*, v.codigo AS vacuna, v.color AS vacuna_color, d.codigo AS dose, g.codigo AS grupo_edad, s.codigo AS seccion_codigo, s.titulo AS seccion_titulo
                           FROM ESNI_LINEA_REPORTE l
                           LEFT JOIN ESNI_VACUNA v ON v.id_vacuna=l.id_vacuna
                           LEFT JOIN ESNI_DOSIS d ON d.id_dosis=l.id_dosis
                           LEFT JOIN ESNI_GRUPO_EDAD g ON g.id_grupo_edad=l.id_grupo_edad
                           INNER JOIN ESNI_SECCION_REPORTE s ON s.id_seccion=l.id_seccion
                           {$where} ORDER BY s.orden, l.orden, l.id_linea")->fetchAll();
?>
<div class="row">
    <div class="col-lg-4">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-plus me-2"></i>Nueva Linea</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_linea">
                    <div class="mb-2"><label class="form-label small fw-semibold">Seccion</label>
                        <select name="id_seccion" class="form-select form-select-sm" required>
                            <?php foreach ($secciones as $s): ?><option value="<?= $s['id_seccion'] ?>"><?= clean($s['codigo']) ?> - <?= clean($s['titulo']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Etiqueta (texto a mostrar)</label><input type="text" name="etiqueta" class="form-control form-control-sm" required placeholder="ej: BCG - 24 HORAS"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Vacuna</label>
                        <select name="id_vacuna" class="form-select form-select-sm"><option value="">-- Sin vacuna --</option>
                            <?php foreach ($vacunas as $v): ?><option value="<?= $v['id_vacuna'] ?>"><?= clean($v['codigo']) ?> - <?= clean($v['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Dosis</label>
                        <select name="id_dosis" class="form-select form-select-sm"><option value="">-- Sin dosis --</option>
                            <?php foreach ($dosis as $d): ?><option value="<?= $d['id_dosis'] ?>"><?= clean($d['codigo']) ?> - <?= clean($d['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Grupo Edad</label>
                        <select name="id_grupo_edad" class="form-select form-select-sm"><option value="">-- Sin grupo edad --</option>
                            <?php foreach ($grupos as $g): ?><option value="<?= $g['id_grupo_edad'] ?>"><?= clean($g['codigo']) ?> - <?= clean($g['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-2"><label class="form-label small fw-semibold">Sexo</label>
                            <select name="sexo" class="form-select form-select-sm">
                                <option value="A">Ambos</option><option value="F">Mujer</option><option value="M">Varon</option>
                            </select>
                        </div>
                        <div class="col-6 mb-2"><label class="form-label small fw-semibold">Orden</label><input type="number" name="orden" class="form-control form-control-sm" value="99"></div>
                    </div>
                    <button class="btn btn-sm btn-his w-100"><i class="fas fa-save me-1"></i>Crear Linea</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="config-card">
            <div class="config-card-header">
                <span><i class="fas fa-list me-2"></i>Lineas (<?= count($lineas) ?>)</span>
                <?php if ($fSec): ?><a href="?tab=lineas" class="btn btn-sm btn-link">Quitar filtro</a><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.78rem">
                    <thead class="table-light"><tr><th>ID</th><th>Sec</th><th>Etiqueta</th><th>Vac</th><th>Dosis</th><th>Edad</th><th>Sexo</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($lineas as $l): ?>
                        <tr>
                            <td class="text-muted small"><?= $l['id_linea'] ?></td>
                            <td><span class="mono-pill"><?= clean($l['seccion_codigo']) ?></span></td>
                            <td>
                                <?php if ($l['vacuna_color']): ?><span class="color-dot" style="background:<?= clean($l['vacuna_color']) ?>"></span><?php endif; ?>
                                <?= clean($l['etiqueta']) ?>
                            </td>
                            <td><small class="mono-pill"><?= clean($l['vacuna'] ?? '-') ?></small></td>
                            <td><small class="mono-pill"><?= clean($l['dose'] ?? '-') ?></small></td>
                            <td><small class="mono-pill"><?= clean($l['grupo_edad'] ?? '-') ?></small></td>
                            <td class="text-center"><?= $l['sexo'] === 'F' ? '<span class="badge bg-info">F</span>' : ($l['sexo'] === 'M' ? '<span class="badge bg-warning text-dark">V</span>' : 'A') ?></td>
                            <td class="text-end">
                                <a href="?tab=reglas&filter_linea=<?= $l['id_linea'] ?>" class="btn btn-sm btn-outline-info" title="Ver reglas"><i class="fas fa-project-diagram"></i></a>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-tipo="linea" data-id="<?= $l['id_linea'] ?>" data-etiqueta="<?= clean($l['etiqueta']) ?>"
                                    data-id-seccion="<?= $l['id_seccion'] ?>" data-id-vacuna="<?= $l['id_vacuna'] ?>"
                                    data-id-dosis="<?= $l['id_dosis'] ?>" data-id-grupo-edad="<?= $l['id_grupo_edad'] ?>"
                                    data-sexo="<?= $l['sexo'] ?>" data-orden="<?= $l['orden'] ?>" data-activo="<?= $l['activo'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar linea? Se eliminaran sus reglas en cascada.')">
                                    <input type="hidden" name="accion" value="eliminar_linea">
                                    <input type="hidden" name="id_linea" value="<?= $l['id_linea'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// ====== TAB: REGLAS ======
elseif ($tab === 'reglas'):
    $vacunas = $pdo->query("SELECT id_vacuna, codigo, nombre FROM ESNI_VACUNA ORDER BY codigo")->fetchAll();
    $dosis = $pdo->query("SELECT id_dosis, codigo, nombre FROM ESNI_DOSIS ORDER BY orden")->fetchAll();
    $grupos = $pdo->query("SELECT id_grupo_edad, codigo, nombre FROM ESNI_GRUPO_EDAD ORDER BY codigo")->fetchAll();
    $secciones = $pdo->query("SELECT id_seccion, codigo, titulo FROM ESNI_SECCION_REPORTE ORDER BY orden")->fetchAll();
    $fLin = $_GET['filter_linea'] ?? '';
    $where = $fLin !== '' ? "WHERE r.id_linea = " . (int)$fLin : "";
    // Detectar dinamicamente si las columnas nuevas (requiere/excluye_valor_lab_cita)
    // existen en ESNI_REGLA. Si la migracion no se ha aplicado, se seleccionan
    // como NULL para evitar errores SQL y permitir que la pagina siga funcionando.
    $tieneColsCita = esniColumnasReglaExisten($pdo, ['requiere_valor_lab_cita', 'excluye_valor_lab_cita']);
    $colRequiere = $tieneColsCita ? 'r.requiere_valor_lab_cita' : 'NULL AS requiere_valor_lab_cita';
    $colExcluye  = $tieneColsCita ? 'r.excluye_valor_lab_cita' : 'NULL AS excluye_valor_lab_cita';
    $reglas = $pdo->query("SELECT r.*, {$colRequiere}, {$colExcluye}, l.etiqueta, l.id_seccion, s.codigo AS seccion_codigo,
                                  g.codigo AS grupo_edad, g.nombre AS grupo_edad_nombre,
                                  g.tipo_edad AS ge_tipo_edad, g.edad_min AS ge_edad_min, g.edad_max AS ge_edad_max
                           FROM ESNI_REGLA r
                           INNER JOIN ESNI_LINEA_REPORTE l ON l.id_linea=r.id_linea
                           INNER JOIN ESNI_SECCION_REPORTE s ON s.id_seccion=l.id_seccion
                           LEFT JOIN ESNI_GRUPO_EDAD g ON g.id_grupo_edad=r.id_grupo_edad
                           {$where} ORDER BY s.orden, l.orden, r.id_regla")->fetchAll();
    $lineas = $pdo->query("SELECT l.id_linea, l.etiqueta, s.codigo AS sec FROM ESNI_LINEA_REPORTE l INNER JOIN ESNI_SECCION_REPORTE s ON s.id_seccion=l.id_seccion ORDER BY s.orden, l.orden")->fetchAll();
?>
<div class="row">
    <div class="col-lg-4">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-plus me-2"></i>Nueva Regla</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear_regla">
                    <div class="mb-2"><label class="form-label small fw-semibold">Linea del reporte</label>
                        <?php if ($fLin !== ''): ?>
                            <div class="alert alert-info py-1 px-2 mb-1 small">
                                <i class="fas fa-filter me-1"></i> Preseleccionado desde "Ver reglas" (ID: <?= (int)$fLin ?>)
                            </div>
                        <?php endif; ?>
                        <select name="id_linea" class="form-select form-select-sm" required>
                            <?php foreach ($lineas as $l):
                                $selected = ($fLin !== '' && (int)$l['id_linea'] === (int)$fLin) ? ' selected' : '';
                            ?>
                                <option value="<?= $l['id_linea'] ?>"<?= $selected ?>>[<?= clean($l['sec']) ?>] <?= clean($l['etiqueta']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label small fw-semibold">cod_item (HIS)</label><input type="text" name="cod_item" class="form-control form-control-sm" required placeholder="ej: 90585, Z232, 90744"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">valor_lab (dosis)</label><input type="text" name="valor_lab" class="form-control form-control-sm" placeholder="ej: 1, 01, D1, DU (vacio = cualquiera)"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Grupo edad requerido</label>
                        <select name="id_grupo_edad" class="form-select form-select-sm"><option value="">-- Sin restriccion --</option>
                            <?php foreach ($grupos as $g): ?><option value="<?= $g['id_grupo_edad'] ?>"><?= clean($g['codigo']) ?> - <?= clean($g['nombre']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-4 mb-2"><label class="form-label small fw-semibold">Sexo</label>
                            <select name="sexo" class="form-select form-select-sm">
                                <option value="A">Ambos</option><option value="F">Mujer</option><option value="M">Varon</option>
                            </select>
                        </div>
                        <div class="col-4 mb-2"><label class="form-label small fw-semibold">A-Mes min</label><input type="text" name="aniomes_min" class="form-control form-control-sm" placeholder="YYYYMM"></div>
                        <div class="col-4 mb-2"><label class="form-label small fw-semibold">A-Mes max</label><input type="text" name="aniomes_max" class="form-control form-control-sm" placeholder="YYYYMM"></div>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" name="requiere_riesgo" value="1" class="form-check-input" id="reqRiesgo">
                        <label class="form-check-label small" for="reqRiesgo">Requiere poblacion en riesgo (id_gruporiesgo=2)</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" name="excluye_riesgo" value="1" class="form-check-input" id="excRiesgo">
                        <label class="form-check-label small" for="excRiesgo">Excluye poblacion en riesgo</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" name="requiere_comorbilidad" value="1" class="form-check-input" id="reqComorb">
                        <label class="form-check-label small" for="reqComorb">
                            Requiere comorbilidad (paciente con otro registro <span class="mono-pill">cod_item=9999</span>)
                            <small class="text-muted d-block">ej: Influenza <strong>con</strong> Comorbilidad, Neumococo <strong>con</strong> Comorbilidad</small>
                        </label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" name="excluye_comorbilidad" value="1" class="form-check-input" id="excComorb">
                        <label class="form-check-label small" for="excComorb">
                            Excluye comorbilidad (paciente SEM registro <span class="mono-pill">cod_item=9999</span>)
                            <small class="text-muted d-block">ej: Influenza <strong>sem</strong> Comorbilidad, Neumococo <strong>sem</strong> Comorbilidad</small>
                        </label>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">
                            Requiere valor_lab en misma Id_cita
                            <small class="text-muted d-block">Solo encaja si existe OTRA fila con la misma Id_cita cuyo valor_lab sea igual al indicado. Ej: <span class="mono-pill">G</span> (gestante), <span class="mono-pill">ST</span> (personal salud).</small>
                        </label>
                        <input type="text" name="requiere_valor_lab_cita" class="form-control form-control-sm" placeholder="ej: G, ST (vacio = sin restriccion)">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">
                            Excluye valor_lab en misma Id_cita
                            <small class="text-muted d-block">Solo encaja si NO existe ninguna otra fila con la misma Id_cita cuyo valor_lab sea igual al indicado. Ej: <span class="mono-pill">G</span> (excluir gestantes).</small>
                        </label>
                        <input type="text" name="excluye_valor_lab_cita" class="form-control form-control-sm" placeholder="ej: G o G,ST (vacio = sin restriccion)">
                    </div>
                    <button class="btn btn-sm btn-his w-100"><i class="fas fa-save me-1"></i>Crear Regla</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="config-card">
            <div class="config-card-header">
                <span><i class="fas fa-list me-2"></i>Reglas de Mapeo (<?= count($reglas) ?>)</span>
                <?php if ($fLin): ?><a href="?tab=reglas" class="btn btn-sm btn-link">Quitar filtro</a><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.78rem">
                    <thead class="table-light"><tr><th>ID</th><th>Sec</th><th>Linea</th><th>cod_item</th><th>valor_lab</th><th>Edad</th><th>Sexo</th><th>Riesgo</th><th>Comorb.</th><th>V.Lab Cita</th><th>A-Mes</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($reglas as $r): ?>
                        <tr>
                            <td class="text-muted small"><?= $r['id_regla'] ?></td>
                            <td><span class="mono-pill"><?= clean($r['seccion_codigo']) ?></span></td>
                            <td title="<?= clean($r['etiqueta']) ?>"><?= clean(mb_strimwidth($r['etiqueta'], 0, 30, '...')) ?></td>
                            <td><strong class="mono-pill"><?= clean($r['cod_item']) ?></strong></td>
                            <td><span class="mono-pill"><?= clean($r['valor_lab'] ?? '(cualq)') ?></span></td>
                            <td><small class="mono-pill"><?php
                                if (!empty($r['grupo_edad'])) {
                                    $rango = '';
                                    if ($r['ge_edad_min'] !== null || $r['ge_edad_max'] !== null) {
                                        $rango = ' [' . ($r['ge_edad_min'] !== null ? $r['ge_edad_min'] : '0')
                                               . '-' . ($r['ge_edad_max'] !== null ? $r['ge_edad_max'] : '∞')
                                               . ' ' . ($r['ge_tipo_edad'] ?: '?') . ']';
                                    }
                                    echo clean($r['grupo_edad'] . $rango);
                                } else {
                                    echo '<span class="badge bg-warning text-dark" title="Esta regla no filtra por edad: contara filas de TODAS las edades que cumplan el resto de condiciones">SIN RANGO</span>';
                                }
                            ?></small></td>
                            <td class="text-center"><?= $r['sexo'] === 'F' ? 'F' : ($r['sexo'] === 'M' ? 'V' : 'A') ?></td>
                            <td class="text-center"><?= $r['requiere_riesgo'] ? '<i class="fas fa-check text-success"></i>' : ($r['excluye_riesgo'] ? '<i class="fas fa-ban text-danger"></i>' : '-') ?></td>
                            <td class="text-center" title="Requiere comorbilidad (cod_item=9999) / Excluye comorbilidad"><?= $r['requiere_comorbilidad'] ? '<i class="fas fa-virus text-warning" title="Requiere comorbilidad"></i>' : ($r['excluye_comorbilidad'] ? '<i class="fas fa-shield-virus text-primary" title="Excluye comorbilidad"></i>' : '-') ?></td>
                            <td class="text-center" title="Filtro por valor_lab en la misma Id_cita (Seccion J: G=gestante, ST=personal salud)"><?php
                                $reqC = $r['requiere_valor_lab_cita'] ?? null;
                                $excC = $r['excluye_valor_lab_cita'] ?? null;
                                if ($reqC) {
                                    echo '<span class="badge bg-info text-dark" title="Requiere que exista otra fila con valor_lab=' . clean($reqC) . ' en la misma Id_cita">req:' . clean($reqC) . '</span>';
                                } elseif ($excC) {
                                    echo '<span class="badge bg-secondary" title="Excluye si existe otra fila con valor_lab=' . clean($excC) . ' en la misma Id_cita">exc:' . clean($excC) . '</span>';
                                } else {
                                    echo '-';
                                }
                            ?></td>
                            <td><small><?= clean($r['aniomes_min'] ?? '') ?>-<?= clean($r['aniomes_max'] ?? '') ?></small></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-tipo="regla" data-id="<?= $r['id_regla'] ?>" data-id-linea="<?= $r['id_linea'] ?>"
                                    data-cod-item="<?= clean($r['cod_item']) ?>" data-valor-lab="<?= clean($r['valor_lab'] ?? '') ?>"
                                    data-id-grupo-edad="<?= $r['id_grupo_edad'] ?>" data-sexo="<?= $r['sexo'] ?>"
                                    data-aniomes-min="<?= clean($r['aniomes_min'] ?? '') ?>" data-aniomes-max="<?= clean($r['aniomes_max'] ?? '') ?>"
                                    data-requiere-riesgo="<?= $r['requiere_riesgo'] ?>" data-excluye-riesgo="<?= $r['excluye_riesgo'] ?>"
                                    data-requiere-comorbilidad="<?= $r['requiere_comorbilidad'] ?? 0 ?>" data-excluye-comorbilidad="<?= $r['excluye_comorbilidad'] ?? 0 ?>"
                                    data-requiere-valor-lab-cita="<?= clean($r['requiere_valor_lab_cita'] ?? '') ?>" data-excluye-valor-lab-cita="<?= clean($r['excluye_valor_lab_cita'] ?? '') ?>"
                                    data-activo="<?= $r['activo'] ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar regla?')">
                                    <input type="hidden" name="accion" value="eliminar_regla">
                                    <input type="hidden" name="id_regla" value="<?= $r['id_regla'] ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// ====== TAB: PARAMETROS ======
elseif ($tab === 'parametros'):
    $params = $pdo->query("SELECT * FROM ESNI_PARAMETRO ORDER BY clave")->fetchAll();
?>
<div class="row">
    <div class="col-lg-4">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-plus me-2"></i>Nuevo / Editar Parametro</span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="guardar_parametro">
                    <div class="mb-2"><label class="form-label small fw-semibold">Clave</label><input type="text" name="clave" class="form-control form-control-sm" required placeholder="ej: tabela_origen, columna_cod_item"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Valor</label><input type="text" name="valor" class="form-control form-control-sm" placeholder="ej: T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO"></div>
                    <div class="mb-2"><label class="form-label small fw-semibold">Descripcion</label><input type="text" name="descripcion" class="form-control form-control-sm"></div>
                    <button class="btn btn-sm btn-his w-100"><i class="fas fa-save me-1"></i>Guardar Parametro</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="config-card">
            <div class="config-card-header"><span><i class="fas fa-list me-2"></i>Parametros (<?= count($params) ?>)</span></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:.78rem">
                    <thead class="table-light"><tr><th>Clave</th><th>Valor</th><th>Descripcion</th><th>Actualizado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($params as $p): ?>
                        <tr>
                            <td><span class="mono-pill"><?= clean($p['clave']) ?></span></td>
                            <td><code><?= clean($p['valor']) ?></code></td>
                            <td><small class="text-muted"><?= clean($p['descripcion']) ?></small></td>
                            <td><small class="text-muted"><?= clean($p['fecha_actualizado']) ?></small></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-tipo="parametro" data-clave="<?= clean($p['clave']) ?>" data-valor="<?= clean($p['valor']) ?>"
                                    data-descricao="<?= clean($p['descripcion']) ?>"><i class="fas fa-edit"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Eliminar parametro?')">
                                    <input type="hidden" name="accion" value="eliminar_parametro">
                                    <input type="hidden" name="clave" value="<?= clean($p['clave']) ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal de edicion generico -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-his text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i><span id="modalTitle">Editar</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Formulario cargado por JS -->
            </div>
        </div>
    </div>
</div>

<script>
// JS que rellena el modal segun data-tipo
document.getElementById('editModal').addEventListener('show.bs.modal', function (event) {
    const btn = event.relatedTarget;
    const tipo = btn.getAttribute('data-tipo');
    const title = document.getElementById('modalTitle');
    const body = document.getElementById('modalBody');
    const v = (k) => btn.getAttribute('data-' + k) ?? '';
    const checked = (k) => v(k) === '1' || v(k) === 'true' ? 'checked' : '';

    let html = '';
    if (tipo === 'vacuna') {
        title.textContent = 'Editar Vacuna';
        html = `<form method="POST">
            <input type="hidden" name="accion" value="editar_vacuna">
            <input type="hidden" name="id_vacuna" value="${v('id')}">
            <div class="mb-2"><label class="form-label small fw-semibold">Codigo</label><input type="text" name="codigo" class="form-control form-control-sm" value="${v('codigo')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" value="${v('nombre')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Descripcion</label><input type="text" name="descripcion" class="form-control form-control-sm" value="${v('descripcion')}"></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Color</label><input type="color" name="color" class="form-control form-control-color" value="${v('color')}"></div>
            <div class="form-check mb-3"><input type="checkbox" name="activo" value="1" class="form-check-input" id="act" ${checked('activo')}><label class="form-check-label" for="act">Activo</label></div>
            <button class="btn btn-sm btn-his"><i class="fas fa-save me-1"></i>Guardar</button>
        </form>`;
    } else if (tipo === 'dose') {
        title.textContent = 'Editar Dose';
        html = `<form method="POST">
            <input type="hidden" name="accion" value="editar_dosis">
            <input type="hidden" name="id_dosis" value="${v('id')}">
            <div class="mb-2"><label class="form-label small fw-semibold">Codigo</label><input type="text" name="codigo" class="form-control form-control-sm" value="${v('codigo')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" value="${v('nombre')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Orden</label><input type="number" name="orden" class="form-control form-control-sm" value="${v('orden')}"></div>
            <div class="form-check mb-3"><input type="checkbox" name="activo" value="1" class="form-check-input" id="act" ${checked('activo')}><label class="form-check-label" for="act">Activo</label></div>
            <button class="btn btn-sm btn-his"><i class="fas fa-save me-1"></i>Guardar</button>
        </form>`;
    } else if (tipo === 'grupo') {
        title.textContent = 'Editar Grupo de Idade';
        html = `<form method="POST">
            <input type="hidden" name="accion" value="editar_grupo">
            <input type="hidden" name="id_grupo_edad" value="${v('id')}">
            <div class="mb-2"><label class="form-label small fw-semibold">Codigo</label><input type="text" name="codigo" class="form-control form-control-sm" value="${v('codigo')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Nombre</label><input type="text" name="nombre" class="form-control form-control-sm" value="${v('nombre')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Tipo edad</label>
                <select name="tipo_edad" class="form-select form-select-sm">
                    <option value="D" ${v('tipo-edad')==='D'?'selected':''}>Dias</option>
                    <option value="M" ${v('tipo-edad')==='M'?'selected':''}>Meses</option>
                    <option value="A" ${v('tipo-edad')==='A'?'selected':''}>Anios</option>
                    <option value="R" ${v('tipo-edad')==='R'?'selected':''}>Rango especial</option>
                </select>
            </div>
            <div class="row"><div class="col-6 mb-2"><label class="form-label small fw-semibold">Edad min</label><input type="number" name="edad_min" class="form-control form-control-sm" value="${v('edad-min')}"></div>
                <div class="col-6 mb-2"><label class="form-label small fw-semibold">Edad max</label><input type="number" name="edad_max" class="form-control form-control-sm" value="${v('edad-max')}"></div></div>
            <div class="form-check mb-3"><input type="checkbox" name="activo" value="1" class="form-check-input" id="act" ${checked('activo')}><label class="form-check-label" for="act">Activo</label></div>
            <button class="btn btn-sm btn-his"><i class="fas fa-save me-1"></i>Guardar</button>
        </form>`;
    } else if (tipo === 'seccion') {
        title.textContent = 'Editar Seccion';
        html = `<form method="POST">
            <input type="hidden" name="accion" value="editar_seccion">
            <input type="hidden" name="id_seccion" value="${v('id')}">
            <div class="mb-2"><label class="form-label small fw-semibold">Codigo</label><input type="text" name="codigo" class="form-control form-control-sm" value="${v('codigo')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Titulo</label><input type="text" name="titulo" class="form-control form-control-sm" value="${v('titulo')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Descripcion</label><textarea name="descripcion" class="form-control form-control-sm" rows="2">${v('descricao')}</textarea></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Layout</label>
                <select name="layout" class="form-select form-select-sm">
                    <option value="lista" ${v('layout')==='lista'?'selected':''}>Lista simple</option>
                    <option value="matriz_dosis" ${v('layout')==='matriz_dosis'?'selected':''}>Matriz por dosis</option>
                    <option value="matriz_edad" ${v('layout')==='matriz_edad'?'selected':''}>Matriz por edad</option>
                    <option value="total_uno" ${v('layout')==='total_uno'?'selected':''}>Total unico</option>
                    <option value="matriz_sexo" ${v('layout')==='matriz_sexo'?'selected':''}>Matriz por sexo</option>
                </select>
            </div>
            <div class="mb-2"><label class="form-label small fw-semibold">Orden</label><input type="number" name="orden" class="form-control form-control-sm" value="${v('orden')}"></div>
            <div class="form-check mb-3"><input type="checkbox" name="activo" value="1" class="form-check-input" id="act" ${checked('activo')}><label class="form-check-label" for="act">Activo</label></div>
            <button class="btn btn-sm btn-his"><i class="fas fa-save me-1"></i>Guardar</button>
        </form>`;
    } else if (tipo === 'linea') {
        // Cargar selects via AJAX no implementado - usamos valor actual
        title.textContent = 'Editar Linea';
        html = `<form method="POST">
            <input type="hidden" name="accion" value="editar_linea">
            <input type="hidden" name="id_linea" value="${v('id')}">
            <div class="mb-2"><label class="form-label small fw-semibold">ID Seccion</label><input type="number" name="id_seccion" class="form-control form-control-sm" value="${v('id-seccion')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Etiqueta</label><input type="text" name="etiqueta" class="form-control form-control-sm" value="${v('etiqueta')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">ID Vacuna</label><input type="number" name="id_vacuna" class="form-control form-control-sm" value="${v('id-vacuna')}" placeholder="0 o vacio"></div>
            <div class="mb-2"><label class="form-label small fw-semibold">ID Dosis</label><input type="number" name="id_dosis" class="form-control form-control-sm" value="${v('id-dosis')}" placeholder="0 o vacio"></div>
            <div class="mb-2"><label class="form-label small fw-semibold">ID Grupo Edad</label><input type="number" name="id_grupo_edad" class="form-control form-control-sm" value="${v('id-grupo-edad')}" placeholder="0 o vacio"></div>
            <div class="row"><div class="col-6 mb-2"><label class="form-label small fw-semibold">Sexo</label>
                <select name="sexo" class="form-select form-select-sm">
                    <option value="A" ${v('sexo')==='A'?'selected':''}>Ambos</option>
                    <option value="F" ${v('sexo')==='F'?'selected':''}>Mujer</option>
                    <option value="M" ${v('sexo')==='M'?'selected':''}>Varon</option>
                </select></div>
                <div class="col-6 mb-2"><label class="form-label small fw-semibold">Orden</label><input type="number" name="orden" class="form-control form-control-sm" value="${v('orden')}"></div></div>
            <div class="form-check mb-3"><input type="checkbox" name="activo" value="1" class="form-check-input" id="act" ${checked('activo')}><label class="form-check-label" for="act">Activo</label></div>
            <button class="btn btn-sm btn-his"><i class="fas fa-save me-1"></i>Guardar</button>
            <small class="text-muted d-block mt-2">Para cambiar las FK (vacuna/dosis/grupo), use el campo ID numerico. Para ver las opciones, vaya a las pestanas correspondientes.</small>
        </form>`;
    } else if (tipo === 'regla') {
        title.textContent = 'Editar Regla';
        html = `<form method="POST">
            <input type="hidden" name="accion" value="editar_regla">
            <input type="hidden" name="id_regla" value="${v('id')}">
            <div class="mb-2"><label class="form-label small fw-semibold">ID Linea</label><input type="number" name="id_linea" class="form-control form-control-sm" value="${v('id-linea')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">cod_item</label><input type="text" name="cod_item" class="form-control form-control-sm" value="${v('cod-item')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">valor_lab</label><input type="text" name="valor_lab" class="form-control form-control-sm" value="${v('valor-lab')}" placeholder="vacio = cualquiera"></div>
            <div class="mb-2"><label class="form-label small fw-semibold">ID Grupo Edad</label><input type="number" name="id_grupo_edad" class="form-control form-control-sm" value="${v('id-grupo-edad')}" placeholder="0 o vacio"></div>
            <div class="row"><div class="col-4 mb-2"><label class="form-label small fw-semibold">Sexo</label>
                <select name="sexo" class="form-select form-select-sm">
                    <option value="A" ${v('sexo')==='A'?'selected':''}>Ambos</option>
                    <option value="F" ${v('sexo')==='F'?'selected':''}>Mujer</option>
                    <option value="M" ${v('sexo')==='M'?'selected':''}>Varon</option>
                </select></div>
                <div class="col-4 mb-2"><label class="form-label small fw-semibold">A-Mes min</label><input type="text" name="aniomes_min" class="form-control form-control-sm" value="${v('aniomes-min')}" placeholder="YYYYMM"></div>
                <div class="col-4 mb-2"><label class="form-label small fw-semibold">A-Mes max</label><input type="text" name="aniomes_max" class="form-control form-control-sm" value="${v('aniomes-max')}" placeholder="YYYYMM"></div></div>
            <div class="form-check"><input type="checkbox" name="requiere_riesgo" value="1" class="form-check-input" id="rr" ${checked('requiere-riesgo')}><label class="form-check-label" for="rr">Requiere poblacion en riesgo</label></div>
            <div class="form-check"><input type="checkbox" name="excluye_riesgo" value="1" class="form-check-input" id="er" ${checked('excluye-riesgo')}><label class="form-check-label" for="er">Excluye poblacion en riesgo</label></div>
            <div class="form-check"><input type="checkbox" name="requiere_comorbilidad" value="1" class="form-check-input" id="rc" ${checked('requiere-comorbilidad')}><label class="form-check-label" for="rc">Requiere comorbilidad (paciente con otro registro <code>cod_item=9999</code>)</label></div>
            <div class="form-check"><input type="checkbox" name="excluye_comorbilidad" value="1" class="form-check-input" id="ec" ${checked('excluye-comorbilidad')}><label class="form-check-label" for="ec">Excluye comorbilidad (paciente SEM registro <code>cod_item=9999</code>)</label></div>
            <div class="mb-2 mt-2"><label class="form-label small fw-semibold">Requiere valor_lab en misma Id_cita
                <small class="text-muted d-block">Solo encaja si existe OTRA fila con la misma Id_cita cuyo valor_lab sea el indicado. Ej: <code>G</code> (gestante), <code>ST</code> (personal salud). Vacio = sin restriccion.</small>
            </label><input type="text" name="requiere_valor_lab_cita" class="form-control form-control-sm" value="${v('requiere-valor-lab-cita')}" placeholder="ej: G, ST"></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Excluye valor_lab en misma Id_cita
                <small class="text-muted d-block">Solo encaja si NO existe ninguna otra fila con la misma Id_cita cuyo valor_lab sea el indicado. Ej: <code>G</code> (excluir gestantes), <code>G,ST</code> (excluir gestantes y personal salud). Vacio = sin restriccion.</small>
            </label><input type="text" name="excluye_valor_lab_cita" class="form-control form-control-sm" value="${v('excluye-valor-lab-cita')}" placeholder="ej: G o G,ST"></div>
            <div class="form-check mb-3"><input type="checkbox" name="activo" value="1" class="form-check-input" id="act" ${checked('activo')}><label class="form-check-label" for="act">Activo</label></div>
            <button class="btn btn-sm btn-his"><i class="fas fa-save me-1"></i>Guardar</button>
        </form>`;
    } else if (tipo === 'parametro') {
        title.textContent = 'Editar Parametro';
        html = `<form method="POST">
            <input type="hidden" name="accion" value="guardar_parametro">
            <div class="mb-2"><label class="form-label small fw-semibold">Clave</label><input type="text" name="clave" class="form-control form-control-sm" value="${v('clave')}" required></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Valor</label><input type="text" name="valor" class="form-control form-control-sm" value="${v('valor')}"></div>
            <div class="mb-2"><label class="form-label small fw-semibold">Descripcion</label><input type="text" name="descripcion" class="form-control form-control-sm" value="${v('descripcion')}"></div>
            <button class="btn btn-sm btn-his"><i class="fas fa-save me-1"></i>Guardar</button>
        </form>`;
    }
    body.innerHTML = html;
});
</script>

<?php include 'includes/footer.php';
