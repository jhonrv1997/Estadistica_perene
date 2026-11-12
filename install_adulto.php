<?php
/**
 * Asistente de instalacion del Modulo ADULTO (indices de la tabla
 * consolidada).
 *
 * PROBLEMA QUE RESUELVE:
 *   La tabla T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO se crea SIN indices.
 *   El reporte ADULTO consulta esa tabla con (Codigo_Item IN (~140 codigos)
 *   OR Codigo_Item LIKE 'A64%'/'E10%'... ) + Anio/Mes/Establecimiento; sin
 *   indices MySQL recorre TODA la tabla en cada clic de "Generar Reporte",
 *   lo que en el hosting compartido (InfinityFree) desencadena:
 *     - Agotamiento del memory_limit  -> Fatal error -> HTTP ERROR 500
 *     - Agotamiento del execution time-> Fatal error -> HTTP ERROR 500
 *   Con estos indices la misma consulta pasa de minutos a segundos.
 *
 * QUE HACE:
 *   1. Verifica que la tabla exista.
 *   2. Crea (si no existen) los indices:
 *        idx_adu_anio         (Anio)
 *        idx_adu_codigo_item  (Codigo_Item)
 *        idx_adu_anio_mesint  (Anio, Mes_Int)   [solo si Mes_Int existe]
 *        idx_adu_codigo_unico (Codigo_Unico)
 *   3. Crea (si no existe) la tabla ADULTO_RPT_CACHE que usa el asistente
 *      de generación por partes para rangos amplios (varios meses/EE.SS):
 *      acumula los parciales sin agotar los limites del hosting
 *      (512M / 60s).
 *   4. Muestra el diagnostico del entorno (limites PHP y tamano de tabla).
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
require_once 'includes/adulto_data.php';

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

// 2) Indices que necesita el reporte ADULTO
//    (la consulta del motor filtra por Codigo_Item + Anio/Mes +
//     Codigo_Unico; ver aduWhereCodigos() / aduEjecutarReporte()).
$indices = [
    ['idx_adu_anio',         "ALTER TABLE {$tabla} ADD INDEX idx_adu_anio (Anio)",                            'Anio'],
    ['idx_adu_codigo_item',  "ALTER TABLE {$tabla} ADD INDEX idx_adu_codigo_item (Codigo_Item)",               'Codigo_Item'],
    ['idx_adu_codigo_unico', "ALTER TABLE {$tabla} ADD INDEX idx_adu_codigo_unico (Codigo_Unico)",             'Codigo_Unico'],
];

// ¿Existe la columna Mes_Int (generada)? -> indice compuesto
$tieneMesInt = false;
try {
    $st = $pdo->query("SHOW COLUMNS FROM {$tabla} LIKE 'Mes_Int'");
    $tieneMesInt = ($st->fetch() !== false);
    if ($tieneMesInt) {
        $indices[] = ['idx_adu_anio_mesint',
                      "ALTER TABLE {$tabla} ADD INDEX idx_adu_anio_mesint (Anio, Mes_Int)",
                      'Anio+Mes_Int'];
        $resultado['pasos'][] = "Columna generada Mes_Int detectada: se creará el índice compuesto (Anio, Mes_Int).";
    } else {
        $resultado['omitidos'][] = 'Mes_Int no existe (MySQL < 5.7): se omite idx_adu_anio_mesint.';
    }
} catch (Throwable $e) {
    $resultado['omitidos'][] = 'No se pudo verificar Mes_Int: ' . $e->getMessage();
}

if ($resultado['ok']) {
    // Indices ya existentes en la tabla
    $existentes = [];
    try {
        foreach ($pdo->query("SHOW INDEX FROM {$tabla}")->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $existentes[$row['Key_name']] = true;
        }
    } catch (Throwable $e) { /* sin indices */ }

    foreach ($indices as [$nombre, $sql, $col]) {
        if (isset($existentes[$nombre])) {
            $resultado['existentes'][] = $nombre . " ($col)";
            continue;
        }
        try {
            $pdo->exec($sql);
            $resultado['creados'][] = $nombre . " ($col)";
        } catch (Throwable $e) {
            // "Duplicate key name" = alguien lo creo en paralelo
            if (stripos($e->getMessage(), 'Duplicate key name') !== false) {
                $resultado['existentes'][] = $nombre . " ($col)";
            } else {
                $resultado['errores'][] = "$nombre: " . $e->getMessage();
            }
        }
    }
}

