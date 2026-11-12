<?php
/**
 * Asistente de instalacion del Modulo ADOLESCENTE (indices de la tabla
 * consolidada).
 *
 * PROBLEMA QUE RESUELVE:
 *   La tabla T_CONSOLIDADO_NUEVA_TRAMA_HISMINSA_DETALLADO se crea SIN indices.
 *   El reporte ADOLESCENTE consulta esa tabla con (Codigo_Item IN (~150
 *   codigos) OR Codigo_Item LIKE 'A0%'/'J1%'... OR Id_Ups IN ('302304',
 *   '301903')) + Anio/Mes/Establecimiento; sin indices MySQL recorre TODA la
 *   tabla en cada clic de "Generar Reporte", lo que en el hosting compartido
 *   (InfinityFree) desencadena:
 *     - Agotamiento del memory_limit  -> Fatal error -> HTTP ERROR 500
 *     - Agotamiento del execution time-> Fatal error -> HTTP ERROR 500
 *   Con estos indices la misma consulta pasa de minutos a segundos.
 *
 * QUE HACE:
 *   1. Verifica que la tabla exista.
 *   2. Crea (si no existen) los indices:
 *        idx_ado_anio         (Anio)
 *        idx_ado_codigo_item  (Codigo_Item)
 *        idx_ado_anio_mesint  (Anio, Mes_Int)   [solo si Mes_Int existe]
 *        idx_ado_codigo_unico (Codigo_Unico)
 *        idx_ado_id_ups       (Id_Ups)          [UPS 302304/301903, SP 16_2/16_3/25]
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
require_once 'includes/adolescente_data.php';

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
$columnaMesInt = adoTieneColumnaMesInt($pdo);
$indicesDeseados = [
    'idx_ado_anio'         => "`Anio`",
    'idx_ado_codigo_item'  => "`Codigo_Item`",
    'idx_ado_codigo_unico' => "`Codigo_Unico`",
    'idx_ado_id_ups'       => "`Id_Ups`",
];
if ($columnaMesInt) {
    // Mes_Int es columna generada STORED (cast(trim(Mes) as unsigned)):
    // indexarla junto a Anio acelera el filtro de mes sin el CAST en cada fila.
    $indicesDeseados['idx_ado_anio_mesint'] = "`Anio`, `Mes_Int`";
} else {
    $resultado['omitidos'][] = "idx_ado_anio_mesint (la columna generada Mes_Int no existe en esta tabla)";
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
            $resultado['creados'][] = $nombre . " ($columnas) en " . round(microtime(true) - $t0, 1) . "s";
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            // Indice duplicado bajo otro nombre: no es fatal
            if (stripos($msg, 'Duplicate key name') !== false) {
                $resultado['existentes'][] = $nombre . " (ya existe)";
            } else {
                $resultado['errores'][] = "$nombre: $msg";
            }
        }
    }
}

// 4) Diagnostico del entorno
$diag = adoDiagnosticoEntorno($pdo);

$pageTitle = 'Instalador del Módulo Adolescente - Sistema HIS';
include 'includes/header.php';
?>
<div class="page-header-section">
    <h4><i class="fas fa-user-graduate me-2 text-primary"></i>Instalador del M&oacute;dulo ADOLESCENTE</h4>
    <p class="subtitle">Crea los &iacute;ndices de la tabla consolidada que aceleran el reporte Adolescente (ejecutar una sola vez).</p>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-database me-2 text-primary"></i>Resultado de la instalaci&oacute;n</h6>
    </div>
    <div class="card-body">
        <?php if (!$resultado['ok']): ?>
            <div class="alert alert-danger mb-0">
                <i class="fas fa-times-circle me-2"></i>No se pudo completar la instalaci&oacute;n:
                <ul class="mb-0 mt-2"><?php foreach ($resultado['errores'] as $er): ?><li><?= htmlspecialchars($er) ?></li><?php endforeach; ?></ul>
            </div>
        <?php else: ?>
            <div class="alert <?= empty($resultado['errores']) ? 'alert-success' : 'alert-warning' ?> mb-3">
                <i class="fas <?= empty($resultado['errores']) ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
                Instalaci&oacute;n <?= empty($resultado['errores']) ? 'completada' : 'completada con advertencias' ?>.
                <strong>Elimine este archivo por seguridad.</strong>
            </div>
            <?php if ($resultado['creados']): ?>
                <h6 class="small fw-bold text-success">&Iacute;NDICES CREADOS</h6>
                <ul class="small"><?php foreach ($resultado['creados'] as $c): ?><li><code><?= htmlspecialchars($c) ?></code></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <?php if ($resultado['existentes']): ?>
                <h6 class="small fw-bold text-secondary">YA EXIST&Iacute;AN</h6>
                <ul class="small text-muted"><?php foreach ($resultado['existentes'] as $c): ?><li><code><?= htmlspecialchars($c) ?></code></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <?php if ($resultado['omitidos']): ?>
                <h6 class="small fw-bold text-warning">OMITIDOS</h6>
                <ul class="small"><?php foreach ($resultado['omitidos'] as $c): ?><li><?= htmlspecialchars($c) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <?php if ($resultado['errores']): ?>
                <h6 class="small fw-bold text-danger">ERRORES</h6>
                <ul class="small"><?php foreach ($resultado['errores'] as $er): ?><li><?= htmlspecialchars($er) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-stethoscope me-2 text-primary"></i>Diagn&oacute;stico del entorno</h6>
    </div>
    <div class="card-body">
        <ul class="mb-0">
            <li>PHP: <code><?= htmlspecialchars($diag['php']) ?></code> &nbsp;|&nbsp; memory_limit: <code><?= htmlspecialchars($diag['memory_limit']) ?></code> &nbsp;|&nbsp; max_execution_time: <code><?= htmlspecialchars($diag['max_exec_time']) ?>s</code></li>
            <li>Filas aprox. en la tabla consolidada: <code><?= $diag['filas_tabla'] !== null ? number_format((int)$diag['filas_tabla']) : 'n/d' ?></code></li>
            <li>Columna generada <code>Mes_Int</code>: <?= $columnaMesInt ? '<span class="badge bg-success">disponible</span>' : '<span class="badge bg-secondary">no existe</span>' ?></li>
            <li>&Iacute;ndices actuales:
                <?php if (empty($diag['indices'])): ?>
                    <span class="badge bg-danger">NINGUNO</span>
                <?php else: ?>
                    <span class="badge bg-success"><?= count($diag['indices']) ?></span>
                    <code class="small"><?= htmlspecialchars(implode(', ', $diag['indices'])) ?></code>
                <?php endif; ?>
            </li>
        </ul>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0 fw-bold"><i class="fas fa-file-excel me-2 text-success"></i>Plantilla oficial del export</h6>
    </div>
    <div class="card-body">
        <?php $tplOk = is_readable(__DIR__ . '/uploads/Reporte_Actividades_Adolescentes.xlsx'); ?>
        <p class="mb-2">
            Para exportar el reporte con el dise&ntilde;o oficial, el archivo
            <code>Reporte_Actividades_Adolescentes.xlsx</code> debe estar en la carpeta <code>uploads/</code>:
        </p>
        <?php if ($tplOk): ?>
            <p class="text-success mb-0"><i class="fas fa-check-circle me-1"></i>Plantilla encontrada: <code>uploads/Reporte_Actividades_Adolescentes.xlsx</code></p>
        <?php else: ?>
            <p class="text-warning mb-0"><i class="fas fa-exclamation-triangle me-1"></i>Plantilla NO encontrada. Copie el archivo
            <code>Reporte_Actividades_Adolescentes.xlsx</code> a <code>uploads/</code> (el reporte web funciona igual; solo el
            export Excel la necesita).</p>
        <?php endif; ?>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <a href="reporte_adolescente.php" class="btn btn-his"><i class="fas fa-play me-1"></i> Ir al Reporte Adolescente</a>
    <a href="reporte_operacionales.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Reportes Operacionales</a>
</div>

<?php include 'includes/footer.php'; ?>
