<?php
/**
 * Sistema de Gestion de Datos HIS
 * Pagina: FED - Gestion de Indicadores (solo rol Administrador)
 *
 * v4 - Separacion del modulo (2026-11-04):
 *  Antes esta funcionalidad era la pestaña "Gestion de Indicadores" del panel
 *  de admin de convenio_fed.php. Ahora es una pagina independiente y ligera.
 *  Funciones:
 *   - Agregar / modificar indicadores FED -> tabla CONVENIO_FED_INDICADORES.
 *   - Activar / desactivar indicadores (los desactivados conservan historial).
 *  El codigo compartido del modulo (conexion, deteccion de esquema, helpers)
 *  vive en includes/fed_common.php.
 *  La visualizacion del avance sigue en convenio_fed.php y la importacion en
 *  fed_importar_avances.php.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
require_once 'includes/functions.php';
require_once 'includes/fed_common.php';

/* ----------------------------------------------------------------------------
 * Pagina exclusiva del rol Administrador: los demas roles vuelven al reporte.
 * ------------------------------------------------------------------------- */
if (!$esAdmin) {
    header('Location: convenio_fed.php');
    exit;
}

/* ============================================================================
 * 1. PROCESAR ACCIONES POST
 * ========================================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $mensaje = 'Token de seguridad invalido. Recargue la pagina e intente nuevamente.';
        $tipoMensaje = 'danger';
    } else {
        $accion = $_POST['accion'] ?? '';
        try {
            /* --------------------------------------------------------------
             * 1.1 GUARDAR INDICADOR (crear o modificar)
             * ----------------------------------------------------------- */
            if ($accion === 'fed_guardar_indicador') {
                if (!$puedeGestionar) {
                    throw new Exception('La tabla CONVENIO_FED_INDICADORES no existe. Ejecute Database/update_fed_v3.sql.');
                }
                $idInd    = (int)($_POST['id_indicador'] ?? 0);
                $codigo   = strtoupper(trim($_POST['codigo'] ?? ''));
                $nombre   = trim($_POST['nombre'] ?? '');
                $descr    = trim($_POST['descripcion'] ?? '');
                $unidad   = trim($_POST['unidad_medida'] ?? '');
                $metaRaw  = trim($_POST['meta_anual'] ?? '');
                $pesoRaw  = trim($_POST['peso_ponderado'] ?? '');
                $periodo  = strtoupper(trim($_POST['periodo'] ?? 'TRIMESTRAL'));
                $respons  = trim($_POST['responsable'] ?? '');
                $orden    = (int)($_POST['orden'] ?? 0);
                $estado   = isset($_POST['estado']) ? 1 : 0;

                if ($codigo === '' || $nombre === '') {
                    throw new Exception('El codigo y el nombre del indicador son obligatorios.');
                }
                if (mb_strlen($codigo) > 20) {
                    throw new Exception('El codigo no puede superar 20 caracteres.');
                }
                if (!in_array($periodo, ['MENSUAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL'], true)) {
                    $periodo = 'TRIMESTRAL';
                }
                $meta = null;
                if ($metaRaw !== '') {
                    if (!is_numeric($metaRaw)) throw new Exception("La meta anual debe ser un numero ('{$metaRaw}').");
                    $meta = (float)$metaRaw;
                }
                $peso = null;
                if ($pesoRaw !== '') {
                    if (!is_numeric($pesoRaw)) throw new Exception("El peso ponderado debe ser un numero ('{$pesoRaw}').");
                    $peso = (float)$pesoRaw;
                    if ($peso < 0 || $peso > 100) throw new Exception('El peso ponderado debe estar entre 0 y 100.');
                }

                if ($idInd > 0) {
                    $st = $pdo->prepare("SELECT COUNT(*) FROM `{$tablaIndicadores}` WHERE id_indicador = ?");
                    $st->execute([$idInd]);
                    if (!$st->fetchColumn()) throw new Exception('El indicador que intenta modificar no existe.');
                    $sql = "UPDATE `{$tablaIndicadores}`
                            SET codigo = ?, nombre = ?, descripcion = ?, unidad_medida = ?, meta_anual = ?,
                                peso_ponderado = ?, periodo = ?, responsable = ?, orden = ?, estado = ?
                            WHERE id_indicador = ?";
                    $pdo->prepare($sql)->execute([$codigo, $nombre, $descr ?: null, $unidad ?: null, $meta,
                                                  $peso, $periodo, $respons ?: null, $orden, $estado, $idInd]);
                    $mensaje = "Indicador <strong>" . clean($codigo) . "</strong> modificado correctamente.";
                } else {
                    // Reparacion preventiva: si la tabla quedo sin AUTO_INCREMENT
                    // (volcados antiguos), se intenta activar; si no se puede,
                    // se inserta con id manual (MAX + 1).
                    $autoIncInd = fedAsegurarAutoIncrement($pdo, $tablaIndicadores, 'id_indicador');
                    if ($autoIncInd) {
                        $sql = "INSERT INTO `{$tablaIndicadores}`
                                (codigo, nombre, descripcion, unidad_medida, meta_anual, peso_ponderado,
                                 periodo, responsable, orden, estado)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $pdo->prepare($sql)->execute([$codigo, $nombre, $descr ?: null, $unidad ?: null, $meta,
                                                      $peso, $periodo, $respons ?: null, $orden, $estado]);
                    } else {
                        $nuevoId = (int)$pdo->query("SELECT COALESCE(MAX(id_indicador), 0) + 1 FROM `{$tablaIndicadores}`")->fetchColumn();
                        $sql = "INSERT INTO `{$tablaIndicadores}`
                                (id_indicador, codigo, nombre, descripcion, unidad_medida, meta_anual, peso_ponderado,
                                 periodo, responsable, orden, estado)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $pdo->prepare($sql)->execute([$nuevoId, $codigo, $nombre, $descr ?: null, $unidad ?: null, $meta,
                                                      $peso, $periodo, $respons ?: null, $orden, $estado]);
                    }
                    $mensaje = "Indicador <strong>" . clean($codigo) . "</strong> agregado correctamente.";
                }
                $tipoMensaje = 'success';

            /* --------------------------------------------------------------
             * 1.2 ACTIVAR / DESACTIVAR INDICADOR
             * ----------------------------------------------------------- */
            } elseif ($accion === 'fed_toggle_indicador') {
                if (!$puedeGestionar) {
                    throw new Exception('La tabla CONVENIO_FED_INDICADORES no existe. Ejecute Database/update_fed_v3.sql.');
                }
                $idInd = (int)($_POST['id_indicador'] ?? 0);
                if ($idInd <= 0) throw new Exception('ID de indicador invalido.');
                $st = $pdo->prepare("SELECT codigo, estado FROM `{$tablaIndicadores}` WHERE id_indicador = ?");
                $st->execute([$idInd]);
                $row = $st->fetch();
                if (!$row) throw new Exception('El indicador no existe.');
                $nuevoEstado = (int)$row['estado'] === 1 ? 0 : 1;
                $pdo->prepare("UPDATE `{$tablaIndicadores}` SET estado = ? WHERE id_indicador = ?")
                    ->execute([$nuevoEstado, $idInd]);
                $mensaje = "Indicador <strong>" . clean($row['codigo']) . "</strong> " .
                           ($nuevoEstado === 1 ? 'ACTIVADO' : 'DESACTIVADO') . " correctamente." .
                           ($nuevoEstado === 0 ? ' Los indicadores desactivados no se muestran en el reporte, pero conservan su historial de avances.' : '');
                $tipoMensaje = 'success';

            } else {
                throw new Exception('Accion no reconocida.');
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $mensaje = 'Error de integridad: probablemente el codigo del indicador ya existe. Verifique e intente con otro codigo.';
            } else {
                $mensaje = 'Error de base de datos: ' . $e->getMessage();
            }
            $tipoMensaje = 'danger';
        } catch (Exception $e) {
            $mensaje = $e->getMessage();
            $tipoMensaje = 'danger';
        }
    }
}

