<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina 08: Gestion de Usuarios (solo Administrador)
 * CRUD completo: listar, crear, editar, activar/desactivar, eliminar, resetear clave.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
verificarAdmin();

require_once 'includes/functions.php';

$pdo = getDBConnection();
$mensaje = '';
$tipoMensaje = '';

// ============================================================
// HELPERS: ACCESO POR MODULOS
// ============================================================
/**
 * Guarda los accesos por modulo de un usuario: reemplaza el set anterior.
 * Los valores recibidos se validan contra el catalogo MODULOS_SISTEMA.
 * NOTA: los administradores no requieren registros (acceso total por rol).
 */
function guardarModulosUsuario($pdo, $idUsuario, $modulosPost, $asignadoPor = null) {
    $validos = [];
    foreach ((array)$modulosPost as $m) {
        if (is_string($m) && array_key_exists($m, MODULOS_SISTEMA)) {
            $validos[] = $m;
        }
    }
    $validos = array_values(array_unique($validos));

    $stmt = $pdo->prepare("DELETE FROM USUARIO_MODULOS WHERE id_usuario = ?");
    $stmt->execute([(int)$idUsuario]);

    if (!empty($validos)) {
        $stmt = $pdo->prepare("INSERT INTO USUARIO_MODULOS (id_usuario, modulo, asignado_por) VALUES (?, ?, ?)");
        foreach ($validos as $m) {
            $stmt->execute([(int)$idUsuario, $m, $asignadoPor]);
        }
    }
    return count($validos);
}

/**
 * Renderiza la lista de checkboxes de modulos para los formularios.
 * $prefix: prefijo unico para ids (crear_mod / edit_mod / accesos_mod).
 * $seleccionados: claves de modulo marcadas de inicio (opcional).
 */
function renderCheckboxesModulos($prefix, $seleccionados = []) {
    foreach (MODULOS_SISTEMA as $clave => $info) {
        $checked = in_array($clave, (array)$seleccionados, true) ? 'checked' : '';
        $id = htmlspecialchars($prefix . '_' . $clave);
        echo '<div class="form-check mb-2">';
        echo '<input class="form-check-input chk-modulo" type="checkbox" name="modulos[]" value="' . htmlspecialchars($clave) . '" id="' . $id . '" ' . $checked . ' data-prefix="' . htmlspecialchars($prefix) . '">';
        echo '<label class="form-check-label" for="' . $id . '">';
        echo '<i class="fas ' . htmlspecialchars($info['icono']) . ' me-1 text-primary"></i><strong>' . htmlspecialchars($info['nombre']) . '</strong>';
        echo ' <small class="text-muted d-block ms-4">' . htmlspecialchars($info['archivo']) . ' &mdash; ' . htmlspecialchars($info['descripcion']) . '</small>';
        echo '</label></div>';
    }
}

