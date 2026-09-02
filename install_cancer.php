<?php
/**
 * Asistente de instalacion del Modulo CANCER (paso 1: indices de la tabla).
 *
 * PROBLEMA QUE RESUELVE:
 *   La tabla T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO se crea SIN indices.
 *   El reporte CANCER consulta esa tabla con (Codigo_Item IN (... ~80 codigos)
 *   OR Codigo_Item LIKE 'C%') + Anio/Mes/Establecimiento; sin indices MySQL
 *   recorre TODA la tabla en cada clic de "Generar Reporte", lo que en el
 *   hosting compartido (InfinityFree) desencadena:
 *     - Agotamiento del memory_limit  -> Fatal error -> HTTP ERROR 500
 *     - Agotamiento del execution time-> Fatal error -> HTTP ERROR 500
 *   Con estos indices la misma consulta pasa de minutos a segundos.
 *
 * QUE HACE:
 *   1. Verifica que la tabla exista.
 *   2. Crea (si no existen) los indices:
 *        idx_cnd_anio         (Anio)
 *        idx_cnd_codigo_item  (Codigo_Item)
 *        idx_cnd_anio_mesint  (Anio, Mes_Int)   [solo si Mes_Int existe]
 *        idx_cnd_codigo_unico (Codigo_Unico)
 *   3. Muestra el diagnostico del entorno (limites PHP y tamano de tabla).
 *
 * Uso: abra este archivo en el navegador como administrador (una sola vez).
 * NOTA: sobre tablas grandes ALTER TABLE puede tardar 1-3 minutos; no cierre
 *       la pagina hasta ver el resultado.
 *
 * Tras la instalacion, ELIMINE este archivo por seguridad.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
verificarAdmin();
require_once 'includes/functions.php';
require_once 'includes/cancer_data.php';

$pdo = getDBConnection();
$tabla = 'T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO';

$resultado = ['pasos' => [], 'ok' => true, 'errores' => [], 'creados' => [], 'existentes' => [], 'omitidos' => []];

// 1) Verificar que la tabla existe
try {
    $existe = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla))->fetchColumn();
    if (!$existe) {
        $resultado['ok'] = false;
        $resultado['errores'][] = "La tabla $tabla no existe en la base de datos " . DB_NAME . ". Importe primero los datos consolidados.";
    } else {
        $resultado['pasos'][] = "Tabla encontrada: $tabla";
    }
} catch (Throwable $e) {
    $resultado['ok'] = false;
    $resultado['errores'][] = "No se pudo verificar la tabla: " . $e->getMessage();
}

// 2) Indices existentes
$indicesActuales = [];
if ($resultado['ok']) {
    try {
        foreach ($pdo->query("SHOW INDEX FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $indicesActuales[$row['Key_name']] = true;
        }
        $resultado['pasos'][] = count($indicesActuales)
            ? "Indices ya existentes: " . implode(', ', array_keys($indicesActuales))
            : "La tabla NO tiene ningun indice (esta es la causa principal del HTTP 500)";
    } catch (Throwable $e) {
        $resultado['errores'][] = "No se pudo leer SHOW INDEX: " . $e->getMessage();
    }
}

// 3) Crear indices faltantes
$columnaMesInt = cancerTieneColumnaMesInt($pdo);
$indicesDeseados = [
    'idx_cnd_anio'         => "`Anio`",
    'idx_cnd_codigo_item'  => "`Codigo_Item`",
    'idx_cnd_codigo_unico' => "`Codigo_Unico`",
];
if ($columnaMesInt) {
    // Mes_Int es columna generada STORED (cast(trim(Mes) as unsigned)):
    // indexarla junto a Anio acelera el filtro de mes sin el CAST en cada fila.
    $indicesDeseados['idx_cnd_anio_mesint'] = "`Anio`, `Mes_Int`";
} else {
    $resultado['omitidos'][] = "idx_cnd_anio_mesint (la columna generada Mes_Int no existe en esta tabla)";
}

if ($resultado['ok']) {
    @set_time_limit(0);
    foreach ($indicesDeseados as $nombre => $columnas) {
        if (isset($indicesActuales[$nombre])) {
            $resultado['existentes'][] = $nombre;
            continue;
        }
        $t0 = microtime(true);
        try {
            $pdo->exec("ALTER TABLE `$tabla` ADD INDEX `$nombre` ($columnas)");
            $resultado['creados'][] = $nombre . ' (' . round(microtime(true) - $t0, 1) . 's)';
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'duplicate key name') !== false) {
                $resultado['existentes'][] = $nombre; // carrera benigna
            } else {
                $resultado['ok'] = false;
                $resultado['errores'][] = "No se pudo crear el indice $nombre: " . $msg;
            }
        }
    }
    if ($resultado['ok']) {
        $resultado['pasos'][] = count($resultado['creados'])
            ? "Indices creados: " . implode(', ', $resultado['creados'])
            : "Todos los indices necesarios ya existian. No hay nada mas que hacer.";
    }
}

// 4) Verificacion final
$indicesFinales = [];
if ($resultado['ok']) {
    try {
        foreach ($pdo->query("SHOW INDEX FROM `$tabla`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $indicesFinales[$row['Key_name']] = $row['Column_name'];
        }
    } catch (Throwable $e) { /* ignorar */ }
}