/* ============================================================================
 * 2. LISTA COMPLETA DE INDICADORES (incluye inactivos)
 * ========================================================================== */
$indicadoresAdmin = [];
if ($tablaIndicadores !== null) {
    $indicadoresAdmin = $pdo->query("SELECT * FROM `{$tablaIndicadores}` ORDER BY orden ASC, codigo ASC")->fetchAll();
}

$csrfToken = generateCSRFToken();
$pageTitle = 'FED: Gestion de Indicadores - Sistema HIS';
include 'includes/header.php';
?>

<style>
/* ---- Estilos especificos de la Gestion de Indicadores FED ---- */
.fed-tabla-admin td { vertical-align: middle; }
</style>

<div class="page-header-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="fas fa-tasks me-2"></i>FED: Gestion de Indicadores</h4>
            <p class="subtitle">Convenio FED - Fondo de Estimulo al Desempeno. Agregue, modifique o desactive los indicadores del convenio.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="convenio_fed.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chart-line me-1"></i> Ver reporte de avances
            </a>
            <a href="fed_importar_avances.php" class="btn btn-warning btn-sm text-dark fw-semibold">
                <i class="fas fa-file-import me-1"></i> Importar Resultados.txt
            </a>
        </div>
    </div>
</div>

<?php if ($mensaje): ?>
<div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
    <i class="fas <?= $tipoMensaje === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
    <?= $mensaje ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!$puedeGestionar): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        La tabla <code>CONVENIO_FED_INDICADORES</code> no existe en la base de datos.
        Ejecute el script <code>Database/update_fed_v3.sql</code> desde phpMyAdmin para crear las tablas
        del Convenio FED.
    </div>