// ============================================================
// PROCESAR ACCIONES (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrfToken)) {
        $mensaje = 'Token de seguridad invalido. Recargue la pagina e intente nuevamente.';
        $tipoMensaje = 'danger';
    } else {
        $accion = $_POST['accion'] ?? '';
        try {
            switch ($accion) {
                case 'crear': {
                    $usuario = trim($_POST['usuario'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
                    $rol = $_POST['rol'] ?? 'usuario';
                    $estado = isset($_POST['estado']) ? 1 : 0;

                    if ($usuario === '' || $password === '' || $nombreCompleto === '') {
                        throw new Exception('Usuario, contrasena y nombre completo son obligatorios.');
                    }
                    if (!in_array($rol, ['admin', 'usuario'], true)) {
                        throw new Exception('Rol invalido.');
                    }
                    if (strlen($password) < 6) {
                        throw new Exception('La contrasena debe tener al menos 6 caracteres.');
                    }
                    // Verificar usuario unico
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM USUARIOS WHERE usuario = ?");
                    $stmt->execute([$usuario]);
                    if ((int)$stmt->fetchColumn() > 0) {
                        throw new Exception('Ya existe un usuario con ese nombre. Elija otro.');
                    }
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO USUARIOS (usuario, password, nombre_completo, rol, estado, fecha_creacion) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$usuario, $hash, $nombreCompleto, $rol, $estado]);
                    $idNuevo = (int)$pdo->lastInsertId();

                    // Acceso por modulos: solo aplica a rol 'usuario'
                    // (el rol admin tiene acceso a todo por defecto)
                    if ($rol !== 'admin') {
                        $nModulos = guardarModulosUsuario($pdo, $idNuevo, $_POST['modulos'] ?? [], (int)($_SESSION['usuario_id'] ?? 0));
                        $mensaje = "Usuario <strong>" . htmlspecialchars($usuario) . "</strong> creado correctamente con " . $nModulos . " modulo(s) asignado(s).";
                    } else {
                        $mensaje = "Usuario <strong>" . htmlspecialchars($usuario) . "</strong> creado correctamente (acceso total por rol Administrador).";
                    }
                    $tipoMensaje = 'success';
                    break;
                }
                case 'editar': {
                    $idUsuario = (int)($_POST['id_usuario'] ?? 0);
                    $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
                    $rol = $_POST['rol'] ?? 'usuario';
                    $estado = isset($_POST['estado']) ? 1 : 0;

                    if ($idUsuario <= 0) throw new Exception('ID de usuario invalido.');
                    if ($nombreCompleto === '') throw new Exception('El nombre completo es obligatorio.');
                    if (!in_array($rol, ['admin', 'usuario'], true)) throw new Exception('Rol invalido.');

                    // Proteger al admin principal (id=1) de ser desactivado o degradado
                    if ($idUsuario === 1) {
                        if ($rol !== 'admin') throw new Exception('El administrador principal no puede ser degradado.');
                        if ($estado === 0) throw new Exception('El administrador principal no puede ser desactivado.');
                    }

                    $stmt = $pdo->prepare("UPDATE USUARIOS SET nombre_completo = ?, rol = ?, estado = ? WHERE id_usuario = ?");
                    $stmt->execute([$nombreCompleto, $rol, $estado, $idUsuario]);

                    // Acceso por modulos: si pasa a admin se limpian sus registros
                    // (acceso total por rol); si es usuario se reemplaza el set.
                    if ($rol !== 'admin') {
                        guardarModulosUsuario($pdo, $idUsuario, $_POST['modulos'] ?? [], (int)($_SESSION['usuario_id'] ?? 0));
                    } else {
                        $stmt = $pdo->prepare("DELETE FROM USUARIO_MODULOS WHERE id_usuario = ?");
                        $stmt->execute([$idUsuario]);
                    }

                    $mensaje = "Usuario actualizado correctamente.";
                    $tipoMensaje = 'success';
                    break;
                }
                case 'asignar_modulos': {
                    $idUsuario = (int)($_POST['id_usuario'] ?? 0);
                    if ($idUsuario <= 0) throw new Exception('ID de usuario invalido.');

                    // Verificar que el usuario exista y su rol
                    $stmt = $pdo->prepare("SELECT usuario, rol FROM USUARIOS WHERE id_usuario = ?");
                    $stmt->execute([$idUsuario]);
                    $uRow = $stmt->fetch();
                    if (!$uRow) throw new Exception('El usuario no existe.');
                    if ($uRow['rol'] === 'admin') {
                        throw new Exception('El rol Administrador ya tiene acceso a todos los modulos; no requiere asignacion.');
                    }

                    guardarModulosUsuario($pdo, $idUsuario, $_POST['modulos'] ?? [], (int)($_SESSION['usuario_id'] ?? 0));
                    $mensaje = "Accesos por modulo actualizados para <strong>" . htmlspecialchars($uRow['usuario']) . "</strong>.";
                    $tipoMensaje = 'success';
                    break;
                }
                case 'resetear_clave': {
                    $idUsuario = (int)($_POST['id_usuario'] ?? 0);
                    $nuevaClave = $_POST['nueva_clave'] ?? '';
                    if ($idUsuario <= 0) throw new Exception('ID de usuario invalido.');
                    if (strlen($nuevaClave) < 6) throw new Exception('La nueva contrasena debe tener al menos 6 caracteres.');
                    $hash = password_hash($nuevaClave, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE USUARIOS SET password = ? WHERE id_usuario = ?");
                    $stmt->execute([$hash, $idUsuario]);
                    $mensaje = "Contrasena reseteada correctamente para el usuario.";
                    $tipoMensaje = 'success';
                    break;
                }
                case 'eliminar': {
                    $idUsuario = (int)($_POST['id_usuario'] ?? 0);
                    if ($idUsuario <= 0) throw new Exception('ID de usuario invalido.');
                    if ($idUsuario === 1) throw new Exception('El administrador principal no puede ser eliminado.');
                    if ($idUsuario === (int)($_SESSION['usuario_id'] ?? 0)) throw new Exception('No puede eliminar su propia cuenta.');

                    // Eliminar primero sus accesos por modulo
                    $stmt = $pdo->prepare("DELETE FROM USUARIO_MODULOS WHERE id_usuario = ?");
                    $stmt->execute([$idUsuario]);

                    $stmt = $pdo->prepare("DELETE FROM USUARIOS WHERE id_usuario = ?");
                    $stmt->execute([$idUsuario]);
                    $mensaje = "Usuario eliminado correctamente.";
                    $tipoMensaje = 'success';
                    break;
                }
                case 'toggle_estado': {
                    $idUsuario = (int)($_POST['id_usuario'] ?? 0);
                    if ($idUsuario <= 0) throw new Exception('ID de usuario invalido.');
                    if ($idUsuario === 1) throw new Exception('El administrador principal no puede ser desactivado.');

                    $stmt = $pdo->prepare("SELECT estado FROM USUARIOS WHERE id_usuario = ?");
                    $stmt->execute([$idUsuario]);
                    $estadoActual = (int)$stmt->fetchColumn();
                    $nuevoEstado = $estadoActual === 1 ? 0 : 1;
                    $stmt = $pdo->prepare("UPDATE USUARIOS SET estado = ? WHERE id_usuario = ?");
                    $stmt->execute([$nuevoEstado, $idUsuario]);
                    $mensaje = "Estado del usuario actualizado a: <strong>" . ($nuevoEstado === 1 ? 'ACTIVO' : 'INACTIVO') . "</strong>.";
                    $tipoMensaje = 'success';
                    break;
                }
                default:
                    throw new Exception('Accion no reconocida.');
            }
        } catch (Exception $e) {
            $mensaje = $e->getMessage();
            $tipoMensaje = 'danger';
        }
    }
}