$diag = cancerDiagnosticoEntorno($pdo);

$pageTitle = 'Instalacion Modulo CANCER - Sistema HIS';
include 'includes/header.php';
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow">
                <div class="card-header bg-his text-white">
                    <h5 class="mb-0"><i class="fas fa-ribbon me-2"></i>Instalacion del Modulo CANCER &mdash; Indices de la tabla consolidada</h5>
                </div>
                <div class="card-body">
                    <?php if ($resultado['ok']): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Instalacion completada correctamente.</strong>
                        El boton "Generar Reporte" del modulo CANCER ahora consulta la tabla con indices.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>La instalacion tuvo problemas.</strong>
                        Revise los errores a continuacion.
                    </div>
                    <?php endif; ?>

                    <h6 class="fw-bold mt-3"><i class="fas fa-list-ol me-2"></i>Pasos ejecutados:</h6>
                    <ol>
                        <?php foreach ($resultado['pasos'] as $p): ?>
                            <li><?= htmlspecialchars($p) ?></li>
                        <?php endforeach; ?>
                    </ol>

                    <?php if (!empty($resultado['existentes'])): ?>
                        <p class="small text-muted mb-1">Indices que ya existian (no se tocaron): <code><?= htmlspecialchars(implode(', ', $resultado['existentes'])) ?></code></p>
                    <?php endif; ?>
                    <?php if (!empty($resultado['omitidos'])): ?>
                        <p class="small text-muted mb-1">Omitidos: <code><?= htmlspecialchars(implode('; ', $resultado['omitidos'])) ?></code></p>
                    <?php endif; ?>

                    <?php if (!empty($resultado['errores'])): ?>
                    <h6 class="fw-bold text-danger mt-3"><i class="fas fa-bug me-2"></i>Errores:</h6>
                    <ul>
                        <?php foreach ($resultado['errores'] as $e): ?>
                            <li class="text-danger small"><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <h6 class="fw-bold mt-3"><i class="fas fa-key me-2"></i>Indices finales en <code><?= htmlspecialchars($tabla) ?></code>:</h6>
                    <?php if (empty($indicesFinales)): ?>
                        <p class="small text-danger mb-0">La tabla sigue sin indices. Verifique permisos del usuario de base de datos.</p>
                    <?php else: ?>
                    <table class="table table-sm">
                        <thead><tr><th>Indice</th><th>Columna(s)</th></tr></thead>
                        <tbody>
                            <?php foreach ($indicesFinales as $k => $col): ?>
                            <tr><td><code><?= htmlspecialchars($k) ?></code></td><td><code><?= htmlspecialchars($col) ?></code></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>

                    <h6 class="fw-bold mt-3"><i class="fas fa-stethoscope me-2"></i>Diagnostico del entorno:</h6>
                    <ul class="small mb-0">
                        <li>PHP: <code><?= htmlspecialchars($diag['php']) ?></code></li>
                        <li>memory_limit: <code><?= htmlspecialchars($diag['memory_limit']) ?></code> &nbsp;|&nbsp; max_execution_time: <code><?= htmlspecialchars($diag['max_exec_time']) ?>s</code></li>
                        <li>Filas aprox. en la tabla consolidada: <code><?= $diag['filas_tabla'] !== null ? number_format((int)$diag['filas_tabla']) : 'n/d' ?></code></li>
                    </ul>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Por seguridad, elimine este archivo <code>install_cancer.php</code> despues de la instalacion.
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="reporte_cancer.php" class="btn btn-his"><i class="fas fa-ribbon me-1"></i> Ir al Reporte CANCER</a>
                        <a href="reporte_operacionales.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Reportes Operacionales</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