<?php else: ?>

<div class="card shadow-sm mb-4 border-warning">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center mb-0 flex-wrap gap-2">
            <div>
                <h6 class="fw-bold mb-0"><i class="fas fa-tasks me-2 text-warning"></i>Indicadores del Convenio FED</h6>
                <small class="text-muted">Agregue, modifique o desactive los indicadores. Los indicadores pueden variar cada anio: los desactivados conservan su historial.</small>
            </div>
            <button type="button" class="btn btn-his btn-sm" id="btnNuevoIndicador" data-bs-toggle="modal" data-bs-target="#modalIndicador">
                <i class="fas fa-plus me-1"></i> Nuevo Indicador
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($indicadoresAdmin)): ?>
            <div class="empty-state">
                <i class="fas fa-tasks"></i>
                <h5>Sin indicadores</h5>
                <p>Aun no hay indicadores FED registrados. Use el boton <strong>Nuevo Indicador</strong> para agregar el primero.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 fed-tabla-admin">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;">ID</th>
                            <th style="width:90px;">Codigo</th>
                            <th>Nombre</th>
                            <th style="width:80px;">Unidad</th>
                            <th style="width:80px;" class="text-end">Meta</th>
                            <th style="width:70px;" class="text-end">Peso</th>
                            <th style="width:105px;">Periodo</th>
                            <th style="width:120px;">Responsable</th>
                            <th style="width:60px;" class="text-end">Orden</th>
                            <th style="width:95px;">Estado</th>
                            <th style="width:150px;" class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($indicadoresAdmin as $ia): ?>
                        <tr>
                            <td class="text-muted"><?= (int)$ia['id_indicador'] ?></td>
                            <td><code><?= clean($ia['codigo']) ?></code></td>
                            <td>
                                <strong><?= clean($ia['nombre']) ?></strong>
                                <?php if (!empty($ia['descripcion'])): ?>
                                    <small class="d-block text-muted" style="font-size:.72rem;" title="<?= clean($ia['descripcion']) ?>"><?= clean(mb_strimwidth($ia['descripcion'], 0, 70, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= clean($ia['unidad_medida'] ?? '-') ?></small></td>
                            <td class="text-end"><?= $ia['meta_anual'] !== null ? number_format((float)$ia['meta_anual'], 2) : '-' ?></td>
                            <td class="text-end"><?= $ia['peso_ponderado'] !== null ? number_format((float)$ia['peso_ponderado'], 1) . '%' : '-' ?></td>
                            <td><small><?= clean($ia['periodo'] ?? '-') ?></small></td>
                            <td><small><?= clean($ia['responsable'] ?? '-') ?></small></td>
                            <td class="text-end"><?= (int)$ia['orden'] ?></td>
                            <td>
                                <?php if ((int)$ia['estado'] === 1): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button type="button"
                                        class="btn btn-xs btn-outline-primary btn-editar-indicador"
                                        data-indicador='<?= clean(json_encode([
                                            'id_indicador'   => (int)$ia['id_indicador'],
                                            'codigo'         => $ia['codigo'],
                                            'nombre'         => $ia['nombre'],
                                            'descripcion'    => $ia['descripcion'],
                                            'unidad_medida'  => $ia['unidad_medida'],
                                            'meta_anual'     => $ia['meta_anual'],
                                            'peso_ponderado' => $ia['peso_ponderado'],
                                            'periodo'        => $ia['periodo'],
                                            'responsable'    => $ia['responsable'],
                                            'orden'          => (int)$ia['orden'],
                                            'estado'         => (int)$ia['estado'],
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>'
                                        title="Modificar indicador">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <form method="POST" action="fed_gestion_indicadores.php" class="d-inline form-toggle-indicador"
                                      data-confirm="<?= (int)$ia['estado'] === 1
                                          ? 'Desactivar el indicador ' . clean($ia['codigo']) . '? No se eliminara y conservara su historial, pero dejara de mostrarse en el reporte.'
                                          : 'Activar el indicador ' . clean($ia['codigo']) . '?' ?>">
                                    <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">
                                    <input type="hidden" name="accion" value="fed_toggle_indicador">
                                    <input type="hidden" name="id_indicador" value="<?= (int)$ia['id_indicador'] ?>">
                                    <button type="submit" class="btn btn-xs <?= (int)$ia['estado'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                            title="<?= (int)$ia['estado'] === 1 ? 'Desactivar' : 'Activar' ?>">
                                        <i class="fas <?= (int)$ia['estado'] === 1 ? 'fa-toggle-off' : 'fa-toggle-on' ?>"></i>
                                        <?= (int)$ia['estado'] === 1 ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; /* fin puedeGestionar */ ?>

<!-- ============ MODAL: NUEVO / MODIFICAR INDICADOR ============ -->
<div class="modal fade" id="modalIndicador" tabindex="-1" aria-labelledby="tituloModalIndicador" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="fed_gestion_indicadores.php" id="formIndicador">
                <input type="hidden" name="csrf_token" value="<?= clean($csrfToken) ?>">
                <input type="hidden" name="accion" value="fed_guardar_indicador">
                <input type="hidden" name="id_indicador" id="f_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="tituloModalIndicador">Nuevo Indicador FED</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Codigo *</label>
                            <input type="text" class="form-control form-control-sm" name="codigo" id="f_codigo" maxlength="20" placeholder="FED-08" required>
                            <div class="form-text">Unico, ej. FED-01... FED-NN</div>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label small fw-semibold">Nombre del indicador *</label>
                            <input type="text" class="form-control form-control-sm" name="nombre" id="f_nombre" maxlength="300" placeholder="Nombre oficial del indicador" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Descripcion</label>
                            <textarea class="form-control form-control-sm" name="descripcion" id="f_descripcion" rows="2" maxlength="1000" placeholder="Formula, denominator, exclusiones, etc."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Unidad de medida</label>
                            <input type="text" class="form-control form-control-sm" name="unidad_medida" id="f_unidad" maxlength="50" placeholder="% / Numero">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Meta anual</label>
                            <input type="number" class="form-control form-control-sm" name="meta_anual" id="f_meta" step="0.01" min="0" placeholder="Ej. 90">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Peso ponderado (%)</label>
                            <input type="number" class="form-control form-control-sm" name="peso_ponderado" id="f_peso" step="0.01" min="0" max="100" placeholder="0 - 100">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Periodicidad</label>
                            <select class="form-select form-select-sm" name="periodo" id="f_periodo">
                                <option value="MENSUAL">Mensual</option>
                                <option value="TRIMESTRAL" selected>Trimestral</option>
                                <option value="SEMESTRAL">Semestral</option>
                                <option value="ANUAL">Anual</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Responsable</label>
                            <input type="text" class="form-control form-control-sm" name="responsable" id="f_responsable" maxlength="150" placeholder="Nombre del responsable">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Orden de presentacion</label>
                            <input type="number" class="form-control form-control-sm" name="orden" id="f_orden" step="1" value="0">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="estado" id="f_estado" value="1" checked>
                                <label class="form-check-label small fw-semibold" for="f_estado">Indicador activo (visible en el reporte)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-his btn-sm"><i class="fas fa-save me-1"></i> Guardar Indicador</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/* ============================================================
 * Interactividad de la Gestion de Indicadores FED (JS puro)
 * ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

    /* ---- 1) Modal de indicadores: rellenar al editar / limpiar al crear ---- */
    document.querySelectorAll('.btn-editar-indicador').forEach(function (b) {
        b.addEventListener('click', function () {
            var d;
            try { d = JSON.parse(b.getAttribute('data-indicador')); } catch (e) { return; }
            document.getElementById('tituloModalIndicador').textContent = 'Modificar Indicador: ' + d.codigo;
            document.getElementById('f_id').value = d.id_indicador;
            document.getElementById('f_codigo').value = d.codigo || '';
            document.getElementById('f_nombre').value = d.nombre || '';
            document.getElementById('f_descripcion').value = d.descripcion || '';
            document.getElementById('f_unidad').value = d.unidad_medida || '';
            document.getElementById('f_meta').value = (d.meta_anual !== null && d.meta_anual !== undefined) ? d.meta_anual : '';
            document.getElementById('f_peso').value = (d.peso_ponderado !== null && d.peso_ponderado !== undefined) ? d.peso_ponderado : '';
            document.getElementById('f_periodo').value = d.periodo || 'TRIMESTRAL';
            document.getElementById('f_responsable').value = d.responsable || '';
            document.getElementById('f_orden').value = (d.orden !== null && d.orden !== undefined) ? d.orden : 0;
            document.getElementById('f_estado').checked = parseInt(d.estado, 10) === 1;
        });
    });
    var btnNuevo = document.getElementById('btnNuevoIndicador');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function () {
            document.getElementById('tituloModalIndicador').textContent = 'Nuevo Indicador FED';
            document.getElementById('formIndicador').reset();
            document.getElementById('f_id').value = '0';
            document.getElementById('f_orden').value = '0';
            document.getElementById('f_estado').checked = true;
        });
    }

    /* ---- 2) Confirmaciones de activar / desactivar ---- */
    document.querySelectorAll('.form-toggle-indicador').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