// ============================================================
// LISTAR USUARIOS
// ============================================================
// Filtros
$fBuscar = trim($_GET['buscar'] ?? '');
$fRol = trim($_GET['rol'] ?? '');
$fEstado = trim($_GET['estado'] ?? '');

$where = "1=1";
$params = [];
if ($fBuscar !== '') {
    $where .= " AND (usuario LIKE :b1 OR nombre_completo LIKE :b2)";
    $params[':b1'] = '%' . $fBuscar . '%';
    $params[':b2'] = '%' . $fBuscar . '%';
}
if ($fRol !== '') { $where .= " AND rol = :rol"; $params[':rol'] = $fRol; }
if ($fEstado !== '') { $where .= " AND estado = :est"; $params[':est'] = intval($fEstado); }

$countSql = "SELECT COUNT(*) FROM USUARIOS WHERE " . $where;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRegistros = (int)$countStmt->fetchColumn();

$porPagina = 20;
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;
$totalPaginas = ceil($totalRegistros / $porPagina);

$dataSql = "SELECT id_usuario, usuario, nombre_completo, rol, estado, fecha_creacion, ultimo_acceso FROM USUARIOS WHERE {$where} ORDER BY id_usuario ASC LIMIT {$porPagina} OFFSET {$offset}";
$dataStmt = $pdo->prepare($dataSql);
$dataStmt->execute($params);
$usuarios = $dataStmt->fetchAll();

// Accesos por modulo de los usuarios listados (badges + modales)
$modulosPorUsuario = [];
if (!empty($usuarios)) {
    $idsLista = array_map('intval', array_column($usuarios, 'id_usuario'));
    $inIds = implode(',', $idsLista);
    $rowsMods = $pdo->query("SELECT id_usuario, modulo FROM USUARIO_MODULOS WHERE id_usuario IN ($inIds)")->fetchAll();
    foreach ($rowsMods as $rm) {
        $modulosPorUsuario[(int)$rm['id_usuario']][] = $rm['modulo'];
    }
}

