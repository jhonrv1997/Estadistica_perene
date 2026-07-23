<?php
/**
 * Asistente de instalacion del modulo ESNI.
 *
 * Ejecuta el script Database/install_esni.sql en la base de datos MySQL
 * configurada en config.php y verifica que las 7 tablas se hayan creado
 * correctamente.
 *
 * Uso: abra este archivo en el navegador como administrador, o ejecute desde CLI:
 *      php install_esni.php
 *
 * Tras la instalacion, ELIMINE este archivo por seguridad.
 */
require_once 'includes/auth.php';
verificarAutenticacion();
verificarAdmin();
require_once 'includes/functions.php';
require_once 'includes/esni_data.php';

$pdo = getDBConnection();
$resultado = ['pasos' => [], 'ok' => true, 'errores' => []];

// 1) Verificar que el archivo SQL existe
$sqlFile = __DIR__ . '/Database/install_esni.sql';
if (!file_exists($sqlFile)) {
    $resultado['ok'] = false;
    $resultado['errores'][] = "No se encontro el archivo: $sqlFile";
} else {
    $resultado['pasos'][] = "Archivo SQL encontrado: " . basename($sqlFile);

    // 2) Leer el contenido
    $sql = file_get_contents($sqlFile);
    $resultado['pasos'][] = "SQL leido (" . number_format(strlen($sql)) . " bytes)";

    // 3) Dividir en statements individuales (respetando DELIMITER no usado aqui)
    // Eliminar comentarios de linea
    $sqlLimpio = preg_replace('/^--[^\n]*$/m', '', $sql);
    $sqlLimpio = preg_replace('/^SET[^\n]*$/m', '', $sqlLimpio);
    $statements = array_filter(array_map('trim', explode(';', $sqlLimpio)));
    $resultado['pasos'][] = "Dividido en " . count($statements) . " statements SQL";

    // 4) Ejecutar uno por uno
    $ok = 0; $err = 0;
    foreach ($statements as $i => $stmt) {
        if (empty($stmt) || strlen(trim($stmt)) < 5) continue;
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (PDOException $e) {
            $err++;
            // No fallar si la tabla ya existe
            if (strpos($e->getMessage(), 'already exists') === false) {
                $resultado['errores'][] = "Stmt $i: " . substr($e->getMessage(), 0, 200);
            }
        }
    }
    $resultado['pasos'][] = "Ejecutados: $ok OK, $err errores (algunos esperados si ya existian)";
}

// 5) Verificar tablas creadas
$tablasEsperadas = ['ESNI_VACUNA', 'ESNI_GRUPO_EDAD', 'ESNI_DOSIS',
                    'ESNI_SECCION_REPORTE', 'ESNI_LINEA_REPORTE', 'ESNI_REGLA', 'ESNI_PARAMETRO'];
$tablasCreadas = [];
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'ESNI_%'");
    $tablasCreadas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

$faltantes = array_diff($tablasEsperadas, $tablasCreadas);
if (empty($faltantes)) {
    $resultado['pasos'][] = "OK - Las 7 tablas ESNI_* estan presentes en la BD";
} else {
    $resultado['ok'] = false;
    $resultado['errores'][] = "Faltan tablas: " . implode(', ', $faltantes);
}

// 6) Mostrar conteos
$conteos = [];
foreach ($tablasEsperadas as $t) {
    try {
        $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        $conteos[$t] = (int)$c;
    } catch (PDOException $e) {
        $conteos[$t] = 'ERR';
    }
}
$resultado['conteos'] = $conteos;

// 7) Verificar instalacion final
$resultado['instalado'] = esniEsquemaInstalado($pdo);

$pageTitle = 'Instalacion Modulo ESNI - Sistema HIS';
include 'includes/header.php';
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow">
                <div class="card-header bg-his text-white">
                    <h5 class="mb-0"><i class="fas fa-syringe me-2"></i>Instalacion del Modulo ESNI</h5>
                </div>
                <div class="card-body">
                    <?php if ($resultado['ok'] && $resultado['instalado']): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Instalacion completada correctamente.</strong>
                        El modulo ESNI esta listo para usarse.
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

                    <?php if (!empty($resultado['errores'])): ?>
                    <h6 class="fw-bold text-danger mt-3"><i class="fas fa-bug me-2"></i>Errores:</h6>
                    <ul>
                        <?php foreach ($resultado['errores'] as $e): ?>
                            <li class="text-danger small"><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <h6 class="fw-bold mt-3"><i class="fas fa-table me-2"></i>Conteos por tabla:</h6>
                    <table class="table table-sm">
                        <thead><tr><th>Tabla</th><th class="text-end">Filas</th></tr></thead>
                        <tbody>
                            <?php foreach ($resultado['conteos'] as $t => $c): ?>
                            <tr><td><code><?= htmlspecialchars($t) ?></code></td><td class="text-end fw-bold"><?= is_int($c) ? number_format($c) : $c ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Por seguridad, elimine este archivo <code>install_esni.php</code> despues de la instalacion.
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="reporte_esni.php" class="btn btn-his"><i class="fas fa-chart-line me-1"></i> Ir al Reporte ESNI</a>
                        <a href="esni_config.php" class="btn btn-success"><i class="fas fa-cog me-1"></i> Configurar ESNI</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