// 3) Tabla de cache del asistente por trozos (ADULTO_RPT_CACHE)
if ($resultado['ok']) {
    if (aduCacheCrearTablaSiFalta($pdo)) {
        $resultado['creados'][] = aduTablaCache() . ' (asistente por partes)';
        $resultado['pasos'][] = 'Tabla ' . aduTablaCache() . ' lista (CREATE TABLE IF NOT EXISTS: no duplica si ya existía).';
    } else {
        $resultado['errores'][] = 'No se pudo crear ' . aduTablaCache() . ' (verifique permisos CREATE).';
    }
}

// 4) Diagnóstico del entorno
$diag = aduDiagnosticoEntorno($pdo);

$pageTitle = 'Instalación del módulo ADULTO - Sistema HIS';
include 'includes/header.php';
?>
<div class="page-header-section">
    <h4><i class="fas fa-database me-2 text-primary"></i>Instalación del módulo ADULTO</h4>
    <p class="subtitle">Índices de la tabla consolidada + tabla de caché del asistente de generación por partes.</p>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-screwdriver-wrench me-2 text-primary"></i>Resultado de la instalación</h6>
                <?php if (empty($resultado['errores'])): ?>
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Completado</span>
                <?php else: ?>
                    <span class="badge bg-danger"><?= count($resultado['errores']) ?> error(es)</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($resultado['pasos']): ?>
                    <h6 class="small fw-bold text-muted">Pasos</h6>
                    <ul class="small ps-3 mb-3">
                        <?php foreach ($resultado['pasos'] as $p): ?>
                            <li><?= htmlspecialchars($p) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($resultado['creados']): ?>
                    <h6 class="small fw-bold text-success"><i class="fas fa-plus-circle me-1"></i>Creados ahora</h6>
                    <ul class="small ps-3 mb-3">
                        <?php foreach ($resultado['creados'] as $c): ?>
                            <li><code><?= htmlspecialchars($c) ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($resultado['existentes']): ?>
                    <h6 class="small fw-bold text-info"><i class="fas fa-check-double me-1"></i>Ya existían (nada que hacer)</h6>
                    <ul class="small ps-3 mb-3">
                        <?php foreach ($resultado['existentes'] as $c): ?>
                            <li><code><?= htmlspecialchars($c) ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($resultado['omitidos']): ?>
                    <h6 class="small fw-bold text-secondary">Omitidos</h6>
                    <ul class="small ps-3 mb-3">
                        <?php foreach ($resultado['omitidos'] as $c): ?>
                            <li><?= htmlspecialchars($c) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($resultado['errores']): ?>
                    <h6 class="small fw-bold text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Errores</h6>
                    <ul class="small ps-3">
                        <?php foreach ($resultado['errores'] as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="fas fa-stethoscope me-2 text-primary"></i>Diagnóstico del entorno</h6></div>
            <div class="card-body small">
                <ul class="ps-3 mb-0">
                    <li>PHP: <code><?= htmlspecialchars($diag['php']) ?></code></li>
                    <li>memory_limit: <code><?= htmlspecialchars($diag['memory_limit']) ?></code></li>
                    <li>max_execution_time: <code><?= htmlspecialchars($diag['max_exec_time']) ?>s</code></li>
                    <li>Filas en la tabla consolidada:
                        <code><?= $diag['filas_tabla'] !== null ? number_format((int)$diag['filas_tabla']) : 'n/d' ?></code></li>
                    <li>Índices en la tabla:
                        <?php if (empty($diag['indices'])): ?>
                            <span class="badge bg-danger">NINGUNO</span>
                        <?php else: ?>
                            <span class="badge bg-success"><?= count($diag['indices']) ?></span>
                            <div class="small text-muted mt-1"><code><?= htmlspecialchars(implode(', ', $diag['indices'])) ?></code></div>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
        <div class="alert alert-warning small">
            <i class="fas fa-shield-alt me-2"></i>
            <strong>Seguridad:</strong> tras verificar el resultado, <strong>elimine este archivo</strong>
            (install_adulto.php) del servidor. Ya no es necesario.
        </div>
        <div class="alert alert-info small">
            <i class="fas fa-info-circle me-2"></i>
            Después de la instalación abra
            <a href="reporte_adulto.php" class="fw-bold">reporte_adulto.php</a>
            (Reportes Operacionales → Adulto → Generar Reporte) y copie la plantilla oficial a
            <code>uploads/Reporte_Actividades_Adulto.xlsx</code> para poder usar
            <em>Exportar Excel</em>.
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