// Stats
$statsRow = $pdo->query("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN estado=1 THEN 1 ELSE 0 END) as activos,
    SUM(CASE WHEN estado=0 THEN 1 ELSE 0 END) as inactivos,
    SUM(CASE WHEN rol='admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN rol='usuario' THEN 1 ELSE 0 END) as usuarios_comun
    FROM USUARIOS")->fetch();

$csrfToken = generateCSRFToken();
$pageTitle = 'Gestion de Usuarios - Sistema HIS';
include 'includes/header.php';
?>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-users-cog me-2"></i>Gestion de Usuarios</h4>
            <p class="subtitle">Crear, editar, activar/desactivar y eliminar usuarios del sistema.</p>
        </div>
        <button type="button" class="btn btn-his" data-bs-toggle="modal" data-bs-target="#modalCrear">
            <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
        </button>
    </div>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
    <i class="fas <?= $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
    <?= $mensaje ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-primary">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($statsRow['total'] ?? 0) ?></span>
                <span class="stat-label">Total Usuarios</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-success">
            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($statsRow['activos'] ?? 0) ?></span>
                <span class="stat-label">Activos</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-warning">
            <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($statsRow['admins'] ?? 0) ?></span>
                <span class="stat-label">Administradores</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card stat-info">
            <div class="stat-icon"><i class="fas fa-user"></i></div>
            <div class="stat-info">
                <span class="stat-value"><?= number_format($statsRow['usuarios_comun'] ?? 0) ?></span>
                <span class="stat-label">Usuarios</span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-filter me-2 text-primary"></i>Filtros</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="usuarios.php" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Buscar</label>
                <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Usuario o nombre" value="<?= htmlspecialchars($fBuscar) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Rol</label>
                <select name="rol" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <option value="admin" <?= $fRol === 'admin' ? 'selected' : '' ?>>Administrador</option>
                    <option value="usuario" <?= $fRol === 'usuario' ? 'selected' : '' ?>>Usuario</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">-- Todos --</option>
                    <option value="1" <?= $fEstado === '1' ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= $fEstado === '0' ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-his btn-sm w-100"><i class="fas fa-search me-1"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de usuarios -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-table me-2 text-primary"></i>Lista de Usuarios (<?= number_format($totalRegistros) ?>)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($usuarios)): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <h5>Sin usuarios</h5>
                <p>No se encontraron usuarios con los filtros seleccionados.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Nombre Completo</th>
                            <th>Rol</th>
                            <th>Accesos por Modulo</th>
                            <th>Estado</th>
                            <th>Fecha Creacion</th>
                            <th>Ultimo Acceso</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="text-muted"><?= (int)$u['id_usuario'] ?></td>
                            <td>
                                <strong><?= clean($u['usuario']) ?></strong>
                                <?php if ((int)$u['id_usuario'] === 1): ?>
                                    <span class="badge bg-warning text-dark ms-1" title="Administrador principal"><i class="fas fa-crown"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><?= clean($u['nombre_completo']) ?></td>
                            <td>
                                <?php if ($u['rol'] === 'admin'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-user-shield me-1"></i>Administrador</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><i class="fas fa-user me-1"></i>Usuario</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:220px;">
                                <?php if ($u['rol'] === 'admin'): ?>
                                    <span class="badge bg-secondary"><i class="fas fa-universal-access me-1"></i>Todos (rol Admin)</span>
                                <?php else:
                                    $modsUsu = $modulosPorUsuario[(int)$u['id_usuario']] ?? [];
                                    if (empty($modsUsu)): ?>
                                        <span class="text-muted small"><i class="fas fa-ban me-1"></i>Sin accesos asignados</span>
                                    <?php else: foreach ($modsUsu as $mc): ?>
                                        <span class="badge bg-light text-dark border mb-1" title="<?= htmlspecialchars(MODULOS_SISTEMA[$mc]['archivo'] ?? $mc) ?>">
                                            <i class="fas <?= htmlspecialchars(MODULOS_SISTEMA[$mc]['icono'] ?? 'fa-square') ?> me-1 text-primary"></i><?= htmlspecialchars(MODULOS_SISTEMA[$mc]['nombre'] ?? $mc) ?>
                                        </span>
                                    <?php endforeach; endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$u['estado'] === 1): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= formatDateTime($u['fecha_creacion']) ?></small></td>
                            <td><small><?= $u['ultimo_acceso'] ? formatDateTime($u['ultimo_acceso']) : '<span class="text-muted">Nunca</span>' ?></small></td>
                            <td class="text-end">
                                <?php if ($u['rol'] !== 'admin'): ?>
                                <button type="button" class="btn btn-xs btn-outline-success"
                                        onclick='asignarModulos(<?= (int)$u["id_usuario"] ?>, <?= json_encode($u["usuario"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($modulosPorUsuario[(int)$u["id_usuario"]] ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="fas fa-shield-halved" title="Accesos por modulos"></i>
                                </button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-xs btn-outline-primary"
                                        onclick='editarUsuario(<?= json_encode([
                                            "id_usuario" => (int)$u["id_usuario"],
                                            "usuario" => $u["usuario"],
                                            "nombre_completo" => $u["nombre_completo"],
                                            "rol" => $u["rol"],
                                            "estado" => (int)$u["estado"],
                                            "modulos" => $modulosPorUsuario[(int)$u["id_usuario"]] ?? [],
                                        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="fas fa-edit" title="Editar"></i>
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-warning"
                                        onclick='resetearClave(<?= (int)$u["id_usuario"] ?>, <?= json_encode($u["usuario"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="fas fa-key" title="Resetear clave"></i>
                                </button>
                                <?php if ((int)$u['id_usuario'] !== 1): ?>
                                <form method="POST" action="usuarios.php" class="d-inline" onsubmit="return confirm('¿Cambiar el estado de este usuario?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="accion" value="toggle_estado">
                                    <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                    <button type="submit" class="btn btn-xs btn-outline-<?= (int)$u['estado'] === 1 ? 'secondary' : 'success' ?>">
                                        <i class="fas fa-<?= (int)$u['estado'] === 1 ? 'toggle-off' : 'toggle-on' ?>" title="<?= (int)$u['estado'] === 1 ? 'Desactivar' : 'Activar' ?>"></i>
                                    </button>
                                </form>
                                <?php if ((int)$u['id_usuario'] !== (int)($_SESSION['usuario_id'] ?? 0)): ?>
                                <form method="POST" action="usuarios.php" class="d-inline" onsubmit="return confirm('¿ELIMINAR este usuario? Esta accion no se puede deshacer.');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                    <button type="submit" class="btn btn-xs btn-outline-danger">
                                        <i class="fas fa-trash" title="Eliminar"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php endif; ?>
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

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCrear" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Usuario <span class="text-danger">*</span></label>
                        <input type="text" name="usuario" class="form-control" required minlength="3" maxlength="50" placeholder="Ingrese DNI">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_completo" class="form-control" required maxlength="150" placeholder="Ej: Juan Perez Garcia">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contrasena <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="crear_password" class="form-control" required minlength="6" placeholder="Minimo 6 caracteres">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('crear_password', this)"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Rol</label>
                            <select name="rol" class="form-select">
                                <option value="usuario" selected>Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="estado" id="crear_estado" checked value="1">
                                <label class="form-check-label" for="crear_estado">Activo</label>
                            </div>
                        </div>
                    </div>
                    <!-- Acceso por modulos (aplica al rol Usuario; el Admin tiene acceso total) -->
                    <div class="border-top pt-3 mt-1">
                        <label class="form-label fw-semibold"><i class="fas fa-shield-halved me-1 text-success"></i>Acceso por modulos</label>
                        <div class="alert alert-info py-1 px-2 small mb-2">
                            Con rol <strong>Administrador</strong> el acceso es total por defecto.
                            Los modulos marcados aplican cuando el rol es <strong>Usuario</strong>.
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="crear_mod_todos" onchange="toggleTodosModulos('crear_mod', this.checked)">
                            <label class="form-check-label fw-semibold" for="crear_mod_todos">Seleccionar todos / quitar todos</label>
                        </div>
                        <div style="max-height:240px;overflow-y:auto;" class="ps-2">
                            <?php renderCheckboxesModulos('crear_mod'); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-his"><i class="fas fa-save me-1"></i> Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id_usuario" id="edit_id_usuario">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Usuario</label>
                        <input type="text" id="edit_usuario" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_completo" id="edit_nombre_completo" class="form-control" required maxlength="150">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Rol</label>
                            <select name="rol" id="edit_rol" class="form-select">
                                <option value="usuario">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="estado" id="edit_estado" value="1">
                                <label class="form-check-label" for="edit_estado">Activo</label>
                            </div>
                        </div>
                    </div>
                    <!-- Acceso por modulos (aplica al rol Usuario; el Admin tiene acceso total) -->
                    <div class="border-top pt-3 mt-1">
                        <label class="form-label fw-semibold"><i class="fas fa-shield-halved me-1 text-success"></i>Acceso por modulos</label>
                        <div class="alert alert-info py-1 px-2 small mb-2">
                            Con rol <strong>Administrador</strong> el acceso es total por defecto.
                            Los modulos marcados aplican cuando el rol es <strong>Usuario</strong>.
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="edit_mod_todos" onchange="toggleTodosModulos('edit_mod', this.checked)">
                            <label class="form-check-label fw-semibold" for="edit_mod_todos">Seleccionar todos / quitar todos</label>
                        </div>
                        <div style="max-height:240px;overflow-y:auto;" class="ps-2">
                            <?php renderCheckboxesModulos('edit_mod'); ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-his"><i class="fas fa-save me-1"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Resetear Clave -->
<div class="modal fade" id="modalReset" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="accion" value="resetear_clave">
                <input type="hidden" name="id_usuario" id="reset_id_usuario">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Resetear Contrasena</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Resetear contrasena del usuario: <strong id="reset_usuario"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nueva Contrasena <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="nueva_clave" id="reset_clave" class="form-control" required minlength="6" placeholder="Minimo 6 caracteres">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('reset_clave', this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <small class="text-muted">La contrasena anterior sera reemplazada.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-key me-1"></i> Resetear Contrasena</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Accesos por Modulos -->
<div class="modal fade" id="modalAccesos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="accion" value="asignar_modulos">
                <input type="hidden" name="id_usuario" id="accesos_id_usuario">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shield-halved me-2"></i>Acceso por Modulos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Usuario: <strong id="accesos_usuario"></strong></p>
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        El rol <strong>Administrador</strong> siempre tiene acceso a todos los modulos.
                        Marque solo los modulos que este usuario podra abrir; los no marcados le seran
                        bloqueados y no apareceran en su menu.
                    </div>
                    <div class="form-check border-bottom pb-2 mb-2">
                        <input class="form-check-input" type="checkbox" id="accesos_todos" onchange="toggleTodosModulos('accesos_mod', this.checked)">
                        <label class="form-check-label fw-semibold" for="accesos_todos">Seleccionar todos / quitar todos</label>
                    </div>
                    <div style="max-height:320px;overflow-y:auto;" class="ps-1">
                        <?php renderCheckboxesModulos('accesos_mod'); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-his"><i class="fas fa-save me-1"></i> Guardar Accesos</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarUsuario(data) {
    document.getElementById('edit_id_usuario').value = data.id_usuario;
    document.getElementById('edit_usuario').value = data.usuario;
    document.getElementById('edit_nombre_completo').value = data.nombre_completo;
    document.getElementById('edit_rol').value = data.rol;
    document.getElementById('edit_estado').checked = data.estado === 1;
    marcarModulos('edit_mod', data.modulos || []);
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function asignarModulos(id, usuario, modulos) {
    document.getElementById('accesos_id_usuario').value = id;
    document.getElementById('accesos_usuario').textContent = usuario;
    marcarModulos('accesos_mod', modulos || []);
    new bootstrap.Modal(document.getElementById('modalAccesos')).show();
}

function marcarModulos(prefix, lista) {
    document.querySelectorAll('input[type="checkbox"][data-prefix="' + prefix + '"]').forEach(function(chk) {
        chk.checked = lista.indexOf(chk.value) !== -1;
    });
}

function toggleTodosModulos(prefix, checked) {
    document.querySelectorAll('input[type="checkbox"][data-prefix="' + prefix + '"]').forEach(function(chk) {
        chk.checked = checked;
    });
}

function resetearClave(id, usuario) {
    document.getElementById('reset_id_usuario').value = id;
    document.getElementById('reset_usuario').textContent = usuario;
    document.getElementById('reset_clave').value = '';
    new bootstrap.Modal(document.getElementById('modalReset')).show();
}

function togglePwd(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?>
